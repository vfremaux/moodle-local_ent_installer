<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * LDAP and Sync operations.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Synchronizes cohorts by getting records from a group holding ldap context.
 * @param array $options an array of options
 */
function local_ent_installer_sync_cohorts($ldapauth, $options = array()) {
    global $DB, $CFG;

    $config = get_config('local_ent_installer');

    mtrace('');

    $licenselimit = 1000000;
    if (empty($config->sync_enable)) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    if (empty($config->sync_cohorts_enable)) {
        mtrace(get_string('synccohortsdisabled', 'local_ent_installer'));
        return;
    }

    $insertcount = 0;
    $updatecount = 0;
    $inserterrorcount = 0;
    $updateerrorcount = 0;

    list($usec, $sec) = explode(' ', microtime());
    $starttick = (float)$sec + (float)$usec;

    $systemcontext = context_system::instance();

    core_php_time_limit::raise(600);

    if (local_ent_installer_supports_feature() == 'pro') {
        include_once($CFG->dirroot.'/local/ent_installer/pro/prolib.php');
        $promanager = local_ent_installer\pro_manager::instance();
        $check = $promanager->set_and_check_license_key($config->licensekey, $config->licenseprovider, true);
        if (!preg_match('/SET OK/', $check)) {
            $licenselimit = 3000;
        }
    } else {
        $licenselimit = 3000;
    }

    $ldapconnection = $ldapauth->ldap_connect();
    // Ensure an explicit limit, or some defaults may  cur some results.
    ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, min($licenselimit, 1000000));
    // Read the effective limit in a variable.
    ldap_get_option($ldapconnection, LDAP_OPT_SIZELIMIT, $retvalue);
    mtrace("Ldap opened with sizelimit $retvalue");

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ', microtime());
    $starttick = (float)$sec + (float)$usec;

    mtrace(get_string('lastrun', 'local_ent_installer', userdate(@$config->last_sync_date_cohort)));

    // Define table user to be created.

    $table = new xmldb_table('tmp_extcohort');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extcohort'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    $contexts = explode(';', $config->cohort_contexts);
    list($institutionidlist, $institutionalias) = local_ent_installer_strip_alias($config->institution_id);
    $institutionids = explode(',', $institutionidlist);

    // Ldap paging ?
    $ldappagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    if ($ldappagedresults) {
        mtrace("Paging results...\n");
    } else {
        mtrace("Paging not supported...\n");
    }

    $ldapcookie = '';
    $requid = '*';
    if (!empty($options['chid'])) {
        // Force the ldap filter to match only one single user. We cannot be in forced mode in this case.
        $options['force'] = false;
        $cohort = $DB->get_record('cohort', array('id' => $options['chid']));
        $requid = $cohort->idnumber;
    }

    $cohortrecordfields = array($config->cohort_idnumber_attribute,
                                $config->cohort_name_attribute,
                                $config->cohort_description_attribute,
                                $config->cohort_membership_attribute,
                                $config->cohort_course_binding_attribute,
                                $config->record_date_fieldname);

    if (!empty($config->cohort_selector_filter)) {

        mtrace("Filtering and processing cohorts...\n");

        // First fetch idnumbers to compare.
        foreach ($institutionids as $institutionid) {

            $filter = '(&('.$config->cohort_idnumber_attribute.'='.$requid.')';
            $filter .= str_replace('%ID%', $institutionid, $config->cohort_selector_filter).')';

            foreach ($contexts as $context) {
                $context = trim($context);
                if (empty($context)) {
                    continue;
                }

                do {
                    if ($ldappagedresults) {
                        ldap_control_paged_result($ldapconnection, $ldapauth->config->pagesize, true, $ldapcookie);
                    }
                    if ($ldapauth->config->search_sub) {
                        // Use ldap_search to find first user from subtree.
                        mtrace("ldapsearch $context, $filter for ".$config->cohort_idnumber_attribute.". Dated by {$config->record_date_fieldname}");
                        $params = array($config->cohort_idnumber_attribute, $config->record_date_fieldname);
                        $ldapresult = ldap_search($ldapconnection, $context, $filter, $params);
                    } else {
                        // Search only in this context.
                        mtrace("ldaplist $context, $filter for ".$config->cohort_idnumber_attribute.". Dated by {$config->record_date_fieldname}");
                        $params = array($config->cohort_idnumber_attribute, $config->record_date_fieldname);
                        $ldapresult = ldap_list($ldapconnection, $context, $filter, $params);
                    }
                    if (!$ldapresult) {
                        continue;
                    }
                    if ($ldappagedresults) {
                        ldap_control_paged_result_response($ldapconnection, $ldapresult, $ldapcookie);
                    }
                    if ($entry = @ldap_first_entry($ldapconnection, $ldapresult)) {
                        do {
                            $value = ldap_get_values_len($ldapconnection, $entry, $config->cohort_idnumber_attribute);
                            $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                            if (preg_match('/'.$config->cohort_idnumber_filter.'/', $value, $matches)) {
                                $value = $matches[1];
                            }

                            if (!empty($config->record_date_fieldname)) {
                                $modify = @ldap_get_values_len($ldapconnection, $entry, $config->record_date_fieldname);
                                if (!empty($modify[0])) {
                                    if ($config->timestamp_format == 'ad') {
                                        $modify = convert_from_ad_timestamp($modify[0]);
                                    } else {
                                        $modify = strtotime($modify[0]);
                                    }
                                } else {
                                    $modify = time();
                                }
                            } else {
                                $modify = time();
                            }

                            $value = $config->cohort_ix.'_'.$value;
                            local_ent_installer_ldap_bulk_cohort_insert($value, $modify, $options);
                        } while ($entry = ldap_next_entry($ldapconnection, $entry));
                    }
                    echo "\n";
                    unset($ldapresult); // Free mem.
                } while ($ldappagedresults && !empty($ldapcookie));
            }
        }

        /*
         * preserve our cohort database
         * if the temp table is empty, it probably means that something went wrong, exit
         * so as to avoid mass deletion of cohorts; which is hard to undo.
         */
        $count = $DB->count_records_sql('SELECT COUNT(*) AS count, 1 FROM {tmp_extcohort}');
        if ($count < 1) {
            mtrace(get_string('didntgetcohortsfromldap', 'auth_ldap'));
            $dbman->drop_table($table);
            $ldapauth->ldap_close(true);

            // Mark last time the cohort sync was run.
            set_config('last_sync_date_cohort', time(), 'local_ent_installer');
            return false;
        } else {
            mtrace(get_string('gotcountrecordsfromldap', 'auth_ldap', $count));
        }

        /*
         * If LDAP paged results were used, the current connection must be completely
         * closed and a new one created, to work without paged results from here on.
         */
        if ($ldappagedresults) {
            $ldapauth->ldap_close(true);
            $ldapconnection = $ldapauth->ldap_connect();
        }

        $captureautocohorts = '';
        if (empty($options['disableautocohortscheck'])) {
            $captureautocohorts = "AND
                c.component = 'local_ent_installer'";
        }

        if (!empty($config->cohort_ix)) {
            $idnumberclause = "CONCAT('".$config->cohort_ix."_', tc.idnumber) = c.idnumber";
            $deletionhavingclause = " c.idnumber LIKE '".$config->cohort_ix."_%' ";
        } else {
            $idnumberclause = "tc.idnumber = c.idnumber";
            $deletionhavingclause = " c.idnumber NOT LIKE '_%' ";
        }

        // Deleted cohorts.
        $sql = "
            SELECT
                c.idnumber,
                c.id as cid
            FROM
                {cohort} c
            LEFT JOIN
                {tmp_extcohort} tc
            ON
                $idnumberclause
            WHERE
                tc.idnumber IS NULL
                $captureautocohorts
            HAVING
               $deletionhavingclause
        ";
        /*
         * HAVING : Only delete cohorts of the same milesim. In case cohort_ix is not used,
         * provides a way to protect some cohorts from deletion, using '_' prefixed idnumbers
         */
        $deleted = $DB->get_records_sql($sql);

        // New cohorts.
        $sql = "
            SELECT
                tc.idnumber
            FROM
                {tmp_extcohort} tc
            LEFT JOIN
                {cohort} c
            ON
                $idnumberclause
            WHERE
                c.idnumber IS NULL
        ";

        $created = $DB->get_records_sql($sql);

        $lastmodified = '';
        $params = array();
        if (empty($options['force']) && empty($requid)) {
            // If not force, do check when cohorts have changed in ldap.
            $lastmodified = ' AND tc.lastmodified > ? ';
            $params = array(0 + @$config->last_sync_date_cohort);

        }

        // Updated cohorts.
        $sql = "
            SELECT
                tc.idnumber,
                c.id as cid
            FROM
                {cohort} c,
                {tmp_extcohort} tc
            WHERE
                $idnumberclause
                $lastmodified
                $captureautocohorts
        ";

        $updated = $DB->get_records_sql($sql, $params);

        if (!empty($options['force']) && empty($options['updateonly'])) {
            mtrace("\n>> ".get_string('deletingcohorts', 'local_ent_installer'));

            // Getting site level cohorts ids to protect.
            $protectids = array();
            if ($config->create_students_site_cohort) {
                $protectids[] = local_ent_installer_ensure_global_cohort_exists('students', $options);
            }
            if ($config->create_staff_site_cohort) {
                $protectids[] = local_ent_installer_ensure_global_cohort_exists('staff', $options);
            }
            if ($config->create_adminstaff_site_cohort) {
                $protectids[] = local_ent_installer_ensure_global_cohort_exists('adminstaff', $options);
            }
            $protectids[] = local_ent_installer_ensure_global_cohort_exists('admins', $options);

            if ($deleted) {
                $dlcnt = 0;
                foreach ($deleted as $dl) {

                    if (in_array($dl->cid, $protectids)) {
                        continue;
                    }

                    mtrace('--'.++$dlcnt.'--');
                    if (empty($options['simulate'])) {
                        if ($members = $DB->get_records('cohort_members', array('cohortid' => $dl->cid))) {
                            foreach ($members as $m) {
                                // This will trigger cascade events to get everything clean.
                                \cohort_remove_member($dl->cid, $m->userid);
                            }
                        }
                        $DB->delete_records('cohort', array('id' => $dl->cid));
                        mtrace(get_string('cohortdeleted', 'local_ent_installer', $dl->idnumber)."\n");
                    } else {
                        mtrace('[SIMULATION] '.get_string('cohortdeleted', 'local_ent_installer', $dl->idnumber)."\n");
                    }
                }
            } else {
                mtrace(get_string('nothingtodo', 'local_ent_installer'));
            }
        }

        mtrace("\n>> ".get_string('updatingcohorts', 'local_ent_installer'));
        if ($updated) {
            $upcnt = 0;
            foreach ($updated as $up) {

                mtrace('--'.++$upcnt.'--');

                // Build an external pattern.
                $cohortldapidentifier = $config->cohort_id_pattern;
                $cidnumber = preg_replace('/^'.$config->cohort_ix.'_/', '', $up->idnumber); // Unprefix the cohort idnumber.

                // The following filters may not be usefull.
                $cohortldapidentifier = str_replace('%CID%', $cidnumber, $cohortldapidentifier);
                $cohortldapidentifier = str_replace('%ID%', $config->institution_id, $cohortldapidentifier);

                if (!$cohortinfo = local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortldapidentifier, $options)) {
                    mtrace('ERROR : Cohort info error');
                    continue;
                }

                $oldrec = $DB->get_record('cohort', array('id' => $up->cid));
                // Ensure we have a correctly prefixed cohort IDNum and wellformed idnumber.
                if (!empty($config->cohort_ix)) {
                    $oldrec->idnumber = $config->cohort_ix.'_'.$cidnumber;
                    $oldrec->name = $config->cohort_ix.' '.$cohortinfo->name;
                } else {
                    $oldrec->idnumber = $cidnumber;
                    $oldrec->name = $cohortinfo->name;
                }

                $oldrec->description = ''.@$cohortinfo->description;
                $oldrec->descriptionformat = FORMAT_HTML;
                $oldrec->contextid = $systemcontext->id;
                $oldrec->component = 'local_ent_installer';
                $oldrec->timecreated = time();
                $oldrec->timemodified = time();

                if (empty($options['simulate'])) {
                    try {
                        $DB->update_record('cohort', $oldrec);
                        $updatecount++;
                        mtrace(get_string('cohortupdated', 'local_ent_installer', $oldrec)."\n");
                    } catch (Exception $e) {
                        $updateerrorcount++;
                        mtrace("ERROR : ".get_string('cohortupdated', 'local_ent_installer', $oldrec)."\n");
                    }
                } else {
                    mtrace('[SIMULATION] '.get_string('cohortupdated', 'local_ent_installer', $oldrec)."\n");
                }

                local_ent_installer_cohort_process_members($cohortinfo, $oldrec, $options);

                if (!empty($config->sync_cohort_to_course_enable) && !empty($config->cohort_course_binding_attribute)) {
                    local_ent_installer_cohort_process_courses($cohortinfo, $oldrec, $options);
                }
            }
        } else {
            mtrace(get_string('nothingtodo', 'local_ent_installer'));
        }

        if (empty($options['updateonly'])) {
            mtrace("\n>> ".get_string('creatingcohorts', 'local_ent_installer'));
            if ($created) {
                $crcnt = 0;
                foreach ($created as $cr) {

                    mtrace('--'.++$crcnt.'--');

                    // Build an external pattern.
                    $cohortldapidentifier = $config->cohort_id_pattern;
                    $cidnumber = preg_replace('/^'.$config->cohort_ix.'_/', '', $cr->idnumber); // Unprefix the cohort idnumber.
                    $cohortldapidentifier = str_replace('%CID%', $cidnumber, $cohortldapidentifier);
                    $cohortldapidentifier = str_replace('%ID%', $config->institution_id, $cohortldapidentifier);

                    if (!$cohortinfo = local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortldapidentifier, $options)) {

                        continue;
                    }

                    $cohort = new StdClass;
                    $cohort->description = ''.@$cohortinfo->description;
                    $cohort->descriptionformat = FORMAT_HTML;
                    if (!empty($config->cohort_ix)) {
                        $cohort->name = $config->cohort_ix.' '.$cohortinfo->name;
                        $cohort->idnumber = $config->cohort_ix.'_'.$cohortinfo->idnumber;
                    } else {
                        $cohort->name = $cohortinfo->name;
                        $cohort->idnumber = $cohortinfo->idnumber;
                    }
                    $cohort->contextid = $systemcontext->id;
                    $cohort->component = 'local_ent_installer';
                    $cohort->timecreated = time();
                    $cohort->timemodified = time();
                    if (empty($options['simulate'])) {
                        // Even when creating, do really check idnumber not in base.
                        if (!$oldrecord = $DB->get_record('cohort', ['idnumber' => $cohort->idnumber])) {
                            try {
                                $cohort->id = $DB->insert_record('cohort', $cohort);
                                $insertcount++;
                                mtrace(get_string('cohortcreated', 'local_ent_installer', $cohort)."\n");
                            } catch (Exception $e) {
                                $inserterrorcount++;
                                mtrace('ERROR : '.get_string('cohortcreated', 'local_ent_installer', $cohort)."\n");
                            }
                        } else {
                            $cohort->id = $oldrecord->id;
                            try {
                                $DB->update_record('cohort', $cohort);
                                $updatecount++;
                                mtrace(get_string('cohortupdated', 'local_ent_installer', $cohort)."\n");
                            } catch (Exception $e) {
                                $updateerrorcount++;
                                mtrace('ERROR : '.get_string('cohortupdated', 'local_ent_installer', $cohort)."\n");
                            }
                        }
                    } else {
                        if (!$oldrecord = $DB->get_record('cohort', ['idnumber' => $cohort->idnumber])) {
                            mtrace('[SIMULATION] '.get_string('cohortcreated', 'local_ent_installer', $cohort)."\n");
                        } else {
                            mtrace('[SIMULATION/+] '.get_string('cohortupdated', 'local_ent_installer', $cohort)."\n");
                        }
                    }

                    local_ent_installer_cohort_process_members($cohortinfo, $cohort, $options);

                    if (!empty($config->sync_cohort_to_course_enable) && !empty($config->cohort_course_binding_attribute)) {
                        local_ent_installer_cohort_process_courses($cohortinfo, $cohort, $options);
                    }
                }
            } else {
                mtrace(get_string('nothingtodo', 'local_ent_installer'));
            }
        }
    }

    mtrace("\n>> ".get_string('finaloperations', 'local_ent_installer'));

    ent_installer_clear_obsolete_cohorts($options);

    // Clean temporary table.
    try {
        $dbman->drop_table($table);
    } catch (Exception $e) {
        assert(1);
    }

    $ldapauth->ldap_close();

    // Calculate bench time.
    list($usec, $sec) = explode(' ', microtime());
    $stoptick = (float)$sec + (float)$usec;

    $deltatime = $stoptick - $starttick;

    mtrace('Execution time : '.$deltatime);
    mtrace('Insertions : '.$insertcount);
    mtrace('Updates : '.$updatecount);
    mtrace('Insertion errors : '.$inserterrorcount);
    mtrace('Update errors : '.$updateerrorcount);

    $benchrec = new StdClass();
    $benchrec->synctype = 'cohorts';
    $benchrec->timestart = floor($starttick);
    $benchrec->timerun = ceil($deltatime);
    $benchrec->added = 0 + @$insertcount;
    $benchrec->updated = 0 + @$updatecount;
    $benchrec->updateerrors = 0 + @$inserterrorcount;
    $benchrec->inserterrors = 0 + @$updateerrorcount;
    try {
        $DB->insert_record('local_ent_installer', $benchrec);
    } catch (Exception $e) {
        mtrace('Stat insertion failure');
    }

    set_config('last_sync_date_cohort', time(), 'local_ent_installer');

}

function ent_installer_clear_obsolete_cohorts($options = array()) {
    global $DB;

    $config = get_config('local_ent_installer');

    // Delete obsolete cohorts.
    if (!empty($config->cohort_old_prefixes)) {
        $prefixes = explode(',', $config->cohort_old_prefixes);
        foreach ($prefixes as $prf) {
            $select = " idnumber LIKE ? ";
            $params = array(trim($prf).'%');
            $cohorts = $DB->get_records_select('cohort', $select, $params);
            if ($cohorts) {
                mtrace("\n>> ".get_string('removingoldcohorts', 'local_ent_installer'));
                foreach ($cohorts as $ch) {
                    if (empty($options['simulate'])) {
                        mtrace(get_string('removingoldcohort', 'local_ent_installer', $ch));
                        cohort_delete_cohort($ch);
                    } else {
                        mtrace('[SIMULATION] '.get_string('oldcohortdeleted', 'local_ent_installer', $ch->id));
                    }
                }
            }
        }
    }
}

/**
 * Reads user information from ldap and returns it in array()
 *
 * Function should return all information available. If you are saving
 * this information to moodle user-table you should honor syncronization flags
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $cohortidentifier cohort identifier (ldap side format)
 * @param array $options an array with CLI input options
 *
 * @return mixed array with no magic quotes or false on error
 */
function local_ent_installer_get_cohortinfo($ldapauth, $cohortidentifier, $options = array()) {
    global $DB;
    static $cohortattributes;
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    // Load some cached static data.
    if (!isset($cohortattributes)) {
        // Aggregate additional ent specific attributes that hold interesting information.
        $cohortattributes = array(
            'name' => core_text::strtolower($config->cohort_name_attribute),
            'description' => core_text::strtolower($config->cohort_description_attribute),
            'idnumber' => core_text::strtolower($config->cohort_idnumber_attribute),
            'members' => core_text::strtolower($config->cohort_membership_attribute),
            'courses' => core_text::strtolower($config->cohort_course_binding_attribute),
        );
    }

    $extcohortidentifier = core_text::convert($cohortidentifier, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($cohortdn = local_ent_installer_ldap_find_cohort_dn($ldapauth, $ldapconnection, $extcohortidentifier, $options))) {
        $ldapauth->ldap_close();
        if ($options['verbose']) {
            mtrace("Internal Error : Could not locate $extcohortidentifier ");
        }
        return false;
    }

    if ($options['verbose']) {
        mtrace("Getting $cohortdn for ".implode(',', array_values($cohortattributes)));
    }
    if (!$cohortinforesult = ldap_read($ldapconnection, $cohortdn, '(objectClass=*)', array_values($cohortattributes))) {
        $ldapauth->ldap_close();
        return false;
    }

    $cohortentry = ldap_get_entries_moodle($ldapconnection, $cohortinforesult);
    if (empty($cohortentry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($cohortattributes as $key => $value) {

        // Value is an attribute name.
        $entry = array_change_key_case($cohortentry[0], CASE_LOWER);

        if (!array_key_exists($value, $entry)) {
            if ($options['verbose']) {
                mtrace("Requested value $value but missing in record");
            }
            continue; // Wrong data mapping!
        }

        if ($key == 'members') {
            if (!empty($options['verbose'])) {
                mtrace("\nProcessing membership.");
            }
            // Get the full array of values.
            $newval = array();
            $i = 0;
            $members = count($entry[$value]);
            foreach ($entry[$value] as $newvalopt) {
                // For each member extract identifier.
                $i++;
                $newvalopt  = core_text::convert($newvalopt, $ldapauth->config->ldapencoding, 'utf-8');

                // Avoid the last record wich is the array count.
                if ($i == $members) {
                    continue;
                }

                if (!$ldapauth->config->memberattribute_isdn) {
                    if (!empty($options['verbose'])) {
                        mtrace("Extracting from $newvalopt with {$config->cohort_membership_filter} ");
                    }
                    // Member attribute contains value from where the user identifier can be directly extracted.
                    if (preg_match('/'.$config->cohort_membership_filter.'/', $newvalopt, $matches)) {
                        // Exclude potential arity count that comes at end of multivalued entries.
                        $identifier = core_text::strtolower($matches[1]);
                        if (!empty($options['verbose'])) {
                            mtrace("Getting user record for {$config->cohort_user_identifier} = $identifier");
                        }
                        $fields = 'id,username,firstname,lastname';
                        $user = $DB->get_record('user', array($config->cohort_user_identifier => $identifier), $fields);
                        if (!$user) {
                            mtrace("Error : User record not found for $identifier. Skipping membership");
                            continue;
                        }
                        $user->userid = $user->id;
                        $newval[] = $user;
                    }
                } else {
                    /*
                     * Member attribute contains a true user DN. This may, but MAY NOT contain direct
                     * reference to a moodle user identifier. In this case, for more stability, we
                     * fetch the associated username known by LDAP in user ldap main username attribute.
                     */
                    if (!empty($options['verbose'])) {
                        mtrace("Extracting from $newvalopt as DN ");
                    }
                    $username = local_ent_installer_get_username_from_dn($ldapauth, $newvalopt, $options, $ldapconnection);
                    $fields = 'id, username, firstname, lastname';
                    // 'username' is the static value of configroleasignuseridentifier.
                    $user = $DB->get_record('user', array('username' => $username), $fields);
                    if (!$user) {
                        mtrace("Error : User record not found for $username. Skipping membership");
                        continue;
                    }
                    $user->userid = $user->id;
                    $newval[] = $user;
                }
            }
            $result[$key] = $newval;
            continue;
        } else {
            // Normal attribute case.
            if (is_array($entry[$value])) {
                $newval = core_text::convert($entry[$value][0], $ldapauth->config->ldapencoding, 'utf-8');
            } else {
                $newval = core_text::convert($entry[$value], $ldapauth->config->ldapencoding, 'utf-8');
            }
        }

        if ($key == 'courses') {
            // Prepare validated course list for later bindings
            /*
             * We accept potentially multivalued entries, but also entries that may have internal list
             * form such as separator list of identifiers
             */
            if (!empty($options['verbose'])) {
                mtrace("\nProcessing course bindings.");
            }
            // Get the full array of values.
            $newval = array();
            $i = 0;
            $members = count($entry[$value]);
            $allcourses = array();
            foreach ($entry[$value] as $newvalopt) {
                if ($newvalopt == 'no') {
                    break;
                }
                $courseids = explode($config->list_separator, $newvalopt);
                $allcourses = $allcourses + $courseids;
            }

            // Convert and validate courses by id.
            $validatedcourses = array();
            if (!empty($allcourses)) {
                foreach ($allcourses as $courseidentifier) {
                    if (!empty($options['verbose'])) {
                        mtrace("\tExaminating course $courseidentifier as {$config->cohort_course_binding_identifier}");
                    }
                    $params = array($config->cohort_course_binding_identifier => $courseidentifier);
                    if ($courseid = $DB->get_field('course', 'id', $params)) {
                        $validatedcourses[] = $courseid;
                    } else {
                        if (!empty($options['verbose'])) {
                            mtrace("\tInvalid course for binding.");
                        }
                    }
                }
            }

            $result[$key] = $validatedcourses;
            continue;
        }

        // Special processing of fields.
        $filterkey = 'cohort_'.$key.'_filter';
        if (!empty($options['verbose'])) {
            mtrace("Checking attribute $key");
        }
        if (!empty($config->$filterkey)) {
            if (!empty($options['verbose'])) {
                mtrace("Extracting with {$config->$filterkey} from attribute $key = $newval");
            }
            // If a filter exists, apply the filter and extract the partial value.
            // The filter MUST have one subpattern capture group () and no opening/closing char.
            preg_match('/'.$config->$filterkey.'/', $newval, $matches);
            $newval = $matches[1];
        }

        if (!empty($newval)) {
            // Favour ldap entries that are set.
            $ldapval = $newval;
        }

        if (!is_null($ldapval)) {
            $result[$key] = $ldapval;
        }
    }

    $ldapauth->ldap_close();
    return $result;
}

/**
 * Search specified contexts for username and return the user dn
 * like: cn=username,ou=suborg,o=org. It's actually a wrapper
 * around ldap_find_userdn().
 *
 * @param resource $ldapconnection a valid LDAP connection
 * @param string $extcohortdn the username to search (in external LDAP encoding, no db slashes)
 * @param array $options some behaviour options
 * @return mixed the user dn (external LDAP encoding) or false
 */
function local_ent_installer_ldap_find_cohort_dn($ldapauth, $ldapconnection, $extcohortdn, $options = null) {
    static $config;

    if (!isset($config)) {
        // We might be called a lot of times.
        $config = get_config('local_ent_installer');
    }

    $ldapcontexts = explode(';', $config->cohort_contexts);

    return ldap_find_cohortdn($ldapauth, $ldapconnection, $extcohortdn, $ldapcontexts, $config->cohort_objectclass,
                            $config->cohort_id_attribute, $options);
}

/**
 * Search specified contexts for username and return the user dn like:
 * cn=username,ou=suborg,o=org
 *
 * @param mixed $ldapconnection a valid LDAP connection.
 * @param mixed $cohortidentifier external cohort identifier (external LDAP encoding, no db slashes).
 * @param array $contexts contexts to look for the cohort.
 * @param string $objectclass objectlass of the cohorts (in LDAP filter syntax).
 * @param string $searchattrib the attribute use to look for the cohort.
 * @param array $options some behavioural options
 * @return mixed the cohort dn (external LDAP encoding, no db slashes) or false
 *
 */
function ldap_find_cohortdn($ldapauth, $ldapconnection, $cohortidentifier, $contexts, $objectclass, $searchattrib, $options = null) {

    $config = get_config('local_ent_installer');

    if (empty($ldapconnection) || empty($cohortidentifier) || empty($contexts) || empty($objectclass) || empty($searchattrib)) {
        if (!empty($options['verbose'])) {
            mtrace('Missing data at find_cohortdn input');
        }
        return false;
    }

    // Default return value.
    $ldapcohortdn = false;

    // Get all contexts and look for first matching user.
    foreach ($contexts as $context) {
        $context = trim($context);
        if (empty($context)) {
            continue;
        }

        $filter = '(&'.$objectclass.'('.$searchattrib.'='.$cohortidentifier.'))';
        if (empty($ldapauth->config->search_sub)) {
            if (!empty($options['verbose'])) {
                mtrace("List cohort DN in : $context, $filter by $searchattrib ");
            }
            $ldapresult = @ldap_list($ldapconnection, $context, $filter, array($searchattrib));
        } else {
            if (!empty($options['verbose'])) {
                mtrace("Search cohort DN in : $context, $filter by $searchattrib ");
            }
            $ldapresult = @ldap_search($ldapconnection, $context, $filter, array($searchattrib));
        }

        if (!$ldapresult) {
            if (!empty($options['verbose'])) {
                mtrace('find_cohortdn : No results in context '.$context);
            }
            continue; // Not found in this context.
        }

        $entry = ldap_first_entry($ldapconnection, $ldapresult);
        if ($entry) {
            $ldapcohortdn = ldap_get_dn($ldapconnection, $entry);
            break;
        }
    }

    return $ldapcohortdn;
}

/**
 * Reads cohort information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $cohortidentifier cohort (with system magic quotes)
 * @return mixed object or false on error
 */
function local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortidentifier, $options = array()) {

    $cohortarr = local_ent_installer_get_cohortinfo($ldapauth, $cohortidentifier, $options);

    if ($cohortarr == false) {
        return false; // Error or not found.
    }

    $cohortarr = truncate_userinfo($cohortarr);
    $cohort = new stdClass();
    foreach ($cohortarr as $key => $value) {
        $cohort->{$key} = $value;
    }
    return $cohort;
}

/**
 * Bulk insert in SQL's temp table.
 * @param string $cohortidentifier an unprefixed external cohort identifier.
 * @param int $timemodified modification timestamp.
 */
function local_ent_installer_ldap_bulk_cohort_insert($cohortidentifier, $timemodified, $options = array()) {
    global $DB;

    if (!$DB->record_exists('tmp_extcohort', array('idnumber' => $cohortidentifier))) {
        $params = array('idnumber' => $cohortidentifier, 'lastmodified' => $timemodified);
        if (!empty($options['verbose'])) {
            mtrace("Inserting $cohortidentifier, ". userdate($timemodified));
        }
        $DB->insert_record_raw('tmp_extcohort', $params, false, true);
    }
    echo '.';
}

/**
 * Differentially manages the memberships using cohort enrol methods.
 * @param object $cohortinfo
 * @param object $cohort the created cohort
 * @param $options runtime options
 */
function local_ent_installer_cohort_process_members($cohortinfo, $cohort, $options = array()) {
    global $DB;

    if ($allmembers = $DB->get_records('cohort_members', array('cohortid' => $cohort->id), 'id', 'userid,userid')) {
        $allmemberids = array_keys($allmembers);
    } else {
        $allmemberids = array();
    }

    if (!empty($cohortinfo->members)) {
        foreach ($cohortinfo->members as $m) {
            $e = new StdClass;
            $e->username = $DB->get_field('user', 'username', array('id' => $m->userid));
            $e->uidnumber = $DB->get_field('user', 'idnumber', array('id' => $m->userid));
            $e->idnumber = $cohort->idnumber;
            if (!in_array($m->userid, $allmemberids)) {
                if (empty($options['simulate'])) {
                    \cohort_add_member($cohort->id, $m->userid);
                    mtrace(get_string('cohortmemberadded', 'local_ent_installer', $e));
                } else {
                    mtrace('[SIMULATION] '.get_string('cohortmemberadded', 'local_ent_installer', $e));
                }
            } else {
                mtrace(get_string('cohortmemberexists', 'local_ent_installer', $e));
                unset($allmembers[$m->userid]);
            }
        }
    }

    // Remove discarded members.
    if (!empty($allmembers)) {
        foreach (array_keys($allmembers) as $todeleteid) {
            $e = new StdClass;
            $e->username = $DB->get_field('user', 'username', array('id' => $todeleteid));
            $e->uidnumber = $DB->get_field('user', 'idnumber', array('id' => $todeleteid));
            $e->idnumber = $cohort->idnumber;
            if (empty($options['simulate'])) {
                \cohort_remove_member($cohort->id, $todeleteid);
                mtrace(get_string('cohortmemberremoved', 'local_ent_installer', $e));
            } else {
                mtrace('[SIMULATION] '.get_string('cohortmemberremoved', 'local_ent_installer', $e));
            }
        }
    }
}

/**
 * Differentially manages the course bindings using cohort enrol methods.
 * @param object $cohortinfo
 * @param object $cohort the created cohort
 * @param $options runtime options
 */
function local_ent_installer_cohort_process_courses($cohortinfo, $cohort, $options = array()) {
    global $DB;

    $config = get_config('local_ent_installer');

    // Get old bindings as courseid to enrolid mapping.
    mtrace("\n".get_string('cohortbindings', 'local_ent_installer', $cohort));
    $params = array('enrol' => 'cohort', 'customint1' => $cohort->id);
    $oldbindings = $DB->get_records_menu('enrol', $params, 'id', 'courseid, id');

    $oldbindingcourseids = array_keys($oldbindings);

    $role = $DB->get_record('role', array('shortname' => 'student'));

    // Bind new course entries.
    if (!empty($cohortinfo->courses)) {
        foreach ($cohortinfo->courses as $courseid) {
            if (!is_numeric($courseid)) {
                die("Fatal error : should be an numeric id.");
            }

            $e = new StdClass;
            $e->idnumber = $cohort->idnumber;
            $e->shortname = $DB->get_field('course', 'shortname', array('id' => $courseid));
            $e->cidnumber = $DB->get_field('course', 'idnumber', array('id' => $courseid));
            $e->role = $role->shortname;
            if (!in_array($courseid, $oldbindingcourseids)) {
                // Add enrol method.
                $enrol = new StdClass;
                $enrol->enrol = 'cohort';
                $enrol->status = 0;
                $enrol->courseid = $courseid;
                $enrol->roleid = $role->id;
                $enrol->customint1 = $cohort->id;
                if (empty($options['simulate'])) {
                    $DB->insert_record('enrol', $enrol);
                    mtrace("\t".get_string('cohortbindingadded', 'local_ent_installer', $e));
                } else {
                    mtrace("\t".'[SIMULATION] '.get_string('cohortbindingadded', 'local_ent_installer', $e));
                }
            } else {
                $enrol = $DB->get_record('enrol', array('id' => $oldbindings[$courseid]));
                $enrol->roleid = $role->id;
                if ($enrol->status == 0) {
                    mtrace("\t".get_string('cohortbindingexists', 'local_ent_installer', $e));
                } else {
                    $enrol->status == 0;
                    mtrace("\t".get_string('cohortbindingenabled', 'local_ent_installer', $e));
                }
                $DB->update_record('enrol', $enrol);
                unset($oldbindings[$courseid]);
            }
        }
    } else {
        mtrace("\t".get_string('cohortnobindings', 'local_ent_installer'));
    }

    $plugin = enrol_get_plugin('cohort');

    // Remove old enrols (or disable them if soft delete).
    if (!empty($oldbindings)) {
        foreach ($oldbindings as $todeletecourseid => $todeleteenrolid) {
            $e = new StdClass;
            $e->idnumber = $cohort->idnumber;
            $e->shortname = $DB->get_field('course', 'shortname', array('id' => $todeletecourseid));;
            $e->cidnumber = $DB->get_field('course', 'idnumber', array('id' => $todeletecourseid));;
            if (empty($options['simulate'])) {
                if ($config->cohort_hard_cohort_unenrol == 'soft') {
                    $params = array('id' => $todeleteenrolid);
                    $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, $params);
                    mtrace("\t".get_string('cohortbindingdisabled', 'local_ent_installer', $e));
                } else {
                    $instance = $DB->get_record('enrol', array('id' => $todeleteenrolid));
                    // Protect against course/enrol corruption
                    if ($DB->record_exists('course', array('id' => $instance->courseid))) {
                        mtrace("\t".get_string('cohortbindingremoved', 'local_ent_installer', $e));
                        $plugin->delete_instance($instance);
                    } else {
                        mtrace("Warning : possible enrol table corruption on course ".$instance->courseid);
                    }
                }
            } else {
                if ($config->cohort_hard_cohort_unenrol == 'soft') {
                    mtrace("\t".'[SIMULATION] '.get_string('cohortbindingdisabled', 'local_ent_installer', $e));
                } else {
                    mtrace("\t".'[SIMULATION] '.get_string('cohortbindingremoved', 'local_ent_installer', $e));
                }
            }
        }
    }
}