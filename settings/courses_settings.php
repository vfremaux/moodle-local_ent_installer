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
 * settings for course synchronisation
 *
 * @package     local_ent_installer
 * @category    local
 * @copyright   2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_ent_installer\settings;

use \admin_setting_configdatetime;
use \admin_settingpage;
use \admin_setting_heading;
use \admin_setting_configtext;
use \admin_setting_configcheckbox;
use \admin_setting_configselect;

defined('MOODLE_INTERNAL') || die();

class courses {

    public static function settings() {

        $settings = new admin_settingpage('local_ent_installer_courses', get_string('settingscourses', 'local_ent_installer'));

        $settings->add(new admin_setting_heading('courseshdr', get_string('coursefilters', 'local_ent_installer'), ''));

        $key = 'local_ent_installer/sync_coursecat_enable';
        $label = get_string('configsynccoursecatsenable', 'local_ent_installer');
        $desc = '';
        $default = 1;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/last_sync_date_coursecats';
        $label = get_string('configlastsyncdate', 'local_ent_installer');
        $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_contexts';
        $label = get_string('configcoursecatcontexts', 'local_ent_installer');
        $desc = get_string('configcoursecatcontexts_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_selector_filter';
        $label = get_string('configcoursecatselectorfilter', 'local_ent_installer');
        $desc = get_string('configcoursecatselectorfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course category FQDN id */

        $key = 'local_ent_installer/coursecat_id_attribute';
        $label = get_string('configcoursecatidattribute', 'local_ent_installer');
        $desc = get_string('configcoursecatidattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course category idnumber */

        $key = 'local_ent_installer/coursecat_idnumber_attribute';
        $label = get_string('configcoursecatidnumberattribute', 'local_ent_installer');
        $desc = get_string('configcoursecatidnumberattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_idnumber_filter';
        $label = get_string('configcoursecatidnumberfilter', 'local_ent_installer');
        $desc = get_string('configcoursecatidnumberfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_idnumber_pattern';
        $label = get_string('configcoursecatidnumberpattern', 'local_ent_installer');
        $desc = get_string('configcoursecatidnumberpattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course category name */

        $key = 'local_ent_installer/coursecat_name_attribute';
        $label = get_string('configcoursecatnameattribute', 'local_ent_installer');
        $desc = get_string('configcoursecatnameattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_name_is_full_path';
        $label = get_string('configcoursecatnameisfullpath', 'local_ent_installer');
        $desc = get_string('configcoursecatnameisfullpath_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_parent_attribute';
        $label = get_string('configcoursecatparentattribute', 'local_ent_installer');
        $desc = get_string('configcoursecatparentattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_parent_filter';
        $label = get_string('configcoursecatparentfilter', 'local_ent_installer');
        $desc = get_string('configcoursecatparentfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/coursecat_parent_pattern';
        $label = get_string('configcoursecatparentpattern', 'local_ent_installer');
        $desc = get_string('configcoursecatparentpattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* ************************* courses ***************************** */

        $key = 'local_ent_installer/sync_course_enable';
        $label = get_string('configsynccourseenable', 'local_ent_installer');
        $desc = '';
        $default = 1;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/last_sync_date_courses';
        $label = get_string('configlastsyncdate', 'local_ent_installer');
        $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_contexts';
        $label = get_string('configcoursecontexts', 'local_ent_installer');
        $desc = get_string('configcoursecontexts_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_selector_filter';
        $label = get_string('configcourseselectorfilter', 'local_ent_installer');
        $desc = get_string('configcourseselectorfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course FQDN id fieldname in ldap */

        $key = 'local_ent_installer/course_id_attribute';
        $label = get_string('configcourseidattribute', 'local_ent_installer');
        $desc = get_string('configcourseidattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course id pattern using the primary id */

        $key = 'local_ent_installer/course_id_pattern';
        $label = get_string('configcourseidpattern', 'local_ent_installer');
        $desc = get_string('configcourseidpattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* Primary key match (moodle field) */

        $key = 'local_ent_installer/course_primary_key';
        $label = get_string('configcourseprimarykey', 'local_ent_installer');
        $desc = get_string('configcourseprimarykey_desc', 'local_ent_installer');
        $default = 'idnumber';
        $options = array(
            'shortname' => get_string('shortname'),
            'idnumber' => get_string('idnumber'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        /* course shortname */

        $key = 'local_ent_installer/course_shortname_attribute';
        $label = get_string('configcourseshortnameattribute', 'local_ent_installer');
        $desc = get_string('configcourseshortnameattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_shortname_filter';
        $label = get_string('configcourseshortnamefilter', 'local_ent_installer');
        $desc = get_string('configcourseshortnamefilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_shortname_pattern';
        $label = get_string('configcourseshortnamepattern', 'local_ent_installer');
        $desc = get_string('configcourseshortnamepattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course fullname */
        /* No filtering - no refactoring */

        $key = 'local_ent_installer/course_fullname_attribute';
        $label = get_string('configcoursefullnameattribute', 'local_ent_installer');
        $desc = get_string('configcoursefullnameattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course idnumber */

        $key = 'local_ent_installer/course_idnumber_attribute';
        $label = get_string('configcourseidnumberattribute', 'local_ent_installer');
        $desc = get_string('configcourseidnumberattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_idnumber_filter';
        $label = get_string('configcourseidnumberfilter', 'local_ent_installer');
        $desc = get_string('configcourseidnumberfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_idnumber_pattern';
        $label = get_string('configcourseidnumberpattern', 'local_ent_installer');
        $desc = get_string('configcourseidnumberpattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course category */

        $key = 'local_ent_installer/course_category_attribute';
        $label = get_string('configcoursecategoryattribute', 'local_ent_installer');
        $desc = get_string('configcoursecategoryattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_categorypath_separator';
        $label = get_string('configcoursecategorypathseparator', 'local_ent_installer');
        $desc = get_string('configcoursecategorypathseparator_desc', 'local_ent_installer');
        $default = '/';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_categorysyntax_attribute';
        $label = get_string('configcoursecategorysyntaxattribute', 'local_ent_installer');
        $desc = get_string('configcoursecategorysyntaxattribute_desc', 'local_ent_installer');
        $default = '';
        $options = array('composite' => get_string('categorysyntaxcomposite', 'local_ent_installer'),
                         'simplepath' => get_string('categorysyntaxsimplepath', 'local_ent_installer'),
                         'id' => get_string('categorysyntaxdirectid', 'local_ent_installer'),
                         'idnumber' => get_string('categorysyntaxidnumber', 'local_ent_installer'),
                         );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/course_default_category_idnumber';
        $label = get_string('configcoursedefaultcategoryidnumber', 'local_ent_installer');
        $desc = get_string('configcoursedefaultcategoryidnumber_desc', 'local_ent_installer');
        $default = '/';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course summary */
        /* No filtering - no refactoring */

        $key = 'local_ent_installer/course_summary_attribute';
        $label = get_string('configcoursesummaryattribute', 'local_ent_installer');
        $desc = get_string('configcoursesummaryattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course visibility */
        /* No filtering - no refactoring */

        $key = 'local_ent_installer/course_visible_attribute';
        $label = get_string('configcoursevisibleattribute', 'local_ent_installer');
        $desc = get_string('configcoursevisibleattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* course template */

        $key = 'local_ent_installer/course_template_attribute';
        $label = get_string('configcoursetemplateattribute', 'local_ent_installer');
        $desc = get_string('configcoursetemplateattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_template_filter';
        $label = get_string('configcoursetemplatefilter', 'local_ent_installer');
        $desc = get_string('configcoursetemplatefilter_desc', 'local_ent_installer');
        $default = '(.*)';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_template_pattern';
        $label = get_string('configcoursetemplatepattern', 'local_ent_installer');
        $desc = get_string('configcoursetemplatepattern_desc', 'local_ent_installer');
        $default = '%TPL%';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_template_default';
        $label = get_string('configcoursetemplatedefault', 'local_ent_installer');
        $desc = get_string('configcoursetemplatedefault_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* Course membership filter */
        /*
         * operates on the return of the moodle ldap standard "memberattribute" value to extract the
         * user primary identifier.
         */

        $key = 'local_ent_installer/course_user_key';
        $label = get_string('configcourseuserkey', 'local_ent_installer');
        $desc = get_string('configcourseuserkey_desc', 'local_ent_installer');
        $default = 'username';
        $options = array(
            'id' => get_string('moodleid', 'local_ent_installer'),
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/course_editingteachers_attribute';
        $label = get_string('configcourseeditingteachersattribute', 'local_ent_installer');
        $desc = get_string('configcourseeditingteachersattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_teachers_attribute';
        $label = get_string('configcourseteachersattribute', 'local_ent_installer');
        $desc = get_string('configcourseteachersattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_teachers_filter';
        $label = get_string('configcourseteachersfilter', 'local_ent_installer');
        $desc = get_string('configcourseteachersfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_membership_filter';
        $label = get_string('configcoursemembershipfilter', 'local_ent_installer');
        $desc = get_string('configcoursemembershipfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/course_membership_dereference_attribute';
        $label = get_string('configcoursemembershipdereferenceattribute', 'local_ent_installer');
        $desc = get_string('configcoursemembershipdereferenceattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* Course membership cohort detector */
        /*
         * A pattern matcher to detect if the membership is a cohort or a single user DN
         */
        $key = 'local_ent_installer/course_membership_cohort_detector';
        $label = get_string('configcoursemembershipcohortdetector', 'local_ent_installer');
        $desc = get_string('configcoursemembershipcohortdetector_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* Course membership cohort filter */
        /*
         * operates on the return of the moodle ldap standard "memberattribute" value to
         * extract the cohort primary identifier.
         */

        $key = 'local_ent_installer/course_membership_cohort_filter';
        $label = get_string('configcoursemembershipcohortfilter', 'local_ent_installer');
        $desc = get_string('configcoursemembershipcohortfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $options = array('soft' => get_string('softcohortunenrol', 'local_ent_installer'),
                         'hard' => get_string('hardcohortunenrol', 'local_ent_installer'));
        $key = 'local_ent_installer/course_hard_cohort_unenrol';
        $label = get_string('configcoursehardcohortunenrol', 'local_ent_installer');
        $desc = get_string('configcoursehardcohortunenrol_desc', 'local_ent_installer');
        $default = 'soft';
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        /* Course enrol method */

        $key = 'local_ent_installer/course_enrol_method';
        $label = get_string('configcourseenrolmethod', 'local_ent_installer');
        $desc = get_string('configcourseenrolmethod_desc', 'local_ent_installer');
        $default = 'manual';
        $enrolplugins = enrol_get_plugins(true);
        $options = array('' => get_string('noenrol', 'local_ent_installer'));
        foreach ($enrolplugins as $plugkey => $epl) {
            if (preg_match('/cohort/', $plugkey)) {
                // Cohorts are automated enrol methods that cannot be pre-feed.
                continue;
            }
            $options[$plugkey] = $epl->get_instance_name(null);
        }
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        return $settings;
    }
}