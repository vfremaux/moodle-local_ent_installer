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

if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    require_once($CFG->dirroot.'/local/adminsettings/lib.php');
    list($hasconfig, $hassiteconfig, $capability) = local_adminsettings_access();
} else {
    // Standard Moodle code.
    $capability = 'moodle/site:config';
    $hasconfig = $hassiteconfig = has_capability($capability, context_system::instance());
}

require_once($CFG->dirroot.'/local/ent_installer/lib.php');
require_once($CFG->dirroot.'/local/ent_installer/adminlib.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/structures_settings.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/coursegroups_settings.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/courses_settings.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/roleassigns_settings.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/cohorts_settings.php');
require_once($CFG->dirroot.'/local/ent_installer/settings/users_settings.php');

if ($hasconfig && is_dir($CFG->dirroot.'/local/ent_installer')) {

    // Add a light weight resync service access to site managers.
    if (!$ADMIN->locate('automation')) {
        $ADMIN->add('root', new admin_category('automation', new lang_string('automation', 'local_ent_installer')));
    }

    $settings = new admin_settingpage('local_ent_installer_light', get_string('entupdate', 'local_ent_installer'));

    if (local_ent_installer_supports_feature() == 'pro') {
        include_once($CFG->dirroot.'/local/ent_installer/pro/prolib.php');
    }

    if ($ADMIN->fulltree) {
        if (local_ent_installer_supports_feature() == 'pro') {
            $PAGE->requires->js_call_amd('local_ent_installer/pro', 'init');
            $config = get_config('local_ent_installer');
            $check = \local_ent_installer\pro_manager::set_and_check_license_key(@$config->customerkey, @$config->provider, true);
            if (!preg_match('/SET OK/', $check)) {
                $licensemess = \local_ent_installer\pro_manager::print_empty_license_message();
                $settings->add(new admin_setting_heading('licensesatus', get_string('licensestatus', 'local_ent_installer'), $licensemess));
            }
        }
        $settingurl = new moodle_url('/local/ent_installer/synctimereport.php');
        $settings->add(new admin_setting_heading('syncbench', get_string('syncbench', 'local_ent_installer'),
                       get_string('syncbenchreport_desc', 'local_ent_installer', $settingurl->out())));

        $settingurl = new moodle_url('/local/ent_installer/sync.php');
        $settings->add(new admin_setting_heading('syncusers', get_string('syncusers', 'local_ent_installer'),
                       get_string('syncusers_desc', 'local_ent_installer', $settingurl->out())));

    }
    $ADMIN->add('automation', $settings);
}

if ($hassiteconfig) {

    $settingcategory = new admin_category('local_ent_installer', get_string('pluginname', 'local_ent_installer'));
    $ADMIN->add('localplugins', $settingcategory);

    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_ent_installer_generals', get_string('generals', 'local_ent_installer'));

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
    $defaults = array('h' => get_config('local_ent_installer', 'cron_hour'),
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

    $key = 'local_ent_installer/record_date_fieldname';
    $label = get_string('configrecorddatefieldname', 'local_ent_installer');
    $desc = get_string('configrecorddatefieldname_desc', 'local_ent_installer');
    $default = 'modifyTimestamp';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $formatoptions = array('unix' => 'Unix', 'ad' => 'Active Directory');
    $key = 'local_ent_installer/timestamp_format';
    $label = get_string('configtimestampformat', 'local_ent_installer');
    $desc = get_string('configtimestampformat_desc', 'local_ent_installer');
    $default = 'unix';
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $formatoptions));

    $key = 'local_ent_installer/timestamp_shift';
    $label = get_string('configtimestampshift', 'local_ent_installer');
    $desc = get_string('configtimestampshift_desc', 'local_ent_installer');
    $default = '0';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

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

    $key = 'local_ent_installer/protect_categories_from_relocate';
    $label = get_string('configprotectcategoriesfromrelocate', 'local_ent_installer');
    $desc = get_string('configprotectcategoriesfromrelocate_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

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

    $key = 'local_ent_installer/create_adminstaff_site_cohort';
    $label = get_string('configcreateadminstaffsitecohort', 'local_ent_installer');
    $desc = get_string('configcreateadminstaffsitecohort_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $key = 'local_ent_installer/adminstaff_site_cohort_name';
    $label = get_string('configadminstaffsitecohortname', 'local_ent_installer');
    $desc = get_string('configadminstaffsitecohortname_desc', 'local_ent_installer');
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

    $key = 'local_ent_installer/enrol_deans';
    $label = get_string('configenroldeans', 'local_ent_installer');
    $desc = get_string('configenroldeans_desc', 'local_ent_installer');
    $default = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

    $installcatsstr = get_string('configinstallcategories', 'local_ent_installer');
    $html = '<a href="'.$CFG->wwwroot.'/local/ent_installer/installcats.php">';
    $html .= '<input type="button" class="btn" value="'.$installcatsstr.'" /></a>';
    $settings->add(new admin_setting_heading('head6', get_string('sitecategories', 'local_ent_installer'), $html));

    $key = 'local_ent_installer/initialcategories';
    $label = get_string('configinitialcategories', 'local_ent_installer');
    $desc = get_string('configinitialcategories_desc', 'local_ent_installer');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, ''));

    $settings->add(new admin_setting_heading('head7', get_string('miscsettings', 'local_ent_installer'), ''));

    $key = 'local_ent_installer/composite_separator';
    $label = get_string('configcompositeseparator', 'local_ent_installer');
    $desc = get_string('configcompositeseparator_desc', 'local_ent_installer');
    $options = array('|' => '|', '/' => '/', ':' => ':', '#' => '#');
    $settings->add(new admin_setting_configselect($key, $label, $desc, '|', $options));

    $key = 'local_ent_installer/list_separator';
    $label = get_string('configlistseparator', 'local_ent_installer');
    $desc = get_string('configlistseparator_desc', 'local_ent_installer');
    $options = array('|' => '|', '/' => '/', ':' => ':', ',' => ',', ';' => ';');
    $settings->add(new admin_setting_configselect($key, $label, $desc, '|', $options));

    /* **************************** Entities synchronisation **************************** */

    $ADMIN->add('local_ent_installer', $settings);

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\users::settings());

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\courses::settings());

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\cohorts::settings());

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\roleassigns::settings());

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\coursegroups::settings());

    /* **************************** Structure seek **************************** */

    $ADMIN->add('local_ent_installer', \local_ent_installer\settings\structure::settings());

    if (local_ent_installer_supports_feature('emulate/community') == 'pro') {
        include_once($CFG->dirroot.'/local/ent_installer/pro/prolib.php');
        \local_ent_installer\pro_manager::add_settings($ADMIN, $settings);
    } else {
        $label = get_string('plugindist', 'local_ent_installer');
        $desc = get_string('plugindist_desc', 'local_ent_installer');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }
}

