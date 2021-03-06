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
 * settings for roleassign synchronisation
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
use \admin_setting_configtextarea;
use \admin_setting_configcheckbox;
use \admin_setting_configselect;

defined('MOODLE_INTERNAL') || die();

class roleassigns {

    public static function settings() {
        global $CFG;

        $settings = new admin_settingpage('local_ent_installer_roleassigns', get_string('settingsroleassigns', 'local_ent_installer'));

        $settings->add(new admin_setting_heading('roleassignshdr', get_string('roleassignsfilters', 'local_ent_installer'), ''));

        $key = 'local_ent_installer/sync_roleassigns_enable';
        $label = get_string('configsyncroleassignsenable', 'local_ent_installer');
        $desc = '';
        $default = 1;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/last_sync_date_roles';
        $label = get_string('configlastsyncdate', 'local_ent_installer');
        $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_contexts';
        $label = get_string('configroleassigncontexts', 'local_ent_installer');
        $desc = get_string('configroleassigncontexts_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_selector_filter';
        $label = get_string('configroleassignselectorfilter', 'local_ent_installer');
        $desc = get_string('configroleassignselectorfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_use_alias';
        $label = get_string('configroleassignusealias', 'local_ent_installer');
        $desc = get_string('configroleassignusealias_desc', 'local_ent_installer');
        $default = true;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_objectclass';
        $label = get_string('configroleassignobjectclass', 'local_ent_installer');
        $desc = get_string('configroleassignobjectclass_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_id_attribute';
        $label = get_string('configroleassignidattribute', 'local_ent_installer');
        $desc = get_string('configroleassignidattribute_desc', 'local_ent_installer');
        $default = 'cn';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* roleassign role */

        $key = 'local_ent_installer/roleassign_role_attribute';
        $label = get_string('configroleassignroleattribute', 'local_ent_installer');
        $desc = get_string('configroleassignroleattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_role_filter';
        $label = get_string('configroleassignrolefilter', 'local_ent_installer');
        $desc = get_string('configroleassignrolefilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_role_mapping';
        $label = get_string('configroleassignrolemapping', 'local_ent_installer');
        $desc = get_string('configroleassignrolemapping_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtextarea($key, $label, $desc, $default));

        /* roleassign contextlevel */

        $key = 'local_ent_installer/roleassign_contextlevel_attribute';
        $label = get_string('configroleassigncontextlevelattribute', 'local_ent_installer');
        $desc = get_string('configroleassigncontextlevelattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_contextlevel_filter';
        $label = get_string('configroleassigncontextlevelfilter', 'local_ent_installer');
        $desc = get_string('configroleassigncontextlevelfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_contextlevel_mapping';
        $label = get_string('configroleassigncontextlevelmapping', 'local_ent_installer');
        $desc = get_string('configroleassigncontextlevelmapping_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtextarea($key, $label, $desc, $default));

        /* roleassign context id */

        $key = 'local_ent_installer/roleassign_context_attribute';
        $label = get_string('configroleassigncontextattribute', 'local_ent_installer');
        $desc = get_string('configroleassigncontextattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_context_filter';
        $label = get_string('configroleassigncontextfilter', 'local_ent_installer');
        $desc = get_string('configroleassigncontextfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* targetting the context id */

        $key = 'local_ent_installer/roleassign_coursecat_key';
        $label = get_string('configroleassigncoursecatkey', 'local_ent_installer');
        $desc = get_string('configroleassigncoursecatkey_desc', 'local_ent_installer');
        $default = 'idnumber';
        $options = array(
            'id' => 'ID',
            'idnumber' => get_string('idnumber'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/roleassign_course_key';
        $label = get_string('configroleassigncoursekey', 'local_ent_installer');
        $desc = get_string('configroleassigncoursekey_desc', 'local_ent_installer');
        $default = 'idnumber';
        $options = array(
            'id' => 'ID',
            'idnumber' => get_string('idnumber'),
            'shortname' => get_string('shortname'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/roleassign_module_key';
        $label = get_string('configroleassignmodulekey', 'local_ent_installer');
        $desc = get_string('configroleassignmodulekey_desc', 'local_ent_installer');
        $default = 'idumber';
        $options = array(
            'id' => get_string('module', 'local_ent_installer').' ID',
            'idnumber' => get_string('idnumber'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/roleassign_block_key';
        $label = get_string('configroleassignblockkey', 'local_ent_installer');
        $desc = get_string('configroleassignblockkey_desc', 'local_ent_installer');
        $default = 'id';
        $options = array(
            'id' => get_string('block').' ID',
        );
        if (is_dir($CFG->dirroot.'/course/format/page')) {
            $options['idnumber'] = get_string('idnumber');
        }
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/roleassign_targetuser_key';
        $label = get_string('configroleassigntargetuserkey', 'local_ent_installer');
        $desc = get_string('configroleassigntargetuserkey_desc', 'local_ent_installer');
        $default = 'username';
        $options = array(
             'id' => get_string('id', 'local_ent_installer'),
            'idnumber' => get_string('idnumber'),
            'username' => get_string('username'),
            'email' => get_string('email'),
        );
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $key = 'local_ent_installer/roleassign_membership_attribute';
        $label = get_string('configroleassignmembershipattribute', 'local_ent_installer');
        $desc = get_string('configroleassignmembershipattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_membership_filter';
        $label = get_string('configroleassignmembershipfilter', 'local_ent_installer');
        $desc = get_string('configroleassignmembershipfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/roleassign_user_key';
        $label = get_string('configroleassignuserkey', 'local_ent_installer');
        if (empty($ldapauth->config->memberattribute_isdn)) {
            $desc = get_string('configroleassignuserkey_desc', 'local_ent_installer');
            $default = 'username';
            $options = array(
                'username' => get_string('username'),
                'id' => get_string('id', 'local_ent_installer'),
                'idnumber' => get_string('idnumber'),
                'email' => get_string('email')
            );
            $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));
        } else {
            set_config('roleassign_user_key', 'username', 'local_ent_installer');
            $desclocked = get_string('configroleassignuserkeylocked_desc', 'local_ent_installer');
            $settings->add(new admin_setting_static($key, $label, $desclocked, 'username'));
        }

        $key = 'local_ent_installer/roleassign_enrol_method';
        $label = get_string('configroleassignenrolmethod', 'local_ent_installer');
        $desc = get_string('configroleassignenrolmethod_desc', 'local_ent_installer');
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