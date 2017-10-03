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
 * Initialise initial categories from config settings.
 *
 * @package    local
 * @subpackage ent_installer
 * @copyright  2014 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose'           => false,
        'help'              => false,
        'simulate'          => false,
        'host'              => false,
        'debug'             => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        's' => 'simulate',
        'H' => 'host',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo get_string('cliunknowoption', 'admin', $unrecognized)."\n";
    exit(1);
}

if ($options['help']) {
    $help = "
Command line ENT Initial categories initializer.

    Options:
     v, --verbose       Provides more output
    -h, --help          Print out this help
    -s, --simulate      Get all data for simulation but will NOT process any writing in database.
    -H, --host          Set the host (physical or virtual) to operate on
    -d, --debug         Turn on debug mode.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (defined('VMOODLE_BOOT')) {
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

// Fakes an admin identity for all the process.
global $USER;
$USER = get_admin();

echo "Installing site categories\n";
local_ent_installer_install_categories(!empty($optiona['simulate']));

echo "Done.\n";
exit(0);