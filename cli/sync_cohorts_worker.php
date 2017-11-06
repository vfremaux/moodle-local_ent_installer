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
        'empty'             => false,
        'force'             => false,
        'verbose'           => false,
        'logroot'           => false,
        'logmode'           => false,
        'horodate'          => false,
        'notify'            => false,
        'fullstop'          => false,
        'debug'             => false,
        'nocheck'           => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logroot',
        'e' => 'empty',
        'm' => 'logmode',
        'v' => 'verbose',
        'f' => 'force',
        'H' => 'horodate',
        'N' => 'notify',
        's' => 'fullstop',
        'd' => 'debug',
        'x' => 'nocheck',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo "$unrecognized is not a recognized option \n";
    exit(1);
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
Command line ENT Sync worker for cohorts.

Options:
    -h, --help          Print out this help
    -n, --nodes         Node ids to work with.
    -l, --logroot       The Root to log in.
    -e, --empty         propagates an empty option to final workers
    -m, --logmode       'append' or 'overwrite'
    -f, --force         Force updating accounts even if not modified in user sourse.
    -v, --verbose       More output.
    -H, --horodate      If set horodates log files.
    -N, --notify        Sends a mail on failure.
    -S, --fullstop      Stops on first error.
    -d, --debug         Turn on debug in workers.
    -x, --nocheck       Do NOT check component origin.

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

$empty = '';
if (!empty($options['empty'])) {
    $empty = ' --empty ';
}

$nocheck = '';
if (!empty($options['nocheck'])) {
    $nocheck = ' --nocheck ';
}

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {

    if (!empty($options['logroot'])) {
        $logfile = $options['logroot'].'/ent_sync_cohorts_'.$host->shortname;
        if (!empty($options['horodate'])) {
            $logfile .= '_'.$runtime;
        }
        $logfile .= '.log';
        $LOG = fopen($logfile, $options['logmode']);
    }

    if (isset($LOG)) {
        fputs($LOG, "Starting worker for nodes {$options['nodes']}\n");
    };

    mtrace("\nStarting process for node $nodeid\n");
    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_cohorts.php {$debug} --host={$host->vhostname} ";
    $cmd .= "{$force} {$empty} {$nocheck}";
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
            fclose($LOG);
            echo implode("\n", $output)."\n";
            die ("Worker failed");
        }
        echo "Cohort Worker execution error on {$host->vhostname}:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    }

    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }
    if (isset($LOG)) {
        fclose($LOG);
    }

    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

exit(0);