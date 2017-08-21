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
 * Version details.
 *
 * @package    local_ent_installer
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com> for French Public Education
 * @copyright  2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2017080800;   // The (date) version of this plugin.
$plugin->requires = 2016120500;   // Requires this Moodle version.
$plugin->component = 'local_ent_installer';
$plugin->release = '3.2.0 (Build 2017062100)';
// $plugin->dependencies = array('local_vmoodle' => '2013020800'); // Not mandatory.

// Non moodle attributes.
$plugin->codeincrement = '3.2.0002';