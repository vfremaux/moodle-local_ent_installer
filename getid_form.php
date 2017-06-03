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
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux pour ac-rennes.fr
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class GetIdForm extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'search', get_string('search', 'local_ent_installer'), '' );
        $mform->setType('search', PARAM_TEXT);

        $radioarray = array();
        $radioarray[] = & $mform->createElement('radio', 'searchby', '', get_string('byname', 'local_ent_installer'), 'name');
        $radioarray[] = & $mform->createElement('radio', 'searchby', '', get_string('bycity', 'local_ent_installer'), 'city');
        $radioarray[] = & $mform->createElement('radio', 'searchby', '', get_string('byid', 'local_ent_installer'), 'id');
        $mform->addGroup($radioarray, 'radioar', '', array(' '), false);

        $this->add_action_buttons();
    }
}