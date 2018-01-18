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

require_once($CFG->dirroot.'/group/lib.php');

/**
 * Synchronizes groups by getting records from a group holding ldap context.
 * @param array $options an array of options
 */
function local_ent_installer_sync_groups($ldapauth, $options = array()) {
    global $DB;

    $config = get_config('local_ent_installer');

    $debughardlimit = '';
    if ($CFG->debug == DEBUG_DEVELOPER) {
        $debughardlimit = ' LIMIT 30 ';
        echo '<span style="font-size:2.5em">';
        mtrace('RUNNING WITH HARD LIMIT OF 30 Objets');
        echo '</span>';
        mtrace('Turn off the developper mode to process all records.');
    }

    mtrace('');
    $enable = get_config('local_ent_installer', 'sync_enable');
    if (!$enable) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    core_php_time_limit::raise(600);

    $ldapconnection = $ldapauth->ldap_connect();
    // Ensure an explicit limit, or some defaults may  cur some results.
    if ($CFG->debug == DEBUG_DEVELOPER) {
        ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 30);
    } else {
        ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 500000);
    }
    ldap_get_option($ldapconnection, LDAP_OPT_SIZELIMIT, $retvalue);

    mtrace("Ldap opened with sizelimit $retvalue");

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ', microtime());
    $starttick = (float)$sec + (float)$usec;

    mtrace(get_string('lastrun', 'local_ent_installer', userdate(@$config->last_sync_date_group)));

    // Define table user to be created.

    $table = new xmldb_table('tmp_extgroup');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('groupname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extgroup'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    if (!empty($config->use_groupings)) {
        $gptable = new xmldb_table('tmp_extgrouping');
        $gptable->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $gptable->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $gptable->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $gptable->add_field('groupingname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $gptable->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $gptable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extgrouping'));

        if ($dbman->table_exists($gptable)) {
            $dbman->drop_table($gptable);
        }
        $dbman->create_temp_table($gptable);
    }

    $contexts = explode(';', $config->group_contexts);
    list($institutionidlist, $institutionalias) = local_ent_installer_strip_alias($config->institution_id);
    $institutionids = explode(',', $institutionidlist);

    $ldappagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    if ($ldappagedresults) {
        mtrace("Paging results...\n");
    } else {
        mtrace("Paging not supported...\n");
    }

    $ldapcookie = '';

    $grouprecordfields = array($config->group_idnumber_attribute,
                               $config->group_course_attribute,
                               $config->group_name_attribute,
                               $config->group_grouping_attribute,
                               $config->group_membership_attribute,
                               'modifyTimestamp');

    $grouprecordattribs = array();
    foreach ($grouprecordfields as $field) {
        if (!empty($field) && !in_array($field, $grouprecordattribs)) {
            $grouprecordattribs[] = $field;
        }
    }

    // First fetch idnnumbers to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->group_selector_filter);

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
                    mtrace("ldapsearch $context, $filter for attributes ".implode(', ', $grouprecordattribs));
                    $ldapresult = ldap_search($ldapconnection, $context, $filter, $grouprecordattribs);
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for attributes ".implode(', ', $grouprecordattribs));
                    $ldapresult = ldap_list($ldapconnection, $context, $filter, $grouprecordattribs);
                }
                if (!$ldapresult) {
                    continue;
                }
                if ($ldappagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldapresult, $ldapcookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldapresult)) {
                    do {
                        $gidnumber = '';
                        $gname = '';

                        $value = ldap_get_values_len($ldapconnection, $entry, $config->group_idnumber_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->group_idnumber_filter.'/', $value, $matches)) {
                            $gidnumber = $matches[1];
                        } else {
                            mtrace("Empty GIDNumber.");
                        }

                        // Get course and final moodle course id.
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->group_course_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->group_course_filter.'/', $value, $matches)) {
                            $courseid = $matches[1];
                            if ($course = $DB->get_record('course', array($config->group_course_identifier => $courseid))) {
                                $gcourse = $course->id;
                            } else {
                                if ($options['verbose']) {
                                    mtrace("Group course not found by {$config->group_course_identifier} for identifier $courseid");
                                }
                                continue;
                            }
                        } else {
                            if ($options['verbose']) {
                                mtrace("Filter catched no value in {$config->group_course_attribute}");
                            }
                            continue;
                        }

                        $value = ldap_get_values_len($ldapconnection, $entry, $config->group_name_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (!empty($options['verbose'])) {
                            mtrace("Getting group name from {$config->group_name_attribute} with {$config->group_name_filter} ");
                        }
                        if (preg_match('/'.$config->group_name_filter.'/', $value, $matches)) {
                            $gname = $matches[1];
                        }
                        if (!empty($options['verbose'])) {
                            mtrace("Got $gname ");
                        }
                        if (empty($gname)) {
                            if (empty($gidnumber)) {
                                mtrace("Group identity error. No name nor idnumber found by matches. Ignoring\n");
                            } else {
                                if (!empty($options['verbose'])) {
                                    mtrace("Empty name, taking IDNumer as name.");
                                }
                                $gname = $gidnumber;
                            }
                        }

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        local_ent_installer_ldap_bulk_group_insert($gidnumber, $gcourse, $gname, $modify);
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                echo "\n";
                unset($ldapresult); // Free mem.
            } while ($ldappagedresults && !empty($ldapcookie));
        }
    }

    /*
     * If LDAP paged results were used, the current connection must be completely
     * closed and a new one created, to work without paged results from here on.
     */
    if ($ldappagedresults) {
        $ldapauth->ldap_close(true);
        $ldapconnection = $ldapauth->ldap_connect();
    }

    // Groups auto protection is done using a significant prefix to group name.
    $captureautogroups = '';
    $params = array();
    if (!empty($config->group_auto_name_prefix)) {
        if (empty($options['disableautogroupscheck'])) {
            $captureautogroups = "AND
                g.name LIKE ?";
            $params[] = $config->group_auto_name_prefix.'%';
        }
    }

    // Deleted groups.
    $sql = "
        SELECT
            g.id as gid,
            g.name,
            g.courseid as course
        FROM
            {groups} g
        LEFT JOIN
            {tmp_extgroup} tg
        ON
            CONCAT('".$config->group_auto_name_prefix."', tg.groupname) = g.name AND
            g.courseid = tg.course
        WHERE
            tg.groupname IS NULL
            $captureautogroups
    ";
    $deleted = $DB->get_records_sql($sql, $params);

    // New groups.
    $sql = "
        SELECT
            tg.id,
            tg.course,
            tg.groupname,
            tg.idnumber
        FROM
            {tmp_extgroup} tg
        LEFT JOIN
            {groups} g
        ON
            CONCAT('".$config->group_auto_name_prefix."', tg.groupname) = g.name AND
            g.courseid = tg.course
        WHERE
            g.name IS NULL
    ";
    $created = $DB->get_records_sql($sql);

    // Updated groups.
    $sql = "
        SELECT
            tg.id,
            tg.course,
            tg.groupname,
            tg.idnumber,
            g.id as gid
        FROM
            {groups} g,
            {tmp_extgroup} tg
        WHERE
            CONCAT('".$config->group_auto_name_prefix."', tg.groupname) = g.name AND
            g.courseid = tg.course
            $captureautogroups
    ";

    if (empty($options['force'])) {
        $sql .= "
            AND tg.lastmodified > ?
        ";
        $params[] = 0 + @$config->last_sync_date_group;
    }

    $updated = $DB->get_records_sql($sql, $params);

    if (empty($options['updateonly']) && empty($config->no_delete) && !empty($options['force'])) {
        mtrace("\n>> ".get_string('deletinggroups', 'local_ent_installer'));
        if ($deleted) {
            foreach ($deleted as $dl) {
                if (empty($options['simulate'])) {
                    if ($members = $DB->get_records('groups_members', array('groupid' => $dl->gid))) {
                        foreach ($members as $m) {
                            // This will trigger cascade events to get everything clean.
                            \groups_remove_member($dl->gid, $m->userid);
                        }
                    }
                    $DB->delete_records('groups', array('id' => $dl->gid));
                    mtrace(get_string('groupdeleted', 'local_ent_installer', $dl));
                } else {
                    mtrace('[SIMULATION] '.get_string('groupdeleted', 'local_ent_installer', $dl));
                }
            }
        }
    }
    if (!empty($config->no_delete)) {
        mtrace("Group deletion disabled by global configuration\n");
    }

    mtrace("\n>> ".get_string('updatinggroups', 'local_ent_installer'));
    if ($updated) {
        foreach ($updated as $up) {

            /*
             * Build an external pattern for identifying the group identity in ldap from
             * avaliable internal values. This is in case the ldap do not store a directly
             * mappable group ID or name.
             */
            $groupldapidentifier = $config->group_id_pattern;
            $groupldapidentifier = str_replace('%CID%', $up->course, $groupldapidentifier);
            $groupldapidentifier = str_replace('%GID%', $up->gid, $groupldapidentifier);
            $groupldapidentifier = str_replace('%GIDNUMBER%', $up->idnumber, $groupldapidentifier);
            $gname = $up->groupname;
            if (!empty($config->group_auto_name_prefix)) {
                $gname = str_replace($config->group_auto_name_prefix, '', $up->name); // Unprefix the group name.
            }
            $groupldapidentifier = str_replace('%GNAME%', $gname, $groupldapidentifier);
            $groupldapidentifier = str_replace('%ID%', $config->institution_id, $groupldapidentifier);

            if (!$groupinfo = local_ent_installer_get_groupinfo_asobj($ldapauth, $groupldapidentifier, $options)) {
                mtrace('ERROR : group info error');
                continue;
            }

            $oldrec = $DB->get_record('groups', array('id' => $up->gid));
            $oldrec->name = $gname;
            $oldrec->idnumber = $up->idnumber;
            $oldrec->description = $groupinfo->description;
            $oldrec->descriptionformat = FORMAT_HTML;
            $oldrec->timecreated = time();
            $oldrec->timemodified = time();
            if (empty($options['simulate'])) {
                $DB->update_record('groups', $oldrec);
                mtrace(get_string('groupupdated', 'local_ent_installer', $oldrec));
            } else {
                mtrace('[SIMULATION] '.get_string('groupupdated', 'local_ent_installer', $oldrec));
            }

            if (empty($options['skipmembership'])) {
                if (!empty($groupinfo->members)) {

                    if ($oldmembers = $DB->get_records_menu('groups_members', array('groupid' => $oldrec->id), 'userid', 'userid,userid')) {
                        $oldmemberids = array_keys($oldmembers);
                    } else {
                        $oldmemberids = array();
                    }

                    foreach ($groupinfo->members as $m) {
                        if (!in_array($m->id, $oldmemberids)) {
                            $e = new StdClass;
                            $e->username = $m->username;
                            $e->idnumber = $oldrec->idnumber;
                            $e->course = $oldrec->courseid;
                            if (empty($options['simulate'])) {
                                \groups_add_member($up->gid, $m->id);
                                mtrace(get_string('groupmemberadded', 'local_ent_installer', $e));
                            } else {
                                mtrace('[SIMULATION] '.get_string('groupmemberadded', 'local_ent_installer', $e));
                            }
                        } else {
                            unset($oldmembers[$m->id]);
                        }
                    }

                    // Need reset register of ids after all updated have been cleaned out.
                    $oldmemberids = array_keys($oldmembers);

                    // Remains only old ids in members. Remove them.
                    if (!empty($oldmemberids)) {
                        foreach ($oldmemberids as $userid) {
                            $e = new StdClass;
                            $e->username = $DB->get_field('user', 'username', array('id' => $userid));
                            $e->id = $userid;
                            $e->idnumber = $oldrec->idnumber;
                            $e->course = $oldrec->courseid;
                            if (empty($options['simulate'])) {
                                // This will trigger cascade events to get everything clean.
                                \groups_remove_member($up->gid, $userid);
                                mtrace(get_string('groupmemberremoved', 'local_ent_installer', $e));
                            } else {
                                mtrace('[SIMULATION] '.get_string('groupmemberremoved', 'local_ent_installer', $e));
                            }
                        }
                    }
                }
            } else {
                mtrace(get_string('skippingmembership', 'local_ent_installer'));
            }
        }
    }

    if (empty($options['updateonly'])) {
        mtrace("\n>> ".get_string('creatinggroups', 'local_ent_installer'));
        if ($created) {
            foreach ($created as $cr) {

                $course = $DB->get_record('course', array('id' => $cr->course));

                // Build an external pattern.
                $groupldapidentifier = $config->group_id_pattern;
                $groupldapidentifier = str_replace('%CID%', $cr->course, $groupldapidentifier);
                $groupldapidentifier = str_replace('%CSHORTNAME%', $course->shortname, $groupldapidentifier);
                $groupldapidentifier = str_replace('%CIDNUMBER%', $course->idnumber, $groupldapidentifier);
                $groupldapidentifier = str_replace('%GIDNUMBER%', $cr->idnumber, $groupldapidentifier);
                $groupldapidentifier = str_replace('%GNAME%', $cr->groupname, $groupldapidentifier);
                $groupldapidentifier = str_replace('%ID%', $config->institution_id, $groupldapidentifier);

                $groupinfo = local_ent_installer_get_groupinfo_asobj($ldapauth, $groupldapidentifier, $options);

                $gname = $cr->groupname;
                if (!empty($config->group_auto_name_prefix)) {
                    $gname = $config->group_auto_name_prefix.$cr->groupname;
                }

                $group = new StdClass;
                $group->name = $gname;
                $group->courseid = $cr->course;
                $group->description = $groupinfo->description;
                $group->idnumber = $config->group_auto_name_prefix.$cr->idnumber;
                $group->component = 'local_ent_installer';
                $group->timecreated = time();
                $group->timemodified = time();
                if (empty($options['simulate'])) {
                    $group->id = $DB->insert_record('groups', $group);
                    mtrace(get_string('groupcreated', 'local_ent_installer', $group));
                } else {
                    mtrace('[SIMULATION] '.get_string('groupcreated', 'local_ent_installer', $group));
                }

                if (empty($options['skipmembership'])) {
                    if (!empty($groupinfo->members)) {
                        foreach ($groupinfo->members as $m) {
                            $e = new StdClass;
                            $e->username = $m->username;
                            $e->idnumber = $group->idnumber;
                            $e->course = $cr->course;
                            if (empty($options['simulate'])) {
                                if (!empty($m->userid)) {
                                    \groups_add_member($group->id, $m->userid);
                                    mtrace(get_string('groupmemberadded', 'local_ent_installer', $e));
                                } else {
                                    mtrace(get_string('groupmissinguser', 'local_ent_installer', $e));
                                }
                            } else {
                                mtrace('[SIMULATION] '.get_string('groupmemberadded', 'local_ent_installer', $e));
                            }
                        }
                    }
                } else {
                    mtrace(get_string('skippingmembership', 'local_ent_installer'));
                }
            }
        }
    }

    mtrace("\n>> ".get_string('finaloperations', 'local_ent_installer'));

    // Prune empty groups.
    if (!empty($options['clearempty'])) {

        // Detect empty groups.
        $sql = "
            SELECT
                g.*
            FROM
                {groups} g
            LEFT JOIN
                {groups_members} gm
            ON
                gm.groupid = g.id
            WHERE
                gm.id IS NULL AND
                (g.idnumber <> '' OR g.idnumber IS NULL)
        ";

        $empties = $DB->get_records_sql($sql);

        foreach ($empties as $eg) {
            if (empty($options['simulate'])) {
                groups_delete_group($eg->id);
                if (!empty($options['verbose'])) {
                    mtrace("Clear empty group ($eg->id) $eg->name");
                }
            } else {
                mtrace("[SIMULATION] Clear empty group ($eg->id) $eg->name");
            }
        }
    }

    echo "Cleaning temp group tables out\n";
    // Clean temporary table.
    try {
        $dbman->drop_table($table);
    } catch (Exception $e) {
        assert(1);
    }

    if (!empty($config->use_groupings)) {
        echo "Cleaning grouping temp tables out\n";
        try {
            $dbman->drop_table($gptable);
        } catch (Exception $e) {
            assert(1);
        }
    }

    $ldapauth->ldap_close();
    set_config('last_sync_date_group', time(), 'local_ent_installer');
}

/**
 * Reads user information from ldap and returns it in array()
 *
 * Function should return all information available. If you are saving
 * this information to moodle user-table you should honor syncronization flags
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $groupidentifier group identifier (ldap side format)
 * @param array $options an array with CLI input options
 *
 * @return mixed array with no magic quotes or false on error
 */
function local_ent_installer_get_groupinfo($ldapauth, $groupidentifier, $options = array()) {
    global $DB;
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    // Load some cached static data.
    $groupattributes = array(
        'members' => core_text::strtolower($config->group_membership_attribute),
        'description' => $config->group_description_attribute,
    );

    $extgroupidentifier = core_text::convert($groupidentifier, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($groupdn = local_ent_installer_ldap_find_group_dn($ldapconnection, $extgroupidentifier))) {
        $ldapauth->ldap_close();
        if (!empty($options['verbose'])) {
            mtrace("Internal Error : Could not locate $extgroupidentifier ");
        }
        return false;
    }

    if ($options['verbose']) {
        mtrace("\nGetting $groupdn for ".implode(',', $groupattributes));
    }
    if (!$groupinforesult = ldap_read($ldapconnection, $groupdn, '(objectClass=*)', array_values($groupattributes))) {
        $ldapauth->ldap_close();
        return false;
    }

    $groupentry = ldap_get_entries_moodle($ldapconnection, $groupinforesult);
    if (empty($groupentry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($groupattributes as $key => $value) {
        // Value is an attribute name.
        $entry = array_change_key_case($groupentry[0], CASE_LOWER);

        if (!array_key_exists($value, $entry)) {
            if (!empty($options['verbose'])) {
                mtrace("Requested value $value but missing in record");
            }
            continue; // Wrong data mapping!
        }

        if ($key == 'members') {
            // Get the full array of values.
            $newval = array();
            $arity = array_pop($entry[$value]);
            if (!empty($options['verbose'])) {
                mtrace("Found $arity record...");
            }
            foreach ($entry[$value] as $newvalopt) {
                $newvalopt  = core_text::convert($newvalopt, $ldapauth->config->ldapencoding, 'utf-8');
                if (!empty($options['verbose'])) {
                    mtrace("Extracting from $newvalopt with {$config->group_membership_filter} ");
                }
                if (preg_match('/'.$config->group_membership_filter.'/', $newvalopt, $matches)) {
                    if ($config->group_user_identifier == 'username') {
                        $identifier = core_text::strtolower($matches[1]);
                    } else {
                        $identifier = $matches[1];
                    }
                    if (!empty($options['verbose'])) {
                        mtrace("Getting user record for {$config->group_user_identifier} = $identifier");
                    }
                    $params = array($config->group_user_identifier => $identifier, 'deleted' => 0);
                    $user = $DB->get_record('user', $params, 'id,username,firstname,lastname');
                    if (!$user) {
                        mtrace("Error : User record not found for $identifier. Skipping membership");
                        continue;
                    }
                    // Ensure we have same fields when scaning the tmp table as source.
                    $user->userid = $user->id;
                    $newval[] = $user;
                }
            }
            $result[$key] = $newval;
            $ldapauth->ldap_close();
        } else {
            if (is_array($entry[$value])) {
                $newval = core_text::convert($entry[$value][0], $ldapauth->config->ldapencoding, 'utf-8');
            } else {
                $newval = core_text::convert($entry[$value], $ldapauth->config->ldapencoding, 'utf-8');
            }
        }

        if (!empty($newval)) { // Favour ldap entries that are set.
            $ldapval = $newval;
        }

        if (isset($ldapval) && !is_null($ldapval)) {
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
 * @param string $extgroupdn the username to search (in external LDAP encoding, no db slashes)
 * @return mixed the user dn (external LDAP encoding) or false
 */
function local_ent_installer_ldap_find_group_dn($ldapconnection, $extgroupdn) {
    static $config;

    if (!isset($config)) {
        // We might be called a lot of times.
        $config = get_config('local_ent_installer');
    }

    $ldapcontexts = explode(';', $config->group_contexts);

    return ldap_find_groupdn($ldapconnection, $extgroupdn, $ldapcontexts, $config->group_objectclass,
                            $config->group_id_attribute);
}

/**
 * Search specified contexts for username and return the user dn like:
 * cn=username,ou=suborg,o=org
 *
 * @param mixed $ldapconnection a valid LDAP connection.
 * @param mixed $groupidentifier external group identifier (external LDAP encoding, no db slashes).
 * @param array $contexts contexts to look for the group.
 * @param string $objectclass objectlass of the groups (in LDAP filter syntax).
 * @param string $searchattrib the attribute use to look for the group.
 * @return mixed the group dn (external LDAP encoding, no db slashes) or false
 *
 */
function ldap_find_groupdn($ldapconnection, $groupidentifier, $contexts, $objectclass, $searchattrib) {
    if (empty($ldapconnection) || empty($groupidentifier) || empty($contexts) || empty($objectclass) || empty($searchattrib)) {
        return false;
    }

    // Default return value.
    $ldapgroupdn = false;

    // Get all contexts and look for first matching user.
    foreach ($contexts as $context) {
        $context = trim($context);
        if (empty($context)) {
            continue;
        }

        $ldapresult = @ldap_list($ldapconnection, $context,
                                  '(&'.$objectclass.'('.$searchattrib.'='.$groupidentifier.'))',
                                  array($searchattrib));

        if (!$ldapresult) {
            continue; // Not found in this context.
        }

        $entry = ldap_first_entry($ldapconnection, $ldapresult);
        if ($entry) {
            $ldapgroupdn = ldap_get_dn($ldapconnection, $entry);
            break;
        }
    }

    return $ldapgroupdn;
}

/**
 * Reads group information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $groupidentifier group (with system magic quotes)
 * @return mixed object or false on error
 */
function local_ent_installer_get_groupinfo_asobj($ldapauth, $groupidentifier, $options = array()) {

    $grouparr = local_ent_installer_get_groupinfo($ldapauth, $groupidentifier, $options);

    if ($grouparr == false) {
        return false; // Error or not found.
    }

    $grouparr = truncate_userinfo($grouparr);
    $group = new stdClass();
    foreach ($grouparr as $key => $value) {
        $group->{$key} = $value;
    }
    return $group;
}

/**
 * Bulk insert in SQL's temp table
 */
function local_ent_installer_ldap_bulk_group_insert($groupidentifier, $course, $groupname, $timemodified) {
    global $DB;

    if (!$DB->record_exists('tmp_extgroup', array('idnumber' => $groupidentifier))) {
        $params = array('idnumber' => $groupidentifier,
                        'course' => $course,
                        'groupname' => $groupname,
                        'lastmodified' => $timemodified);
        $DB->insert_record_raw('tmp_extgroup', $params, false, true);
    }
    echo '.';
}
