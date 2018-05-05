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
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading('head2', get_string('coursefilters', 'local_ent_installer'), ''));

$key = 'local_ent_installer/sync_coursecat_enable';
$label = get_string('configsynccoursecatsenable', 'local_ent_installer');
$desc = '';
$default = 1;
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

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
$label = get_string('configsynccoursessenable', 'local_ent_installer');
$desc = '';
$default = 1;
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

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

/* course FQDN id */

$key = 'local_ent_installer/course_id_attribute';
$label = get_string('configcourseidattribute', 'local_ent_installer');
$desc = get_string('configcourseidattribute_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

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

$key = 'local_ent_installer/course_fullname_attribute';
$label = get_string('configcoursefullnameattribute', 'local_ent_installer');
$desc = get_string('configcoursefullnameattribute_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

/* course idnumber */

$key = 'local_ent_installer/cohort_idnumber_attribute';
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

/* course summary */

$key = 'local_ent_installer/course_summary_attribute';
$label = get_string('configcoursesummaryattribute', 'local_ent_installer');
$desc = get_string('configcoursesummaryattribute_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

/* course visibility */

$key = 'local_ent_installer/course_visible_attribute';
$label = get_string('configcoursevisibleattribute', 'local_ent_installer');
$desc = get_string('configcoursevisibleattribute_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));
