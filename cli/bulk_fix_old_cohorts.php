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

define('CLI_SCRIPT', true);

define('HOST_INTERLEAVE', 1);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'logroot'          => false,
        'verbose'          => false,
        'fullstop'         => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'l' => 'logroot',
        'v' => 'verbose',
        's' => 'fullstop',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line ENT Sync worker.

    Options:
    -h, --help          Print out this help
    -l, --logroot       Root directory for logs.
    -v, --verbose       More output.
    -d, --debug         Turns on debug on workers.
    -s, --fullstop      Stops on first error.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$verbose = '';
if (!empty($options['verbose'])) {
    echo "checking options\n";
    $verbose = '--verbose';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start spreading workers, and pass the list of vhost ids. Launch workers in background.
// Linux only implementation.

foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/fix_old_cohorts.php {$debug} --host={$h->vhostname} {$verbose}";
    mtrace("Executing $workercmd\n######################################################\n");

    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die("Worker ended with error\n");
        } else {
            echo "Worker ended with error:\n";
            echo implode("\n", $output)."\n";
        }
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }

    sleep(HOST_INTERLEAVE);
}