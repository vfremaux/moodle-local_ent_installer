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

require_once($CFG->dirroot.'/mnet/lib.php');
require_once($CFG->dirroot.'/blocks/vmoodle/plugins/plugins/pluginscontrolslib.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

/**
 * will provide all ent specific initializers after install
 *
 */
function xmldb_local_ent_installer_install() {
    global $DB, $CFG;

    mtrace("Installing local distribution configurations");

    /**
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

    // lang package should be installed on automated install asking for french language.

    // disable lang menu
    set_config('langmenu', 0);

    // ## Publishflow

    // Initiate publishflow platform type.
    set_config('moodlenodetype', 'factory,catalog', 'block_publishflow');

    // Change frontpage.
    set_config('frontpage', '');

    // Change frontpage.
    set_config('forcelogin', 1);

    // Initiate publishflow retrofit mode for common
    set_config('enableretrofit', 1, 'block_publishflow');

    // Initiate publishflow files delivery.
    set_config('coursedeliveryislocal', 1, 'block_publishflow');

    // Initiate publishflow categories.
    set_config('deploycategory', 1, 'block_publishflow');

    // Initiate publishflow categories.
    set_config('runningcategory', 1, 'block_publishflow');

    // Initiate publishflow categories.
    set_config('closedcategory', 1, 'block_publishflow');

    // Initiate publishflow topology refresh.
    set_config('networkrefreshautomation', 604800, 'block_publishflow');

    // Initial categories.

    if ($CFG->wwwroot == @$CFG->mainhostroot) {

        $categories = array(
            'Administration Moodle' => 'ADMIN',
            'Cours mutualisés' => '',
            'Cours mutualisés/Exemples de cours' => 'EXEMPLE',
            'Cours mutualisés/Cours déployables' => 'SHARED',
            'Espaces de travail inter-établissements' => 'WORKPLACES',
            'Gabarits et modèles' => 'TEMPLATES',
        );

    } else {
        $categories = array(
            'Administration Moodle' => 'ADMIN',
            'Corbeille' => 'ARCHIVE',
            'Espaces enseignants' => 'ACADEMIC'
        );
    }

    if ($categories) {
        foreach ($categories as $category => $catidnumber) {
            $parts = explode('/', $category);
            $maxdepth = count($parts);
            $parentid = 0;
            $depth = 1;
            $path = '';
            foreach ($parts as $part) {
                if (!$thiscat = $DB->get_record('course_categories', array('name' => $part))) {
                    // Do not try to create them twice or more times.
                    $catrec = new StdClass();
                    $catrec->parent = $parentid;
                    $catrec->visible = 1;
                    $catrec->visibleold = 1;
                    $catrec->timemodified = time();
                    $catrec->depth = $depth;
                    $catrec->name = $part;
                    if ($depth == $maxdepth) {
                        $catrec->idnumber = $catidnumber;
                    }
                    $parentid = $DB->insert_record('course_categories', $catrec);
                    $path = $path . '/'.$parentid;
                    // Post update the path chen knowning the inserted ID.
                    $DB->set_field('course_categories', 'path', $path, array('id' => $parentid));
                    $depth++;
                } else {
                    $parentid = $thiscat->id;
                    $depth++;
                    $path = $path . '/'.$parentid;
                }
            };
        }

        if ($CFG->wwwroot != @$CFG->mainhostroot) {
            // Fix stub category id, where teacher owned categories will be created. This is only for subsites.
            $stubcat = $DB->get_record('course_categories', array('idnumber' => 'ACADEMIC'));
            set_config('teacher_stub_category', $stubcat->id, 'local_ent_installer');
        }
    }

    // Sharedresource.

    // Initiate sharedresource model.
    set_config('schema', 'scolomfr', 'sharedresource');

    // activate filters
    $lastorder = $DB->get_field('filter_active' , 'MAX(sortorder)', array('contextid' => 1));
    $filteractive = new StdClass;
    $filteractive->filter = 'filter/multilangenhanced';
    $filteractive->contextid = 1;
    $filteractive->active = 1;
    $filteractive->sortorder = $lastorder + 1;
    $DB->delete_records('filter_active', array('filter' => 'filter/multilangenhanced', 'contextid' => 1));
    $DB->insert_record('filter_active', $filteractive);

    // ## enhanced my

    // Enable enhanced my.
    set_config('enable', 1, 'local_my');

    set_config('force', 1, 'local_my');

    // Enable my pinting categories.
    set_config('printcategories', 1, 'local_my');

    // Initialising my enhanced module list.
    set_config('modules', "my_caption\nme\nmy_courses\ncourse_areas\navailable_courses", 'local_my');
    set_config('teachermodules', "me\nauthored_courses", 'local_my');
    set_config('heatmaprange', 6, 'local_my');

    // Adding remote login capability to authenticated user
    $contextsystemid = context_system::instance()->id;
    $cap = new StdClass();
    $cap->contextid = $contextsystemid;
    $cap->roleid = 7;
    $cap->capability = 'moodle/site:mnetlogintoremote';
    $cap->permission = 1;
    $cap->timemodified = time();
    $cap->modifierid = 2;
    if (!$DB->record_exists('role_capabilities', array('contextid' => $contextsystemid, 'roleid' => 7, 'capability' => 'moodle/site:mnetlogintoremote'))) {
        $DB->insert_record('role_capabilities', $cap);
    }

    // ## Adjust some fields length

    $dbman = $DB->get_manager();

    $table = new xmldb_table('user');
    $field = new xmldb_field('department');
    $field->set_attributes(XMLDB_TYPE_CHAR, '126', null, null, null, null, 'institution');
    $dbman->change_field_precision($table, $field);

    // ## Adding usertypes categorization

    $categoryrec = new StdClass;
    $categoryrec->name = ent_installer_string('usertypecategoryname');
    if (!$oldcat = $DB->record_exists('user_info_category', array('name' => $categoryrec->name))) {
        $usertypecategoryid = $DB->insert_record('user_info_category', $categoryrec);
    } else {
        $usertypecategoryid = $oldcat->id;
    }

    // ## Adding usertypes for ENT model

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

    // ## Adding academic information

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

    // Add fullage

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
    // Primary assignation should be marked if the Moodle node
    // matches the registered primary facility of the user in ldap attributes.
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

    // Adding personaltitle
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

    return true;
}