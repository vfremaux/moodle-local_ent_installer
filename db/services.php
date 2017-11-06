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
 * Forum external functions and service definitions.
 *
 * @package    local_ent_installer
 * @copyright  2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(

    'local_ent_installer_check_runtime_dates' => array(
        'classname' => 'local_ent_installer_external',
        'methodname' => 'check_runtime_dates',
        'classpath' => 'local/ent_installer/externallib.php',
        'testclientpath' => 'local/ent_installer/testclient_forms.php',
        'description' => 'Returns the last known runtime date for a given setting, or
            alternatively all the vmoodle dates in a known network.',
        'type' => 'read',
        'capabilities' => ''
    ),
);

$services = array(
   'ENT Installer Monitoring Services'  => array(
        'functions' => array (
            'local_ent_installer_check_runtime_dates'),
        'enabled' => 1,
        'restrictedusers' => 0,
        'shortname' => 'MOODLE_ENT_MONITORING_SERVICE',
        'downloadfiles' => 1,
        'uploadfiles' => 0
    ),
);

