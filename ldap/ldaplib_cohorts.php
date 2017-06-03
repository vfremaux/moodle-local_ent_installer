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
    global $DB;

    $config = get_config('local_ent_installer');

    mtrace('');
    $enable = get_config('local_ent_installer', 'sync_enable');
    if (!$enable) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    $ldapconnection = $ldapauth->ldap_connect();

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ',microtime());
    $starttick = (float)$sec + (float)$usec;

    if (!isset($config->last_cohort_sync_date)) {
        $config->last_cohort_sync_date = 0;
        set_config('lastrun', 0, 'local_ent_installer');
    }

    mtrace(get_string('lastrun', 'local_ent_installer', userdate($config->last_cohort_sync_date)));

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
    $institutionids = explode(',', $config->institution_id);

    $ldap_pagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    $ldapcookie = '';

    $cohortrecordfields = array($config->cohort_idnumber_attribute,
                                $config->cohort_name_attribute,
                                $config->cohort_description_attribute,
                                $config->cohort_membership_attribute,
                                'modifyTimestamp');

    // First fetch idnnumbers to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->cohort_selector_filter);

        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            do {
                if ($ldap_pagedresults) {
                    ldap_control_paged_result($ldapconnection, $ldapauth->config->pagesize, true, $ldapcookie);
                }
                if ($ldapauth->config->search_sub) {
                    // Use ldap_search to find first user from subtree.
                    mtrace("ldapsearch $context, $filter for ".$config->cohort_idnumber_attribute);
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, $config->cohort_idnumber_attribute, 'modifyTimestamp');
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".$config->cohort_idnumber_attribute);
                    $ldap_result = ldap_list($ldapconnection, $context, $filter, array($config->cohort_idnumber_attribute, 'modifyTimestamp'));
                }
                if (!$ldap_result) {
                    continue;
                }
                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldap_result, $ldapcookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                    do {
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->cohort_idnumber_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->cohort_idnumber_filter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        local_ent_installer_ldap_bulk_cohort_insert($value, $modify);
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                echo "\n";
                unset($ldap_result); // Free mem.
            } while ($ldap_pagedresults && !empty($ldapcookie));
        }
    }

    /*
     * If LDAP paged results were used, the current connection must be completely
     * closed and a new one created, to work without paged results from here on.
     */
    if ($ldap_pagedresults) {
        $ldapauth->ldap_close(true);
        $ldapconnection = $ldapauth->ldap_connect();
    }

    $captureautocohorts = '';
    if (empty($options['disableautocohortscheck'])) {
        $captureautocohorts = "AND
            c.component = 'local_ent_installer'";
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
            CONCAT('".$config->cohort_ix."', tc.idnumber) = c.idnumber 
        WHERE
            tc.idnumber IS NULL
            $captureautocohorts
    ";

    $deleted = $DB->get_records_sql($sql);

    // New cohorts.
    $sql = "
        SELECT
            tc.idnumber
        FROM
            {cohort} c
        LEFT JOIN
            {tmp_extcohort} tc
        ON
            CONCAT('".$config->cohort_ix."', tc.idnumber) = c.idnumber
        WHERE
            c.idnumber IS NULL
    ";

    $created = $DB->get_records_sql($sql);

    // Updated cohorts.
    $sql = "
        SELECT
            tc.idnumber,
            c.id as cid
        FROM
            {cohort} c,
            {tmp_extcohort} tc
        WHERE
            CONCAT('".$config->cohort_ix."', tc.idnumber) = c.idnumber AND
            tc.lastmodified > ? 
            $captureautocohorts
    ";

    if (empty($options['force'])) {
        $lastmodified = $config->last_cohort_sync_date;
    } else {
        $lastmodified = 0;
    }

    $updated = $DB->get_records_sql($sql, array($lastmodified));

    mtrace("\n>> ".get_string('deletingcohorts', 'local_ent_installer'));
    if ($deleted) {
        foreach ($deleted as $dl) {
            if (empty($options['simulate'])) {
                if ($members = $DB->get_records('cohort_members', array('cohortid' => $dl->cid))) {
                    foreach($members as $m) {
                        // This will trigger cascade events to get everything clean.
                        \cohort_remove_member($dl->cid, $m->userid);
                    }
                }
                $DB->delete_records('cohort', array('id' => $dl->cid));
                mtrace(get_string('cohortdeleted', 'local_ent_installer', $dl->idnumber));
            } else {
                mtrace('[SIMULATION] '.get_string('cohortdeleted', 'local_ent_installer', $dl->idnumber));
            }
        }
    }

    mtrace("\n>> ".get_string('updatingcohorts', 'local_ent_installer'));
    if ($updated) {
        foreach ($updated as $up) {

            // Build an external pattern
            $cohortldapidentifier = $config->cohort_id_pattern;
            $cidnumber = str_replace($config->cohort_ix, '', $up->idnumber); // Unprefix the cohort idnumber.

            // The following filters may not be usefull.
            $cohortldapidentifier = str_replace('%CID%', $cidnumber, $cohortldapidentifier);
            $cohortldapidentifier = str_replace('%ID%', $config->institution_id, $cohortldapidentifier);

            if (!$cohortinfo = local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortldapidentifier, $options)) {
                mtrace('ERROR : Cohort info error');
                continue;
            }

            $oldrec = $DB->get_record('cohort', array('id' => $up->cid));
            $oldrec->name = $cohortinfo->name;
            $oldrec->idnumber = $config->cohort_ix.$cidnumber; // Ensure we have a correctly prefixed cohort IDNum.
            $oldrec->description = $cohortinfo->description;
            $oldrec->descriptionformat = FORMAT_HTML;
            $oldrec->contextid = $systemcontext->id;
            $oldrec->component = 'local_ent_installer';
            $oldrec->timecreated = time();
            $oldrec->timemodified = time();
            if (empty($options['simulate'])) {
                $DB->update_record('cohort', $oldrec);
                mtrace(get_string('cohortupdated', 'local_ent_installer', $oldrec));
            } else {
                mtrace('[SIMULATION] '.get_string('cohortupdated', 'local_ent_installer', $oldrec));
            }

            if (!empty($cohortinfo->members)) {

                if ($oldmembers = $DB->get_records_menu('cohort_members', array('cohortid' => $oldrec->id), 'userid,userid')) {
                    $oldmemberids = array_keys($oldmembers);
                } else {
                    $oldmemberids = array();
                }

                foreach ($cohortinfo->members as $m) {
                    if (!in_array($m->id, $oldmemberids)) {
                        $e = new StdClass;
                        $e->username = $m->username;
                        $e->idnumber = $oldrec->idnumber;
                        if (empty($options['simulate'])) {
                            \cohort_add_member($cohort->id, $m->userid);
                            mtrace(get_string('cohortmemberadded', 'local_ent_installer', $e));
                        } else {
                            mtrace('[SIMULATION] '.get_string('cohortmemberadded', 'local_ent_installer', $e));
                        }
                    } else {
                        unset($oldmemberids[$m->id]);
                        unset($oldmembers[$m->id]);
                    }
                }

                // Need reset register of ids after all updated have been cleaned out.
                $oldmemberids = array_keys($oldmembers);

                // remains only old ids in members. Remove them.
                if (!empty($oldmemberids)) {
                    foreach ($oldmemberids as $userid) {
                        $e = new StdClass;
                        $e->username = $DB->get_field('user', 'username', array('id' => $userid));
                        $e->idnumber = $oldrec->idnumber;
                        if (empty($options['simulate'])) {
                            // This will trigger cascade events to get everything clean.
                            \cohort_remove_member($dl->cid, $userid);
                            mtrace(get_string('cohortmemberremoved', 'local_ent_installer', $e));
                        } else {
                            mtrace('[SIMULATION] '.get_string('cohortmemberremoved', 'local_ent_installer', $e));
                        }
                    }
                }
            }
        }
    }

    mtrace("\n>> ".get_string('creatingcohorts', 'local_ent_installer'));
    if ($created) {
        foreach ($created as $cr) {

            // Build an external pattern
            $cohortldapidentifier = $config->cohort_id_pattern;
            $cidnumber = str_replace($config->cohort_ix, '', $cr->idnumber); // Unprefix the cohort idnumber.
            $cohortldapidentifier = str_replace('%CID%', $cidnumber, $cohortldapidentifier);
            $cohortldapidentifier = str_replace('%ID%', $config->institution_id, $cohortldapidentifier);

            $cohortinfo = local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortldapidentifier, $options);

            $cohort = new StdClass;
            $cohort->name = $cohortinfo->name;
            $cohort->description = $cohortinfo->description;
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->idnumber = $config->cohort_ix.$cohortinfo->idnumber;
            $cohort->contextid = $systemcontext->id;
            $cohort->component = 'local_ent_installer';
            $cohort->timecreated = time();
            $cohort->timemodified = time();
            if (empty($options['simulate'])) {
                $cohort->id = $DB->insert_record('cohort', $cohort);
                mtrace(get_string('cohortcreated', 'local_ent_installer', $cohort));
            } else {
                mtrace('[SIMULATION] '.get_string('cohortcreated', 'local_ent_installer', $cohort));
            }

            if (!empty($cohortinfo->members)) {
                foreach ($cohortinfo->members as $m) {
                    $e = new StdClass;
                    $e->username = $DB->get_field('user', 'username', array('id' => $m->username));
                    $e->idnumber = $cohort->idnumber;
                    if (empty($options['simulate'])) {
                        \cohort_add_member($cohort->id, $m->userid);
                        mtrace(get_string('cohortmemberadded', 'local_ent_installer', $e));
                    } else {
                        mtrace('[SIMULATION] '.get_string('cohortmemberadded', 'local_ent_installer', $e));
                    }
                }
            }
        }
    }

    mtrace("\n>> ".get_string('finaloperations', 'local_ent_installer'));
    // Clean temporary table.
    try {
        $dbman->drop_table($table);
    } catch (Exception $e) {
        assert(1);
    }

    $ldapauth->ldap_close();

    set_config('last_cohort_sync_date', time(), 'local_ent_installer');

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
    static $entattributes;
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    // Load some cached static data.
    if (!isset($entattributes)) {
        // aggregate additional ent specific attributes that hold interesting information
        $entattributes = array(
            'name' => $config->cohort_name_attribute,
            'description' => $config->cohort_description_attribute,
            'idnumber' => $config->cohort_idnumber_attribute,
            'members' => $config->cohort_membership_attribute
        );
    }

    $extcohortidentifier = core_text::convert($cohortidentifier, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($cohort_dn = local_ent_installer_ldap_find_cohort_dn($ldapconnection, $extcohortidentifier))) {
        $ldapauth->ldap_close();
        if ($options['verbose']) {
            mtrace("Internal Error : Could not locate $extcohortidentifier ");
        }
        return false;
    }

    $searchattribs = array();
    foreach ($entattributes as $key => $value) {
        if (!in_array($value, $searchattribs)) {
            array_push($searchattribs, $value);
            // Add attributes to $attrmap so they are pulled down into final cohort object.
            $attrmap[$key] = strtolower($value);
        }
    }

    if ($options['verbose']) {
        mtrace("Getting $cohort_dn for ".implode(',', $searchattribs));
    }
    if (!$cohort_info_result = ldap_read($ldapconnection, $cohort_dn, '(objectClass=*)', $searchattribs)) {
        $ldapauth->ldap_close();
        return false;
    }

    $cohort_entry = ldap_get_entries_moodle($ldapconnection, $cohort_info_result);
    if (empty($cohort_entry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($attrmap as $key => $value) {
        // Value is an attribute name.
        $entry = array_change_key_case($cohort_entry[0], CASE_LOWER);

        if (!array_key_exists($value, $entry)) {
            if ($options['verbose']) {
                mtrace("Requested value $value but missing in record");
            }
            continue; // Wrong data mapping!
        }

        if ($key == 'members') {
            // Get the full array of values.
            $newval = array();
            foreach ($entry[$value] as $newvalopt) {
                $newvalopt  = core_text::convert($newvalopt, $ldapauth->config->ldapencoding, 'utf-8');
                if (!empty($options['verbose'])) {
                    mtrace("Extracting from $newvalopt with {$config->cohort_membership_filter} ");
                }
                if (preg_match('/'.$config->cohort_membership_filter.'/', $newvalopt, $matches)) {
                    // Exclude potential arity count that comes at end of multivalued entries.
                    $identifier = core_text::strtolower($matches[1]);
                    if (!empty($options['verbose'])) {
                        mtrace("Getting user record for {$config->cohort_user_identifier} = $identifier");
                    }
                    $user = $DB->get_record('user', array($config->cohort_user_identifier => $identifier), 'id,username,firstname,lastname');
                    if (!$user) {
                        mtrace("Error : User record not found for $identifier. Skipping membership");
                        continue;
                    }
                    $newval[] = $user;
                }
            }
            $result[$key] = $newval;
            $ldapauth->ldap_close();
            return $result;
        } else {
            if (is_array($entry[$value])) {
                $newval = core_text::convert($entry[$value][0], $ldapauth->config->ldapencoding, 'utf-8');
            } else {
                $newval = core_text::convert($entry[$value], $ldapauth->config->ldapencoding, 'utf-8');
            }
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

        if (!empty($newval)) { // Favour ldap entries that are set.
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
 * @return mixed the user dn (external LDAP encoding) or false
 */
function local_ent_installer_ldap_find_cohort_dn($ldapconnection, $extcohortdn) {
    static $config;

    if (!isset($config)) {
        // We might be called a lot of times.
        $config = get_config('local_ent_installer');
    }

    $ldap_contexts = explode(';', $config->cohort_contexts);

    return ldap_find_cohortdn($ldapconnection, $extcohortdn, $ldap_contexts, $config->cohort_objectclass,
                            $config->cohort_id_attribute);
}

/**
 * Search specified contexts for username and return the user dn like:
 * cn=username,ou=suborg,o=org
 *
 * @param mixed $ldapconnection a valid LDAP connection.
 * @param mixed $cohortidentifier external cohort identifier (external LDAP encoding, no db slashes).
 * @param array $contexts contexts to look for the cohort.
 * @param string $objectclass objectlass of the cohorts (in LDAP filter syntax).
 * @param string $search_attrib the attribute use to look for the cohort.
 * @return mixed the cohort dn (external LDAP encoding, no db slashes) or false
 *
 */
function ldap_find_cohortdn($ldapconnection, $cohortidentifier, $contexts, $objectclass, $search_attrib) {
    if (empty($ldapconnection) || empty($cohortidentifier) || empty($contexts) || empty($objectclass) || empty($search_attrib)) {
        return false;
    }

    // Default return value
    $ldap_cohort_dn = false;

    // Get all contexts and look for first matching user
    foreach ($contexts as $context) {
        $context = trim($context);
        if (empty($context)) {
            continue;
        }

        $ldap_result = @ldap_list($ldapconnection, $context,
                                  '(&'.$objectclass.'('.$search_attrib.'='.$cohortidentifier.'))',
                                  array($search_attrib));

        if (!$ldap_result) {
            continue; // Not found in this context.
        }

        $entry = ldap_first_entry($ldapconnection, $ldap_result);
        if ($entry) {
            $ldap_cohort_dn = ldap_get_dn($ldapconnection, $entry);
            break;
        }
    }

    return $ldap_cohort_dn;
}

/**
 * Reads cohort information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $cohortidentifier cohort (with system magic quotes)
 * @return mixed object or false on error
 */
function local_ent_installer_get_cohortinfo_asobj($ldapauth, $cohortidentifier, $options = array()) {

    $cohort_array = local_ent_installer_get_cohortinfo($ldapauth, $cohortidentifier, $options);

    if ($cohort_array == false) {
        return false; //error or not found
    }

    $cohort_array = truncate_userinfo($cohort_array);
    $cohort = new stdClass();
    foreach ($cohort_array as $key => $value) {
        $cohort->{$key} = $value;
    }
    return $cohort;
}

/**
 * Bulk insert in SQL's temp table
 */
function local_ent_installer_ldap_bulk_cohort_insert($cohortidentifier, $timemodified) {
    global $DB;

    if (!$DB->record_exists('tmp_extcohort', array('idnumber' => $cohortidentifier))) {
        $params = array('idnumber' => $cohortidentifier, 'lastmodified' => $timemodified);
        $DB->insert_record_raw('tmp_extcohort', $params, false, true);
    }
    echo '.';
}
