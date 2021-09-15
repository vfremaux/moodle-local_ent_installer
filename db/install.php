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
 * Form for activating manual resync.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mnet/lib.php');

if (is_dir($CFG->dirroot.'/local/vmoodle')) {
    require_once($CFG->dirroot.'/local/vmoodle/plugins/plugins/pluginscontrolslib.php');
}

require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

/**
 * will provide all ent specific initializers after install
 *
 */
function xmldb_local_ent_installer_install() {
    global $DB, $CFG;

    mtrace("Installing local distribution configurations");

    if (is_dir($CFG->dirroot.'/local/vmoodle')) {
        /*
         * initalize MNET and ensure providing a first key
         * Unfortunately, during initial install, a suitable key pair WILL NOT be generated.
         * This will be fixed by further fix_config.php script.
         */
        $mnet = get_mnet_environment();
        $mnet->init();

        // Init mnet auth.
        $authcontrol = new auth_remote_control('mnet');
        $authcontrol->action('enable');

        // Disable IMS module.
        $authcontrol = new mod_remote_control('imscp');
        $authcontrol->action('disable');

        // Disable Etherpad module (Etherpad not installed).
        $authcontrol = new mod_remote_control('etherpad');
        $authcontrol->action('disable');

        // Enable multilang enhanced filter.
        $filtercontrol = new filter_remote_control('multilangenhanced');
        $filtercontrol->action('enable');
    }

    if (is_dir($CFG->dirroot.'/local/ent_access_point')) {

        // Marks academic platforms.
        // Initial categories.

        local_ent_installer_install_categories();

    }

    // Activate filters.
    if (is_dir($CFG->dirroot.'/filter/multilangenhanced')) {
        $lastorder = $DB->get_field('filter_active' , 'MAX(sortorder)', array('contextid' => 1));
        $filteractive = new StdClass;
        $filteractive->filter = 'filter/multilangenhanced';
        $filteractive->contextid = 1;
        $filteractive->active = 1;
        $filteractive->sortorder = $lastorder + 1;
        $DB->delete_records('filter_active', array('filter' => 'filter/multilangenhanced', 'contextid' => 1));
        $DB->insert_record('filter_active', $filteractive);
    }

    if (is_dir($CFG->dirroot.'/blocks/user_mnet_hosts')) {
        // Adding remote login capability to authenticated user.
        $contextsystemid = context_system::instance()->id;
        $cap = new StdClass();
        $cap->contextid = $contextsystemid;
        $cap->roleid = 7;
        $cap->capability = 'moodle/site:mnetlogintoremote';
        $cap->permission = 1;
        $cap->timemodified = time();
        $cap->modifierid = 2;
        $params = array('contextid' => $contextsystemid, 'roleid' => 7, 'capability' => 'moodle/site:mnetlogintoremote');
        if (!$DB->record_exists('role_capabilities', $params)) {
            $DB->insert_record('role_capabilities', $cap);
        }
    }

    // Adjust some fields length.

    $dbman = $DB->get_manager();

    $table = new xmldb_table('user');
    $field = new xmldb_field('department');
    $field->set_attributes(XMLDB_TYPE_CHAR, '126', null, null, null, null, 'institution');
    $dbman->change_field_precision($table, $field);

    // Adding student role as default role for home pages.

    if (is_dir($CFG->dirroot.'/local/ent_access_point')) {
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        set_config('defaultfrontpageroleid', $studentrole->id);
    }

    // Adding usertypes categorization.

    if (is_dir($CFG->dirroot.'/local/ent_access_point')) {

        // Academic sites are identified by this plugin.

        $categoryrec = new StdClass;
        $categoryrec->name = ent_installer_string('usertypecategoryname');
        if (!$oldcat = $DB->record_exists('user_info_category', array('name' => $categoryrec->name))) {
            $usertypecategoryid = $DB->insert_record('user_info_category', $categoryrec);
        } else {
            $usertypecategoryid = $oldcat->id;
        }

        // Adding usertypes for ENT model.

        $i = 0;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('usertypestudent');
        $userfield->shortname = 'eleve';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('usertypestudent_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $usertypecategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible    = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'eleve'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('usertypeteacher');
        $userfield->shortname = 'enseignant';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('usertypeteacher_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $usertypecategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'enseignant'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('usertypeparent');
        $userfield->shortname = 'parent';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('usertypeparent_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $usertypecategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible    = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'parent'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('usertypestaff');
        $userfield->shortname = 'administration';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('usertypestaff_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $usertypecategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible    = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'administration'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('usertypeworkmanager');
        $userfield->shortname = 'cdt';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('usertypeworkmanager_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $usertypecategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'cdt'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        // Adding academic information.

        $categoryrec = new StdClass;
        $categoryrec->name = ent_installer_string('academicinfocategoryname');
        $academicinfocategoryid = $DB->insert_record('user_info_category', $categoryrec);

        $i = 0;
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('transport');
        $userfield->shortname = 'transport';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('transport_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible    = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'transport'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;

        $userfield = new StdClass;
        $userfield->name = ent_installer_string('cohort');
        $userfield->shortname = 'cohort';
        $userfield->datatype = 'text';
        $userfield->description    = ent_installer_string('cohort_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 30;
        $userfield->param2 = 32;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'cohort'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        $i++;

        $userfield = new StdClass;
        $userfield->name = ent_installer_string('regime');
        $userfield->shortname = 'regime';
        $userfield->datatype = 'text';
        $userfield->description = ent_installer_string('regime_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 30;
        $userfield->param2 = 128;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'regime'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        // Add fullage.

        $i++;

        $userfield = new StdClass;
        $userfield->name = ent_installer_string('fullage');
        $userfield->shortname = 'fullage';
        $userfield->datatype = 'text';
        $userfield->description = ent_installer_string('fullage_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 10;
        $userfield->param2 = 10;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'fullage'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        // Adding primary assignation.
        /*
         * Primary assignation should be marked if the Moodle node
         * matches the registered primary facility of the user in ldap attributes.
         */
        $i++;

        $userfield = new StdClass;
        $userfield->name = ent_installer_string('isprimaryassignation');
        $userfield->shortname = 'isprimaryassignation';
        $userfield->datatype = 'checkbox';
        $userfield->description = ent_installer_string('isprimaryassignation_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'isprimaryassignation'))) {
            $DB->insert_record('user_info_field', $userfield);
        }

        // Adding personaltitle.
        $i++;

        $userfield = new StdClass;
        $userfield->name = ent_installer_string('personaltitle');
        $userfield->shortname = 'personaltitle';
        $userfield->datatype = 'text';
        $userfield->description = ent_installer_string('personaltitle_desc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $i;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 10;
        $userfield->param2 = 10;
        if (!$DB->record_exists('user_info_field', array('shortname' => 'personaltitle'))) {
            $DB->insert_record('user_info_field', $userfield);
        }
    }

    return true;
}