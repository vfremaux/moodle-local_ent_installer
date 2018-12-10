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
 * settings for cohort synchronisation
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

require_once($CFG->dirroot.'/local/ent_installer/adminlib.php');

class cohorts {

    public static function settings() {

        $settings = new admin_settingpage('local_ent_installer_cohorts', get_string('settingscohorts', 'local_ent_installer'));

        $settings->add(new admin_setting_heading('cohortshdr', get_string('cohortsfilters', 'local_ent_installer'), ''));

        $key = 'local_ent_installer/sync_cohorts_enable';
        $label = get_string('configsynccohortsenable', 'local_ent_installer');
        $desc = '';
        $default = 1;
        $settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

        $key = 'local_ent_installer/last_sync_date_cohort';
        $label = get_string('configlastsyncdate', 'local_ent_installer');
        $desc = get_string('configlastsyncdate_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configdatetime($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_contexts';
        $label = get_string('configcohortcontexts', 'local_ent_installer');
        $desc = get_string('configcohortcontexts_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_selector_filter';
        $label = get_string('configcohortselectorfilter', 'local_ent_installer');
        $desc = get_string('configcohortselectorfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_objectclass';
        $label = get_string('configcohortobjectclass', 'local_ent_installer');
        $desc = get_string('configcohortobjectclass_desc', 'local_ent_installer');
        $default = '(objectClass=group)';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /* Cohort id attribute */
        /*
         * where in ldap to match the cohort primary identifier (idnumber)
         */

        /** Cohort identifier **/

        $key = 'local_ent_installer/cohort_id_attribute';
        $label = get_string('configcohortidattribute', 'local_ent_installer');
        $desc = get_string('configcohortidattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_id_pattern';
        $label = get_string('configcohortidpattern', 'local_ent_installer');
        $desc = get_string('configcohortidpattern_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        /** membership attribute name **/

        $key = 'local_ent_installer/cohort_membership_attribute';
        $label = get_string('configcohortmembershipattribute', 'local_ent_installer');
        $desc = get_string('configcohortmembershipattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_membership_filter';
        $label = get_string('configcohortmembershipfilter', 'local_ent_installer');
        $desc = get_string('configcohortmembershipfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_name_attribute';
        $label = get_string('configcohortnameattribute', 'local_ent_installer');
        $desc = get_string('configcohortnameattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_name_filter';
        $label = get_string('configcohortnamefilter', 'local_ent_installer');
        $desc = get_string('configcohortnamefilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_idnumber_attribute';
        $label = get_string('configcohortidnumberattribute', 'local_ent_installer');
        $desc = get_string('configcohortidnumberattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_idnumber_filter';
        $label = get_string('configcohortidnumberfilter', 'local_ent_installer');
        $desc = get_string('configcohortidnumberfilter_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_description_attribute';
        $label = get_string('configcohortdescriptionattribute', 'local_ent_installer');
        $desc = get_string('configcohortdescriptionattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $key = 'local_ent_installer/cohort_course_binding_attribute';
        $label = get_string('configcohortcoursebindingattribute', 'local_ent_installer');
        $desc = get_string('configcohortcoursebindingattribute_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $options = array(
            'id' => get_string('id', 'local_ent_installer'),
            'shortname' => get_string('shortname'),
            'idnumber' => get_string('idnumber'),
        );
        $key = 'local_ent_installer/cohort_course_binding_identifier';
        $label = get_string('configcohortcoursebindingidentifier', 'local_ent_installer');
        $desc = get_string('configcohortcoursebindingidentifier_desc', 'local_ent_installer');
        $default = 'shortname';
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $options = array('soft' => get_string('softcohortunenrol', 'local_ent_installer'),
                         'hard' => get_string('hardcohortunenrol', 'local_ent_installer'));
        $key = 'local_ent_installer/cohort_hard_cohort_unenrol';
        $label = get_string('configcohorthardcohortunenrol', 'local_ent_installer');
        $desc = get_string('configcohorthardcohortunenrol_desc', 'local_ent_installer');
        $default = 'soft';
        $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

        $ldapauth = get_auth_plugin('ldap');

        $key = 'local_ent_installer/cohort_user_identifier';
        $label = get_string('configcohortuseridentifier', 'local_ent_installer');
        if (empty($ldapauth->config->memberattribute_isdn)) {
            $desc = get_string('configcohortuseridentifier_desc', 'local_ent_installer');
            $default = 'username';
            $options = array('username' => get_string('username'),
                             'id' => get_string('id', 'local_ent_installer'),
                             'idnumber' => get_string('idnumber'),
                             'email' => get_string('email'));
            $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));
        } else {
            set_config('cohort_user_identifier', 'username', 'local_ent_installer');
            $desclocked = get_string('configcohortuseridentifierlocked_desc', 'local_ent_installer');
            $settings->add(new admin_setting_static($key, $label, $desclocked, 'username'));
        }

        $key = 'local_ent_installer/cohort_old_prefixes';
        $label = get_string('configcohortoldprefixes', 'local_ent_installer');
        $desc = get_string('configcohortoldprefixes_desc', 'local_ent_installer');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        return $settings;
    }
}