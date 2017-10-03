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
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'nodes'             => false,
        'force'             => false,
        'level'             => false,
        'verbose'           => false,
        'logroot'           => false,
        'logmode'           => false,
        'horodate'           => false,
        'notify'            => false,
        'fullstop'          => false,
        'debug'             => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logroot',
        'L' => 'level',
        'm' => 'logmode',
        'H' => 'horodate',
        'v' => 'verbose',
        'r' => 'role',
        'N' => 'notify',
        's' => 'fullstop',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo "$unrecognized is not a recognized option\n";
    exit(1);
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
Command line ENT Sync worker for cohorts.

Options:
    -h, --help          Print out this help
    -n, --nodes         Node ids to work with.
    -l, --logroot       The system root to log in.
    -L, --level         The context level to synchronize (system, coursecat, course, etc...)
    -m, --logmode       'append' or 'overwrite'
    -H, --horodate      Append timestamp to the log file name.
    -f, --force         Force updating accounts even if not modified in user sourse.
    -v, --verbose       More output.
    -N, --notify        Notify on error.
    -s, --fullstop      Stops on first error.
    -d, --debug         Turn on debug mode in workers.

"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if (empty($options['logmode'])) {
    $options['logmode'] = 'w';
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$force = '';
if (!empty($options['force'])) {
    $force = ' --force ';
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

$level = '';
if (!empty($options['level'])) {
    $level = ' --level ';
}

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {
    mtrace("\nStarting rolassign process for node $nodeid\n");

    if (!empty($options['logroot'])) {
        $logfile = $options['logroot'].'/ent_sync_roles_'.$host->shortname;
        if (!empty($options['horodate'])) {
            $logfile .= '_'.$runtime;
        }
        $logfile .= '.log';
        $LOG = fopen($logfile, $options['logmode']);
    }

    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_roleassigns.php {$debug} --host={$host->vhostname} ";
    $cmd .= " {$force} {$level}";
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
            die ("Worker failed\n");
        }
        echo "Role assign Worker execution error on {$host->vhostname}\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }

    fclose($LOG);

    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

return 0;