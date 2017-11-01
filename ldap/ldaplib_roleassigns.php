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
    global $DB, $CFG;
    static $rolemapcache = array();

    $config = get_config('local_ent_installer');

    $debughardlimit = '';
    if ($CFG->debug == DEBUG_DEVELOPER) {
        $debughardlimit = ' LIMIT 30 ';
        echo '<span style="font-size:2.5em">';
        mtrace('RUNNING WITH HARD LIMIT OF 30 OBJECTS');
        echo '</span>';
        mtrace('Turn off the developper mode to process all records.');
    }

    $enrolplugin = null;
    if (!empty($config->roleassign_enrol_method)) {
        $enrolplugin = enrol_get_plugin($config->roleassign_enrol_method);
    }

     if (!empty($enrolplugin)) {
        mtrace("Enrol plugin : $config->roleassign_enrol_method");
    } else {
        mtrace("No enrol plugin in config. Only assign roles\n");
    }
    if (empty($config->sync_enable)) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    if (empty($config->sync_roleassigns_enable)) {
        mtrace(get_string('syncroleassignsdisabled', 'local_ent_installer'));
        return;
    }

    $systemcontext = context_system::instance();

    core_php_time_limit::raise(600);

    $ldapconnection = $ldapauth->ldap_connect();
    // Ensure an explicit limit, or some defaults may  cut some results.
    if ($CFG->debug == DEBUG_DEVELOPER) {
        ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 30);
    } else {
        ldap_set_option($ldapconnection, LDAP_OPT_SIZELIMIT, 500000);
    }
    ldap_get_option($ldapconnection, LDAP_OPT_SIZELIMIT, $retvalue);
    mtrace("Ldap opened with sizelimit $retvalue");

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ',microtime());
    $starttick = (float)$sec + (float)$usec;

    mtrace(get_string('lastrun', 'local_ent_installer', userdate(@$config->last_sync_date_roles)));

    // Define table roleassigns to be created.

    $table = new xmldb_table('tmp_extroleassigns');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('context', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    // those fields will be used for avoiding querying the db again and again to get displayable info for reports.
    $table->add_field('userinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('roleinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('contextinfo', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('contextlevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extroleassigns'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    $contexts = explode(';', $config->roleassign_contexts);

    list($institutionidlist, $institutionalias) = local_ent_installer_strip_alias($config->institution_id);
    $institutionids = explode(',', $institutionidlist);

    if (!empty($institutionalias) && !empty($config->roleassign_use_alias)) {
        /*
         * If we do NOT use the institution separate ids in the selector, then we assume all data can be fetched
         * using the institution alias. We replace the institutionids array with a single member array.
         *
         * The institution loop will run once. the filter selector should have no change and still use the %ID% place-holder.
         */
         $institutionids = array($institutionalias);
    }

    $ldap_pagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    $ldapcookie = '';

    /*
     * A role assign first fetch an application profile record that may list members (users)
     * The application profile should contain enough data to tell about moodle context level, moodle context indentity
     * and the role required.
     */

    $rarecordfields = array($config->roleassign_id_attribute, 'modifyTimestamp');
    if (!empty($config->roleassign_contextlevel_attribute)) {
        $rarecordfields[] = $config->roleassign_contextlevel_attribute;
    }
    if (!empty($config->roleassign_context_attribute)) {
        if (!in_array($config->roleassign_context_attribute, $rarecordfields)) {
            $rarecordfields[] = $config->roleassign_context_attribute;
        }
    }
    if (!in_array($config->roleassign_contextlevel_attribute, $rarecordfields)) {
        $rarecordfields[] = $config->roleassign_contextlevel_attribute;
    }
    if (!in_array($config->roleassign_role_attribute, $rarecordfields)) {
        $rarecordfields[] = $config->roleassign_role_attribute;
    }

    // First fetch and map external records to compare.
    foreach ($institutionids as $institutionid) {

        $filter = str_replace('%ID%', $institutionid, $config->roleassign_selector_filter);
        $filter = str_replace('%ALIAS%', $institutionalias, $filter);

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
                    if (!empty($options['verbose'])) {
                        mtrace("ldapsearch $context, $filter for ".implode(',', $rarecordfields));
                    }
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, $rarecordfields);
                } else {
                    // Search only in this context.
                    if (!empty($options['verbose'])) {
                        mtrace("ldaplist $context, $filter for ".implode(',', $rarecordfields));
                    }
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

                        // Get primary cn value.
                        $dn = ldap_get_dn($ldapconnection, $entry);
                        $dn = core_text::convert($dn, $ldapauth->config->ldapencoding, 'utf-8');

                        // Get role part in primary entity.
                        if ($config->roleassign_role_attribute != $config->roleassign_membership_attribute) {
                            $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_role_attribute);
                            $roleid = local_ent_installer_get_role_from_value($value, $ldapauth, $config);
                        }

                        // Get context part.
                        if ($config->roleassign_contextlevel_attribute != $config->roleassign_membership_attribute) {
                            $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_contextlevel_attribute);
                            $clevelvalue = local_ent_installer_get_clevel_from_value($value, $ldapauth, $config);
                        }

                        // Get context object id part.
                        /*
                         * the context id may be obtained indirectly giving a context instance identifier and a
                         * context level info.
                         * The context finder will match any instance at the appropriate level using the required
                         * identifying field, and gives back the context object required for the role assignation.
                         *
                         * If the context attribute is not defined, then only system context assignations are
                         * possible.
                         */
                        $cidvalue = 0;
                        if (($config->roleassign_context_attribute != $config->roleassign_membership_attribute) && isset($clevelvalue)) {
                            if (!empty($config->roleassign_context_attribute)) {
                                if (!empty($options['verbose'])) {
                                    mtrace("Searching context identifier value in {$config->roleassign_context_attribute}");
                                }
                                $value = ldap_get_values_len($ldapconnection, $entry, $config->roleassign_context_attribute);
                                $cidvalue = local_ent_installer_get_from_value('context', $value, $ldapauth, $config);
                            }
                            if (!empty($options['verbose'])) {
                                mtrace("Searching context level $clevelvalue for identifier $cidvalue ");
                            }
                            $context = local_ent_installer_find_context($clevelvalue, $cidvalue);

                            if (!$context) {
                                mtrace("ERROR : Missing $clevelvalue context for identifier $cidvalue ");
                                continue;
                            }
                            if (!empty($options['verbose'])) {
                                mtrace("Found context $context->id ");
                            }
                        }

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        if (!empty($options['force']) || ($modify > 0 + $config->last_sync_date_roles)) {

                            mtrace("Searching members for $dn");
                            // Get members from the roleassign dn.
                            if (!$roleassigninfo = local_ent_installer_get_roleassigninfo_asobj($ldapauth, $dn, $options)) {
                                mtrace('ERROR : roleassign info error');
                                continue;
                            }

                            if (!empty($roleassigninfo->members)) {
                                foreach ($roleassigninfo->members as $m) {

                                    // If some data need be fetched in the member value. We get it from memberdn attribute.
                                    if ($config->roleassign_role_attribute == $config->roleassign_membership_attribute) {
                                        $roleid = local_ent_installer_get_role_from_value($m->memberdn, $ldapauth, $config);
                                    }

                                    if ($config->roleassign_contextlevel_attribute == $config->roleassign_membership_attribute) {
                                        $clevelvalue = local_ent_installer_get_clevel_from_value($m-memberdn, $ldapauth, $config);
                                    }

                                    if (($config->roleassign_context_attribute == $config->roleassign_membership_attribute)) {
                                        $cidvalue = local_ent_installer_get_from_value('context', $m-memberdn, $ldapauth, $config);
                                        if (!empty($options['verbose'])) {
                                            mtrace("Searching context level in membership $clevelvalue for identifier $cidvalue ");
                                        }
                                        $context = local_ent_installer_find_context($clevelvalue, $cidvalue);

                                        if (!$context) {
                                            mtrace("ERROR : Missing $clevelvalue context for identifier $cidvalue ");
                                            continue;
                                        }
                                    }

                                    $roleinfo = $DB->get_field('role', 'shortname', array('id' => $roleid));
                                    $contextinfo = $clevelvalue.' '. (0 + @$context->instanceid);

                                    // Store in temp table all composites plus full dn of the roleassign set.
                                    local_ent_installer_ldap_bulk_roleassign_insert($roleid, 0 + @$context->id, $m->id,
                                                                                    $roleinfo,
                                                                                    $contextinfo,
                                                                                    $clevelvalue,
                                                                                    $m->lastname.' '.$m->firstname);
                                }
                            } else {
                                if (!empty($options['verbose'])) {
                                    mtrace("No members");
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

    mtrace("\n########### Processing results #####################\n");

    $captureautoroleassigns = '';
    if (empty($options['disableautoroleassignscheck'])) {
        $captureautoroleassigns = "AND
            ra.component = 'local_ent_installer'";
    }

    // Deleted roleassigns.
    $sql = "
        SELECT
            ra.id as raid,
            ra.roleid,
            ra.contextid,
            ra.userid,
            u.username as userinfo,
            r.shortname as roleinfo,
            CONCAT(ctx.contextlevel,'_',ctx.instanceid) as contextinfo,
            ctx.contextlevel
        FROM
            {role_assignments} ra
        LEFT JOIN
            {tmp_extroleassigns} tra
        ON
            ra.contextid = tra.context AND
            ra.roleid = tra.role AND
            ra.userid = tra.user
        JOIN
            {user} u
        ON
            ra.userid = u.id
        JOIN
            {role} r
        ON
            ra.roleid = r.id
        JOIN
            {context} ctx
        ON
            ra.contextid = ctx.id
        WHERE
            tra.id IS NULL
            $captureautoroleassigns
    ";

    $deleted = $DB->get_records_sql($sql);

    // New roleassigns.
    $sql = "
        SELECT
            CONCAT(tra.role,'-',tra.context,'-',tra.user) as pkey,
            tra.role as roleid,
            tra.context as contextid,
            tra.user as userid,
            tra.userinfo,
            tra.contextinfo,
            tra.contextlevel,
            tra.roleinfo
        FROM
            {tmp_extroleassigns} tra
        LEFT JOIN
            {role_assignments} ra
        ON
            ra.contextid = tra.context AND
            ra.roleid = tra.role AND
            ra.userid = tra.user
        WHERE
            ra.id IS NULL
    ";

    $created = $DB->get_records_sql($sql);

    // No change roleassigns.
    $sql = "
        SELECT
            ra.id,
            tra.role as roleid,
            tra.context as contextid,
            tra.user as userid,
            tra.userinfo,
            tra.contextinfo,
            tra.contextlevel,
            tra.roleinfo
        FROM
            {role_assignments} ra,
            {tmp_extroleassigns} tra
        WHERE
            ra.contextid = tra.context AND
            ra.roleid = tra.role AND
            ra.userid = tra.user
    ";

    $nochange = $DB->get_records_sql($sql);

    if (!empty($options['force'])) {
        mtrace("\n>> ".get_string('deletingroleassigns', 'local_ent_installer'));
        if ($deleted) {
            foreach ($deleted as $dl) {
                $ctx = $DB->get_record('context', array('id' => $dl->contextid));
                if (empty($options['simulate'])) {
                    role_unassign($dl->roleid, $dl->userid, $dl->contextid, 'local_ent_installer');
                    mtrace(get_string('roleunassigned', 'local_ent_installer', $dl));

                    if ($ctx->contextlevel == CONTEXT_COURSE && $enrolplugin) {

                        $params = array('enrol' => $config->roleassign_enrol_method, 'courseid' => $ctx->instanceid, 'status' => ENROL_INSTANCE_ENABLED);
                        if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                            mtrace("No enrol instance found in course for this enrol method\n");
                            continue;
                        } else {
                            $enrol = reset($enrols);
                        }

                        // We only manage in course.
                        // Unenrol the required plugin.
                        $enrolplugin->unenrol_user($enrol, $dl->userid);
                        mtrace(get_string('unenrolled', 'local_ent_installer', $options['enrol']));
                    }
                } else {
                    mtrace('[SIMULATION] '.get_string('roleunassigned', 'local_ent_installer', $dl));
                    if ($enrolplugin && $ctx->contextlevel == CONTEXT_COURSE) {
                        // Context level comes from context table.
                        mtrace('[SIMULATION] '.get_string('unenrolled', 'local_ent_installer', $enrol->enrol.' '.$enrol->id));
                    }
                }
            }
        } else {
            if (!empty($options['verbose'])) {
                mtrace('Nothing to delete');
            }
        }
    } else {
        mtrace('No deletion possible unless in forced mode');
    }

    mtrace("\n>> ".get_string('creatingroleassigns', 'local_ent_installer'));
    if ($created) {
        foreach ($created as $cr) {
            if (empty($options['simulate'])) {
                // Ensures we capture the assignment in the component scope.
                $ctx = $DB->get_record('context', array('id' => $cr->contextid));
                role_unassign($cr->roleid, $cr->userid, $cr->contextid);
                role_assign($cr->roleid, $cr->userid, $cr->contextid, 'local_ent_installer');
                mtrace(get_string('roleassigned', 'local_ent_installer', $cr));
                if ($options['verbose']) {
                    print_object($cr);
                }
                if (($cr->contextlevel == 'course') && $enrolplugin) {
                    // Context level comes from temp table.

                    $params = array('enrol' => $config->roleassign_enrol_method, 'courseid' => $ctx->instanceid, 'status' => ENROL_INSTANCE_ENABLED);
                    if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                        mtrace("No enrol instance found in course for this enrol method\n");
                        continue;
                    } else {
                        $enrol = reset($enrols);
                    }

                    // We only manage in course.
                    // Enrol with specified plugin.
                    $enrolplugin->enrol_user($enrol, $cr->userid);
                    mtrace(get_string('enrolled', 'local_ent_installer', $enrol->enrol.' '.$enrol->id));
                }
            } else {
                mtrace('[SIMULATION] '.get_string('roleassigned', 'local_ent_installer', $cr));
            }
        }
    } else {
        if (!empty($options['verbose'])) {
            mtrace('Nothing to create');
        }
    }

    mtrace("\n>> ".get_string('unchangedroleassigns', 'local_ent_installer'));
    if ($nochange) {
        foreach ($nochange as $ra) {
            mtrace(get_string('norolechange', 'local_ent_installer', $ra));
        }
    } else {
        if (!empty($options['verbose'])) {
            mtrace('No matching assigns');
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

    set_config('last_sync_date_roles', time(), 'local_ent_installer');
}

function local_ent_installer_get_role_from_value($value, $ldapauth, $config) {
    global $DB;
    static $rolemapcache = array();

    $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
    if (preg_match('/'.$config->roleassign_role_filter.'/', $value, $matches)) {
        $value = $matches[1];
    }
    $rolevalue = local_ent_installer_remap_role($value, $config);
    if (!array_key_exists($rolevalue, $rolemapcache)) {
        $rolemapcache[$rolevalue] = $DB->get_record('role', array('shortname' => $rolevalue), 'id,shortname');
    }
    $roleid = $rolemapcache[$rolevalue]->id;
    return $roleid;

}

function local_ent_installer_get_clevel_from_value($value, $ldapauth, $config) {
    $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
    if (preg_match('/'.$config->roleassign_contextlevel_filter.'/', $value, $matches)) {
        $value = $matches[1];
    }
    $clevelvalue = local_ent_installer_remap_contextlevel($value, $config);
    return $clevelvalue;
}

function local_ent_installer_get_from_value($key, $value, $ldapauth, $config) {
    $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');
    $varkey = 'roleassign_'.$key.'_filter';
    if (preg_match('/'.$config->$varkey.'/', $value, $matches)) {
        $value = $matches[1];
    }
    return $value;
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

    $ldapconnection = $ldapauth->ldap_connect();

    $extdn = core_text::convert($dn, 'utf-8', $ldapauth->config->ldapencoding);

    if (!empty($options['verbose'])) {
        mtrace("\nGetting $dn for ".$config->roleassign_membership_attribute);
    }
    if (!$roleassign_info_result = ldap_read($ldapconnection, $extdn, '(objectClass=*)', array($config->roleassign_membership_attribute))) {
        $ldapauth->ldap_close();
        return false;
    }

    $attrmap = array('members' => core_text::strtolower($config->roleassign_membership_attribute));

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
            $arity = array_pop($entry[$value]);
            if (!empty($options['verbose'])) {
                mtrace("Found $arity record...");
            }
            foreach ($entry[$value] as $newvalopt) {
                $newvalopt  = core_text::convert($newvalopt, $ldapauth->config->ldapencoding, 'utf-8');
                if (!empty($options['verbose'])) {
                    mtrace("Extracting from $newvalopt with {$config->roleassign_membership_filter} ");
                }
                if (preg_match('/'.$config->roleassign_membership_filter.'/', $newvalopt, $matches)) {
                    // Exclude potential arity count that comes at end of multivalued entries.
                    if ($config->roleassign_user_key == 'username') {
                        $identifier = core_text::strtolower($matches[1]);
                    } else {
                        $identifier = $matches[1];
                    }
                    if (!empty($options['verbose'])) {
                        mtrace("Getting user record for {$config->roleassign_user_key} = $identifier");
                    }
                    $user = $DB->get_record('user', array($config->roleassign_user_key => $identifier, 'deleted' => 0), 'id,username,firstname,lastname');
                    if (!$user) {
                        mtrace("Error : User record not found for $identifier. Skipping membership");
                        continue;
                    }
                    $user->memberdn = $newvalopt; // Store original ldap record value into user.
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
function local_ent_installer_ldap_bulk_roleassign_insert($roleid, $contextid, $userid, $roleinfo, $contextinfo, $contextlevel, $userinfo) {
    global $DB;

    $params = array('role' => $roleid, 'context' => $contextid, 'user' => $userid);
    if (!$DB->record_exists('tmp_extroleassigns', $params)) {
        $params = array('role' => $roleid,
                        'context' => $contextid,
                        'user' => $userid,
                        'roleinfo' => shorten_text($roleinfo, 50),
                        'contextinfo' => shorten_text($contextinfo, 50),
                        'contextlevel' => $contextlevel,
                        'userinfo' => shorten_text($userinfo, 50));

        $DB->insert_record_raw('tmp_extroleassigns', $params, false, true);
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
        $mapconf = get_config('local_ent_installer', 'roleassign_role_mapping');
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

function local_ent_installer_find_context($clevelvalue, $cidvalue = 0) {
    global $DB;

    static $contextcache = array();
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    if (($clevelvalue != 'system') && empty($cidvalue)) {
        return false;
    }

    if (!isset($contextcache[$clevelvalue])) {
        $contextcache[$clevelvalue] = array();
    }

    if (!array_key_exists($cidvalue, $contextcache[$clevelvalue])) {
        switch ($clevelvalue) {
            case 'system':
                    $contextcache[$clevelvalue][$cidvalue] = context_system::instance();
                break;

            case 'coursecat':
                if (!$objid = $DB->get_field('course_categories', 'id', array($config->roleassign_coursecat_key => $cidvalue))) {
                    return false;
                }
                $contextcache[$clevelvalue][$cidvalue] = context_coursecat::instance($objid);
                break;

            case 'course':
                mtrace("Search course context by {$config->roleassign_course_key} with identifier $cidvalue ");
                if (!$objid = $DB->get_field('course', 'id', array($config->roleassign_course_key => $cidvalue))) {
                    return false;
                }
                $contextcache[$clevelvalue][$cidvalue] = context_course::instance($objid);
                break;

            case 'module':
                if (!$objid = $DB->get_field('course_modules', 'id', array($config->roleassign_module_key => $cidvalue))) {
                    return false;
                }
                $contextcache[$clevelvalue][$cidvalue] = context_module::instance($objid);
                break;

            case 'block':
                if (!$objid = $DB->get_field('block_instances', 'id', array($config->roleassign_block_key => $cidvalue))) {
                    return false;
                }
                $contextcache[$clevelvalue][$cidvalue] = context_block::instance($objid);
                break;

            case 'user':
                if (!$objid = $DB->get_field('user', 'id', array($config->roleassign_usertarget_key => $cidvalue))) {
                    return false;
                }
                $contextcache[$clevelvalue][$cidvalue] = context_user::instance($objid)->id();
                break;
        }
    }

    return $contextcache[$clevelvalue][$cidvalue];
}