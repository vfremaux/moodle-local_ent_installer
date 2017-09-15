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

define('ENT_INSTALLER_SYNC_MAX_WORKERS', 4);
define('JOB_INTERLEAVE', 2);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

raise_memory_limit(MEMORY_HUGE);

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'workers'          => false,
        'distributed'      => false,
        'level'            => false,
        'logroot'          => false,
        'force'            => false,
        'verbose'          => false,
        'notify'           => false,
        'debug'            => false,
        'fullstop'         => false,
    ),
    array(
        'h' => 'help',
        'w' => 'workers',
        'D' => 'distributed',
        'L' => 'level',
        'l' => 'logroot',
        'f' => 'force',
        'v' => 'verbose',
        'N' => 'notify',
        'd' => 'debug',
        'S' => 'fullstop',
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
    -w, --workers       Number of workers.
    -D, --distributed   Distributed operations.
    -l, --logroot       Root directory for logs.
    -L, --level         the contexct level to synchronize.
    -f, --force         Force updating accounts even if not modified in user sourse.
    -v, --verbose       More output.
    -N, --notify        Sends email on failure.
    -d, --debug         Turn on debug on.
    -S, --fullstop      Stops on first failure.

    "; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if ($options['workers'] === false) {
    $options['workers'] = ENT_INSTALLER_SYNC_MAX_WORKERS;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

if (!empty($options['logroot'])) {
    $logroot = $options['logroot'];
} else {
    $logroot = $CFG->dataroot;
}

$force = '';
if (!empty($options['force'])) {
    $force = ' --force ';
}

$notify = '';
if (!empty($options['notify'])) {
    $notify = ' --notify ';
}

$fullstop = '';
if (!empty($options['fullstop'])) {
    $fullstop = ' --fullstop ';
}

$verbose = '';
if (!empty($options['verbose'])) {
    echo "checking options\n";
    $verbose = ' --verbose ';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Make worker lists.

$joblists = array();
$i = 0;
foreach ($allhosts as $h) {
    $joblist[$i][] = $h->id;
    $i++;
    if ($i == $options['workers']) {
        $i = 0;
    }
}

$level = '';
if (!empty($options['level'])) {
    $level = ' --level ';
}

// Start spreading workers, and pass the list of vhost ids. Launch workers in background.
// Linux only implementation.

$i = 1;
foreach ($joblist as $jl) {
    $jobids = array();
    if (!empty($jl)) {
        $hids = implode(',', $jl);
        $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_roleassigns_worker.php {$debug} --nodes=\"$hids\" ";
        $workercmd .= "--logfile={$logroot}/ent_sync_cohorts_log_{$i}.log {$force} {$verbose} {$level} {$notify} {$fullstop}";
        if ($options['distributed']) {
            $workercmd .= ' &';
        }
        mtrace("Executing $workercmd\n######################################################\n");
        $output = array();
        exec($workercmd, $output, $return);
        if ($return) {
            if (!empty($options['fullstop'])) {
                echo implode("\n", $output)."\n";
                die("Worker ended with error");
            }
            echo "Worker ended with error:\n";
            echo implode("\n", $output)."\n";
            echo "Pursuing anyway.\n";
        }
        if (empty($options['distributed']) && !empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
        $i++;
        sleep(JOB_INTERLEAVE);
    }
}