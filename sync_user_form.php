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
 * Form for activating manual resync of a single user.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class SyncUserForm extends moodleform {

    public function definition() {
        global $CFG, $DB, $OUTPUT;

        $config = get_config('local_ent_installer');
        $isent = is_dir($CFG->dirroot.'/local/ent_access_point');

        $mform = $this->_form;

        $mform->addElement('html', '<h3>'.get_string('entities', 'local_ent_installer').'</h3>');

        $params = array('auth' => $config->real_used_auth, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0);
        $fields = 'id, CONCAT(lastname, " ", firstname, " (", username, ")")';
        $usersopts = $DB->get_records_menu('user', $params, 'lastname, firstname', $fields, 0, 200);
        $countall = $DB->count_records('user', $params);
        if ($countall > 200) {
            $mform->addelement('static', 'overnumnotice', '', $OUTPUT->notification(get_string('usefilternotice', 'local_ent_installer')));
        }

        if (!empty($config->sync_users_enable)) {
            $attrs = array('size' => 15);
            $select = & $mform->addElement('text', 'filter', get_string('filter', 'local_ent_installer'), $attrs);
            $mform->setType('filter', PARAM_TEXT);

            $select = & $mform->addElement('select', 'uid', get_string('user'), $usersopts);
            $select->setMultiple(false); // May become multiple.
        }

        $mform->addElement('html', '<h3>'.get_string('options', 'local_ent_installer').'</h3>');

        $mform->addElement('hidden', 'operation', 'update');
        $mform->setType('operation', PARAM_TEXT);
        /*
        $radioarr = array();
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('doall', 'local_ent_installer'), 0);
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('createonly', 'local_ent_installer'), 'create');
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('updateonly', 'local_ent_installer'), 'update');
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('deleteonly', 'local_ent_installer'), 'delete');
        $mform->addGroup($radioarr, 'operationgroup', '', array(''), false);
        */

        $mform->addElement('checkbox', 'simulate', get_string('simulate', 'local_ent_installer'));

        $mform->addElement('checkbox', 'verbose', get_string('verbose', 'local_ent_installer'));

        $this->add_action_buttons(true, get_string('runsync', 'local_ent_installer'));
    }
}