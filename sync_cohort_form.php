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

class SyncCohortForm extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $config = get_config('local_ent_installer');
        $isent = is_dir($CFG->dirroot.'/local/ent_access_point');

        $mform = $this->_form;

        $mform->addElement('html', '<h3>'.get_string('entities', 'local_ent_installer').'</h3>');

        $fields = "id, CONCAT(name, ' (', idnumber, ')')";
        $cohortsopts = $DB->get_records_menu('cohort', array(), 'name', $fields, 0, 200);

        if (!empty($config->sync_cohorts_enable)) {
            $attrs = array('size' => 15);
            $select = & $mform->addElement('text', 'filter', get_string('filter', 'local_ent_installer'), $attrs);
            $mform->setType('filter', PARAM_TEXT);

            $select = & $mform->addElement('select', 'chid', get_string('cohort', 'local_ent_installer'), $cohortsopts);
            $select->setMultiple(false); // May become multiple.
        }

        $mform->addElement('html', '<h3>'.get_string('options', 'local_ent_installer').'</h3>');

        $radioarr = array();
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('doall', 'local_ent_installer'), 0);
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('createonly', 'local_ent_installer'), 'create');
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('updateonly', 'local_ent_installer'), 'update');
        $radioarr[] = $mform->createElement('radio', 'operation', '', get_string('deleteonly', 'local_ent_installer'), 'delete');
        $mform->addGroup($radioarr, 'operationgroup', '', array(''), false);

        $mform->addElement('checkbox', 'simulate', get_string('simulate', 'local_ent_installer'));

        $mform->addElement('checkbox', 'verbose', get_string('verbose', 'local_ent_installer'));

        $this->add_action_buttons(true, get_string('runsync', 'local_ent_installer'));
    }
}