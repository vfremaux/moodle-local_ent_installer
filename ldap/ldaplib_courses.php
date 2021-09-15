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

require_once($CFG->dirroot.'/local/ent_installer/lib.php');
require_once($CFG->dirroot.'/local/ent_installer/compatlib.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

/**
 * Synchronizes courses categories by scanning group records in a OU.
 * @param array $options an array of options
 */
function local_ent_installer_sync_coursecats($ldapauth, $options = array()) {
    global $DB, $SITE;

    $config = get_config('local_ent_installer');

    mtrace('');

    if (empty($config->sync_enable)) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    if (empty($config->sync_coursecat_enable)) {
        mtrace(get_string('synccoursecatsdisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    core_php_time_limit::raise(600);

    $ldapconnection = $ldapauth->ldap_connect();
    // Ensure an explicit limit, or some defaults may  cur some results.
    ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 100000);
    // Read the effective limit in a variable.
    ldap_get_option($ldapconnection, LDAP_OPT_SIZELIMIT, $retvalue);
    mtrace("Ldap opened with sizelimit $retvalue");

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ', microtime());
    $starttick = (float)$sec + (float)$usec;

    mtrace(get_string('lastrun', 'local_ent_installer', userdate(@$config->last_sync_date_coursecats)));

    // Define table user to be created.

    $table = new xmldb_table('tmp_extcoursecats');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extcoursecats'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    $contexts = explode(';', $config->coursecat_contexts);
    list($institutionidlist, $institutionalias) = local_ent_installer_strip_alias($config->institution_id);
    $institutionids = explode(',', $institutionidlist);
    if (empty($institutionids)) {
        // Defaults to the current site name as unique institution to process.
        $institutionids = array($SITE->shortname);
    }

    $ldappagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    if ($ldappagedresults) {
        mtrace("Paging results...\n");
    } else {
        mtrace("Paging not supported...\n");
    }

    $ldapcookie = '';

    $coursecatrecordfields = array($config->coursecat_idnumber_attribute,
                                $config->coursecat_name_attribute,
                                $config->record_date_fieldname);
    if (!empty($config->coursecat_is_full_path) && !empty($config->coursecat_parent_attribute)) {
        // Optional parent.
        $coursecatrecordfields[] = $config->coursecat_parent_attribute;
    }

    // First fetch idnumbers to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->coursecat_selector_filter);

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
                    mtrace("ldapsearch $context, $filter for ".$config->coursecat_idnumber_attribute);
                    $params = array($config->coursecat_idnumber_attribute, $config->record_date_fieldname);
                    $ldapresult = ldap_search($ldapconnection, $context, $filter, $params);
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".$config->coursecat_idnumber_attribute);
                    $params = array($config->coursecat_idnumber_attribute, $config->record_date_fieldname);
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
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->coursecat_idnumber_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->coursecat_idnumber_filter.'/', $value, $matches)) {
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

                        local_ent_installer_ldap_bulk_coursecat_insert($value, $modify, $options);
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                echo "\n";
                unset($ldapresult); // Free mem.
            } while ($ldappagedresults && !empty($ldapcookie));
        }
    }

    // TODO : Finish writing the coursecat synchronisation.
    // ...

    set_config('last_sync_date_coursecats', time(), 'local_ent_installer');
}

/**
 * Synchronizes courses by getting records from a group holding ldap context.
 * @param object $ldapauth an initialized ldap authentication instance
 * @param array $options an array of options
 */
function local_ent_installer_sync_courses($ldapauth, $options = array()) {
    global $DB, $SITE, $CFG;

    $config = get_config('local_ent_installer');

    if ($config->course_enrol_method == 'sync') {
        include_once($CFG->dirroot.'/enrol/sync/lib.php');
    }

    mtrace('');

    if (empty($config->sync_enable)) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    if (empty($config->sync_course_enable)) {
        mtrace(get_string('synccoursedisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    core_php_time_limit::raise(600);

    $ldapconnection = $ldapauth->ldap_connect();
    // Ensure an explicit limit, or some defaults may  cur some results.
    ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 100000);
    ldap_get_option($ldapconnection, LDAP_OPT_SIZELIMIT, $retvalue);
    mtrace("Ldap opened with sizelimit $retvalue");

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ', microtime());
    $starttick = (float)$sec + (float)$usec;

    mtrace(get_string('lastrun', 'local_ent_installer', userdate(@$config->last_sync_date_courses)));

    // Define table user to be created.

    $table = new xmldb_table('tmp_extcourse');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extcourse'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    $contexts = explode(';', $config->course_contexts);
    list($institutionidlist, $institutionalias) = local_ent_installer_strip_alias($config->institution_id);
    $institutionids = explode(',', $institutionidlist);
    if (empty($institutionids)) {
        // Defaults to the current site name as unique institution to process.
        $institutionids = array($SITE->shortname);
    }

    $ldappagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    if ($ldappagedresults) {
        mtrace("Paging results...\n");
    } else {
        mtrace("Paging not supported...\n");
    }

    $ldapcookie = '';

    $primaryattribute = 'course_'.$config->course_primary_key.'_attribute';
    $primaryfilter = 'course_'.$config->course_primary_key.'_filter';
    $primaryldapattribute = $config->$primaryattribute;

    // Receive an eventual single course request.
    $requid = '*';
    if (!empty($options['cid'])) {
        // Force the ldap filter to match only one single course. We cannot be in forced mode in this case to avoid
        // deletion.
        // But we will disable the timestamp check.
        $options['force'] = false;
        $course = $DB->get_record('course', array('id' => $options['cid']));
        $requid = $course->{$config->course_primary_key};
    }

    $courserecordfields = array($config->course_idnumber_attribute,
                                $config->course_fullname_attribute,
                                $config->course_summary_attribute,
                                $config->course_shortname_attribute,
                                $config->record_date_fieldname);

    $insertcount = 0;
    $updatecount = 0;
    $inserterrorcount = 0;
    $updateerrorcount = 0;
    $hasentries = false;

    // First fetch idnumbers to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->course_selector_filter);
        // Adds the "single or all" records filter.
        $filter = "(&({$primaryldapattribute}={$requid}){$filter})";

        foreach ($contexts as $context) {
            $cnt = 0;
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
                    mtrace("ldapsearch $context, $filter for ".$config->$primaryattribute.' Timed by: '.$config->record_date_fieldname);
                    $params = array($config->$primaryattribute, $config->record_date_fieldname);
                    $ldapresult = ldap_search($ldapconnection, $context, $filter, $params);
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".$config->$primaryattribute.' Timed by: '.$config->record_date_fieldname);
                    $params = array($config->$primaryattribute, $config->record_date_fieldname);
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
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->$primaryattribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        $raw = $value;
                        if (preg_match('/'.$config->$primaryfilter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }
                        if (!empty($options['verbose'])) {
                            mtrace("Got ext data in {$config->$primaryattribute} > raw: $raw, filtered: $value");
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

                        $cnt++;
                        local_ent_installer_ldap_bulk_course_insert($value, $modify, $options, $cnt);
                        $hasentries = true;
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                echo "\n";
                unset($ldapresult); // Free mem.
            } while ($ldappagedresults && !empty($ldapcookie));
            echo "Got $cnt records in context\n";
        }
    }

    /*
     * If no entries at all get out. there may be a misconfig in fitlers and we need protect all our data.
     */
     if (!$hasentries) {
        echo "No entries from LDAP. Resuming.";
        return;
     }

    /*
     * If LDAP paged results were used, the current connection must be completely
     * closed and a new one created, to work without paged results from here on.
     */
    if ($ldappagedresults) {
        $ldapauth->ldap_close(true);
        $ldapconnection = $ldapauth->ldap_connect();
    }

    mtrace('');

    // Deleted courses.
    $sql = "
        SELECT
            c.{$config->course_primary_key},
            c.id as cid
        FROM
            {course} c
        LEFT JOIN
            {tmp_extcourse} xc
        ON
            xc.{$config->course_primary_key} = c.{$config->course_primary_key}
        WHERE
            xc.{$config->course_primary_key} IS NULL AND
            xc.id != ?
        HAVING
           c.{$config->course_primary_key} NOT LIKE '_%'
    ";
    // HAVING : Only delete courses that are known being NOT automated.

    $deleted = $DB->get_records_sql($sql, array(SITEID));

    // New courses.
    $sql = "
        SELECT
            xc.{$config->course_primary_key}
        FROM
            {tmp_extcourse} xc
        LEFT JOIN
            {course} c
        ON
            xc.{$config->course_primary_key} = c.{$config->course_primary_key}
        WHERE
            c.{$config->course_primary_key} IS NULL
    ";

    $created = $DB->get_records_sql($sql);

    $lastmodified = '';
    $params = array();
    if (empty($options['force']) && empty($options['cid'])) {
        // If not force, do check when courses have changed in ldap.
        // If we are getting a single record, avoid masking with timestamp check.
        $lastmodified = ' AND xc.lastmodified > ? ';
        $params[] = 0 + @$config->last_sync_date_courses;
    }

    // Updated courses.
    $sql = "
        SELECT
            xc.{$config->course_primary_key},
            c.id as cid
        FROM
            {course} c,
            {tmp_extcourse} xc
        WHERE
            xc.{$config->course_primary_key} = c.{$config->course_primary_key}
            $lastmodified
    ";

    $updated = $DB->get_records_sql($sql, $params);

    /* ************ course deletion *************** */

    $deletecount = 0;
    if (($options['operation'] == 0) || ($options['operation'] == 'delete')) {
        mtrace(">> ".get_string('deletingcourses', 'local_ent_installer'));
        mtrace('');
        // Getting site level course ids to protect.
        $protectids = array();

        if ($deleted) {
            $dlcnt = 0;
            foreach ($deleted as $dl) {
                if (in_array($dl->cid, $protectids) || empty($dl->cid)) {
                    continue;
                }

                mtrace('--'.++$dlcnt.'--');

                if (empty($options['simulate'])) {
                    delete_course($dl->cid);
                    $deletecount++;
                    mtrace(get_string('coursedeleted', 'local_ent_installer', $dl->idnumber));
                } else {
                    mtrace('[SIMULATION] '.get_string('coursedeleted', 'local_ent_installer', $dl->idnumber));
                }
            }
            mtrace('');
        } else {
            mtrace(get_string('nothingtodo', 'local_ent_installer'));
            mtrace('');
        }
    }

    /* ************ course update *************** */

    if (($options['operation'] == 0) || ($options['operation'] == 'update')) {
        mtrace(">> ".get_string('updatingcourses', 'local_ent_installer'));
        mtrace('');
        if ($updated) {
            $upcnt = 0;
            foreach ($updated as $up) {

                mtrace('--'.++$upcnt.'--');

                // Build an external pattern.
                $primaryid = $up->{$config->course_primary_key}; // Unprefix the course idnumber.

                // The following filters may not be usefull.
                $courseldapidentifier = $config->course_id_pattern;
                $courseldapidentifier = str_replace('%CID%', $primaryid, $courseldapidentifier);
                $courseldapidentifier = str_replace('%ID%', $config->institution_id, $courseldapidentifier);

                if (!$courseinfo = local_ent_installer_get_courseinfo_asobj($ldapauth, $courseldapidentifier, '', $options)) {
                    mtrace('ERROR : course info error');
                    continue;
                }

                $oldrec = $DB->get_record('course', array('id' => $up->cid));
                $oldrec->fullname = $courseinfo->fullname;
                $oldrec->shortname = ($config->course_primary_key == 'shortname') ? $primaryid : $courseinfo->shortname;
                // Ensure we have a correctly prefixed course IDNum and wellformed idnumber.
                $oldrec->idnumber = ($config->course_primary_key == 'idnumber') ? $primaryid : $courseinfo->idnumber;
                $oldrec->summary = @$courseinfo->summary;
                $oldrec->summaryformat = FORMAT_HTML;
                $oldrec->timemodified = time();

                if (!$oldrec) {
                    echo "FATAL ERROR : Updated course cannot get record \n";
                    return;
                }

                $category = local_ent_installer_check_category($courseinfo, $options);
                if ($category <= 0) {
                    mtrace("FATAL ERROR : Unresolved category for updating course. Course will not move. Error code : {$course['category']}");
                } else {
                    $oldrec->category = $category;
                }

                if (empty($options['simulate'])) {
                    try {
                        $DB->update_record('course', $oldrec);
                        $updatecount++;
                        mtrace(get_string('courseupdated', 'local_ent_installer', $oldrec));
                        mtrace('');
                    } catch (Exception $e) {
                        $updateerrorcount++;
                    }
                } else {
                    mtrace('[SIMULATION] '.get_string('courseupdated', 'local_ent_installer', $oldrec));
                    mtrace('');
                }

                local_ent_installer_process_cohorts($courseinfo, $oldrec, $options);
                local_ent_installer_process_students($courseinfo, $oldrec, $options);

                // Make an array course descriptor for updates
                $oldcourse = array();
                $oldcourse['fullname'] = $courseinfo->fullname;
                $oldcourse['shortname'] = $courseinfo->shortname;
                $oldcourse['category'] = $category;
                $oldcourse['summary'] = @$courseinfo->summary;
                $oldcourse['summaryformat'] = FORMAT_HTML;
                $oldcourse['idnumber'] = $courseinfo->idnumber;
                $oldcourse['contextid'] = $systemcontext->id;
                $oldcourse['component'] = 'local_ent_installer';
                $oldcourse['timecreated'] = $oldrec->timecreated;
                $oldcourse['timemodified'] = time();
                $oldcourse['template'] = @$courseinfo->template;
                $oldcourse['teachers_enrol'] = @$courseinfo->teachers;
                $oldcourse['editingteachers_enrol'] = @$courseinfo->editingteachers;

                if (!empty($options['verbose'])) {
                    mtrace("Processing power roles");
                }
                // Extract teacher and editing teacher users and setup teacher_enrol entry.
                local_ent_installer_decode_teachers($oldcourse, $courseinfo, $options);
                local_ent_installer_process_teachers($oldrec->id, $oldcourse, $options);
                local_ent_installer_process_editingteachers($oldrec->id, $oldcourse, $options);
            }
            mtrace('');
        } else {
            mtrace(get_string('nothingtodo', 'local_ent_installer'));
            mtrace('');
        }
    }

    /* ************ course creation *************** */

    if (($options['operation'] == 0) || ($options['operation'] == 'create')) {
        mtrace(">> ".get_string('creatingcourses', 'local_ent_installer'));
        mtrace('');
        if ($created) {
            $crcnt = 0;
            foreach ($created as $cr) {

                mtrace('--'.++$crcnt.'--');
                // Build an external pattern.
                $courseldapidentifier = $config->course_id_pattern;
                $primaryid = $cr->{$config->course_primary_key}; // Unprefix the course idnumber.
                $courseldapidentifier = str_replace('%CID%', $primaryid, $courseldapidentifier);
                $courseldapidentifier = str_replace('%ID%', $config->institution_id, $courseldapidentifier);

                $courseinfo = local_ent_installer_get_courseinfo_asobj($ldapauth, $courseldapidentifier, '', $options);

                $course = array();
                $course['fullname'] = $courseinfo->fullname;
                $course['shortname'] = ($config->course_primary_key == 'shortname') ? $primaryid : $courseinfo->shortname;
                $course['category'] = local_ent_installer_check_category($courseinfo, $options);
                $course['summary'] = @$courseinfo->summary;
                $course['summaryformat'] = FORMAT_HTML;
                $course['idnumber'] = ($config->course_primary_key == 'idnumber') ? $primaryid : $courseinfo->idnumber;
                $course['contextid'] = $systemcontext->id;
                $course['component'] = 'local_ent_installer';
                $course['timecreated'] = time();
                $course['timemodified'] = time();
                $course['template'] = @$courseinfo->template;

                if ($course['category'] <= 0) {
                    mtrace("FATAL ERROR : Unresolved category for creating course. Skipping. Error code : {$course['category']}");
                    continue;
                }

                // Extract teacher and editing teacher users and setup teacher_enrol entry.
                local_ent_installer_decode_teachers($course, $courseinfo, $options);

                $e = new StdClass;
                $course['id'] = false;

                // Simulate is processed internally to course creation.
                $course['id'] = local_ent_installer_create_course($course, $options);
                $e->id = $course['id'];
                $e->name = $courseinfo->fullname;
                if (($course['id'] > 0) && ($course['id'] != 999999)) {
                    mtrace(get_string('coursecreated', 'local_ent_installer', $e));
                    mtrace('');
                } else {
                    mtrace(get_string('coursecreationerror', 'local_ent_installer', $e));
                    mtrace('');
                    continue;
                }

                if (($course['id'] > 0) && ($course['id'] != 999999)) {
                    $insertcount++;

                    // Get final record of the course.
                    $newcourse = $DB->get_record('course', array('id' => $course['id']));

                    if (!empty($options['simulate']) && !$newcourse) {
                        $newcourse = (object) array('id' => 'N.C.',
                                                    'shortname' => 'SIMUL',
                                                    'fullname' => 'Simulated created course',
                                                    'idnumber' => 'SIMULATED');
                    }

                    local_ent_installer_process_cohorts($courseinfo, $newcourse, $options);
                    local_ent_installer_process_students($courseinfo, $newcourse, $options);

                    if (is_dir($CFG->dirroot.'/local/moodlescript')) {
                        // If the moodlescript engine is installed.
                        local_ent_installer_post_process_course($newcourse);
                    }
                } else {
                    $inserterrorcount++;
                }
                mtrace('');
            }
        } else {
            mtrace(get_string('nothingtodo', 'local_ent_installer'));
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

    // Calculate bench time.
    list($usec, $sec) = explode(' ', microtime());
    $stoptick = (float)$sec + (float)$usec;

    $deltatime = $stoptick - $starttick;

    mtrace('Execution time : '.$deltatime);
    mtrace('Insertions : '.$insertcount);
    mtrace('Updates : '.$updatecount);
    mtrace('Deletes : '.$deletecount);
    mtrace('Insertion errors : '.$inserterrorcount);
    mtrace('Update errors : '.$updateerrorcount);

    $benchrec = new StdClass();
    $benchrec->synctype = 'courses';
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

    set_config('last_sync_date_courses', time(), 'local_ent_installer');

}

/**
 * Reads course information from ldap and returns it in array()
 *
 * Function should return all information available. If you are saving
 * this information to moodle user-table you should honor syncronization flags
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $courseidentifier course identifier (ldap side format)
 * @param array $options incoming processing options
 *
 * @return mixed array with no magic quotes or false on error
 */
function local_ent_installer_get_courseinfo($ldapauth, $courseidentifier, $extracourseattributes = array(), $options = array()) {
    global $DB;
    static $courseattributes;
    static $config;
    static $ldapconfig;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    if (!empty($options['verbose'])) {
        mtrace("Receiving attributes from LDAP");
    }

    // Prepare attribute list to fetch.

    // Load some cached static data.
    if (!isset($courseattributes)) {
        // set specific attributes that hold interesting information.
        $courseattributes = array();
        if (!empty($config->course_fullname_attribute)) {
            $courseattributes['fullname'] = core_text::strtolower($config->course_fullname_attribute);
        }
        if (!empty($config->course_shortname_attribute)) {
            $courseattributes['shortname'] = core_text::strtolower($config->course_shortname_attribute);
        }
        if (!empty($config->course_summary_attribute)) {
            $courseattributes['summary'] = core_text::strtolower($config->course_summary_attribute);
        }
        if (!empty($config->course_idnumber_attribute)) {
            $courseattributes['idnumber'] = core_text::strtolower($config->course_idnumber_attribute);
        }
        if (!empty($config->course_category_attribute)) {
            $courseattributes['category'] = core_text::strtolower($config->course_category_attribute);
        }
        if (!empty($config->course_teachers_attribute)) {
            $courseattributes['teachers'] = core_text::strtolower($config->course_teachers_attribute);
        }
        if (!empty($config->course_template_attribute)) {
            $courseattributes['template'] = core_text::strtolower($config->course_template_attribute);
        }
        if (!empty($config->course_editingteachers_attribute)) {
            $courseattributes['editingteachers'] = core_text::strtolower($config->course_editingteachers_attribute);
        }
        if (!empty($ldapauth->config->memberattribute)) {
            $courseattributes['students'] = core_text::strtolower($ldapauth->config->memberattribute);
        } else {
            $courseattributes['students'] = 'member';
        }
    }

    // Add extra attributes.
    if (!empty($extracourseattributes)) {
        foreach ($extracourseattributes as $local => $remote) {
            $courseattributes[$local] = $remote;
        }
    }

    $remoteattributes = array();
    foreach (array_values($courseattributes) as $attr) {
        if (!in_array($attr, $remoteattributes)) {
            $remoteattributes[] = $attr;
        }
    }

    // Get primary DN of data record.

    $extcourseidentifier = core_text::convert($courseidentifier, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($coursedn = local_ent_installer_ldap_find_course_dn($ldapconnection, $extcourseidentifier, $options))) {
        $ldapauth->ldap_close();
        if (!empty($options['verbose'])) {
            mtrace("\tInternal Error : Could not locate $extcourseidentifier ");
        }
        return false;
    }

    // Master call to ldap for getting attributes.

    if (!empty($options['verbose'])) {
        mtrace("\tGetting $coursedn for ".implode(',', $remoteattributes));
    }
    if (!$courseinforesult = ldap_read($ldapconnection, $coursedn, '(objectClass=*)', $remoteattributes)) {
        $ldapauth->ldap_close();
        if (!empty($options['verbose'])) {
            mtrace("\tFailed reading in LDAP ");
        }
        return false;
    }

    $courseentry = ldap_get_entries_moodle($ldapconnection, $courseinforesult);
    if (empty($courseentry)) {
        $ldapauth->ldap_close();
        if (!empty($options['verbose'])) {
            mtrace("\tNo course entries found in LDAP ");
        }
        return false; // Entry not found.
    }

    // Decode all required attributes.

    $result = array();
    foreach ($courseattributes as $key => $value) {

        // Value is an attribute name.
        $entry = array_change_key_case($courseentry[0], CASE_LOWER);

        if (!array_key_exists($value, $entry)) {
            if (!empty($options['verbose'])) {
                mtrace("\tRequested value $value but missing in record");
            }
            continue; // Wrong data mapping!
        }

        if ($key == 'students') {
            /*
             * Students can be cohorts or single users. If cohorts, then we should add a cohort enrol method
             * to the course.
             * If a single member, we should just enrol the member to the course.
             */
            // Get the full array of values.
            // Dispatch values in two arrays for single users and cohorts.
            $students = array();
            $cohorts = array();
            mtrace ("\tProcessing members");
            foreach ($entry[$value] as $newvalopt) {
                $newvalopt  = core_text::convert($newvalopt, $ldapauth->config->ldapencoding, 'utf-8');

                $isuser = true;
                if (!empty($config->course_membership_cohort_detector)) {
                    $isuser = false;
                    if (preg_match('/'.$config->course_membership_cohort_detector.'/', $newvalopt)) {
                        if (!empty($options['verbose'])) {
                            mtrace("\t\tExtracting as cohort from $newvalopt with {$config->course_membership_cohort_filter} ");
                        }
                        if (preg_match('/'.$config->course_membership_cohort_filter.'/', $newvalopt, $matches)) {
                            // entry is a cohort DN.
                            // $identifier = core_text::strtolower($matches[1]); // Maybe let case as is.
                            $identifier = $matches[1]; // Maybe let case as is.
                            if (!empty($options['verbose'])) {
                                mtrace("\t\tGetting cohort record for 'idnumber' = $identifier");
                            }
                            $fields = 'id,idnumber,name';
                            $cohort = $DB->get_record('cohort', array('idnumber' => $identifier), $fields);
                            if (!$cohort) {
                                mtrace("\t\tError : Cohort record not found for $identifier. Skipping membership");
                                continue;
                            }
                            $cohort->cohortid = $cohort->id;
                            $cohorts[$cohort->id] = $cohort;
                        } else {
                            if (!empty($options['verbose'])) {
                                mtrace("\t\tUnrecognized cohort pattern.");
                            }
                            // Record is failing.
                            continue;
                        }
                    } else {
                        $isuser = true;
                    }
                }
                if ($isuser) {
                    if (!empty($config->course_membership_dereference_attribute)) {

                        if (is_numeric($newvalopt)) {
                            // This is the "End of list" counter. Skip it.
                            continue;
                        }

                        // Newvalopt is a true DN we must query to get an attribute inside.
                        if (!empty($options['verbose'])) {
                            mtrace("\t\tGetting user DN object with $newvalopt for {$config->course_membership_dereference_attribute} ");
                        }
                        $userinforesult = ldap_read($ldapconnection, $newvalopt, '(objectClass=*)', array($config->course_membership_dereference_attribute));
                        $userentry = ldap_get_entries_moodle($ldapconnection, $userinforesult);
                        if (!array_key_exists($config->course_membership_dereference_attribute, $userentry)) {

                            // Take first and unique entry in record.
                            $uservalopt = @$userentry[0][$config->course_membership_dereference_attribute][0];
                            if (empty($uservalopt)) {
                                if (!empty($options['verbose'])) {
                                    mtrace("\t\tEmpty dereference attribute. Skipping.");
                                }
                                continue;
                            }
                            $uservalopt  = core_text::convert($uservalopt, $ldapauth->config->ldapencoding, 'utf-8');

                            if (!empty($config->course_membership_filter)) {
                                if (preg_match('/'.$config->course_membership_filter.'/', $uservalopt, $matches)) {
                                    $uservalopt = $matches[1];
                                }
                            }

                            $fields = 'id,username,firstname,lastname,idnumber';
                            $user = $DB->get_record('user', array($config->course_user_key => $uservalopt), $fields);
                            if (!$user) {
                                mtrace("\t\tError : User record not found for {$uservalopt} by {$config->course_user_key}. Skipping membership");
                                continue;
                            }
                            $user->userid = $user->id;
                            $students[$user->id] = $user;
                        }

                    } else if (!empty($config->course_membership_filter)) {

                        if (is_numeric($newvalopt)) {
                            // This is the "End of list" counter. Skip it.
                            continue;
                        }

                        if (!empty($options['verbose'])) {
                            mtrace("\t\tExtracting as user from $newvalopt with {$config->course_membership_filter} ");
                        }
                        if (preg_match('/'.$config->course_membership_filter.'/', $newvalopt, $matches)) {
                            // Exclude potential arity count that comes at end of multivalued entries.
                            $identifier = core_text::strtolower($matches[1]);
                            if (!empty($options['verbose'])) {
                                mtrace("\t\tGetting user record for {$config->course_user_key} = $identifier");
                            }
                            $fields = 'id,username,firstname,lastname';
                            $user = $DB->get_record('user', array($config->course_user_key => $identifier), $fields);
                            if (!$user) {
                                mtrace("\t\tError : User record not found for $identifier. Skipping membership");
                                continue;
                            }
                            $user->userid = $user->id;
                            $students[$user->id] = $user;
                        } else {
                            if (!empty($options['verbose'])) {
                                mtrace("\t\tNo course membership match with filter {$config->course_membership_filter}");
                            }
                        }
                    }
                }
            }
            $result['students'] = $students;

            if (!empty($cohorts)) {
                $result['cohorts'] = $cohorts;
            }
            continue;
        }

        // Normal attribute case.

        if (is_array($entry[$value])) {
            $newval = core_text::convert($entry[$value][0], $ldapauth->config->ldapencoding, 'utf-8');
        } else {
            $newval = core_text::convert($entry[$value], $ldapauth->config->ldapencoding, 'utf-8');
        }

        // Special processing of fields.
        if (!in_array($key, array('teachers', 'editingteachers'))) {
            /*
             * teachers and editingteachers fields admin list syntax that needs to be decoded/filtered later.
             */
            if (!empty($options['verbose'])) {
                mtrace("\tChecking attribute $key as $value");
            }

            $filterkey = 'course_'.$key.'_filter';
            if (!empty($config->$filterkey)) {
                if (!empty($options['verbose'])) {
                    mtrace("\tExtracting with {$config->$filterkey} from attribute $key = $newval");
                }
                // If a filter exists, apply the filter and extract the partial value.
                // The filter MUST have one subpattern capture group () and no opening/closing char.
                preg_match('/'.$config->$filterkey.'/', $newval, $matches);
                $newval = @$matches[1];
            }
        } else {
            if (!empty($options['verbose'])) {
                mtrace("\tChecking attribute $key as $value");
            }
        }

        // Ouptut patternisation (standard patternisation using generic %VALUE%).
        $patternkey = 'course_'.$key.'_pattern';
        if (!empty($config->$patternkey)) {
            if (!empty($options['verbose'])) {
                mtrace("\tOutput patternisation with {$config->$patternkey} ");
            }
            $newval = preg_replace('/%VALUE%/', $newval, $config->$patternkey);
        }

        if (!empty($newval)) { // Favour ldap entries that are set.
            $ldapval = $newval;
        }

        if (!is_null($ldapval)) {
            $result[$key] = $ldapval;
        }
    }

    // Process template default.
    if (!empty($config->course_template_default)) {
        if (array_key_exists('template', $result) && ($result['template'] == 'default')) {
            if (!empty($options['verbose'])) {
                mtrace("\tSetting default template to {$config->course_template_default} ");
            }
            $result['template'] = $config->course_template_default;
        }
    }

    $ldapauth->ldap_close();
    return $result;
}

/**
 * Search specified contexts for course by external course identifier and return the course dn
 * like: cn=shortname,ou=suborg,o=org.
 * Adapts the algorithm of /lib/ldaplib/ldap_find_userdn().
 *
 * @param resource $ldapconnection a valid LDAP connection
 * @param string $extcoursedn the courseid to search (in external LDAP encoding, no db slashes)
 * @return mixed the user dn (external LDAP encoding) or false
 */
function local_ent_installer_ldap_find_course_dn($ldapconnection, $extcoursedn, $options = array()) {
    static $config;
    static $ldapconfig;

    if (!isset($config)) {
        // We might be called a lot of times.
        $config = get_config('local_ent_installer');
    }

    if (!isset($ldapconfig)) {
        // We might be called a lot of times.
        $ldapconfig = get_config('auth_ldap');
    }

    $ldapcontexts = explode(';', $config->course_contexts);

    $searchattrib = $config->course_id_attribute;
    $coursefilter = $config->course_selector_filter;

    if (empty($ldapconnection) || empty($extcoursedn) || empty($ldapcontexts)) {
        return false;
    }

    // Default return value.
    $ldapcoursedn = false;

    // Get all contexts and look for first matching user
    foreach ($ldapcontexts as $context) {
        $context = trim($context);
        if (empty($context)) {
            continue;
        }

        if (!empty($options['verbose'])) {
            mtrace('Searching DN in LDAP in '.$context.' querying with (&'.$coursefilter.'('.$searchattrib.'='.ldap_filter_addslashes($extcoursedn).'))'.
            ' for attributes '.print_r($searchattrib, true));
        }
        if ($ldapconfig->search_sub) {
            $ldapresult = @ldap_search($ldapconnection, $context,
                                        '(&'.$coursefilter.'('.$searchattrib.'='.ldap_filter_addslashes($extcoursedn).'))',
                                        array($searchattrib));
        } else {
            $ldapresult = @ldap_list($ldapconnection, $context,
                                      '(&'.$coursefilter.'('.$searchattrib.'='.ldap_filter_addslashes($extcoursedn).'))',
                                      array($searchattrib));
        }

        if (!$ldapresult) {
            continue; // Not found in this context.
        }

        $entry = ldap_first_entry($ldapconnection, $ldapresult);
        if ($entry) {
            $ldapcoursedn = ldap_get_dn($ldapconnection, $entry);
            break;
        }
    }

    return $ldapcoursedn;
}

/**
 * Reads course information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $courseidentifier course (with system magic quotes)
 * @param array $extracourseattributes additional ldap attributes that need to be fetched in ldap
 * @param array $options incoming procesisng options
 * @return mixed object or false on error
 */
function local_ent_installer_get_courseinfo_asobj($ldapauth, $courseidentifier, $extracourseattributes = array(), $options = array()) {

    $coursearr = local_ent_installer_get_courseinfo($ldapauth, $courseidentifier, $extracourseattributes, $options);

    if ($coursearr == false) {
        return false; // Error or not found.
    }

    $course = new stdClass();
    foreach ($coursearr as $key => $value) {
        $course->{$key} = $value;
    }
    return $course;
}

/**
 * Bulk insert in SQL's temp table.
 * @param string $coursecatidentifier a course category identifier.
 * @param int $timemodified modification timestamp.
 * @param array $options incoming processing options
 */
function local_ent_installer_ldap_bulk_coursecat_insert($coursecatidentifier, $timemodified, $options = array()) {
    global $DB;

    if (!$DB->record_exists('tmp_extcoursecat', array('idnumber' => $coursecatidentifier))) {
        $params = array('idnumber' => $coursecatidentifier, 'lastmodified' => $timemodified);
        if (!empty($options['force'])) {
            mtrace("Inserting $coursecatidentifier, $timemodified");
        }
        $DB->insert_record_raw('tmp_extcoursecat', $params, false, true);
    }
    echo '.';
}

/**
 * Bulk insert in SQL's temp table.
 * @param string $courseidentifier a course identifier.
 * @param int $timemodified modification timestamp.
 * @param array $options incoming processing options.
 */
function local_ent_installer_ldap_bulk_course_insert($courseidentifier, $timemodified, $options = array(), $cnt) {
    global $DB;

    $config = get_config('local_ent_installer');

    if (!$DB->record_exists('tmp_extcourse', array($config->course_primary_key => $courseidentifier))) {
        $params = array($config->course_primary_key => $courseidentifier, 'lastmodified' => $timemodified);
        // if (!empty($options['verbose'])) {
            mtrace("Inserting $courseidentifier by {$config->course_primary_key}, ".userdate($timemodified));
        // }
        $DB->insert_record_raw('tmp_extcourse', $params, false, true);
    }
    echo '.';
    if (($cnt > 0) && ($cnt % 50 == 0)) {
        echo "\n";
    }
}

/**
 * Checks and create course categories when creating or updating courses.
 * @param objectref &$courseinfo the course info coming from ldap.
 * @param array $options incoming processing options (simulate)
 * @return the course final category id.
 */
function local_ent_installer_check_category(&$courseinfo, $options = array()) {
    global $DB;

    if (!empty($options['verbose'])) {
        mtrace("Checking category");
    }

    $config = get_config('local_ent_installer');

    $defaultcategoryid = 0;
    if (!empty($config->course_default_category_idnumber)) {
        $defaultcategoryid = $DB->get_field('course_categories', 'id', array('idnumber' => $config->course_default_category_idnumber));
    }

    if (empty($config->course_category_attribute)) {
        if (!empty($options['verbose'])) {
            mtrace("\tCategory attribute not specified. Resuming");
        }
        if ($defaultcategoryid) {
            mtrace("\tUndefined attribute. Taking default category\n");
        }
        return $defaultcategoryid;
    }

    if (empty($courseinfo->category)) {
        if (!empty($options['verbose'])) {
            mtrace("\tEmpty category data. Resuming\n");
        }
        if ($defaultcategoryid) {
            mtrace("\tEmpty category data. Taking default category\n");
        }
        return $defaultcategoryid;
    }

    $compositesep = $config->composite_separator;
    $pathsep = $config->course_categorypath_separator;

    if (!empty($options['verbose'])) {
        mtrace("Parsing category data: ".$courseinfo->category);
    }

    $pathparts = explode($pathsep, $courseinfo->category);
    $cats = array();

    // Prepare and check category data.
    if ($config->course_categorysyntax_attribute == 'composite') {

        mtrace(' as composite using path separator as '.$pathsep);

        foreach ($pathparts as $pathelm) {

            if ($pathelm == '#') {
                // MBS empty elements.
                continue;
            }

            if (!empty($options['verbose'])) {
                mtrace("Parsing category element : ".$pathelm.' using composite separator as '.$compositesep);
            }

            @list($idnumber, $catname) = explode($compositesep, trim($pathelm));
            $idnumber = trim($idnumber);
            $catname = trim($catname);
            if (empty($idnumber)) {
                if (!empty($options['verbose'])) {
                    mtrace("\tPath issue. Missing IDNumber. Resuming\n");
                }

                if ($defaultcategoryid) {
                    mtrace("\tPath issue. Taking default category\n");
                    return $defaultcategoryid;
                }

                return -1; // Path issue to report.
            }
            if (empty($catname)) {
                if (!empty($options['verbose'])) {
                    mtrace("\tPath issue. Missing Category Name. Resuming\n");
                }

                if ($defaultcategoryid) {
                    mtrace("\tPath issue. Taking default category\n");
                    return $defaultcategoryid;
                }

                return -2; // Path issue to report.
            }
            $cat = new Stdclass;
            $cat->idnumber = $idnumber;
            $cat->name = $catname;
            $cats[] = $cat;
        }
    } else if ($config->course_categorysyntax_attribute == 'idnumber') {
        // Confirm category existance by idnumber...
        $parent = $DB->get_record('course_categories', array('idnumber' => $courseinfo->category));
        if (!$parent) {
            if ($defaultcategoryid) {
                mtrace("\tNot existing idnumber. Taking default category\n");
                return $defaultcategoryid;
            }
            return -4;
        } else {
            return $parent->id;
        }
    } else if ($config->course_categorysyntax_attribute == 'id') {
        // Confirm category existance by id...
        $parent = $DB->get_record('course_categories', array('id' => $courseinfo->category));
        if (!$parent) {
            if ($defaultcategoryid) {
                mtrace("\tNot existing id. Taking default category\n");
                return $defaultcategoryid;
            }
            return -4;
        } else {
            return $parent->id;
        }
    } else {

        foreach ($pathparts as $catname) {
            $catname = trim($catname);
            if (empty($catname)) {
                if (!empty($options['verbose'])) {
                    mtrace("\tPath issue. Missing Category name. Resuming\n");
                }
                if ($defaultcategoryid) {
                    mtrace("\tPath issue. Taking default category\n");
                    return $defaultcategoryid;
                }
                return -2; // Path issue to report.
            }
            $cat = new Stdclass;
            $cat->name = $catname;
            $cats[] = $cat;
        }
    }

    // Check and create missing cats.
    $parent = 0;
    $namepath = '';
    try {
        foreach ($cats as $cat) {
            $namepath .= '/'.$cat->name;
            if (!$existing = $DB->get_record('course_categories', array('name' => $cat->name, 'parent' => $parent))) {
                $cat->parent = $parent;
                if (empty($options['simulate'])) {
                    mtrace("\tCreating category {$cat->name} ");
                    $newcat = local_ent_installer_coursecat_create($cat, null);
                } else {
                    mtrace("\tSIMULATION: Starting building category tree... ");
                    $parent = 0;
                    break;
                }
                $parent = $newcat->id;
            } else {
                $parent = $existing->id;
            }
        }
    } catch (Exception $e) {
        mtrace("\tIdnumber probable collision when creating category: ".$namepath);
        if ($defaultcategoryid) {
            mtrace("\tPath issue. Taking default category\n");
            return $defaultcategoryid;
        }
        return -3;
    }

    return $parent; // Has been last updated to the leaf category.
}

/**
 * create a course.
 */
function local_ent_installer_create_course($course, $options = array()) {
    global $DB;

    $config = get_config('local_ent_installer');

    if (!is_array($course)) {
        return -1;
    }

    // Trap when template not found.
    if (!empty($course['template']) && $course['template'] != "\n") {
        if (local_ent_installer_is_course_identifier($course['template'])) {
            if (!($DB->get_record('course', array('shortname' => $course['template'])))) {
                if (!empty($options['verbose'])) {
                    mtrace("Template not found at {$course['template']} by shortname");
                }
                return -7;
            }
        }
    }

    /*
     * Dynamically Create Query Based on number of headings excluding Teacher[1,2,...] and Topic[1,2,...]
     * Added for increased functionality with newer versions of moodle
     * Author: Ashley Gooding & Cole Spicer
     */

    $courserec = (object)$course;
    unset($courserec->template);

    $template = trim(@$course['template']);

    if (!empty($template)) {
        if (!empty($options['verbose'])) {
            mtrace('Checking template for course as '.$template);
        }
        $result = local_ent_installer_create_course_from_template($courserec, $template, $options = array());
        if ($result < 0) {
            // Early return. Nothing else to do.
            return $result;
        }
        // Reload course for later updates.
        $newcourse = $DB->get_record('course', array('id' => $result));
    } else {
        // Check course format. Use default if missing.

        // Create default course.
        if (empty($options['simulate'])) {

            // Resolve default format.
            if (empty($course['format'])) {
                $course['format'] = get_config('moodlecourse', 'format');
            }
            if (empty($course['format'])) {
                $course['format'] = 'topics';
            }
            $courserec->format = $course['format'];

            $newcourse = create_course($courserec);
            $result = $newcourse->id;

            // Adding some sections.
            $numsections = get_config('numsections', 'moodlecourse');
            for ($i = 1; $i < $numsections; $i++) {
                // Use course default to reshape the course creation.
                $csection = new StdClass;
                $csection->course = $newcourse->id;
                $csection->section = $i;
                $csection->name = '';
                $csection->summary = '';
                $csection->sequence = '';
                $csection->visible = 1;
                $DB->insert_record('course_sections', $csection);
            }

            rebuild_course_cache($newcourse->id, true);

            // Success check?
            if (!$context = \context_course::instance($newcourse->id)) {
                return -6;
            }

        } else {
            $newcourse = new StdClass;
            $newcourse->shortname = $course['shortname'];
            $newcourse->fullname = $course['fullname'];
            $newcourse->name = $course['fullname'];
            $result = $newcourse->id = 999999;
            mtrace('SIMULATION : '.get_string('coursecreated', 'local_ent_installer', $newcourse));
        }
    }

    // Process eventual teachers bindings after course creation.
    if (is_object($newcourse) &&
            !empty($newcourse->id)) {
        local_ent_installer_process_teachers($newcourse->id, $course, $options);
        local_ent_installer_process_editingteachers($newcourse->id, $course, $options);
    }

    return $result;
}

/**
 * Create a course from a backup template
 */
function local_ent_installer_create_course_from_template($course, $template, $options = array()) {
    global $DB, $CFG, $USER;

    $origincourse = $DB->get_record('course', array('shortname' => $template));

    // Find the most suitable archive file.
    if (local_ent_installer_is_course_identifier($template)) {
        // Template is NOT a real path and thus designates a course shortname.
        if (!$archive = local_ent_installer_locate_backup_file($origincourse->id, 'course')) {

            // Get course template from publishflow backups as second chance if publishflow block is installed.
            if ($DB->get_record('block', array('name' => 'publishflow'))) {
                $archive = local_ent_installer_locate_backup_file($origincourse->id, 'publishflow');
                if (!$archive) {
                    return -20;
                }
            } else {
                return -21;
            }
        }
    } else {
        if (!preg_match('/^\/|[a-zA-Z]\:/', $template)) {
            /*
             * If relative path we expect finding those files somewhere in the distribution.
             * Not in dataroot that may be a fresh installed one).
             */
            $template = $CFG->dirroot.'/'.$template;
        }

        /*
         * Template is a real path. Integrate in a draft filearea of current user
         * (defaults to admin) and get an archive stored_file for it.
         */
        if (!file_exists($template)) {
            return -22;
        }

        // Now create a draft file from this.
        $fs = get_file_storage();

        $contextid = \context_user::instance($USER->id)->id;

        $fs->delete_area_files($contextid, 'user', 'draft', 0);

        $filerec = new StdClass;
        $filerec->contextid = $contextid;
        $filerec->component = 'user';
        $filerec->filearea = 'draft';
        $filerec->itemid = 0;
        $filerec->filepath = '/';
        $filerec->filename = basename($template);
        $archive = $fs->create_file_from_pathname($filerec, $template);
    }

    mtrace('Creating course from archive : '.$archive->get_filename());

    $uniq = rand(1, 9999);

    $tempdir = $CFG->tempdir . '/backup/' . $uniq;
    if (!is_dir($tempdir)) {
        mkdir($tempdir, 0777, true);
    }
    // Unzip all content in temp dir.

    // Actually locally copying archive.
    $contextid = \context_system::instance()->id;

    require_once($CFG->dirroot.'/lib/filestorage/mbz_packer.php');

    if ($archive->extract_to_pathname(new \mbz_packer(), $tempdir)) {

        if (!empty($options['simulate'])) {
            // We say we have creatred the course (but we did not).
            return 999999;
        }

        // Transaction.
        $transaction = $DB->start_delegated_transaction();

        // Create new course.
        $userdoingtherestore = $USER->id; // E.g. 2 == admin.
        $newcourseid = \restore_dbops::create_new_course('', '', $course->category);

        /*
         * Restore backup into course.
         * folder needs being a relative path from $CFG->tempdir.'/backup/'.
         * @see /backup/util/helper/convert_helper.class.php function detect_moodle2_format
         */
        $controller = new \restore_controller($uniq, $newcourseid,
                \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $userdoingtherestore,
                \backup::TARGET_NEW_COURSE );
        $controller->execute_precheck();
        $controller->execute_plan();

        // Commit.
        $transaction->allow_commit();

        // And import.
        if ($newcourseid) {

            // Add all changes from incoming courserec.
            $newcourse = $DB->get_record('course', array('id' => $newcourseid));
            foreach ((array)$course as $field => $value) {
                if (($field == 'format') || ($field == 'id')) {
                    continue; // Protect sensible identifying fields.
                }
                $newcourse->$field = $value;
            }
            if (empty($options['simulate'])) {
                try {
                    $DB->update_record('course', $newcourse);
                } catch (Exception $e) {
                    mtrace('failed updating');
                }
            }
            return $newcourseid;
        } else {
            return -23;
        }
    } else {
        return -24;
    }
}

/**
 * Checks if the token is a path to an archive (.mbz)
 * If not, should be a course shortname.
 * @param $str string to check
 * @return true is a shortname, false elsewhere
 */
function local_ent_installer_is_course_identifier($str) {
    return (!preg_match('/\.mbz/', $str));
}

/**
 * Decodes the teacher input, checks teachers and provide a teacher_enrol
 * array of usernames in $course array.
 * @param arrayref &$course
 * @param objectref &$courseinfo
 */
function local_ent_installer_decode_teachers(&$course, &$courseinfo, $options = array()) {
    global $DB, $CFG;

    if (!empty($options['verbose'])) {
        mtrace("Decoding teacher list {$courseinfo->teachers}");
    }

    $config = get_config('local_ent_installer');

    if (empty($courseinfo->teachers)) {
        return;
    }

    debug_trace("Composite sep: {$config->composite_separator}\n{$course['shortname']} => T: {$courseinfo->teachers} => ET: {$courseinfo->editingteachers} ", "SYNC:");

    $courseuserkey = $config->course_user_key;
    if (!empty($CFG->course_teacher_key)) {
        // Let local config override the course_user_key definition.
        $courseuserkey = $CFG->course_teacher_key;
    }

    $course['teachers_enrol'] = array();
    $emptypattern = '/^[\\'.$config->list_separator.'\\'.$config->composite_separator.']+$/';
    if (empty($courseinfo->teachers) ||
        preg_match($emptypattern, $courseinfo->teachers)) {
        if (!empty($options['verbose'])) {
            mtrace("\tEmpty teacher list");
        }
    } else {
        $teachers = explode($config->list_separator, $courseinfo->teachers);
        if (!empty($options['verbose'])) {
            mtrace("\tEmpty teacher list");
        }
        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {

                $teacher = trim($teacher);

                // Filter some unwanted parts.
                if (preg_match('/'.$config->course_teachers_filter.'/', $teacher, $matches)) {
                    $teacher = $matches[1];
                }

                $params = array($courseuserkey => $teacher);
                if (!empty($options['verbose'])) {
                    mtrace("\tGetting teacher record $teacher by $courseuserkey using filter {$config->course_teachers_filter}");
                }
                if ($teacherrec = $DB->get_record('user', $params, 'id,username,firstname,lastname')) {
                    $course['teachers_enrol'][] = $teacherrec;
                } else {
                    mtrace("\tERROR: Want to enrol teacher $teacher but cannot because not found in database by $courseuserkey");
                }
            }
        }
    }

    if (!empty($options['verbose'])) {
        mtrace("Decoding editingteachers list {$courseinfo->editingteachers}");
    }
    $course['editingteachers_enrol'] = array();
    if (empty($courseinfo->editingteachers) ||
            preg_match($emptypattern, $courseinfo->editingteachers)) {
        if (!empty($options['verbose'])) {
            mtrace("\tEmpty editing teacher list");
        }
    } else {
        $teachers = explode($config->list_separator, $courseinfo->editingteachers);
        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {

                $teacher = trim($teacher);

                // Filter some unwanted parts.
                if (preg_match('/'.$config->course_teachers_filter.'/', $teacher, $matches)) {
                    $teacher = $matches[1];
                }
                $params = array($courseuserkey => $teacher);
                if (!empty($options['verbose'])) {
                    mtrace("\tGetting editing teacher record $teacher by $courseuserkey using filter {$config->course_teachers_filter}");
                }
                if ($teacherrec = $DB->get_record('user', $params, 'id,username,firstname,lastname')) {
                    $course['editingteachers_enrol'][] = $teacherrec;
                } else {
                    mtrace("\tERROR: Want to enrol editing teacher $teacher but cannot because not found in database by $courseuserkey");
                }
            }
        }
    }
}

/**
 * Post processes a course by running a moodlescript in the course context.
 * @use /local/moodlescript
 * @param object $course
 * @return false or an error report.
 */
function local_ent_installer_post_process_course($course) {
    global $CFG;
    static $loaded = false;

    if (!$loaded) {
        include_once($CFG->dirroot.'/local/moodlescript/lib.php');
        local_moodlescript_load_engine();
        $loaded = true;
    }

    $config = get_config('local_ent_installer');

    if (!empty($config->courses_postprocessing)) {

        // Building a global context.
        $globalcontext = new StdClass;
        $globalcontext->courseid = $course->id;
        $globalcontext->coursecatid = $course->category;
        $globalcontext->config = $config;

        // Make a postprocessing parser, parse postprocessing script and execute the resulting stack.
        $parser = new \local_moodlescript\engine\parser($config->courses_postprocessing);
        $stack = $parser->parse((array)$globalcontext);

        if ($parser->has_errors()) {
            if (function_exists('debug_trace')) {
                if ($CFG->debug = DEBUG_DEVELOPER) {
                    debug_trace("ENT Installer Postprocessing Parsed Trace : ".$parser->print_trace());
                }
                debug_trace("ENT Installer Parsed stack errors : ".$parser->print_errors());
            }
            $report = $parser->print_errors();
            $report .= "\n".$parser->print_stack();
            return $report;
        }

        if (function_exists('debug_trace')) {
            debug_trace("EN Installer Parsed Stack :\n ".$parser->print_stack());
        }

        $result = $stack->check((array)$globalcontext);
        if ($stack->has_errors()) {
            if (function_exists('debug_trace')) {
                if ($CFG->debug = DEBUG_DEVELOPER) {
                    debug_trace("ENT Instaler Check warnings : ".$stack->print_log('warnings'));
                    debug_trace("ENT Installer Check errors : ".$stack->print_log('errors'));
                }
            }
            return $stack->print_log('errors');
        }

        $result = $stack->execute((array)$globalcontext);

        if (function_exists('debug_trace')) {
            if ($stack->has_errors()) {
                // If the engine is robust enough. There should be not...
                debug_trace("ENT Installer Stack errors : ".$stack->print_log('warnings'));
                debug_trace("ENT Installer Stack errors : ".$stack->print_log('errors'));
            }
        }
        if (function_exists('debug_trace')) {
            if ($CFG->debug = DEBUG_DEVELOPER) {
                debug_trace("ENT Installer Stack execution log : ".$stack->print_log('log'));
            }
        }
    }
}

function local_ent_installer_process_students(&$courseinfo, $courserec, &$options) {
    global $DB;

    if (!is_object($courserec)) {
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        die;
    }

    $config = get_config('local_ent_installer');

    mtrace('Processing students using '.$config->roleassign_enrol_method.'. (See role assign enrol method setting).');

    $enrolplugin = enrol_get_plugin($config->roleassign_enrol_method);

    // Get actual students enrolments in manual plugin.
    $params = array('courseid' => $courserec->id, 'enrol' => $config->roleassign_enrol_method, 'status' => ENROL_INSTANCE_ENABLED);
    $enrols = $DB->get_records('enrol', $params);
    $enrol = array_shift($enrols); // Take first available.

    $oldenrols = array();
    if ($enrol) {
        // A sync enrol may not having been created yet.
        $params = array('enrolid' => $enrol->id, 'status' => ENROL_INSTANCE_ENABLED);
        $oldenrols = $DB->get_records('user_enrolments', $params);
    }

    if (empty($courseinfo->students) && empty($oldenrols)) {
        mtrace("\tNothing to do.");
        mtrace('');
        // Nothing to do.
        return;
    }

    $oldusers = array();
    foreach ($oldenrols as $ueid => $ue) {
        // Should be only one.
        $oldusers[$ue->userid] = $ue;
    }

    $studentrole = $DB->get_record('role', array('shortname' => 'student'));

    if (!empty($courseinfo->students)) {
        foreach ($courseinfo->students as $uid => $user) {
            if (empty($options['simulate'])) {
                if ($config->roleassign_enrol_method == 'sync') {
                    enrol_sync_plugin::static_enrol_user($courserec, $uid, $studentrole->id);
                } else {
                    $enrolplugin->enrol_user($enrol, $uid, $studentrole->id, time(), 0);
                }
                mtrace("\tUser {$user->username} ($user->idnumber) enrolled in {$courserec->shortname} using {$config->roleassign_enrol_method} ");
            } else {
                mtrace("\t[SIMULATION] enroling user {$user->username} ($user->idnumber) in {$courserec->shortname} using {$config->roleassign_enrol_method} ");
            }
            if (array_key_exists($uid, $oldusers)) {
                unset($oldusers[$uid]);
            }
        }
    }

    if (!empty($oldusers)) {
        // Some users to remove ?

        foreach ($oldusers as $uid => $ue) {
            $username = $DB->get_field('user', 'username', array('id' => $uid));
            $idnumber = $DB->get_field('user', 'idnumber', array('id' => $uid));
            if (empty($options['simulate'])) {
                if ($config->roleassign_enrol_method == 'sync') {
                    enrol_sync_plugin::static_unenrol_user($courserec, $uid);
                } else {
                    $enrolplugin->unenrol_user($enrol, $uid);
                }
                mtrace("\tUser {$username} ($idnumber) unenrolled from {$courserec->shortname} using {$config->roleassign_enrol_method} ");
            } else {
                mtrace("\t[SIMULATION] unenrolling user {$username} ($idnumber) in {$courserec->shortname} using {$config->roleassign_enrol_method} ");
            }
        }
    }
    mtrace('');
}

/**
 * Enrols teachers given a 'teachers_enrol' array in the course array descriptor.
 * @param int $courseid
 * @param array $course a course descriptor retreived from ldap. (do not contain any course id)
 * @param array $option behaviour options
 */
function local_ent_installer_process_teachers($courseid, $course, $options) {
    global $DB, $CFG;

    if (!empty($options['verbose'])) {
        mtrace("Processing teachers ");
    }
    if (!isset($course['teachers_enrol']) ||
            (count($course['teachers_enrol']) == 0)) {
        if (!empty($options['verbose'])) {
            mtrace("\tNo teachers in records.");
        }
        return;
    }

    $config = get_config('local_ent_installer');

    mtrace('Processing teachers using '.$config->course_enrol_method.'. (See role assign enrol method setting).');

    // Get enrol plugin.
    $enrolplugin = null;
    if (!empty($config->course_enrol_method)) {
        $enrolplugin = enrol_get_plugin($config->course_enrol_method);
    }

    if (!empty($enrolplugin)) {
        mtrace("Enrol plugin : $config->course_enrol_method");
    } else {
        mtrace("Missing enrol plugin in ent_installer config. Only assign roles (Not yet implemented)\n");
        return;
    }

    $teachersyncrole = 'teacher';
    if (!empty($CFG->ent_installer_teacher_role)) {
        $teachersyncrole = $CFG->ent_installer_teacher_role;
    }
    $roleid = $DB->get_field('role', 'id', array('shortname' => $teachersyncrole));

    $coursecontext = context_course::instance($courseid);
    $oldenrolledusers = local_ent_installer_get_enrolled_with_role($roleid, $coursecontext, $config->course_enrol_method);

    if (empty($options['simulate'])) {
        // Get enrol instance.
        if ($config->course_enrol_method != 'sync') {
            $params = array('enrol' => $config->course_enrol_method,
                            'courseid' => $courseid,
                            'status' => ENROL_INSTANCE_ENABLED);
            if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                mtrace("ERROR: No enrol instance found in course {$course['shortname']} for enrol {$config->course_enrol_method}\n");
            } else {
                $enrol = reset($enrols);
            }
        }

        // Any teachers specified?
        foreach (array_values($course['teachers_enrol']) as $teacher) {
            if (!array_key_exists($teacher->id, $oldenrolledusers)) {
                if ($config->course_enrol_method == 'sync') {
                    enrol_sync_plugin::static_enrol_user($courseid, $teacher->id, $roleid);
                } else {
                    $enrolplugin->enrol_user($enrol, $teacher->id, $roleid, time(), 0);
                }
                mtrace("Teacher {$teacher->username} enrolled on course as {$teachersyncrole}.");
            } else {
                mtrace("Teacher {$teacher->username} keeped in course as {$teachersyncrole}.");
                unset($oldenrolledusers[$teacher->id]);
            }
        }
    } else {
        mtrace('SIMULATION: Registering '.count($course['teachers_enrol']).' teachers:');
        foreach (array_values($course['teachers_enrol']) as $teacher) {
            if (!array_key_exists($teacher->id, $oldenrolledusers)) {
                mtrace("SIMULATION:\tTeacher {$teacher->username} enrolled on course as {$teachersyncrole}.");
            } else {
                mtrace("SIMULATION:\tTeacher {$teacher->username} keeped in course as {$teachersyncrole}.");
                unset($oldenrolledusers[$teacher->id]);
            }
        }
    }

    local_ent_installer_remove_old_users($oldenrolledusers, $enrolplugin, $courseid, $course, $config);
}

/**
 * Enrols editing teachers given a 'editingteachers_enrol' array in the course array descriptor.
 * @param int $courseid
 * @param array $course a course descriptor retreived from ldap. (do not contain any course id)
 * @param array $option behaviour options
 */
function local_ent_installer_process_editingteachers($courseid, $course, $options) {
    global $DB, $CFG;

    if (!empty($options['verbose'])) {
        mtrace("Processing editingteachers ");
    }
    if (!isset($course['editingteachers_enrol']) || (count($course['editingteachers_enrol']) == 0)) {
        if (!empty($options['verbose'])) {
            mtrace("\tNo editing teachers in records.");
        }
        return;
    }

    $config = get_config('local_ent_installer');

    mtrace("\tProcessing editing teachers using ".$config->course_enrol_method.'. (See role assign enrol method setting).');

    // Same with editing teachers.
    // Process eventual teachers bindings after course creation.

    // Get enrol plugin.
    $enrolplugin = null;
    if (!empty($config->course_enrol_method)) {
        $enrolplugin = enrol_get_plugin($config->course_enrol_method);
    }

    if (!empty($enrolplugin)) {
        mtrace("\tEnrol plugin : $config->course_enrol_method");
    } else {
        mtrace("\tMissing enrol plugin in ent_installer config. Only assign roles. (Not yet implemented).\n");
        return;
    }

    $editingteachersyncrole = 'editingteacher';
    if (!empty($CFG->ent_installer_editingteacher_role)) {
        $editingteachersyncrole = $CFG->ent_installer_editingteacher_role;
    }
    $roleid = $DB->get_field('role', 'id', array('shortname' => $editingteachersyncrole));

    $coursecontext = context_course::instance($courseid);
    $oldenrolledusers = local_ent_installer_get_enrolled_with_role($roleid, $coursecontext, $config->course_enrol_method);

    if (empty($options['simulate'])) {
        // Get enrol instance.
        if ($config->course_enrol_method != 'sync') {
            $params = array('enrol' => $config->course_enrol_method,
                            'courseid' => $courseid,
                            'status' => ENROL_INSTANCE_ENABLED);
            if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                mtrace("\tERROR: No enrol instance found in course {$course['shortname']} for enrol method $config->course_enrol_method");
            } else {
                $enrol = reset($enrols);
            }
        }

        // Any teachers specified?
        foreach (array_values($course['editingteachers_enrol']) as $teacher) {
            if (!array_key_exists($teacher->id, $oldenrolledusers)) {
                if ($config->course_enrol_method == 'sync') {
                    enrol_sync_plugin::static_enrol_user($courseid, $teacher->id, $roleid);
                } else {
                    $enrolplugin->enrol_user($enrol, $teacher->id, $roleid, time(), 0);
                }
                mtrace("\tTeacher {$teacher->username} enrolled on course {$course['shortname']} as $editingteachersyncrole.");
            } else {
                mtrace("\tTeacher {$teacher->username} keeped in course {$course['shortname']} as $editingteachersyncrole.");
                unset($oldenrolledusers[$teacher->id]);
            }
        }
    } else {
        mtrace('\tSIMULATION: Registering '.count($course['editingteachers_enrol']).' editing teachers:');
        foreach (array_values($course['editingteachers_enrol']) as $teacher) {
            if (!array_key_exists($teacher->id, $oldenrolledusers)) {
                mtrace("\tSIMULATION:\tTeacher {$teacher->username} enrolled on course {$course['shortname']} as $editingteachersyncrole.");
            } else {
                mtrace("\tSIMULATION:\tTeacher {$teacher->username} keeped in course {$course['shortname']} as $editingteachersyncrole.");
                unset($oldenrolledusers[$teacher->id]);
            }
        }
    }

    local_ent_installer_remove_old_users($oldenrolledusers, $enrolplugin, $courseid, $course, $config);
}

/**
 * get a list of enrol records for users enrolled with this role and this plugin type.
 */
function local_ent_installer_get_enrolled_with_role($roleid, $context, $enrolmethod) {
    global $DB;

    $sql = "
        SELECT DISTINCT
            ue.userid as uid,
            e.*
        FROM
            {user_enrolments} ue,
            {enrol} e,
            {context} ctx,
            {role_assignments} ra
        WHERE
            ue.status = 0 AND
            ue.enrolid = e.id AND
            e.status = 0 AND
            e.enrol = ? AND
            e.courseid = ctx.instanceid AND
            ctx.contextlevel = 50 AND
            ctx.id = ra.contextid AND
            ra.userid = ue.userid AND
            ra.contextid = ? AND
            ra.roleid = ?
    ";

    $params = array(trim($enrolmethod), $context->id, $roleid);

    $enrols = $DB->get_records_sql($sql, $params);

    return $enrols;
}

function local_ent_installer_remove_old_users($oldenrolledusers, $enrolplugin, $courseid, $course, $config) {
    global $DB;

    // If there are old enrolled users remaining, remove them.
    if (!empty($oldenrolledusers)) {
        mtrace('Old accounts to remove.');
        foreach ($oldenrolledusers as $uid => $enrol) {
            $username = $DB->get_field('user', 'username', array('id' => $uid));
            $idnumber = $DB->get_field('user', 'idnumber', array('id' => $uid));
            if (empty($options['simulate'])) {
                if ($config->course_enrol_method == 'sync') {
                    enrol_sync_plugin::static_unenrol_user($courseid, $uid);
                } else {
                    $enrolplugin->unenrol_user($enrol, $uid);
                }
                mtrace("User {$username} ($idnumber) unenrolled from {$course['shortname']} using {$config->course_enrol_method} ");
            } else {
                mtrace("[SIMULATION] unenrolling user {$username} ($idnumber) from {$course['shortname']} using {$config->course_enrol_method} ");
            }
        }
    }
}

function local_ent_installer_process_cohorts(&$courseinfo, $courserec, &$options) {
    global $DB;

    mtrace('Processing cohorts');
    $config = get_config('local_ent_installer');

    // Get already enrolled cohorts (active enrol methods only).
    $params = array('courseid' => $courserec->id, 'enrol' => 'cohort', 'status' => ENROL_INSTANCE_ENABLED);
    $oldrecs = $DB->get_records('enrol', $params);

    if (empty($courseinfo->cohorts) && empty($oldrecs)) {
        mtrace("\tNothing to do.");
        mtrace('');
        // Nothing to do.
        return;
    }

    $cohortenrol = enrol_get_plugin('cohort');
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));

    $oldcohortenrols = array();
    if ($oldrecs) {
        // Extract cohort ids and make a list.
        foreach ($oldrecs as $enrolid => $enrol) {
            // Should be only one for the same course.
            $cohortcomponent = $DB->get_field('cohort', 'component', array('id' => $enrol->customint1));
            if ($cohortcomponent == 'local_ent_installer') {
                $oldcohortenrols[$enrol->customint1] = $enrolid;
            }
        }
    }

    if (!empty($courseinfo->cohorts)) {
        foreach ($courseinfo->cohorts as $chid => $cohort) {

            if (empty($options['simulate'])) {
                $fields = array();
                $fields['courseid'] = $courserec->id;
                $fields['roleid'] = $studentrole->id;
                $fields['customint1'] = $chid;
                if (!$oldenrol = $DB->get_record('enrol', $fields)) {
                    // Add new instance.
                    $fields['status'] = ENROL_INSTANCE_ENABLED;
                    $fields['customint2'] = COHORT_CREATE_GROUP;
                    $cohortenrol->add_instance($courserec, $fields);
                } else {
                    if ($oldenrol->status != ENROL_INSTANCE_ENABLED) {
                        // Reactivate disabled instance.
                        $oldenrol->status = ENROL_INSTANCE_ENABLED;
                        $DB->update_record('enrol', $oldenrol);
                    }
                }

                if (array_key_exists($chid, $oldcohortenrols)) {
                    unset($oldcohortenrols[$chid]);
                }

                mtrace("\tCohort {$cohort->name} enrolled in {$courserec->shortname} ");
            } else {
                if (array_key_exists($chid, $oldcohortenrols)) {
                    unset($oldcohortenrols[$chid]);
                }
                mtrace("\t[SIMULATION]: Enroling cohort {$cohort->name} in {$courserec->shortname} ");
            }
        }
    }

    if (!empty($oldcohortenrols)) {
        // There are some old cohort enrols that have gone away. Disable them.
        foreach ($oldcohortenrols as $chid => $enrolid) {
            $cohort = $DB->get_record('cohort', array('id' => $chid));
            if (empty($options['simulate'])) {
                if (empty($config->course_hard_cohort_unenrol)) {
                    $DB->set_field('enrol', 'status', 1, array('id' => $enrolid));
                    mtrace("Soft unenrolling cohort {$cohort->name} from {$courserec->shortname}");
                } else {
                    $plugin = enrol_get_plugin('cohort');
                    $cohortenrolinstance = $DB->get_record('enrol', array('id' => $enrolid));
                    $plugin->delete_instance($cohortenrolinstance);
                    mtrace("Hard unenrolling cohort {$cohort->name} from {$courserec->shortname}");
                }
            } else {
                if (empty($config->course_hard_cohort_unenrol)) {
                    mtrace("SIMULATION: Soft unenrolling cohort {$cohort->name} from {$courserec->shortname}");
                } else {
                    mtrace("SIMULATION: Hard unenrolling cohort {$cohort->name} from {$courserec->shortname}");
                }
            }
        }
    }
    mtrace('');
}

/**
 * checks locally if a deployable/publishable backup is available
 * @param int $courseid the courseid where to locate a backup
 * @param string $filearea the filearea to consider
 * @return false or a stored_file object
 */
function local_ent_installer_locate_backup_file($courseid, $filearea) {

    $fs = get_file_storage();

    $coursecontext = context_course::instance($courseid);
    $files = $fs->get_area_files($coursecontext->id, 'backup', $filearea, 0, 'timecreated DESC', false);

    if (count($files) > 0) {
        return array_pop($files);
    }

    return false;
}
