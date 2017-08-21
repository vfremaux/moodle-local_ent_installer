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

require_once($CFG->dirroot.'/lib/formslib.php');

class SyncUsersForm extends moodleform {

    public function definition() {

        $config = get_config('local_ent_installer');

        $mform = $this->_form;

        $mform->addElement('html', '<h3>'.get_string('entities', 'local_ent_installer').'</h3>');

        if (!empty($config->sync_users_enable)) {
            $mform->addElement('checkbox', 'users', get_string('users', 'local_ent_installer'));
        }

        if (!empty($config->sync_cohorts_enable)) {
            $mform->addElement('checkbox', 'cohorts', get_string('cohorts', 'local_ent_installer'));
        }

        if (!empty($config->sync_groups_enable)) {
            $mform->addElement('checkbox', 'groups', get_string('coursegroups', 'local_ent_installer'));

            $mform->addElement('checkbox', 'emptygroups', get_string('emptygroups', 'local_ent_installer'), get_string('clear', 'local_ent_installer'));
        }

        if (!empty($config->sync_roleassigns_enable)) {
            $mform->addElement('checkbox', 'roleassigns', get_string('roleassigns', 'local_ent_installer'));

            $enrolplugins = enrol_get_plugins(true);
            $options = array();
            foreach ($enrolplugins as $key => $epl) {
                $options[$key] = $epl->get_instance_name(null);
            }
            $mform->addElement('select', 'enrol', get_string('enrolmethod', 'local_ent_installer'), $options);
            $mform->setDefault('enrol', 'manual');
        }

        $mform->addElement('html', '<h3>'.get_string('options', 'local_ent_installer').'</h3>');

        $mform->addElement('checkbox', 'force', get_string('force', 'local_ent_installer'));

        $mform->addElement('checkbox', 'updateonly', get_string('updateonly', 'local_ent_installer'));

        $mform->addElement('checkbox', 'simulate', get_string('simulate', 'local_ent_installer'));

        $mform->addElement('checkbox', 'verbose', get_string('verbose', 'local_ent_installer'));

        $mform->addElement('checkbox', 'disableautocohortscheck', get_string('disableautocohortscheck', 'local_ent_installer'));

        $mform->addElement('submit', 'teachercatreorder', get_string('teachercatreorder', 'local_ent_installer'));

        $this->add_action_buttons(true, get_string('runsync', 'local_ent_installer'));
    }
}