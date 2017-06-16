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

define('ENT_INSTALLER_SYNC_MAX_WORKERS', 2);
define('JOB_INTERLEAVE', 2);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
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
        'empty'            => false,
        'role'             => false,
        'verbose'          => false,
        'notify'           => false,
        'hardstop'         => false,
    ),
    array(
        'h' => 'help',
        'w' => 'workers',
        'd' => 'distributed',
        'D' => 'fulldelete',
        'e' => 'empty',
        'l' => 'logroot',
        'f' => 'force',
        'r' => 'role',
        'v' => 'verbose',
        'H' => 'horodate',
        'N' => 'notify',
        'S' => 'hardstop',
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
    -d, --distributed   Distributed operations.
    -l, --logroot       Root directory for logs.
    -D, --fulldelete    Propagates a full delete option to all workers.
    -f, --force         Force updating accounts even if not modified in user sourse.
    -e, --empty         Empty user structures if no more users in it.
    -r, --role          Role to process if not empty : (eleve,enseignant,administration).
    -v, --verbose       More output.
    -H, --horodate      Horodate log files.
    -N, --notify        Notify on failure.
    -S, --hardstop      Stop on first failure.

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

$force = '';
if (!empty($options['force'])) {
    $force = '--force';
}

$empty = '';
if (!empty($options['empty'])) {
    $empty = '--empty';
}

$notify = '';
if (!empty($options['notify'])) {
    $notify = '--notify';
}

$hardstop = '';
if (!empty($options['hardstop'])) {
    $hardstop = '--hardstop';
}

$horodate = '';
if (!empty($options['horodate'])) {
    $horodate = '--horodate';
}

$logroot = '';
if (!empty($options['logroot'])) {
    $logroot = '--logroot';
}

$role = '';
if (!empty($options['role']) && in_array($options['role'], array('eleve', 'enseignant', 'administration'))) {
    $role = '--role='.$options['role'];
}

$verbose = '';
if (!empty($options['verbose'])) {
    echo "checking options\n";
    $verbose = '--verbose';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

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
        $workercmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_hosts_worker.php --nodes=\"$hids\" ";
        $workercmd .= "--logroot={$logroot} {$horodate} {$force} {$role} {$empty} {$verbose} {$fulldelete} {$notify} {$hardstop}";
        if ($options['distributed']) {
            $workercmd .= ' &';
        }
        mtrace("Executing $workercmd\n######################################################\n");
        $output = array();
        exec($workercmd, $output, $return);
        if ($return) {
            die("Worker ended with error");
        }
        if (!$options['distributed']) {
            mtrace(implode("\n", $output));
        }
        $i++;
        sleep(JOB_INTERLEAVE);
    }
}