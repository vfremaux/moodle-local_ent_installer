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
define('ENT_INSTALLER_SYNC_INTERHOST', 1);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'nodes'             => false,
        'empty'             => false,
        'logmode'           => false,
        'force'             => false,
        'role'              => false,
        'verbose'           => false,
        'logfile'           => false,
        'notify'            => false,
        'fullstop'          => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logfile',
        'e' => 'empty',
        'm' => 'logmode',
        'v' => 'verbose',
        'f' => 'force',
        'r' => 'role',
        'N' => 'notify',
        'S' => 'fullstop',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
    Command line ENT Sync worker for cohorts.

    Options:
    -h, --help          Print out this help
    -n, --nodes         Node ids to work with.
    -l, --logfile       the log file to use. No log if not defined
    -e, --empty         propagates a empty option to final workers
    -m, --logmode       'append' or 'overwrite'
    -f, --force         Force updating accounts even if not modified in user sourse.
    -v, --verbose       More output.
    -N  --notify        Sends a mail when a task fails
    -s  --fullstop      Stops on first failure.
    -d  --debug         Turn on debug mode.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

if (empty($options['logmode'])) {
    $options['logmode'] = 'w';
}

if (!empty($options['logfile'])) {
    $LOG = fopen($options['logfile'], $options['logmode']);
}

$force = '';
if (!empty($options['force'])) {
    $force = ' --force ';
}

$role = '';
if (!empty($options['role']) && in_array($options['role'], array('eleve', 'enseignant', 'administration'))) {
    $role = ' --role='.$options['role'];
}

$verbose = '';
if (!empty($options['verbose'])) {
    $verbose = ' --verbose ';
}

// Fire sequential synchronisation.
mtrace("Starting worker for nodes ".$options['nodes']);
if (isset($LOG)) {
    fputs($LOG, "Starting worker for nodes {$options['nodes']}\n");
};

$empty = '';
if (!empty($options['empty'])) {
    $empty = ' --empty ';
}

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {
    mtrace("\nStarting process for node $nodeid\n");
    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_cohorts.php {$debug} --host={$host->vhostname} ";
    $cmd .= " {$force} {$role} {$empty}";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    exec($cmd, $output, $return);
    if (isset($LOG)) {
        fputs($LOG, "\n$cmd\n#-------------------\n");
        fputs($LOG, implode("\n", $output));
    };
    if ($return) {
        if (isset($LOG)) {
            fputs($LOG, 'Process failure. No output of user feeder.');
        }
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die ("Worker failed");
        }
        echo "Course group Worker execution error on {$host->vhostname}:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    }

    if (!empty($optons['verbose'])) {
        echo implode("\n", $output)."\n";
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

fclose($LOG);

return 0;