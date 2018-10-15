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
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

raise_memory_limit(MEMORY_HUGE);

// Ensure options are blank.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'workers'          => false,
        'distributed'      => false,
        'logroot'          => false,
        'force'            => false,
        'verbose'          => false,
        'horodate'         => false,
        'notify'           => false,
        'fullstop'         => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'w' => 'workers',
        'D' => 'distributed',
        'l' => 'logroot',
        'f' => 'force',
        'v' => 'verbose',
        'H' => 'horodate',
        'N' => 'notify',
        's' => 'fullstop',
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
    Command line ENT Sync worker for course categories.

    Options:
    -h, --help          Print out this help
    -w, --workers       Number of workers.
    -D, --distributed   Distributed operations.
    -l, --logroot       Root directory for logs.
    -d, --debug         Propagates a full delete option to all workers.
    -f, --force         Force updating accounts even if not modified in datasource.
    -v, --verbose       More output.
    -H, --horodate      If set horodate log files
    -N, --notify        Notifies on failure
    -S, --hardstop      Stops on first failure

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if ($options['workers'] === false) {
    $options['workers'] = ENT_INSTALLER_SYNC_MAX_WORKERS;
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = '--debug ';
}

$logroot = '';
if (!empty($options['logroot'])) {
    $logroot = " --logroot={$options['logroot']} ";
}

$force = '';
if (!empty($options['force'])) {
    $force = ' --force ';
}

$notify = '';
if (!empty($options['notify'])) {
    $notify = ' --notify ';
}

$horodate = '';
if (!empty($options['horodate'])) {
    $horodate = ' --horodate ';
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
}

// Make worker lists

$joblists = array();
$i = 0;
foreach ($allhosts as $h) {
    $joblist[$i][] = $h->id;
    $i++;
    if ($i == $options['workers']) {
        $i = 0;
    }
}

// Start spreading workers, and pass the list of vhost ids. Launch workers in background.
// Linux only implementation.

$i = 1;
foreach ($joblist as $jl) {
    $jobids = array();
    if (!empty($jl)) {
        $hids = implode(',', $jl);
        $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_course_categories_worker.php {$debug} --nodes=\"$hids\" ";
        $workercmd .= " {$logroot} {$horodate} {$force} {$verbose} {$notify} {$hardstop}";
        if ($options['distributed']) {
            $workercmd .= ' &';
        }
        echo "Executing $workercmd\n######################################################\n";
        $output = array();
        exec($workercmd, $output, $return);
        if ($return) {
            if (!empty($options['fullstop'])) {
                echo implode("\n", $output)."\n";
                die("Worker ended with error");
            }
            die("Worker ended with error:\n");
            echo implode("\n", $output)."\n";
        }
        if (!$options['distributed'] && !empty($options['verbose'])) {
            echo implode("\n", $output);
        }
        $i++;
        sleep(JOB_INTERLEAVE);
    }
}