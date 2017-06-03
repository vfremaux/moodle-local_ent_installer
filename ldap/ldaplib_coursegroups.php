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
 * Synchronizes groups by getting records from a group holding ldap context.
 * @param array $options an array of options
 */
function local_ent_installer_sync_groups($ldapauth, $options = array()) {
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

    if (!isset($config->last_group_sync_date)) {
        $config->last_group_sync_date = 0;
        set_config('lastrun', 0, 'local_ent_installer');
    }

    mtrace(get_string('lastrun', 'local_ent_installer', userdate($config->last_group_sync_date)));

    // Define table user to be created.

    $table = new xmldb_table('tmp_extgroup');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('groupname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extgroup'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    if ($config->use_groupings) {
        $gptable = new xmldb_table('tmp_extgrouping');
        $gptable->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $gptable->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
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
    $institutionids = explode(',', $config->institution_id);

    $ldap_pagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    $ldapcookie = '';

    $grouprecordfields = array($config->group_idnumber_attribute,
                               $config->group_name_attribute,
                               $config->group_grouping_attribute,
                               $config->group_description_attribute,
                               $config->group_membership_attribute,
                               'modifyTimestamp');

    // First fetch idnnumbers to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->group_selector_filter);

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
                    mtrace("ldapsearch $context, $filter for ".$config->group_idnumber_attribute);
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, $config->group_idnumber_attribute, 'modifyTimestamp');
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".$config->group_idnumber_attribute);
                    $ldap_result = ldap_list($ldapconnection, $context, $filter, array($config->group_idnumber_attribute, 'modifyTimestamp'));
                }
                if (!$ldap_result) {
                    continue;
                }
                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldap_result, $ldapcookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                    do {
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->group_idnumber_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->group_idnumber_filter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        local_ent_installer_ldap_bulk_group_insert($value, $modify);
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

    // Groups auto protection is done using a significant prefix to group name.
    $captureautogroups = '';
    $params = array();
    if (!empty($config->group_auto_name_prefix)) {
        if (empty($options['disableautogroupscheck'])) {
            $captureautogroups = "AND
                g.name LIKE ?";
            $params[] = $config->group_auto_name_prefix;
        }
    }

    // Deleted groups.
    $sql = "
        SELECT
            g.name,
            g.course,
            g.id as gid
        FROM
            {group} g
        LEFT JOIN
            {tmp_extgroup} tg
        ON
            CONCAT('".$config->group_auto_name_prefix."', tg.name) = g.name AND
            g.course = tg.courseid
        WHERE
            tg.idnumber IS NULL
            $captureautogroups
    ";
    $deleted = $DB->get_records_sql($sql, $params);

    // New groups.
    $sql = "
        SELECT
            tg.groupname
        FROM
            {group} g
        LEFT JOIN
            {tmp_extgroup} tg
        ON
            CONCAT('".$config->group_auto_name_prefix."', tg.groupname) = g.name AND
            g.course = tg.courseid
        WHERE
            g.name IS NULL
    ";
    $created = $DB->get_records_sql($sql);

    // Updated groups.
    $sql = "
        SELECT
            tg.idnumber,
            g.id as gid
        FROM
            {group} g,
            {tmp_extgroup} tg
        WHERE
            CONCAT('".$config->group_auto_name_prefix."', tg.groupname) = g.name AND
            g.course = tg.courseid
            $captureautogroups AND
            tc.lastmodified > ? 
    ";

    if (empty($options['force'])) {
        $params[] = $config->last_group_sync_date;
    } else {
        $params[] = 0;
    }

    $updated = $DB->get_records_sql($sql, $params);

    mtrace("\n>> ".get_string('deletinggroups', 'local_ent_installer'));
    if ($deleted) {
        foreach ($deleted as $dl) {
            if (empty($options['simulate'])) {
                if ($members = $DB->get_records('groups_members', array('groupid' => $dl->gid))) {
                    foreach($members as $m) {
                        // This will trigger cascade events to get everything clean.
                        \group_remove_member($dl->gid, $m->userid);
                    }
                }
                $DB->delete_records('groups', array('id' => $dl->gid));
                mtrace(get_string('groupdeleted', 'local_ent_installer', $dl));
            } else {
                mtrace('[SIMULATION] '.get_string('groupdeleted', 'local_ent_installer', $dl));
            }
        }
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
            if (!empty($config->group_auto_name_prefix)) {
                $gname = str_replace($config->group_auto_name_prefix, '', $up->name); // Unprefix the group name.
            }
            $groupldapidentifier = str_replace('%GNAME%', $gname, $groupldapidentifier);
            $groupldapidentifier = str_replace('%ID%', $config->institution_id, $groupldapidentifier);

            if (!$groupinfo = local_ent_installer_get_groupinfo_asobj($ldapauth, $groupldapidentifier, $options)) {
                mtrace('ERROR : group info error');
                continue;
            }

            $oldrec = $DB->get_record('group', array('id' => $up->gid));
            $oldrec->name = @$config->group_auto_name_prefix.$groupinfo->name;
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

            if (!empty($groupinfo->members)) {

                if ($oldmembers = $DB->get_records_menu('group_members', array('groupid' => $oldrec->id), 'userid,userid')) {
                    $oldmemberids = array_keys($oldmembers);
                } else {
                    $oldmemberids = array();
                }

                foreach ($groupinfo->members as $m) {
                    if (!in_array($m->id, $oldmemberids)) {
                        $e = new StdClass;
                        $e->username = $m->username;
                        $e->idnumber = $oldrec->idnumber;
                        if (empty($options['simulate'])) {
                            \group_add_member($group->id, $m->userid);
                            mtrace(get_string('groupmemberadded', 'local_ent_installer', $e));
                        } else {
                            mtrace('[SIMULATION] '.get_string('groupmemberadded', 'local_ent_installer', $e));
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
                            \group_remove_member($dl->cid, $userid);
                            mtrace(get_string('groupmemberremoved', 'local_ent_installer', $e));
                        } else {
                            mtrace('[SIMULATION] '.get_string('groupmemberremoved', 'local_ent_installer', $e));
                        }
                    }
                }
            }
        }
    }

    mtrace("\n>> ".get_string('creatinggroups', 'local_ent_installer'));
    if ($created) {
        foreach ($created as $cr) {

            // Build an external pattern
            $groupldapidentifier = $config->group_id_pattern;
            $groupldapidentifier = str_replace('%CID%', $cr->course, $groupldapidentifier);
            $groupldapidentifier = str_replace('%GID%', $cr->idnumber, $groupldapidentifier);
            if (!empty($config->group_auto_name_prefix)) {
                $gname = str_replace($config->group_auto_name_prefix, '', $cr->name); // Unprefix the group name.
            }
            $groupldapidentifier = str_replace('%GNAME%', $gname, $groupldapidentifier);
            $groupldapidentifier = str_replace('%ID%', $config->institution_id, $groupldapidentifier);

            $groupinfo = local_ent_installer_get_groupinfo_asobj($ldapauth, $groupldapidentifier, $options);

            $group = new StdClass;
            $group->name = $groupinfo->name;
            $group->description = $groupinfo->description;
            $group->idnumber = $config->group_ix.$groupinfo->idnumber;
            $group->contextid = $systemcontext->id;
            $group->component = 'local_ent_installer';
            $group->timecreated = time();
            $group->timemodified = time();
            if (empty($options['simulate'])) {
                $group->id = $DB->insert_record('group', $group);
                mtrace(get_string('groupcreated', 'local_ent_installer', $group));
            } else {
                mtrace('[SIMULATION] '.get_string('groupcreated', 'local_ent_installer', $group));
            }

            if (!empty($groupinfo->members)) {
                foreach ($groupinfo->members as $m) {
                    $e = new StdClass;
                    $e->username = $DB->get_field('user', 'username', array('id' => $m->username));
                    $e->idnumber = $group->idnumber;
                    if (empty($options['simulate'])) {
                        \group_add_member($group->id, $m->userid);
                        mtrace(get_string('groupmemberadded', 'local_ent_installer', $e));
                    } else {
                        mtrace('[SIMULATION] '.get_string('groupmemberadded', 'local_ent_installer', $e));
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

    if ($config->use_groupings) {
        try {
            $dbman->drop_table($gptable);
        } catch (Exception $e) {
            assert(1);
        }
    }

    $ldapauth->ldap_close();

    set_config('last_group_sync_date', time(), 'local_ent_installer');

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
    static $entattributes;
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    // Load some cached static data.
    if (!isset($entattributes)) {
        // Aggregate additional ent specific attributes that hold interesting information.
        $entattributes = array(
            'name' => $config->group_name_attribute,
            'description' => $config->group_description_attribute,
            'idnumber' => $config->group_idnumber_attribute,
            'grouping' => $config->group_grouping_attribute,
            'members' => $config->group_membership_attribute
        );
    }

    $extgroupidentifier = core_text::convert($groupidentifier, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($group_dn = local_ent_installer_ldap_find_group_dn($ldapconnection, $extgroupidentifier))) {
        $ldapauth->ldap_close();
        if ($options['verbose']) {
            mtrace("Internal Error : Could not locate $extgroupidentifier ");
        }
        return false;
    }

    $searchattribs = array();
    foreach ($entattributes as $key => $value) {
        if (!in_array($value, $searchattribs)) {
            array_push($searchattribs, $value);
            // Add attributes to $attrmap so they are pulled down into final group object.
            $attrmap[$key] = strtolower($value);
        }
    }

    if ($options['verbose']) {
        mtrace("Getting $group_dn for ".implode(',', $searchattribs));
    }
    if (!$group_info_result = ldap_read($ldapconnection, $group_dn, '(objectClass=*)', $searchattribs)) {
        $ldapauth->ldap_close();
        return false;
    }

    $group_entry = ldap_get_entries_moodle($ldapconnection, $group_info_result);
    if (empty($group_entry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($attrmap as $key => $value) {
        // Value is an attribute name.
        $entry = array_change_key_case($group_entry[0], CASE_LOWER);

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
                    mtrace("Extracting from $newvalopt with {$config->group_membership_filter} ");
                }
                if (preg_match('/'.$config->group_membership_filter.'/', $newvalopt, $matches)) {
                    // Exclude potential arity count that comes at end of multivalued entries.
                    $identifier = core_text::strtolower($matches[1]);
                    if (!empty($options['verbose'])) {
                        mtrace("Getting user record for {$config->group_user_identifier} = $identifier");
                    }
                    $user = $DB->get_record('user', array($config->group_user_identifier => $identifier), 'id,username,firstname,lastname');
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
        $filterkey = 'group_'.$key.'_filter';
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
 * @param string $extgroupdn the username to search (in external LDAP encoding, no db slashes)
 * @return mixed the user dn (external LDAP encoding) or false
 */
function local_ent_installer_ldap_find_group_dn($ldapconnection, $extgroupdn) {
    static $config;

    if (!isset($config)) {
        // We might be called a lot of times.
        $config = get_config('local_ent_installer');
    }

    $ldap_contexts = explode(';', $config->group_contexts);

    return ldap_find_groupdn($ldapconnection, $extgroupdn, $ldap_contexts, $config->group_objectclass,
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
 * @param string $search_attrib the attribute use to look for the group.
 * @return mixed the group dn (external LDAP encoding, no db slashes) or false
 *
 */
function ldap_find_groupdn($ldapconnection, $groupidentifier, $contexts, $objectclass, $search_attrib) {
    if (empty($ldapconnection) || empty($groupidentifier) || empty($contexts) || empty($objectclass) || empty($search_attrib)) {
        return false;
    }

    // Default return value
    $ldap_group_dn = false;

    // Get all contexts and look for first matching user
    foreach ($contexts as $context) {
        $context = trim($context);
        if (empty($context)) {
            continue;
        }

        $ldap_result = @ldap_list($ldapconnection, $context,
                                  '(&'.$objectclass.'('.$search_attrib.'='.$groupidentifier.'))',
                                  array($search_attrib));

        if (!$ldap_result) {
            continue; // Not found in this context.
        }

        $entry = ldap_first_entry($ldapconnection, $ldap_result);
        if ($entry) {
            $ldap_group_dn = ldap_get_dn($ldapconnection, $entry);
            break;
        }
    }

    return $ldap_group_dn;
}

/**
 * Reads group information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $groupidentifier group (with system magic quotes)
 * @return mixed object or false on error
 */
function local_ent_installer_get_groupinfo_asobj($ldapauth, $groupidentifier, $options = array()) {

    $group_array = local_ent_installer_get_groupinfo($ldapauth, $groupidentifier, $options);

    if ($group_array == false) {
        return false; //error or not found
    }

    $group_array = truncate_userinfo($group_array);
    $group = new stdClass();
    foreach ($group_array as $key => $value) {
        $group->{$key} = $value;
    }
    return $group;
}

/**
 * Bulk insert in SQL's temp table
 */
function local_ent_installer_ldap_bulk_group_insert($groupidentifier, $timemodified) {
    global $DB;

    if (!$DB->record_exists('tmp_extgroup', array('idnumber' => $groupidentifier))) {
        $params = array('idnumber' => $groupidentifier, 'lastmodified' => $timemodified);
        $DB->insert_record_raw('tmp_extgroup', $params, false, true);
    }
    echo '.';
}