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
 * @package    ent_installer
 * @copyright  2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot.'/local/ent_installer/adminlib.php';

if ($hassiteconfig) {
    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_ent_installer', get_string('pluginname', 'local_ent_installer'));

    $settingurl = new moodle_url('/local/ent_installer/synctimereport.php');
    $settings->add(new admin_setting_heading('syncbench', get_string('syncbench', 'local_ent_installer'),
                   get_string('syncbenchreport_desc', 'local_ent_installer', $settingurl->out())));

    $settingurl = new moodle_url('/local/ent_installer/sync.php');
    $settings->add(new admin_setting_heading('syncusers', get_string('syncusers', 'local_ent_installer'),
                   get_string('syncusers_desc', 'local_ent_installer', $settingurl->out())));

    $settings->add(new admin_setting_heading('head0', get_string('datasyncsettings', 'local_ent_installer'), ''));

    $frequoptions = array(
        DAYSECS => get_string('onceaday', 'local_ent_installer'),
        7 * DAYSECS => get_string('onceaweek', 'local_ent_installer'),
        30 * DAYSECS => get_string('onceamonth', 'local_ent_installer'),
    );

    $key = 'local_ent_installer/sync_enable';
    $label = get_string('configsyncenable', 'local_ent_installer');
    $desc = get_string('configsyncenable_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/cron_enable';
    $label = get_string('configcronenable', 'local_ent_installer');
    $desc = get_string('configcronenable_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $keyhour = 'local_ent_installer/cron_hour';
    $keymin = 'local_ent_installer/cron_min';
    $label = get_string('configcrontime', 'local_ent_installer');
    $desc = '';
    $defaults = array('h' => get_config('local_ent_installer','cron_hour'),
                      'm' => get_config('local_ent_installer', 'cron_min'));
    $settings->add(new admin_setting_configtime($keyhour, $keymin, $label, $desc, $defaults));

    $key = 'local_ent_installer/institution_id';
    $label = get_string('configinstitutionid', 'local_ent_installer');
    $desc = get_string('configinstitutionid_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/cohort_ix';
    $label = get_string('configcohortindex', 'local_ent_installer');
    $desc = get_string('configcohortindex_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/cohort_sort_prefix_length';
    $label = get_string('configcohortsortprefixlength', 'local_ent_installer');
    $desc = get_string('configcohortsortprefixlength_desc', 'local_ent_installer');
    $default = 5;
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/last_sync_date';
    $label = get_string('configlastsyncdate', 'local_ent_installer');
    $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

    $authplugins = get_enabled_auth_plugins(true);
    $authoptions = array();
    foreach ($authplugins as $authname) {
        $authoptions[$authname] = get_string('pluginname', 'auth_'.$authname);
    }
    $key = 'local_ent_installer/real_used_auth';
    $label = get_string('configrealauth', 'local_ent_installer');
    $desc = get_string('configrealauth_desc', 'local_ent_installer');
    $default = 'ldap';
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $authoptions));

    $maildisplayoptions = array();
    $maildisplayoptions['0'] = get_string('emaildisplayno');
    $maildisplayoptions['1'] = get_string('emaildisplayyes');
    $maildisplayoptions['2'] = get_string('emaildisplaycourse');
    $key = 'local_ent_installer/initialmaildisplay';
    $label = get_string('configmaildisplay', 'local_ent_installer');
    $desc = get_string('configmaildisplay_desc', 'local_ent_installer');
    $default = '0';
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $maildisplayoptions));

    $key = 'local_ent_installer/fake_mail_domain';
    $label = get_string('configfakemaildomain', 'local_ent_installer');
    $desc = get_string('configfakemaildomain_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/build_teacher_category';
    $label = get_string('configbuildteachercategory', 'local_ent_installer');
    $desc = get_string('configbuildteachercategory_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $categoryoptions = $DB->get_records_menu('course_categories', array(), 'parent,sortorder', 'id, name');
    $key = 'local_ent_installer/teacher_stub_category';
    $label = get_string('configteacherstubcategory', 'local_ent_installer');
    $desc = get_string('configteacherstubcategory_desc', 'local_ent_installer');
    $default = 'ldap';
    $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $categoryoptions));

    $key = 'local_ent_installer/teacher_mask_firstname';
    $label = get_string('configteachermaskfirstname', 'local_ent_installer');
    $desc = get_string('configteachermaskfirstname_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/update_institution_structure';
    $label = get_string('configupdateinstitutionstructure', 'local_ent_installer');
    $desc = get_string('configupdateinstitutionstructure_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/create_students_site_cohort';
    $label = get_string('configcreatestudentssitecohort', 'local_ent_installer');
    $desc = get_string('configcreatestudentssitecohort_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/students_site_cohort_name';
    $label = get_string('configstudentssitecohortname', 'local_ent_installer');
    $desc = get_string('configstudentssitecohortname_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/create_staff_site_cohort';
    $label = get_string('configcreatestaffsitecohort', 'local_ent_installer');
    $desc = get_string('configcreatestaffsitecohort_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/staff_site_cohort_name';
    $label = get_string('configstaffsitecohortname', 'local_ent_installer');
    $desc = get_string('configstaffsitecohortname_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/admins_site_cohort_name';
    $label = get_string('configadminssitecohortname', 'local_ent_installer');
    $desc = get_string('configadminssitecohortname_desc', 'local_ent_installer');
    $default = 'Administrators';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/create_cohorts_from_user_records';
    $label = get_string('configcreatecohortsfromuserrecords', 'local_ent_installer');
    $desc = get_string('configcreatecohortsfromuserrecords_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    /* **************************** Entities synchronisation **************************** */

    include($CFG->dirroot.'/local/ent_installer/settings/users_settings.php');

    include($CFG->dirroot.'/local/ent_installer/settings/cohorts_settings.php');

    include($CFG->dirroot.'/local/ent_installer/settings/roleassigns_settings.php');

    include($CFG->dirroot.'/local/ent_installer/settings/coursegroups_settings.php');

    /* **************************** Structure seek **************************** */

    $settings->add(new admin_setting_heading('head5', get_string('structuresearch', 'local_ent_installer'), ''));

    $key = 'local_ent_installer/structure_context';
    $label = get_string('configstructurecontext', 'local_ent_installer');
    $desc = get_string('configstructurecontext_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/structure_id_attribute';
    $label = get_string('configstructureid', 'local_ent_installer');
    $desc = get_string('configstructureid_desc', 'local_ent_installer');
    $default = 'ENTStructureUAI';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/structure_city_attribute';
    $label = get_string('configstructurecity', 'local_ent_installer');
    $desc = get_string('configstructurecity_desc', 'local_ent_installer');
    $default = 'l';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/structure_city_filter';
    $label = get_string('configstructurecityfilter', 'local_ent_installer');
    $desc = get_string('configstructurecityfilter_desc', 'local_ent_installer');
    $default = '(&(objectClass=ENTEtablissement)(l=%SEARCH%))';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/structure_name_attribute';
    $label = get_string('configstructurename', 'local_ent_installer');
    $desc = get_string('configstructurename_desc', 'local_ent_installer');
    $default = 'ENTDisplayName';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer/structure_name_filter';
    $label = get_string('configstructurenamefilter', 'local_ent_installer');
    $desc = get_string('configstructurenamefilter_desc', 'local_ent_installer');
    $default = '(&(objectClass=ENTEtablissement)(ENTDisplayName=%SEARCH%))';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'local_ent_installer_searchid';
    $label = get_string('configgetid', 'local_ent_installer');
    $getidstr = get_string('configgetinstitutionidservice', 'local_ent_installer');
    $html = '<a href="'.$CFG->wwwroot.'/local/ent_installer/getid.php">'.$getidstr.'</a>';
    $settings->add(new admin_setting_heading($key, $label, $html));

    $ADMIN->add('localplugins', $settings);
}

