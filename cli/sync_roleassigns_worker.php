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
        'logfile'           => false,
        'notify'            => false,
        'hardstop'          => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logfile',
        'L' => 'level',
        'm' => 'logmode',
        'v' => 'verbose',
        'r' => 'role',
        'N' => 'notify',
        'S' => 'hardstop',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['nodes'])) {
    $help =
        "Command line ENT Sync worker for cohorts.

        Options:
        -h, --help          Print out this help
        -n, --nodes         Node ids to work with.
        -l, --logfile       the log file to use. No log if not defined
        -L, --level         the context level to synchronize (system, coursecat, course, etc...)
        -m, --logmode       'append' or 'overwrite'
        -f, --force         Force updating accounts even if not modified in user sourse.
        -v, --verbose       More output.
        -N, --notify        More output.
        -S, --hardstop      More output.

        "; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
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
    mtrace("\nStarting process for node $nodeid\n");
    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_roleassigns.php --host={$host->vhostname} {$force} {$level}";
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
        if (!empty($options['hardstop'])) {
            die ("Worker failed");
        } else {
            echo "Role assign Worker execution error on {$host->vhostname}... Continuing anyway\n";
        }
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

fclose($LOG);

return 0;