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
 * Settings for coursegroups synchronisation
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
use \admin_setting_static;

defined('MOODLE_INTERNAL') || die();

class coursegroups {

    public static function settings() {

        $settings = new admin_settingpage('local_ent_installer_coursegroups', get_string('settingscoursegroups', 'local_ent_installer'));

        $settings->add(new admin_setting_heading('coursegroupshdr', get_string('groupsfilters', 'local_ent_installer'), ''));

        $key = 'local_ent_installer/sync_groups_enable';
        $label = get_string('configsyncgroupsenable', 'local_ent_installer');
        $desc = '';
        $default = 1;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/last_sync_date_group';
        $label = get_string('configlastsyncdate', 'local_ent_installer');
        $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

        $key = 'local_ent_installer/no_delete';
        $label = get_string('configgroupsprotectformdeletion', 'local_ent_installer');
        $desc = get_string('configgroupsprotectformdeletion_desc', 'local_ent_installer');
        $default = 0;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_contexts';
        $label = get_string('configgroupcontexts', 'local_ent_installer');
        $desc = get_string('configgroupcontexts_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_auto_name_prefix';
        $label = get_string('configgroupautonameprefix', 'local_ent_installer');
        $desc = get_string('configgroupautonameprefix_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_selector_filter';
        $label = get_string('configgroupselectorfilter', 'local_ent_installer');
        $desc = get_string('configgroupselectorfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_objectclass';
        $label = get_string('configgroupobjectclass', 'local_ent_installer');
        $desc = get_string('configgroupobjectclass_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_id_attribute';
        $label = get_string('configgroupidattribute', 'local_ent_installer');
        $desc = get_string('configgroupidattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_id_pattern';
        $label = get_string('configgroupidpattern', 'local_ent_installer');
        $desc = get_string('configgroupidpattern_desc', 'local_ent_installer');
        $default = '%GID%';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_membership_attribute';
        $label = get_string('configgroupmembershipattribute', 'local_ent_installer');
        $desc = get_string('configgroupmembershipattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_membership_filter';
        $label = get_string('configgroupmembershipfilter', 'local_ent_installer');
        $desc = get_string('configgroupmembershipfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_name_attribute';
        $label = get_string('configgroupnameattribute', 'local_ent_installer');
        $desc = get_string('configgroupnameattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_name_filter';
        $label = get_string('configgroupnamefilter', 'local_ent_installer');
        $desc = get_string('configgroupnamefilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_grouping_attribute';
        $label = get_string('configgroupgroupingattribute', 'local_ent_installer');
        $desc = get_string('configgroupgroupingattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_grouping_filter';
        $label = get_string('configgroupgroupingfilter', 'local_ent_installer');
        $desc = get_string('configgroupgroupingfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_idnumber_attribute';
        $label = get_string('configgroupidnumberattribute', 'local_ent_installer');
        $desc = get_string('configgroupidnumberattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_idnumber_filter';
        $label = get_string('configgroupidnumberfilter', 'local_ent_installer');
        $desc = get_string('configgroupidnumberfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_course_attribute';
        $label = get_string('configgroupcourseattribute', 'local_ent_installer');
        $desc = get_string('configgroupcourseattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_course_filter';
        $label = get_string('configgroupcoursefilter', 'local_ent_installer');
        $desc = get_string('configgroupcoursefilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_course_identifier';
        $label = get_string('configgroupcourseidentifier', 'local_ent_installer');
        $desc = get_string('configgroupcourseidentifier_desc', 'local_ent_installer');
        $default = 'shortname';
        $options = array('shortname' => get_string('shortname'),
                         'id' => get_string('id', 'local_ent_installer'),
                         'idnumber' => get_string('idnumber'));
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/group_description_attribute';
        $label = get_string('configgroupdescriptionattribute', 'local_ent_installer');
        $desc = get_string('configgroupdescriptionattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/group_user_identifier';
        $label = get_string('configgroupuseridentifier', 'local_ent_installer');
        if (empty($ldapauth->config->memberattribute_isdn)) {
            $desc = get_string('configgroupuseridentifier_desc', 'local_ent_installer');
            $default = 'username';
            $options = array('username' => get_string('username'),
                             'id' => get_string('id', 'local_ent_installer'),
                             'idnumber' => get_string('idnumber'),
                             'email' => get_string('email'));
            $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));
        } else {
            set_config('group_user_identifier', 'username', 'local_ent_installer');
            $desclocked = get_string('configgroupuseridentifierlocked_desc', 'local_ent_installer');
            $settings->add(new admin_setting_static($key, $label, $desclocked, 'username'));
        }

        return $settings;
    }
}