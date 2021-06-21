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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('CLI_SCRIPT', true);

define('ENT_INSTALLER_SYNC_MAX_WORKERS', 2);
define('JOB_INTERLEAVE', 2);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

raise_memory_limit(MEMORY_HUGE);

// Ensure options are blanck;
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'workers'          => false,
        'distributed'      => false,
        'fulldelete'       => false,
        'logroot'          => false,
        'force'            => false,
        'role'             => false,
        'verbose'          => false,
        'notify'           => false,
        'debug'            => false,
        'fullstop'         => false,
    ),
    array(
        'h' => 'help',
        'w' => 'workers',
        'd' => 'distributed',
        'x' => 'fulldelete',
        'l' => 'logroot',
        'f' => 'force',
        'r' => 'role',
        'v' => 'verbose',
        'n' => 'notify',
        'd' => 'debug',
        's' => 'fullstop',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "Command line ENT Sync worker for users.

        Options:
        -h, --help          Print out this help
        -w, --workers       Number of workers.
        -d, --distributed   Distributed operations.
        -l, --logroot       Root directory for logs.
        -x, --fulldelete    Propagates a full delete option to all workers.
        -f, --force         Force updating accounts even if not modified in user sourse.
        -r, --role          Role to process if not empty : (eleve,enseignant,administration).
        -v, --verbose       More output.
        -n, --notify        If present will send a mail when a sync host fails.
        -d, --debug         Turn on debug in workers.
        -s, --fullstop      Stops on first error.

        "; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if ($options['workers'] === false) {
    $options['workers'] = ENT_INSTALLER_SYNC_MAX_WORKERS;
}

if (!empty($options['logroot'])) {
    $logroot = $options['logroot'];
} else {
    $logroot = $CFG->dataroot;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
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

$role = '';
if (!empty($options['role']) && in_array($options['role'], array('eleve', 'enseignant', 'administration'))) {
    $role = '--role='.$options['role'];
}

$verbose = '';
if (!empty($options['verbose'])) {
    echo "checking options\n";
    $verbose = ' --verbose ';
}

$config = get_config('local_vmoodle');

$clusters = 1;
if (!empty($config->clusters)) {
    $clusters = $config->clusters;
}

$clusterix = 1;
if (!empty($config->clusterix)) {
    $clusterix = $config->clusterix;
}

if (!$allhosts = vmoodle_get_vmoodleset($clusters, $clusterix)) {
    die("Nothing to do. No Vhosts");
    exit(1);
}

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

$fulldelete = '';
if (!empty($options['fulldelete'])) {
    $fulldelete = ' --fulldelete ';
}

// Start spreading workers, and pass the list of vhost ids. Launch workers in background
// Linux only implementation.

$i = 1;
foreach ($joblist as $jl) {
    $jobids = array();
    if (!empty($jl)) {
        $hids = implode(',', $jl);

        $logfile = '';
        if (!empty($options['logroot'])) {
            $logfile = " --logfile={$options['logroot']}/ent_sync_log_{$i}.log ";
        }

        $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_users_worker.php {$debug} --nodes=\"$hids\" {$logfile} ";
        $workercmd .= " {$force} {$role} {$verbose} {$fulldelete} {$notify} {$fullstop}";
        if ($options['distributed']) {
            $workercmd .= ' &';
        }
        mtrace("Executing $workercmd\n######################################################\n");
        $output = array();
        exec($workercmd, $output, $return);
        if ($return) {
            if (!empty($fullstop)) {
                echo implode("\n", $output)."\n";
                die("Worker ended with error");
            } else {
                echo "Worker error on worker $hids:\n";
                echo implode("\n", $output)."\n";
                echo "Pursuing anyway\n";
            }
        }
        if (!$options['distributed'] && !empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
        $i++;
        sleep(JOB_INTERLEAVE);
    }
}