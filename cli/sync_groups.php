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
 * CAS user sync script.
 *
 * This script is meant to be called from a cronjob to sync moodle with the LDAP
 * backend in those setups where the LDAP backend acts as 'master'.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * $sudo -u www-data /usr/bin/php /var/www/moodle/auth/ldap/cli/sync_users.php
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *   - If you have a large number of users, you may want to raise the memory limits
 *     by passing -d momory_limit=256M
 *   - For debugging & better logging, you are encouraged to use in the command line:
 *     -d log_errors=1 -d error_reporting=E_ALL -d display_errors=0 -d html_errors=0
 *   - If you have a large number of users, you may want to raise the memory limits
 *     by passing -d momory_limit=256M
 *   - For debugging & better logging, you are encouraged to use in the command line:
 *     -d log_errors=1 -d error_reporting=E_ALL -d display_errors=0 -d html_errors=0
 *
 * Performance notes:
 * We have optimized it as best as we could for PostgreSQL and MySQL, with 27K students
 * we have seen this take 10 minutes.
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
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.
<<<<<<< HEAD
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_users.php'); // Ldap primitives.
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_coursegroups.php'); // Ldap primitives for course groups.
require_once($CFG->dirroot.'/local/ent_installer/locallib.php'); // General primitives.
=======
>>>>>>> MOODLE_39_STABLE

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose'           => false,
        'help'              => false,
        'simulate'          => false,
        'clearempty'        => false,
        'skipmembership'    => false,
        'host'              => false,
        'force'             => false,
        'debug'             => false,
    ),
    array(
        'h' => 'help',
        'f' => 'force',
        'e' => 'clearempty',
        'v' => 'verbose',
        'k' => 'skipmembership',
        's' => 'simulate',
        'H' => 'host',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line ENT Course groups Synchronizer.

Options:
     v, --verbose           Provides lot of output.
    -h, --help              Print out this help.
    -s, --simulate          Get all data for simulation but will NOT process any writing in database.
    -f, --force             Force updating all data.
    -e, --clearempty             Clean empty groups if no user left.
    -k, --skipmembership    Skip membership.
    -H, --host              Set the host (physical or virtual) to operate on.
    -d, --debug             Turn on debug mode.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever (only when vmoodle). If vmoodle switch is armed, will switch now config.
if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    include(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot);
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_users.php'); // Ldap primitives.
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_coursegroups.php'); // Ldap primitives for course groups.
require_once($CFG->dirroot.'/local/ent_installer/locallib.php'); // General primitives.
require_once($CFG->dirroot.'/local/ent_installer/logmuter.class.php'); // Ensure we have coursecat class.

// Ensure errors are well explained.
if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

// Fakes an admin identity for all the process.
global $USER;

// Get main siteadmin.
$USER = $DB->get_record('user', array('username' => $CFG->admin, 'mnethostid' => $CFG->mnet_localhost_id));

// If failed, get first available site admin.
if (empty($USER)) {
    $siteadminlist = $CFG->siteadmins;
    if (empty($siteadminlist)) {
        echo "No site admins. This is not a normal situation. Quitting.\n";
        exit(1);
    }
    $siteadmins = explode(',', $siteadminlist);
    foreach ($siteadmins as $uid) {
        $USER = $DB->get_record('user', array('id' => $uid));
        if (!empty($USER)) {
            break;
        }
    }
}

if (empty($USER)) {
    echo "No site admins at all. This is not a normal situation. Quitting.\n";
    exit(1);
}

// Get ldap params from real ldap plugin.
$ldapauth = get_auth_plugin('ldap');

// Run the customised synchro, with NO logs generated.
$logmuter = new \ent_installer\logmuter();
$logmuter->activate();
local_ent_installer_sync_groups($ldapauth, $options);
$logmuter->deactivate();

exit(0);