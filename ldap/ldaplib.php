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
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_cohorts.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_coursegroups.php');
require_once($CFG->dirroot.'/cohort/lib.php');

define('ENT_MATCH_FULL', 100);
define('ENT_MATCH_ID_NO_USERNAME', 50);
define('ENT_MATCH_ID_LASTNAME_NO_USERNAME_FIRSTNAME', 20);
define('ENT_MATCH_NO_ID_NO_USERNAME_LASTNAME_FIRSTNAME', 10);
define('ENT_MATCH_NO_ID_USERNAME_LASTNAME_FIRSTNAME', 100);
define('ENT_NO_MATCH', 0);

global $MATCH_STATUS;

$MATCH_STATUS = array(
    ENT_MATCH_FULL => 'FULL MATCH',
    ENT_MATCH_ID_NO_USERNAME => 'FIX USERNAME',
    ENT_MATCH_ID_LASTNAME_NO_USERNAME_FIRSTNAME => 'LOW MATCH BY ID LASTNAME',
    ENT_MATCH_NO_ID_NO_USERNAME_LASTNAME_FIRSTNAME => 'LOW MATCH BY LASTNAME AND FIRSTNAME',
    ENT_MATCH_NO_ID_USERNAME_LASTNAME_FIRSTNAME => 'GOOD MATCH BY USERNAME, LASTNAME AND FIRSTNAME'
);

/**
 * Syncronizes user from external LDAP server to moodle user table
 *
 * Sync is now using username attribute.
 *
 * Syncing users removes or suspends users that dont exists anymore in external LDAP.
 * Creates new users and updates coursecreator status of users.
 *
 * @param bool $do_updates will do pull in data updates from LDAP if relevant
 */
function local_ent_installer_sync_users($ldapauth, $options) {
    global $CFG, $DB, $MATCH_STATUS;

    core_php_time_limit::raise(120);

    mtrace('');
    $enable = get_config('local_ent_installer', 'sync_enable');
    if (!$enable) {
        mtrace(get_string('syncdisabled', 'local_ent_installer'));
        return;
    }

    $config = get_config('local_ent_installer');

    ent_installer_check_archive_category_exists();
    if ($config->create_students_site_cohort) {
        $studentsitecohortid = local_ent_installer_ensure_global_cohort_exists('students', $options);
    }
    if ($config->create_staff_site_cohort) {
        $staffsitecohortid = local_ent_installer_ensure_global_cohort_exists('staff', $options);
    }
    $adminssitecohortid = local_ent_installer_ensure_global_cohort_exists('admins', $options);

    $USERFIELDS = local_ent_installer_load_user_fields();

    mtrace(get_string('lastrun', 'local_ent_installer', userdate($config->last_sync_date)));
    mtrace("\n>> ".get_string('connectingldap', 'auth_ldap'));
    $ldapconnection = $ldapauth->ldap_connect();

    $dbman = $DB->get_manager();

    list($usec, $sec) = explode(' ',microtime());
    $starttick = (float)$sec + (float)$usec;

    // Define table user to be created.

    $table = new xmldb_table('tmp_extuser');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('usertype', XMLDB_TYPE_CHAR, '16', null, null, null, null);
    $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_index('userprofile', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username', 'usertype'));

    mtrace("\n>> ".get_string('creatingtemptable', 'auth_ldap', 'tmp_extuser'));

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
    $dbman->create_temp_table($table);

    /*
     * get user's list from ldap to sql in a scalable fashion from different user profiles
     * defined as LDAP filters. We fetch all the user's logins along with a modified timestamp in
     * local temp table so we can further sort what records to consider.
     *
     * prepare some data we'll need
     */

    $filters = array();

    $institutionids = explode(',', $config->institution_id);

    // Students.

    /*
     * Implementation notes :
     * - Atrium : Legacy scheme. => user must have class ENTEleve AND have a ENTEleveClasse containing intitutionid
     * - Toutatice : variant scheme => user must have class ENTEleve AND have ENTPersonStructRattach equals institutionid.
     */

    if (empty($options['role']) || preg_match('/eleve/', $options['role'])) {
        $filterdef = new StdClass();
        foreach($institutionids as $iid) {
            $institutionfilter = $config->student_institution_filter;
            $filterdef->institutions[] = str_replace('%ID%', $iid, $institutionfilter);
        }
        $filterdef->usertype = $config->student_usertype_filter;
        $filterdef->userfield = 'eleve';
        $filters[] = $filterdef;
    }

    // Teaching staff.

    /*
     * Implementation notes :
     * - Atrium : Legacy scheme. => user must have class ENTAuxEnseignant AND have a ENTPersonFonctions containing intitutionid
     * - Toutatice : user must have ENTAuxEnseignant AND title is ENS or CTR and have a ENTPersonFonctions containing intitutionid
     */

    if (empty($options['role']) || preg_match('/enseignant/', $options['role'])) {
        $filterdef = new StdClass();
        foreach ($institutionids as $iid) {
            $institutionfilter = $config->teachstaff_institution_filter;
            $filterdef->institutions[] = str_replace('%ID%', $iid, $institutionfilter);
        }
        $filterdef->usertype = $config->teachstaff_usertype_filter;
        $filterdef->userfield = 'enseignant';
        $filters[] = $filterdef;
    }

    // Non teaching staff.

    /*
     * Implementation notes :
     * - Atrium : Legacy scheme. => user must have class ENTAuxEnseignant AND have a ENTPersonFonctions containing intitutionid
     * - Toutatice : user must have class ENTAuxEnseignant AND title is DIR or DOC or CPE and have a ENTPersonFonctions containing intitutionid
     */

    if (empty($options['role']) || preg_match('/administration/', $options['role'])) {
        $filterdef = new StdClass();
        foreach ($institutionids as $iid) {
            $institutionfilter = $config->adminstaff_institution_filter;
            $filterdef->institutions[] = str_replace('%ID%', $iid, $institutionfilter);
        }
        $filterdef->usertype = $config->adminstaff_usertype_filter;
        $filterdef->userfield = 'administration';
        $filters[] = $filterdef;
    }

    // Site admins

    /*
     * Implementation notes :
     * - Atrium : No site admins except the unique Atrium admin. All other are Managers.
     * - Toutatice : Siteadmins are comming from a ENTPersonProfiles entry containing cn=toutatice_moodle_admin* or cn=toutatice_admin*
     * role profile references.
     */

    if (empty($options['role']) || preg_match('/siteadmins/', $options['role'])) {
        $filterdef = new StdClass();
        if (!empty($config->siteadmins_institution_filter)) {
            // Note that siteadmins may be global admins and have no institution restrictions.
            foreach($institutionids as $iid) {
                $institutionfilter = $config->siteadmins_institution_filter;
                $filterdef->institutions[] = str_replace('%ID%', $iid, $institutionfilter);
            }
        }
        $filterdef->usertype = $config->siteadmins_usertype_filter;
        $filterdef->userfield = 'siteadmin';
        $filters[] = $filterdef;
    }

    $contexts = explode(';', $ldapauth->config->contexts);

    if (!empty($ldapauth->config->create_context)) {
        array_push($contexts, $ldapauth->config->create_context);
    }

    $ldap_pagedresults = ldap_paged_results_supported($ldapauth->config->ldap_version);
    $ldapcookie = '';
    foreach ($filters as $filterdef) {

        $institutions = '';
        if (!empty($filterdef->institutions)) {
            if (count($filterdef->institutions) > 1) {
                $institutions = '(|'.implode('', $filterdef->institutions).')';
            } else {
                $institutions = array_pop($filterdef->institutions);
            }
        }

        $filter = '(&('.$ldapauth->config->user_attribute.'=*)'.$filterdef->usertype.$institutions.')';

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
                    mtrace("ldapsearch $context, $filter for ".$ldapauth->config->user_attribute);
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, array($ldapauth->config->user_attribute, 'modifyTimestamp'));
                } else {
                    // Search only in this context.
                    mtrace("ldaplist $context, $filter for ".$ldapauth->config->user_attribute);
                    $ldap_result = ldap_list($ldapconnection, $context, $filter, array($ldapauth->config->user_attribute, 'modifyTimestamp'));
                }
                if (!$ldap_result) {
                    continue;
                }
                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldap_result, $ldapcookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                    do {
                        $value = ldap_get_values_len($ldapconnection, $entry, $ldapauth->config->user_attribute);
                        $value = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

                        $modify = ldap_get_values_len($ldapconnection, $entry, 'modifyTimestamp');
                        $modify = strtotime($modify[0]);

                        local_ent_installer_ldap_bulk_insert($value, $filterdef->userfield, $modify);
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

    /*
     * preserve our user database
     * if the temp table is empty, it probably means that something went wrong, exit
     * so as to avoid mass deletion of users; which is hard to undo
     */
    $count = $DB->count_records_sql('SELECT COUNT(username) AS count, 1 FROM {tmp_extuser}');
    if ($count < 1) {
        mtrace(get_string('didntgetusersfromldap', 'auth_ldap'));
        $dbman->drop_table($table);
        $ldapauth->ldap_close(true);
        return false;
    } else {
        mtrace(get_string('gotcountrecordsfromldap', 'auth_ldap', $count));
    }

    /**
     * Start cleaning all extraction automated cohorts, only in forced mode as thus we update all living users.
     */
    $cohortix = $config->cohort_ix;

    local_ent_installer_release_old_cohorts();

    if (!empty($options['force'])) {
        $select = " component = 'local_ent_installer' AND name LIKE '{$cohortix}%' ";
        $automated = $DB->get_records_select_menu('cohort', $select, array(), 'id,name');
        mtrace("\n>> ".get_string('cleaningautomatedcohortscontent', 'local_ent_installer', count($automated)));

        if ($automated) {
            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($automated));
            $sql = "
                DELETE FROM
                    {cohort_members}
                WHERE
                    cohortid $insql
            ";

            $DB->execute($sql, $inparams);
        }
    }

    // User removal. *****************************.
    /*
     * Find users in DB that aren't in ldap -- to be removed!
     * this is still not as scalable (but how often do we mass delete?)
     */
    if ($ldapauth->config->removeuser != AUTH_REMOVEUSER_KEEP) {

        mtrace("\n>> ".get_string('usersdeletion', 'local_ent_installer'));

        $sql = '
            SELECT
                u.*
            FROM
                {user} u
            LEFT JOIN
                {tmp_extuser} e
            ON
                (u.username = e.username AND u.mnethostid = e.mnethostid)
            WHERE
                u.auth = ? AND
                u.deleted = 0 AND
                u.suspended = 0 AND
                e.username IS NULL
        ';
        $real_user_auth = $config->real_used_auth;
        $remove_users = $DB->get_records_sql($sql, array($real_user_auth));

        if (!empty($remove_users)) {
            mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

            foreach ($remove_users as $user) {
                if ($ldapauth->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                    if (empty($options['simulate'])) {
                        if (empty($options['fulldelete'])) {
                            // Make a light delete of users, but keeping data for revival.
                            $user->deleted = 1;
                            try {
                                $DB->update_record('user', $user);
                                $params = array('name' => $user->username, 'id' => $user->id);
                                mtrace(get_string('auth_dbdeleteuser', 'auth_db', $params));
                            } catch (Exception $e) {
                                mtrace(get_string('auth_dbdeleteusererror', 'auth_db', $user->username));
                            }
                        } else {
                            // Make a complete delete of users, enrols, grades and data.
                            if (delete_user($user)) {
                                echo "\t";
                                $params = array('name' => $user->username, 'id' => $user->id);
                                mtrace(get_string('auth_dbdeleteuser', 'auth_db', $params));
                            } else {
                                echo "\t";
                                mtrace(get_string('auth_dbdeleteusererror', 'auth_db', $user->username));
                            }
                        }
                    } else {
                        mtrace(get_string('simulateuserdelete', 'ent_installer', $user->username));
                    }
                } else if ($ldapauth->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                    if (empty($options['simulate'])) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->auth = 'nologin';
                        $updateuser->suspended = 1;
                        $DB->update_record('user', $updateuser);
                        echo "\t";
                        mtrace(get_string('auth_dbsuspenduser', 'auth_db', array('name' => $user->username, 'id' => $user->id)));
                        $euser = $DB->get_record('user', array('id' => $user->id));
                        \core\event\user_updated::create_from_userid($euser->id)->trigger();
                    } else {
                        mtrace(get_string('simulateusersuspend', 'ent_installer', $user->username));
                    }
                }
            }
        } else {
            mtrace("\n-- ".get_string('nouserentriestoremove', 'auth_ldap'));
        }
        unset($remove_users); // Free mem!
    }

    // Revive suspended users. *********************************.
    if (!empty($ldapauth->config->removeuser) && $ldapauth->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {

        mtrace("\n>> ".get_string('revivingdeletedorsuspended', 'local_ent_installer'));

        $sql = "
            SELECT
                u.id, u.username
            FROM
                {user} u
            JOIN
                {tmp_extuser} e
            ON
                (u.username = e.username AND u.mnethostid = e.mnethostid)
            WHERE
                u.auth = 'nologin' AND u.deleted = 0
        ";
        $revive_users = $DB->get_records_sql($sql);

        if (!empty($revive_users)) {
            mtrace(get_string('userentriestorevive', 'auth_ldap', count($revive_users)));

            foreach ($revive_users as $user) {
                $updateuser = new stdClass();
                $updateuser->id = $user->id;
                $updateuser->auth = $ldapauth->authtype;
                $DB->update_record('user', $updateuser);
                echo "\t";
                mtrace(get_string('auth_dbreviveduser', 'auth_db', array('name' => $user->username, 'id' => $user->id)));
                $euser = $DB->get_record('user', array('id' => $user->id));
                \core\event\user_updated::create_from_userid($euser->id)->trigger();
            }
        } else {
            mtrace(get_string('nouserentriestorevive', 'auth_ldap'));
        }

        unset($revive_users);
    }

    // User Updates - time-consuming (optional). ***************************.
    // This might be an OBSOLETE code, regarding the updat ecapability of the create process.
    if (!empty($options['doupdates'])) {

        mtrace("\n>> ".get_string('updatingusers', 'local_ent_installer'));

        // Narrow down what fields we need to update.
        $allkeys = array_keys(get_object_vars($ldapauth->config));
        $updatekeys = array();
        foreach ($allkeys as $key) {
            if (preg_match('/^field_updatelocal_(.+)$/', $key, $match)) {
                /*
                 * If we have a field to update it from
                 * and it must be updated 'onlogin' we
                 * update it on cron
                 */
                if (!empty($ldapauth->config->{'field_map_'.$match[1]}) && ($ldapauth->config->{$match[0]} === 'onlogin')) {
                    // The actual key name.
                    array_push($updatekeys, $match[1]);
                }
            }
        }
        unset($allkeys);
        unset($key);

        // Run updates only if relevant.
        $users = $DB->get_records_sql('SELECT u.username, u.id
                                         FROM {user} u
                                        WHERE u.deleted = 0 AND u.auth = ? AND u.mnethostid = ?',
                                      array($ldapauth->authtype, $CFG->mnet_localhost_id));
        if (!empty($users)) {
            mtrace(get_string('userentriestoupdate', 'auth_ldap', count($users)));

            $sitecontext = context_system::instance();
            if (!empty($ldapauth->config->creators) && !empty($ldapauth->config->memberattribute)
                && $roles = get_archetype_roles('coursecreator')) {
                // We can only use one, let's use the first one.
                $creatorrole = array_shift($roles);
            } else {
                $creatorrole = false;
            }

            $transaction = $DB->start_delegated_transaction();
            $xcount = 0;
            $maxxcount = 100;

            foreach ($users as $user) {
                echo "\t";
                $tracestr = get_string('auth_dbupdatinguser', 'auth_db', array('name' => $user->username, 'id' => $user->id)); 
                if (!$ldapauth->update_user_record($user->username, $updatekeys)) {
                    $tracestr .= ' - '.get_string('skipped');
                }
                mtrace($tracestr);
                $xcount++;

                // Update course creators if needed.
                if ($creatorrole !== false) {
                    if ($ldapauth->iscreator($user->username)) {
                        role_assign($creatorrole->id, $user->id, $sitecontext->id, $ldapauth->roleauth);
                    } else {
                        role_unassign($creatorrole->id, $user->id, $sitecontext->id, $ldapauth->roleauth);
                    }
                }
            }
            $transaction->allow_commit();
            unset($users); // Free mem.
        }
    } else {
        mtrace(get_string('noupdatestobedone', 'auth_ldap'));
    }

    // User Additions or full profile update. ********************************.
    /*
     * Find users missing in DB that are in LDAP or users that have been modified since last run
     * and gives me a nifty object I don't want.
     * note: we do not care about deleted accounts anymore, this feature was replaced by suspending to nologin auth plugin
     */
    $maildisplay = get_config('local_ent_installer', 'maildisplay');

    // Diff get users for temporary table.
    if (empty($options['force'])) {
        $sql = '
            SELECT
                e.id,
                e.username,
                e.usertype
            FROM
                {tmp_extuser} e
            LEFT JOIN
                {user} u
            ON
                (e.username = u.username AND
                e.mnethostid = u.mnethostid)
            WHERE
                u.id IS NULL OR
                (e.lastmodified > ? )
            ORDER BY
                e.username';
        $params = array($config->last_sync_date);
    } else {
        $sql = '
            SELECT
                e.id,
                e.username,
                e.usertype
            FROM
                {tmp_extuser} e
            ORDER BY
                e.username';
        $params = array();
    }
    $add_users = $DB->get_records_sql($sql, $params);

    // Process all users.
    if (!empty($add_users)) {
        mtrace("\n>> ".get_string('userentriestoadd', 'auth_ldap', count($add_users)));

        $sitecontext = context_system::instance();
        if (!empty($ldapauth->config->creators) &&
                !empty($ldapauth->config->memberattribute)
                        && $roles = get_archetype_roles('coursecreator')) {
            // We can only use one, let's use the first one.
            $creatorrole = array_shift($roles);
        } else {
            $creatorrole = false;
        }

        $inserterrorcount = 0;
        $updateerrorcount = 0;
        $insertcount = 0;
        $updatecount = 0;

        // We scan new proposed users from LDAP.
        foreach ($add_users as $user) {

            mtrace('----');
            // Save usertype.
            $usertype = $user->usertype;

            $user = local_ent_installer_get_userinfo_asobj($ldapauth, $user->username, $options);

            // Restore usertype in user.
            $user->usertype = $usertype;

            if (!empty($user->idnumber)) {
                // Post filter of idnumber.
                if (strpos($user->idnumber, '$') !== false) {
                    list($foosdetprefix, $user->idnumber) = explode('$', $user->idnumber);
                }
                mtrace('ID:'.$user->idnumber);
            } else {
                $user->idnumber = '';
                mtrace('ID: Notice. Could not get IDNumber for this user');
            }

            if (empty($user->firstname)) {
                mtrace('ERROR : Missing firstname in incoming record '.$user->username);
                $updateerrorcount++;
                continue;
            }

            if (empty($user->lastname)) {
                mtrace('ERROR : Missing lastname in incoming record '.$user->username);
                $updateerrorcount++;
                continue;
            }

            if (empty($user->email)) {
                $user->email = local_ent_installer_generate_email($user);
            }

            // Prep a few params.
            $user->modified = time();
            $user->confirmed = 1;
            $user->deleted = 0;
            $user->suspended = 0;

            // Authentication is the ldap plugin or a real auth plugin defined in setup.
            $realauth = $config->real_used_auth;
            $user->auth = (empty($realauth)) ? $ldapauth->authtype : $realauth ;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->country = $CFG->country;

            // If is set, User is being deleted or faked account. Ignore.
            if (!empty($user->ENTPersonDateSuppression)) {
                mtrace('ERROR : User upon deletion process '.$user->username);
                $updateerrorcount++;

                ent_installer_check_category_archiving($user);

                continue;
            }

            // This is a declared duple. Ignore
            if (!empty($user->seeAlso)) {
                mtrace('ERROR : User '.$user->username.' is a duple. Ignoring.');
                $updateerrorcount++;
                continue;
            }

            /*
             * Get_userinfo_asobj() might have replaced $user->username with the value.
             * from the LDAP server (which can be mixed-case). Make sure it's lowercase
             */
            $user->username = trim(core_text::strtolower($user->username));
            if (empty($user->lang)) {
                $user->lang = $CFG->lang;
            }

            /*
             * Process additional info for student :
             * extra information fields transport and regime.
             */
            if ($user->usertype == 'eleve') {

                // Transport.
                local_ent_installer_user_add_info($user, 'student', 'transport');

                // Regime.
                local_ent_installer_user_add_info($user, 'student', 'regime');

                // Bare legal.
                local_ent_installer_user_add_info($user, 'student', 'fullage');

                // Cohort (must have).
                local_ent_installer_user_add_info($user, 'student', 'cohort');
            }

            $personfunction = @$user->ENTPersonFonctions;
            unset($user->ENTPersonFonctions);

            $personstructure = @$user->ENTPersonStructRattach;
            unset($user->ENTPersonStructRattach);

            // Get the last term of personfunction and set it as department.
            if (!empty($personfunction)) {
                if (preg_match('/\\$([^\\$]+)$/', $personfunction, $matches)) {
                    $user->department = $matches[1];
                } else {
                    $user->department = '';
                }
            }

            if (empty($options['simulate'])) {

                // Creation/full update sequence.
                $a = clone($user);
                $a->function = $personfunction;

                /*
                 * Special case : is there a matching policy possible for previous accounts NOT being
                 * created by this system ?
                 */

                if ($oldrec = local_ent_installer_guess_old_record($user, $status)) {

                    $a->status = $MATCH_STATUS[$status];
                    $id = $user->id = $oldrec->id;
                    try {
                        $DB->update_record('user', $user);
                        mtrace(get_string('dbupdateuser', 'local_ent_installer', $a));
                        $updatecount++;
                    } catch (Exception $e) {
                        mtrace('ERROR : Failed update '.$user->username);
                        $updateerrorcount++;
                        continue;
                    }
                } else {
                    // This is a real new user.
                    $user->maildisplay = 0 + $maildisplay;
                    try {
                        $id = $DB->insert_record('user', $user);
                        mtrace(get_string('dbinsertuser', 'local_ent_installer', $a));
                        $insertcount++;
                    } catch(Exception $e) {
                        mtrace('ERROR : Failed insert '.$user->username);
                        $inserterrorcount++;
                        continue;
                    }
                }

            } else {
                // SIMULATION.
                $a = clone($user);
                $a->function = $personfunction;
                if (!$oldrec = local_ent_installer_guess_old_record($user, $status)) {
                    mtrace(get_string('dbinsertusersimul', 'local_ent_installer', $a));
                } else {
                    $a->status = $MATCH_STATUS[$status];
                    mtrace(get_string('dbupdateusersimul', 'local_ent_installer', $a));
                }

                // For expliciting simulation.
                if ($user->usertype == 'siteadmin') {
                    // Add all site administrators. Mark them as keys to be merged back.
                    if ($oldrec) {
                        $siteadmins[] = $oldrec->id;
                    } else {
                        $siteadmins[] = $user->username;
                    }
                }
            }

            if (empty($options['simulate'])) {
                $euser = $DB->get_record('user', array('id' => $id));
                if (empty($oldrec)) {
                    \core\event\user_created::create_from_userid($euser->id)->trigger();
                } else {
                    \core\event\user_updated::create_from_userid($euser->id)->trigger();
                }
                if (!empty($ldapauth->config->forcechangepassword)) {
                    set_user_preference('auth_forcepasswordchange', 1, $id);
                }

                // Cohort information / create/update cohorts.
                if ($user->usertype == 'eleve') {

                    // Adds user to cohort and create cohort if missing.
                    $cohortshort = local_ent_installer_check_cohort($id, $user->profile_field_cohort);

                    local_ent_installer_update_info_data($id, $USERFIELDS['transport'], @$user->profile_field_transport);
                    local_ent_installer_update_info_data($id, $USERFIELDS['regime'], @$user->profile_field_regime);
                    local_ent_installer_update_info_data($id, $USERFIELDS['fullage'], @$user->profile_field_fullage);
                    local_ent_installer_update_info_data($id, $USERFIELDS['cohort'], $cohortshort);

                    if (isset($studentsitecohortid)) {
                        cohort_add_member($studentsitecohortid, $id);
                    }
                } else {
                    if (isset($staffsitecohortid)) {
                        cohort_add_member($studentsitecohortid, $id);
                    }
                }

                // Update primary assignation for all classes of users.
                mtrace('Checking user primary assignation in '.$personstructure);
                $isprimaryassignation = (local_ent_installer_match_structure($personstructure)) ? 1 : 0;
                local_ent_installer_update_info_data($id, $USERFIELDS['isprimaryassignation'], $isprimaryassignation);

                // Add course creators if needed.
                mtrace('Checking course creator status');
                if ($creatorrole !== false and $ldapauth->iscreator($user->username)) {
                    role_assign($creatorrole->id, $id, $sitecontext->id, $ldapauth->roleauth);
                }

                // Process user_fields setup.
                mtrace('Checking user profile fields');
                if (preg_match('#\\$CTR\\$#', $personfunction)) {
                    // Special case.
                    local_ent_installer_update_info_data($id, $USERFIELDS['cdt'], 1);
                    $user->usertype = 'cdt';
                } else if ($user->usertype != 'siteadmin') {
                    // Other user types unless site admins.
                    local_ent_installer_update_info_data($id, $USERFIELDS[$user->usertype], 1);
                }

                if (!empty($user->personalTitle)) {
                    local_ent_installer_update_info_data($id, $USERFIELDS['personaltitle'], $user->personalTitle);
                }

                mtrace('Checking network accesses');
                if (file_exists($CFG->dirroot.'/blocks/user_mnet_hosts/locallib.php')) {
                    require_once($CFG->dirroot.'/blocks/user_mnet_hosts/locallib.php');

                    // All users have access marked on self.
                    user_mnet_hosts_set_access($id, true);

                    // Setting default access field policy for powered users.
                    if ($user->usertype == 'enseignant' || $user->usertype == 'administration' || $user->usertype == 'cdt') {
                        $like = $DB->sql_like('wwwroot', ':wwwroot', false, false);
                        if ($commonroot = $DB->get_field_select('mnet_host', 'wwwroot', $like, array('wwwroot' => $CFG->mainhostprefix.'%'))){
                            user_mnet_hosts_set_access($id, true, $commonroot);
                        } else {
                            mtrace('Giving teacher access : common host not found ');
                        }
                    }
                }

                // Add a workplace to teachers.
                if ($user->usertype == 'enseignant') {
                    mtrace('Checking teacher category creation service');
                    if ($config->build_teacher_category) {
                        local_ent_installer_make_teacher_category($euser);
                    }
                }

                // Identify librarians and give library enabled role at system level.
                mtrace('Checking librarian attributes');
                if (preg_match('#\\$DOC\\$#', $personfunction)) {
                    mtrace('Adding librarian attributes');
                    if ($role = $DB->get_record('role', array('shortname' => 'librarian'))) {
                        $systemcontext = context_system::instance();
                        role_assign($role->id, $id, $systemcontext->id);
                    }
                }

                if ($user->usertype == 'siteadmin') {
                    // Add all site administrators. Mark them as keys to be merged back.
                    $siteadmins[$id] = true;
                    if (isset($adminssitecohortid)) {
                        cohort_add_member($adminssitecohortid, $id);
                    }
                }

                // Identify school deans and give them Manager role.
                mtrace('Checking school dean attributes');
                if (preg_match('#\\$DIR\\$#', $personfunction)) {
                    mtrace('Adding school dean attributes');
                    if ($role = $DB->get_record('role', array('shortname' => 'manager'))) {
                        $systemcontext = context_system::instance();
                        role_assign($role->id, $id, $systemcontext->id);
                    }
                }
            }
        }
        unset($add_users); // Free mem.
    } else {
        mtrace("\n-- ".get_string('nouserstobeadded', 'auth_ldap'));
    }

    if (!empty($siteadmins)) {
        local_ent_installer_merge_siteadmins($siteadmins, $options);
    }

    local_ent_installer_reorder_teacher_categories();

    mtrace("\n>> ".get_string('finaloperations', 'local_ent_installer'));
    // Clean temporary table.
    try {
        $dbman->drop_table($table);
    } catch (Exception $e) {
        assert(1);
    }

    $ldapauth->ldap_close();

    // Cleanup 0 students cohorts if full sync.
    if (!empty($options['force'])) {
        $sql = "
            SELECT
                c.id,c.id
            FROM
                {cohort} c
            LEFT JOIN
                {cohort_members} cm
            ON
                cm.cohortid = c.id
            WHERE
                cm.id IS NULL AND
                c.component = ? AND
                c.name LIKE ?
        ";

        if ($nullcohorts = $DB->get_records_sql($sql, array('local_ent_installer', $cohortix.'%'))) {

            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($nullcohorts));
            $sql = "
                DELETE FROM
                 {cohort}
                WHERE
                    id $insql
            ";
            $DB->execute($sql, $inparams);
        }
    }

    // Calculate bench time.
    list($usec, $sec) = explode(' ',microtime());
    $stoptick = (float)$sec + (float)$usec;

    $deltatime = $stoptick - $starttick;

    mtrace('Execution time : '.$deltatime);

    $benchrec = new StdClass();
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

    // Mark last time the user sync was run.
    set_config('last_sync_date', time(), 'local_ent_installer');

    return true;
}

/**
 * This sets profile_field values from mapping info from the Person Profile settings.
 * @param objectref &$user the user record being modified
 * @param string $role the user role (usertype)
 * @param string $info the info key
 * @return void
 */
function local_ent_installer_user_add_info(&$user, $role, $info) {
    static $config;

    if (!isset($config)) {
        $config = get_config('local_ent_installer');
    }

    $fieldkey = $role.'_'.$info.'_userfield';
    $filterkey = $role.'_'.$info.'_userfield_filter';
    $field = @$config->$fieldkey;

    if ($field) {
        if (empty($config->$filterkey)) {
            $filter = '(.*)'; // A Catch All pattern
        } else {
            $filter = $config->$filterkey;
        }

        preg_match("/$filter/", $user->$field, $matches);
        $pfkey = 'profile_field_'.$info;

        $value = @$matches[1];

        // Convert boolean values.
        if ($value == 'Y') {
            $value = 1;
        }

        if ($value == 'N') {
            $value = 0;
        }

        $user->$pfkey = $value;
    }
}

/**
 * This function encapsulates all the strategies to find old records in moodle, matching
 * a new user proposal. In standard cases (regular updates), the username is sufficiant and
 * consistant. In cases of a system initialisation or IDP change, the username matching may require
 * some translation ro catch older records.
 *
 * the matching strategy adopted is a regressive check from very constrainted match to less constraint match
 */
function local_ent_installer_guess_old_record($newuser, &$status) {
    global $DB;

    $config = get_config('local_ent_installer');

    // If all ID parts match, we are sure (usual case when regular updating).
    $oldrec = $DB->get_record_select('user', " username = ? AND idnumber = ? AND LOWER(firstname) = ? AND LOWER(lastname) = ? ", array($newuser->username, $newuser->idnumber, strtolower($newuser->firstname), strtolower($newuser->lastname)));
    if ($oldrec) {
        $status = ENT_MATCH_FULL;
        return $oldrec;
    }

    // Usernames match, so do firstname and lastname.
    $oldrecs = $DB->get_records_select('user', " LOWER(firstname) = ? AND LOWER(lastname) = ? AND username = ? ", array(strtolower($newuser->firstname), strtolower($newuser->lastname), $newuser->username));
    if ($oldrecs) {
        $status = ENT_MATCH_NO_ID_USERNAME_LASTNAME_FIRSTNAME;
        return array_shift($oldrecs);
    }

    // Assuming matching IDNumber and all name parts is good : username not matching, will be updated to new.
    $oldrec = $DB->get_record_select('user', " idnumber = ? AND LOWER(firstname) = ? AND LOWER(lastname) = ? ", array($newuser->idnumber, strtolower($newuser->firstname), strtolower($newuser->lastname)));
    if ($oldrec) {
        $status = ENT_MATCH_ID_NO_USERNAME;
        return $oldrec;
    }

    // Failover : IDNumber and last name match, but not firstname. this may occur with misspelling.
    $oldrec = $DB->get_record_select('user', " idnumber = ? AND LOWER(lastname) = ? ", array($newuser->idnumber, strtolower($newuser->lastname)));
    if ($oldrec) {
        $status = ENT_MATCH_ID_LASTNAME_NO_USERNAME_FIRSTNAME;
        return $oldrec;
    }

    // failover : Only lastname and firstname match, but we might have more than one records.
    // Too dangerous option. This will merge real homonyms.
    /*
    $oldrecs = $DB->get_records_select('user', " LOWER(firstname) = ? AND LOWER(lastname) = ? ", array(strtolower($newuser->firstname), strtolower($newuser->lastname)));
    if ($oldrecs) {
        $status = ENT_MATCH_NO_ID_NO_USERNAME_LASTNAME_FIRSTNAME;
        return array_shift($oldrecs);
    }
    */

    $status = ENT_NO_MATCH;
    return null;
}

/**
 * Bulk insert in SQL's temp table
 */
function local_ent_installer_ldap_bulk_insert($username, $usertype, $timemodified) {
    global $DB, $CFG;

    $username = core_text::strtolower($username); // usernames are __always__ lowercase.
    if (!$DB->record_exists('tmp_extuser', array('username' => $username,
                                                'mnethostid' => $CFG->mnet_localhost_id,
                                                'usertype' => $usertype))) {
        $DB->insert_record_raw('tmp_extuser', array('username' => $username,
                                                    'mnethostid' => $CFG->mnet_localhost_id,
                                                    'usertype' => $usertype,
                                                    'lastmodified' => $timemodified), false, true);
    }
    echo '.';
}

/**
 * loads User Type special info fields definition
 * @return an array of info/custom field mappings
 */
function local_ent_installer_load_user_fields() {
    global $DB, $CFG;

    $USERFIELDS = array();

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'eleve'));
    assert($fieldid != 0);
    $USERFIELDS['eleve'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'parent'));
    assert($fieldid != 0);
    $USERFIELDS['parent'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'enseignant'));
    assert($fieldid != 0);
    $USERFIELDS['enseignant'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'administration'));
    assert($fieldid != 0);
    $USERFIELDS['administration'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'cdt'));
    assert($fieldid != 0);
    $USERFIELDS['cdt'] = $fieldid;

    // Academic info.

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'cohort'));
    assert($fieldid != 0);
    $USERFIELDS['cohort'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'transport'));
    assert($fieldid != 0);
    $USERFIELDS['transport'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'regime'));
    assert($fieldid != 0);
    $USERFIELDS['regime'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'fullage'));
    assert($fieldid != 0);
    $USERFIELDS['fullage'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'isprimaryassignation'));
    assert($fieldid != 0);
    $USERFIELDS['isprimaryassignation'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'personaltitle'));
    if (!$fieldid) {

        // Try to add the field if missing.
        require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

        $categoryname = ent_installer_string('academicinfocategoryname');
        $academicinfocategoryid = $DB->get_field('user_info_category', 'id', array('name' => $categoryname));
        $lastorder = $DB->get_field('user_info_field', 'MAX(sortorder)', array('categoryid' => $academicinfocategoryid));

        // Adding primary assignation.
        /*
         * Primary assignation should be marked if the Moodle node
         * matches the registered primary facility of the user in ldap attributes.
         */
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('personaltitle');
        $userfield->shortname = 'personaltitle';
        $userfield->datatype = 'text';
        $userfield->description = ent_installer_string('personaltitledesc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $lastorder + 1;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 10;
        $userfield->param2 = 10;
        $fieldid = $DB->insert_record('user_info_field', $userfield);
    }
    assert($fieldid != 0);
    $USERFIELDS['personaltitle'] = $fieldid;

    return $USERFIELDS;
}

/**
 * an utility function that explores the ldap ENTEtablissement object list to get proper institution id
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $search the search pattern
 * @param array $searchby where to search, either 'name' or 'city'
 * @return an array of objects with institution ID and institution name
 */
function local_ent_installer_ldap_search_institution_id($ldapauth, $search, $searchby = 'name') {
    global $LDAPQUERYTRACE;

    $ldapconnection = $ldapauth->ldap_connect();

    $context = get_config('local_ent_installer', 'structure_context');
    $config = get_config('local_ent_installer');

    // Just for tests.
    if (empty($context)) {
        $context = 'ou=structures,dc=atrium-paca,dc=fr';
    }

    if ($searchby == 'name') {

        if ($search != '*') {
            $search = '*'.$search.'*';
        }

        $filter = str_replace('%SEARCH%', $search, $config->structure_name_filter);
    } else if ($searchby == 'city') {

        if ($search != '*') {
            $search = '*'.$search.'*';
        }

        $filter = str_replace('%SEARCH%', $search, $config->structure_city_filter);
    } else {
        // Search by id.
        $filter = '('.$config->structure_id_attribute.'='.$search.')';
    }

    $structureid = $config->structure_id_attribute;
    $structurename = $config->structure_name_attribute;
    $structurecity = $config->structure_city_attribute;

    // Just for tests.
    if (empty($structurename)) {
        $structurename = 'ENTStructureNomCourant';
    }

    list($usec, $sec) = explode(' ',microtime());
    $pretick = (float)$sec + (float)$usec;

    // Search only in this context.
    echo "Searching in $context where $filter for ($structureid, $structurename, $structurecity) <br/>";
    $ldap_result = @ldap_search($ldapconnection, $context, $filter, array($structureid, $structurename, $structurecity));
    list($usec, $sec) = explode(' ',microtime()); 
    $posttick = (float)$sec + (float)$usec;

    $LDAPQUERYTRACE = $posttick - $pretick. ' s. ('.$context.' '.$filter.' ['.$structureid.','.$structurename.','.$structurecity.'])';

    if (!$ldap_result) {
        return '';
    }

    $results = array();
    if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
        do {
            $institution = new StdClass();

            $value = ldap_get_values_len($ldapconnection, $entry, $structureid);
            $institution->id = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structurename);
            $institution->name = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structurecity);
            $institution->city = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $results[] = $institution;

        } while ($entry = ldap_next_entry($ldapconnection, $entry));
    }
    unset($ldap_result); // Free mem.

    return $results;
}

/**
 * Reads user information from ldap and returns it in array()
 *
 * Function should return all information available. If you are saving
 * this information to moodle user-table you should honor syncronization flags
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $username username
 * @param array $options an array with CLI input options
 *
 * @return mixed array with no magic quotes or false on error
 */
function local_ent_installer_get_userinfo($ldapauth, $username, $options = array()) {
    static $entattributes;

    $config = get_config('local_ent_installer');

    // Load some cached static data.
    if (!isset($entattributes)) {
        // aggregate additional ent specific attributes that hold interesting information
        $configattribs = get_config('local_ent_installer', 'ent_userinfo_attributes');
        if (empty($configattribs)) {
            $entattributes = array('ENTPersonFonctions',
                                   'ENTPersonJointure',
                                   'personalTitle',
                                   'ENTPersonStructRattach',
                                   'ENTEleveClasses',
                                   'ENTEleveTransport',
                                   'ENTEleveRegime',
                                   'ENTEleveMajeur',
                                   'ENTPersonDateSuppression',
                                   'seeAlso');
        } else {
            $entattributes = explode(',', $configattribs);
        }
    }

    $extusername = core_text::convert($username, 'utf-8', $ldapauth->config->ldapencoding);

    $ldapconnection = $ldapauth->ldap_connect();
    if (!($user_dn = $ldapauth->ldap_find_userdn($ldapconnection, $extusername))) {
        $ldapauth->ldap_close();
        return false;
    }

    $searchattribs = array();
    $attrmap = $ldapauth->ldap_attributes();
    foreach ($attrmap as $key => $values) {
        if (!is_array($values)) {
            $values = array($values);
        }
        foreach ($values as $value) {
            if (!in_array($value, $searchattribs)) {
                array_push($searchattribs, $value);
            }
        }
    }

    foreach ($entattributes as $value) {
        if (!in_array($value, $searchattribs)) {
            array_push($searchattribs, $value);
            // Add attributes to $attrmap so they are pulled down into final user object.
            $attrmap[$value] = strtolower($value);
        }
    }

    if ($options['verbose']) {
        mtrace("Getting $user_dn for ".implode(',', $searchattribs));
    }
    if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $searchattribs)) {
        $ldapauth->ldap_close();
        return false;
    }

    $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
    if (empty($user_entry)) {
        $ldapauth->ldap_close();
        return false; // Entry not found.
    }

    $result = array();
    foreach ($attrmap as $key => $values) {
        if (!is_array($values)) {
            $values = array($values);
        }
        $ldapval = null;
        foreach ($values as $value) {
            $entry = array_change_key_case($user_entry[0], CASE_LOWER);

            if (($value == 'dn') || ($value == 'distinguishedname')) {
                $result[$key] = $user_dn;
                continue;
            }

            if (!array_key_exists($value, $entry)) {
                if ($options['verbose']) {
                    mtrace("Requested value $value but missing in record");
                }
                continue; // Wrong data mapping!
            }

            if (is_array($entry[$value])) {
                $newval = core_text::convert($entry[$value][0], $ldapauth->config->ldapencoding, 'utf-8');
            } else {
                $newval = core_text::convert($entry[$value], $ldapauth->config->ldapencoding, 'utf-8');
            }

            if (!empty($newval)) { // Favour ldap entries that are set.
                $ldapval = $newval;
            }
        }
        if (!is_null($ldapval)) {
            $result[$key] = $ldapval;
        }
    }

    $ldapauth->ldap_close();
    return $result;
}

/**
 * Reads user information from ldap and returns it in an object
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $username username (with system magic quotes)
 * @param array $options an array with CLI input options
 * @return mixed object or false on error
 */
function local_ent_installer_get_userinfo_asobj($ldapauth, $username, $options = array()) {

    $user_array = local_ent_installer_get_userinfo($ldapauth, $username, $options);

    if ($user_array == false) {
        return false; //error or not found
    }

    $user_array = truncate_userinfo($user_array);
    $user = new stdClass();
    foreach ($user_array as $key => $value) {
        $user->{$key} = $value;
    }
    return $user;
}

/**
 * add user to cohort after creating cohort if missing and removing to eventual 
 * other cohort.
 * Cohorts are handled in the 'local_ent_installer' component scope and will NOT interfere
 * with locally manually created cohorts.
 * Old cohorts from a preceding session might be protected by switching their component
 * scope to somethin else than 'local_ent_installer'. This will help keeping students from preceding
 * sessions in those historical cohorts.
 * @param int $userid the user id
 * @param string $cohortidentifier a fully qualified cohort or single qualified name (SDET compliant).
 *
 * return cohort short name
 */
function local_ent_installer_check_cohort($userid, $cohortidentifier) {
    global $DB;

    $config = get_config('local_ent_installer');

    $cohortix = $config->cohort_ix;

    if (strpos($cohortidentifier, '$') !== false) {
        // This is a full qualified cohort name.
        list($fooinstitutionid, $cohortname) = explode('$', $cohortidentifier);
        $idnumber = $config->institution_id.'$'.$cohortname;
    } else {
        $cohortname = $cohortidentifier;
        $idnumber = $config->institution_id.'$'.$cohortidentifier;
    }

    if (empty($config->create_cohorts_from_user_records)) {
        // Just give cohort short name from ENTClasses for feeding user profile field.
        // Real cohort assignation will be done further by the cohort synchronisation.
        return $cohortname;
    }

    $now = time();
    // If we have an explicit cohort prefix for the course session, add it to identifying fields.
    $cohortix = get_config('local_ent_installer', 'cohort_ix');
    if (!empty($cohortix)) {
        $cohortname = $cohortix.'_'.$cohortname;
        $idnumber = $cohortix.'_'.$institutionid.'$'.$cohortname;
    }

    if (!$cohort = $DB->get_record('cohort', array('name' => $cohortname))) {

        $systemcontext = context_system::instance();
        $cohort = new StdClass();
        $cohort->name = $cohortname;
        $cohort->contextid = $systemcontext->id;
        $cohort->idnumber = $institutionid.'$'.$cohortname;
        $cohort->description = '';
        $cohort->descriptionformat = 0;
        $cohort->component = 'local_ent_installer';
        $cohort->timecreated = $now;
        $cohort->timemodified = $now;
        $cohort->id = $DB->insert_record('cohort', $cohort);
        mtrace('Creating new cohort of IDNumber '.$cohort->idnumber);
    }

    $sql = "
        DELETE FROM
            {cohort_members}
        WHERE
            userid = ? AND
            cohortid IN (SELECT id FROM {cohort} WHERE component = 'local_ent_installer' AND name LIKE ?)
    ";

    $DB->execute($sql, array($userid, $cohortix.'%'));

    $membership = new StdClass();
    $membership->cohortid = $cohort->id;
    $membership->userid = $userid;
    $membership->timeadded = $now;

    // TODO : Reinforce weird cases of collisions with old cohorts if cohort prefix
    // accidentally not set
    $DB->insert_record('cohort_members', $membership);
    mtrace('Registering in cohort '.$cohort->idnumber);

    return $cohortname;
}

function local_ent_installer_update_info_data($userid, $fieldid, $data) {
    global $DB;

    if (!$oldrec = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => $fieldid))) {
        $userinfodata = new StdClass;
        $userinfodata->fieldid = $fieldid;
        $userinfodata->userid = $userid;
        $userinfodata->data = ''.$data; // protect against null fields
        $DB->insert_record('user_info_data', $userinfodata);
    } else {
        $oldrec->data = ''.$data;
        $DB->update_record('user_info_data', $oldrec);
    }
}

/**
 * Provides an uniform scheme for a teacher category identifier.
 * @param object $user a user object
 * @return string
 */
function local_ent_get_teacher_cat_idnumber($user) {
    $teachercatidnum = strtoupper($user->lastname).'_'.substr(strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';

    return $teachercatidnum;
}

/**
 * make a course category for the teacher and give full control to it
 *
 *
 */
function local_ent_installer_make_teacher_category($user) {
    global $DB, $CFG;

    require_once $CFG->dirroot.'/course/lib.php';

    $teacherstubcategory  = get_config('local_ent_installer', 'teacher_stub_category');

    if (!$teacherstubcategory) {
        mtrace("No stub");
        return;
    }

    $teachercatidnum = local_ent_get_teacher_cat_idnumber($user);

    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

    if (!$oldcat = $DB->get_record('course_categories', array('idnumber' => $teachercatidnum))) {
        $category = new StdClass();
        $category->name = local_ent_installer_teacher_category_name($user);
        $category->idnumber = $teachercatidnum;
        $category->parent = $teacherstubcategory;
        $category->visible = 1;
        $category = coursecat::create($category);

        role_assign($managerrole->id, $user->id, $category->get_context()->id);
    } else {
        // Rehab the old existing category and ensure it is active.
        $oldcat->parent = $teacherstubcategory;
        $oldcat->visible = 1;
        $DB->update_record('course_categories', $oldcat);

        $catcontext = context_coursecat::instance($oldcat->id);
        role_assign($managerrole->id, $user->id, $catcontext->id);
    }
}

/**
 * Provides an uniforme naming scheme for a teacher category
 * @param object $user
 * @return string
 */
function local_ent_installer_teacher_category_name($user) {
    static $USERFIELDS;
    global $DB;

    if (empty($USERFIELDS)) {
        // Initialise once.
        $USERFIELDS = local_ent_installer_load_user_fields();
    }

    $config = get_config('local_ent_installer');

    if (!empty($config->teacher_mask_firstname)) {
        // Initialize firstname
        preg_match_all('/[\wéèöëêôÏîàùç]+/u', $user->firstname, $matches);
        $firstnameinitials = '';
        foreach($matches as $resid => $res) {
            $firstnameinitials .= core_text::strtoupper(substr($res[0], 0, 1)).'.';
        }

        if (empty($user->personalTitle)) {
            $personaltitle = $DB->get_field('user_info_data', 'data', array('fieldid' => $USERFIELDS['personaltitle'], 'userid' => $user->id));
        }

        $name = $personaltitle.' '.$firstnameinitials.' '.$user->lastname;
    } else {
        $name = fullname($user);
    }
    return $name;
}

/**
 * Fix categories idnumbers to help reordering. We find teacher owner of category interrogating
 * the role assignements
 */
function local_ent_installer_fix_teacher_categories() {
    global $DB;

    $config = get_config('local_ent_installer');

    if (empty($config->teacher_stub_category)) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Teacher category unconfigured");
        }
        return;
    }

    // Get all categories in the teacher's stub.
    if (!$DB->get_record('course_categories', array('id' => $config->teacher_stub_category))) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Teacher category missing");
        }
        return;
    }

    $allcats = $DB->get_records('course_categories', array('parent' => $config->teacher_stub_category), 'sortorder', 'id,idnumber,sortorder');
    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
    if ($allcats) {
        foreach ($allcats as $cat) {
            $catcontext = context_coursecat::instance($cat->id);
            $managers = $DB->get_records('role_assignments', array('roleid' => $managerrole->id, 'contextid' => $catcontext->id));
            if (count($managers) > 1) {
                mtrace("Warning : More than one in category $cat->id : $cat->name");
            }
            if ($managers) {
                // We usually expect one manager here
                $first = array_shift($managers);
                $user = $DB->get_record('user', array('id' => $first->userid));
                $teachercatidnum = core_text::strtoupper($user->lastname).'_'.core_text::substr(core_text::strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';
                $DB->set_field('course_categories', 'idnumber', $teachercatidnum, array('id' => $cat->id));
                $DB->set_field('course_categories', 'name', local_ent_installer_teacher_category_name($user), array('id' => $cat->id));
            }
        }
    }
    local_ent_installer_reorder_teacher_categories();
}

/**
 * Reorders teachers category based on teacher name stored in idnumber
 */
function local_ent_installer_reorder_teacher_categories() {
    global $DB;

    $teacherrootcat = get_config('local_ent_installer', 'teacher_stub_category');

    if (empty($teacherrootcat)) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Ordering : Teacher category unconfigured");
        }
        return;
    }

    if (!$teacherstub = $DB->get_record('course_categories', array('id' => $teacherrootcat))) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Ordering : Teacher category missing");
        }
        return;
    }

    $sort = 'idnumber';
    $cattosort = coursecat::get($teacherrootcat, MUST_EXIST, true);
    \core_course\management\helper::action_category_resort_subcategories($cattosort, $sort, true);
}

/**
 * releases all non matching en_installer specific cohorts with the year prefix.
 * release them removing the component name so turn back the cohorts to manual handling
 */
function local_ent_installer_release_old_cohorts() {
    global $DB;

    $config = get_config('local_ent_installer');

    if (!empty($config->cohort_ix)) {
        $sql = '
            UPDATE
               {cohort}
            SET
                component = ""
            WHERE
                component = "local_ent_installer" AND
                name NOT LIKE ?
        ';
    
        $DB->execute($sql, array($config->cohort_ix.'%'));
    }
}

/**
 * checks if the archive cateogry exists in Moodle. Creates it if not.
 *
 */
function ent_installer_check_archive_category_exists() {
    global $DB;

    if (!$archive = $DB->get_record('course_categories', array('idnumber' => 'ARCHIVE'))) {
        $archcat = new StdClass();
        $archcat->name = get_string('defaultarchivecatname', 'local_ent_installer');
        $archcat->idnumber = 'ARCHIVE';
        $archcat->visible = 0;
        $DB->insert_record('course_categories', $archcat);
    }
}

/**
 * Checks if a user teacher category needs to be archived.
 * @param object $user a user being deleted or discarded from the platform
 */
function ent_installer_check_category_archiving($user) {
    global $DB;

    $archivecat = $DB->get_record('course_categories', array('idnumber' => 'ARCHIVE'));

    // Check we have a owned catefory in the place.
    $catidnumber = local_ent_get_teacher_cat_idnumber($user);

    // Archive category if found.
    if ($teachercat = $DB->get_record('course_categories', array('idnumber' => $catidnumber))) {
        $teachercat->parent = $archivecat->id;
        $DB->update_record('course_categories', $teachercat);
    }
}

/**
 * Matches the current structure ID with the attached structure
 * @param string $personstructure the fill value of ENTPersonStructRattach field
 * @return boolean true if matches the current config setting
 */
function local_ent_installer_match_structure($personstructure) {

    $config = get_config('local_ent_installer');

    if (preg_match('/ENTStructureUAI=(.{8})/', $personstructure, $matches)) {
        $uai = $matches[1];
        mtrace("Catched uai $uai vs. $config->institution_id ");
        return $uai == $config->institution_id;
    }

    return false;
}

/**
 * this function merges the incoming siteadmins ids with the non synchronized
 * existing siteadmins.
 * @params array $newadmins an array of new admins ids to merge in.
 */
function local_ent_installer_merge_siteadmins($newadmins, $options = array()) {
    global $DB;
    static $config;

    if (!isset($config)) {
        // Cache config in static for performance.
        $config = get_config('local_ent_installer');
    }

    $oldadmins = array();
    if ($oldadminlist = $DB->get_field('config', 'value', array('name' => 'siteadmins'))) {

        $oldadmins = explode(',', $oldadminlist);

        // First remove all site admins having ent_installer auth method.
        foreach ($oldadmins as $oldid) {
            if ($auth = $DB->get_field('user', 'auth', array('id' => $oldid))) {
                if (!in_array($oldid, $newadmins)) {
                    if (($auth != $config->real_used_auth) || empty($options['force'])) {
                        // If not a synced user and was admin, maintain it.
                        /*
                         * If we are not playing 'forced' mode, just add new to existing list, or we may
                         * revoke accidentally old users.
                         */
                        $newadmins[] = $oldid;
                    }
                }
            }
        }
    }

    sort($newadmins);

    $newadminlist = implode(',', $newadmins);

    if (empty($options['simulate'])) {
        $DB->set_field('config', 'value', $newadminlist, array('name' => 'siteadmins'));
        mtrace(get_string('mergesiteadmins', 'local_ent_installer', $newadminlist));
    } else {
        mtrace('[SIMULATION] '.get_string('mergesiteadmins', 'local_ent_installer', $newadminlist));
    }

    cache_helper::invalidate_by_definition('core', 'config');
}

function sortbyidnumber($a, $b) {
    if ($a->idnumber > $b->idnumber) return 1;
    if ($a->idnumber < $b->idnumber) return -1;
    return 0;
}

function local_ent_installer_ensure_global_cohort_exists($type, $options) {
    global $DB;

    $config = get_config('local_ent_installer');
    $defaultidnums = array(
        'students' => 'ELE',
        'staff' => 'ENS',
        'admins' => 'ADM'
    );

    if (!in_array($type, array_keys($defaultidnums))) {
        return;
    }

    $key = $type.'_site_cohort_name';
    if (!empty($config->$key)) {
        $cohortname = $config->$key;
        if (!$oldcohort = $DB->get_record('cohort', array('name' => $config->$key))) {

            if (strpos($cohortname, '/') !== false) {
                // We have idnumber / name scheme.
                list($idnumber, $cname) = explode('/', $cohortname);
                $idnumber = $config->institution_id.'$'.$idnumber;
            } else {
                $cname = $cohortname;
                $idnumber = $config->institution_id.'$'.$defaultsidnums($type);
            }
            $cohort = new StdClass;
            $cohort->name = $cname;
            $cohort->idnumber = $idnumber;
            $cohort->description = '';
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->timecreated = time();
            $cohort->timemodified = time();
            // Do not assign this cohort to local_ent_installer component.
            // We do not want these cohorts being droped by synchronisation.
            $cohort->component = '';
            $cohort->contextid = context_system::instance()->id;
            $cohort->id = $DB->insert_record('cohort', $cohort);
            if (!empty($options['verbose'])) {
                mtrace("Creating missing global cohort for $type");
            }
            return $cohort->id;
        } else {
            return $oldcohort->id;
        }
    }
}