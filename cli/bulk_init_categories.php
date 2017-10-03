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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'logroot'          => false,
        'fullstop'         => false,
        'debug'            => false,
        'verbose'          => false,
    ),
    array(
        'h' => 'help',
        'l' => 'logroot',
        'f' => 'fullstop',
        'd' => 'debug',
        'v' => 'verbose',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo get_string('cliunknowoption', 'admin', $unrecognized)."\n";
    exit(1);
}

if ($options['help']) {
    $help = "
Command line ENT Global categories initialisation.

Creates initial categories based on 'initialcategories' settings.

    Options:
    -h, --help              Print out this help
    -l, --logroot           Root directory for logs.
    -s, --fullstop          Stops on fist error.
    -d, --debug             Turn on debug mode in workers.
    -v, --verbose           Print out workers output.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Linux only implementation.

echo "Starting creating/checking site categories....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/init_categories.php {$debug} --host=\"{$h->vhostname}\" ";

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
}

echo "All done.\n";
