<?php
// This file is part of the local ent_installer plugin for Moodle - http://moodle.org/
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
 * Cross version compatibility functions.
 * @package local_ent_installer
 * @category mod
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();


function local_ent_installer_coursecat_get($id, $strictness = MUST_EXIST, $alwaysreturnhidden = false, $user = null) {
    \core_course_category::get($id, $strictness, $alwaysreturnhidden, $user);
}

function local_ent_installer_coursecat_create($data, $editoroptions = null) {
    return \core_course_category::create($data, $editoroptions);
}