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
require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // CLI only functions
require_once($CFG->dirroot.'/local/ent_installer/locallib.php'); // CLI only functions

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'nodes'             => false,
        'fulldelete'        => false,
        'force'             => false,
        'role'              => false,
        'verbose'           => false,
        'logroot'           => false,
        'horodate'          => false,
        'notify'            => false,
        'fullstop'          => false,
        'debug'             => false,
        'mail'              => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logroot',
        'x' => 'fulldelete',
        'm' => 'logmode',
        'v' => 'verbose',
        'f' => 'force',
        'r' => 'role',
        'H' => 'horodate',
        'N' => 'notify',
        's' => 'fullstop',
        'd' => 'debug',
        'M' => 'mail',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo get_string('cliunknowoption', 'admin', $unrecognized)."\n";
    exit(1);
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
    Command line ENT Sync worker.

    Options:
    -h, --help          Print out this help
    -n, --nodes         Node ids to work with.
    -l, --logfile       the log file to use. No log if not defined
    -x, --fulldelete    propagates a full delete option to final workers
    -m, --logmode       'append' or 'overwrite'
    -f, --force         Force updating accounts even if not modified in user sourse.
    -r, --role          Role to process if not empty : (eleve,enseignant,administration).
    -v, --verbose       More output.
    -H, --horodate      If set, horodates log files.
    -N, --notify        If present will send a mail when a sync host fails.
    -s, --fullstop      If present, will stop on first errored worker result.
    -d, --debug         Turns on debug mode in worker.
    -M, --mail          Sends mail on key process phases. 1 : worker level, 2 : up to task level

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

$role = '';
if (!empty($options['role']) && in_array($options['role'], array('eleve', 'enseignant', 'administration'))) {
    $role = ' --role='.$options['role'];
}

$verbose = '';
if (!empty($options['verbose'])) {
    $verbose = ' --verbose ';
}

$mailmode = @$options['mail'];
$mailmess = '';
$mail = '';
if ($mailmode > 0) {
    $nextmailmode = $mailmode - 1;
    if ($nextmailmode > 0) {
        $mail = '--mail='.$nextmailmode;
    }
}

// Fire sequential synchronisation.
mtrace("Starting worker for nodes ".$options['nodes']);

$fulldelete = '';
if (!empty($options['fulldelete'])) {
    $fulldelete = ' --fulldelete ';
}

$nodes = explode(',', $options['nodes']);
$i = 0;
$numhosts = count($nodes);
foreach ($nodes as $nodeid) {

    if (!empty($options['logroot'])) {
        $logfile = $options['logroot'].'/ent_sync_users_'.$host->shortname;
        if (!empty($options['horodate'])) {
            $logfile .= '_'.$runtime;
        }
        $logfile .= '.log';
        $LOG = fopen($logfile, $options['logmode']);
    }

    if (isset($LOG)) {
        fputs($LOG, "Starting Users worker for nodes {$options['nodes']}\n");
    };

    mtrace("\nStarting Users process for node $nodeid");

    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_users.php {$debug} ";
    $cmd .= "{$force} {$role} {$fulldelete} {$verbose} {$mail}";
    $cmd .= " --host={$host->vhostname}";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    $mailmess .= $cmd."\n";
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
            $mailmess .= "Worker failed. Stopping.\n";
            die ("Worker failed");
        }
        echo "User Worker execution error on {$host->vhostname}:\n";
        echo implode("\n", $output)."\n";
        $mailmess .= "Worker failed. Pursuing anyway\n";
        echo "Pursuing anyway\n";
    }

    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }

    if (isset($LOG)) {
        fclose($LOG);
    }

    $i++;
    if ($mailmode >= 1) {
        vmoodle_send_cli_progress($numhosts, $i, 'syncusersworker');
    }

    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

if ($mailmode >= 1) {
    local_ent_installer_send_mail_checkpoint('sync_users_worker', $mailmess);
}

return 0;