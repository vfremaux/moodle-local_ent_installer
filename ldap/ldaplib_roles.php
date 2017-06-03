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
 * Synchronizes roleassigns by getting records from a role holding ldap context.
 * @param array $options an array of options
 */
function local_ent_installer_sync_roleassigns($ldapauth, $options = array()) {
    global $DB;
    static $rolemapcache = array();

    $config = get_config('local_ent_installer');

    mtrace('');
    $enable = get_config('local_ent_installer', 'sync_enable');
    if (!$enable) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    $enable = get_config('local_ent_installer', 'sync_enable_roles');
    if (!$enable) {
        mtrace(get_string('syncrolesdisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    $ldapconnection = $ldapauth->ldap_connect();

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ',microtime());
    $starttick = (float)$sec + (float)$usec;

    if (!isset($config->last_roleassign_sync_date)) {
        $config->last_roleassign_sync_date = 0;
        set_config('last_roleassign_sync_date', 0, 'local_ent_installer');
    }

    mtrace(get_string('lastrun', 'local_ent_installer', userdate($config->last_roleassign_sync_date)));

    // Define table roleassigns to be created.

    $table = new xmldb_table('tmp_extroleassigns');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('context', XMLDB_TYPE_INT, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('user', XMLDB_TYPE_INT, '10', null, XMLDB_NOTNULL, null, null);

    // those fields will be used for avoiding querying the db again and again to get displayable info for reports.
    $table->add_field('userinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('roleinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('contextinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extroleassigns'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    $contexts = explode(';', $config->roleassigns_contexts);
    $institutionids = explode(',', $config->institution_id);

    $ldap_pagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    $ldapcookie = '';

    /*
     * A role assign first fetch an application profile record that may list members (users)
     * The application profile should contain enough data to tell about moodle context level, moodle context indentity
     * and the role required.
     */

    $rarecordfields = array($config->roleassign_id_attribute,
                            $config->roleassign_contextlevel_attribute,
                            $config->roleassign_context_attribute,
                            $config->roleassign_role_attribute,
                            'modifyTimestamp');

    // First fetch and map external records to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->roleassign_selector_filter);

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
                    mtrace("ldapsearch $context, $filter for ".implode(',', $rarecordfields));
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, $rarecordfields);
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".implode(',', $rarecordfields));
                    $ldap_result = ldap_list($ldapconnection, $context, $filter, $rarecordfields);
                }
                if (!$ldap_result) {
                    continue;
                }
                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldap_result, $ldapcookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                    do {
                        // Rebuild composite role assign key from attributes.
                        /*
                         * The composite key get info about role, application level and 
                         * an eventual context object id or reference.
                         *
                         * Any part is then filtered to extract clean value, then mapped
                         * to an value crossmapping (roles and contextlevels). 
                         */

                        // Get primary dn value.
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_id_attribute);
                        $dn = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

                        // Get role part.
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_role_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->roleassign_role_filter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }
                        $rolevalue = local_ent_installer_remap_role($value, $config);
                        if (array_key_exists($rolevalue, $rolemapcache)) {
                            $rolemapcache[$rolevalue] = $DB->get_record('role', array('shortname' => $rolevalue), 'id,shortname');
                        }
                        $roleid = $rolemapcache[$rolevalue]->id;

                        // Get context part.
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_contextlevel_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->roleassign_contextlevel_filter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }
                        $clevelvalue = local_ent_installer_remap_contextlevel($value, $config);

                        // Get context object id part.
                        $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_contextid_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
                        if (preg_match('/'.$config->roleassign_contextid_filter.'/', $value, $matches)) {
                            $value = $matches[1];
                        }
                        $cidvalue = $value;
                        $context = local_ent_installer_find_context($clevelvalue, $cidvalue);

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        if (!empty($options['force']) || $modify > $config->last_roleassign_sync_date) {

                            // Get members from the roleassign dn.
                            if (!$roleassigninfo = local_ent_installer_get_roleassigninfo_asobj($ldapauth, $dn, $options)) {
                                mtrace('ERROR : roleassign info error');
                                continue;
                            }

                            if (!empty($roleassigninfo->members)) {
                                $roleinfo = $rolemapcache[$rolevalue]->shortname;
                                $contextinfo = $clevelvalue.' '.$context->instanceid;
                                foreach ($roleassigninfo->members as $m) {
                                    // Store in temp table all composites plus full dn of the roleassign set.
                                    local_ent_installer_ldap_bulk_roleassign_insert($roleid, $clevelvalue, $context->id, $m->id,
                                                                                    $roleinfo,
                                                                                    $contextinfo,
                                                                                    $m->lastname.' '.$m->firstname);
                                }
                            }
                        }

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

    $captureautoroleassigns = '';
    if (empty($options['disableautoroleassignscheck'])) {
        $captureautoroleassigns = "AND
            ra.component = 'local_ent_installer'";
    }

    // Deleted roleassigns.
    $sql = "
        SELECT
            ra.id as raid
            ra.roleid
            ra.contextid,
            ra.userid
        FROM
            {roleassign} ra
        LEFT JOIN
            {tmp_extroleassign} tra
        ON
            ra.contextid = tra.context AND
            ra.roleid = tra.role AND
            ra.userid = tra.user
        WHERE
            tra.id IS NULL
            $captureautoroleassigns
    ";

    $deleted = $DB->get_records_sql($sql);

    // New roleassigns.
    $sql = "
        SELECT
            tra.role as roleid,
            tra.context as contextid,
            tra.user as userid
        FROM
            {roleassign} ra
        LEFT JOIN
            {tmp_extroleassign} tra
        ON
            ra.contextid = tra.context AND
            ra.roleid = tra.role AND
            ra.userid = tra.user
        WHERE
            ra.id IS NULL
    ";

    $created = $DB->get_records_sql($sql);

    mtrace("\n>> ".get_string('deletingroleassigns', 'local_ent_installer'));
    if ($deleted) {
        foreach ($deleted as $dl) {
            if (empty($options['simulate'])) {
                role_unassign($dl->roleid, $dl->userid, $dl->contextid, 'local_ent_installer');
                mtrace(get_string('roleunassigned', 'local_ent_installer', $roleassign));
            } else {
                mtrace('[SIMULATION] '.get_string('roleunassigned', 'local_ent_installer', $roleassign));
            }
        }
    }

    mtrace("\n>> ".get_string('creatingroleassigns', 'local_ent_installer'));
    if ($created) {
        foreach ($created as $cr) {
            if (empty($options['simulate'])) {
                role_assign($cr->role, $cr->user, $cr->context, 'local_ent_installer');
                mtrace(get_string('roleassigned', 'local_ent_installer', $roleassign));
            } else {
                mtrace('[SIMULATION] '.get_string('roleassigned', 'local_ent_installer', $roleassign));
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

    set_config('last_roleassign_sync_date', time(), 'local_ent_installer');

}

/**
 * Reads user information from ldap and returns it in array()
 *
 * Function should return all information available. If you are saving
 * this information to moodle user-table you should honor syncronization flags
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $roleassignidentifier roleassign identifier (ldap side format)
 * @param array $options an array with CLI input options
 *
 * @return mixed array with no magic quotes or false on error
 */
function local_ent_installer_get_roleassigninfo($ldapauth, $dn, $options = array()) {
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
            'members' => $config->roleassign_membership_attribute
        );
    }

    $extdn = core_text::convert($dn, 'utf-8', $ldapauth->config->ldapencoding);

    if ($options['verbose']) {
        mtrace("Getting $roleassign_dn for ".implode(',', $searchattribs));
    }
    if (!$roleassign_info_result = ldap_read($ldapconnection, $extdn, '(objectClass=*)', $entattributes)) {
        $ldapauth->ldap_close();
        return false;
    }

    $roleassign_entry = ldap_get_entries_moodle($ldapconnection, $roleassign_info_result);
    if (empty($roleassign_entry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($attrmap as $key => $value) {
        // Should only fetch members here. Naything else has no use yet.

        // Value is an attribute name.
        $entry = array_change_key_case($roleassign_entry[0], CASE_LOWER);

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
                    mtrace("Extracting from $newvalopt with {$config->roleassign_membership_filter} ");
                }
                if (preg_match('/'.$config->roleassign_membership_filter.'/', $newvalopt, $matches)) {
                    // Exclude potential arity count that comes at end of multivalued entries.
                    $identifier = core_text::strtolower($matches[1]);
                    if (!empty($options['verbose'])) {
                        mtrace("Getting user record for {$config->roleassign_user_key} = $identifier");
                    }
                    $user = $DB->get_record('user', array($config->roleassign_user_key => $identifier), 'id,username,firstname,lastname');
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
        }
    }

    $ldapauth->ldap_close();
    return $result;
}

/**
 * Reads roleassign information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $roleassignidentifier roleassign (with system magic quotes)
 * @return mixed object or false on error
 */
function local_ent_installer_get_roleassigninfo_asobj($ldapauth, $dn, $options = array()) {

    $ra_array = local_ent_installer_get_roleassigninfo($ldapauth, $dn, $options);

    if ($ra_array == false) {
        return false; //error or not found
    }

    $ra_array = truncate_userinfo($ra_array);
    $ra = new stdClass();
    foreach ($ra_array as $key => $value) {
        $ra->{$key} = $value;
    }
    return $ra;
}

/**
 * Bulk insert in SQL's temp table
 */
function local_ent_installer_ldap_bulk_roleassign_insert($roleid, $contextid, $userid, $roleinfo, $contextinfo, $userinfo) {
    global $DB;

    $params = array('role' => $roleid, 'contextid' => $contextid, 'user' => $userid);
    if (!$DB->record_exists('tmp_extroleassign', $params)) {
        $params = array('role' => $roleid,
                        'contextid' => $contextid,
                        'user' => $userid,
                        'roleinfo' => shorten_text($roleinfo, 50),
                        'contextinfo' => shorten_text($contextinfo, 50),
                        'userinfo' => shorten_text($userinfo, 50));
        $DB->insert_record_raw('tmp_extroleassign', $params, false, true);
    }
    echo '.';
}

/**
 * Returns remapped values of role ldap values.
 * @param string $input trimmed value (no side spaces admitted).
 * @return a trimmed mapped output value (should be role shortname).
 */
function local_ent_installer_remap_role($input) {
    static $map;

    if (!isset($map)) {
        $mapconf = get_config('local_ent_installer', 'roleassign_role_mappîng');
        $pairs = explode("\n", $mapconf);
        foreach ($pairs as $p) {
            list($in, $out) = explode('=>', $p);
            $map[trim($in)] = trim($out);
        }
    }

    if (array_key_exists($input, $map)) {
        return $map[$input];
    } else {
        return $input;
    }
}

/**
 * Returns remapped values of role ldap values.
 * @param string $input trimmed value (no side spaces admitted).
 * @return a trimmed mapped output value (should be role shortname).
 */
function local_ent_installer_remap_contextlevel($input) {
    static $map;

    if (!isset($map)) {
        $mapconf = get_config('local_ent_installer', 'roleassign_contextlevel_mapping');
        $pairs = explode("\n", $mapconf);
        foreach ($pairs as $p) {
            list($in, $out) = explode('=>', $p);
            $map[trim($in)] = trim($out);
        }
    }

    if (array_key_exists($input, $map)) {
        return $map[$input];
    } else {
        return $input;
    }
}

function local_ent_installer_find_context($clevelvalue, $cidvalue) {
    static $contextcache = array();
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    if (!isset($contextcache[$clevelvalue])) {

        $contextcache[$clevelvalue] = array();

        if (!array_key_exists($cidvalue, $contextcache[$clevelvalue])) {
            switch ($clevelvalue) {
                case 'system':
                        $contextcache[$clevelvalue][$cidvalue] = context_system::instance()->id;
                    break;

                case 'coursecat':
                    if (!$objid = $DB->get_field('course_categories', 'id', array($config->roleassign_coursecat_key => $cidvalue))) {
                        return false;
                    }
                    $contextcache[$clevelvalue][$cidvalue] = context_coursecat::instance($objid)->id();
                    break;

                case 'course':
                    if (!$objid = $DB->get_field('course', 'id', array($config->roleassign_course_key => $cidvalue))) {
                        return false;
                    }
                    $contextcache[$clevelvalue][$cidvalue] = context_course::instance($objid)->id();
                    break;

                case 'module':
                    if (!$objid = $DB->get_field('course_modules', 'id', array($config->roleassign_module_key => $cidvalue))) {
                        return false;
                    }
                    $contextcache[$clevelvalue][$cidvalue] = context_module::instance($objid)->id();
                    break;

                case 'block':
                    if (!$objid = $DB->get_field('block_instances', 'id', array($config->roleassign_block_key => $cidvalue))) {
                        return false;
                    }
                    $contextcache[$clevelvalue][$cidvalue] = context_block::instance($objid)->id();
                    break;

                case 'user':
                    if (!$objid = $DB->get_field('user', 'id', array($config->roleassign_usertarget_key => $cidvalue))) {
                        return false;
                    }
                    $contextcache[$clevelvalue][$cidvalue] = context_user::instance($objid)->id();
                    break;
            }
        }
    }
}