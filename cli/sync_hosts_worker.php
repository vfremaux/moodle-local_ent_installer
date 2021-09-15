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
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');
require_once($CFG->dirroot.'/local/ent_installer/lib.php');

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'nodes'             => false,
        'fulldelete'        => false,
        'force'             => false,
        'empty'             => false,
        'role'              => false,
        'verbose'           => false,
        'logroot'           => false,
        'debug'             => false,
        'horodate'          => false,
        'fullstop'          => false,
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
        'e' => 'empty',
        'r' => 'role',
        'H' => 'horodate',
        'd' => 'debug',
        's' => 'fullstop',
        'M' => 'mail',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
    Command line ENT Sync worker. A Worker can process serially a set of host nodes.

        Options:
        -h, --help          Print out this help
        -n, --nodes         Node ids to work with.
        -l, --logroot       the log root where to write files. No log if not defined
        -x, --fulldelete    propagates a full delete option to final workers
        -m, --logmode       'append' or 'overwrite'
        -f, --force         Force updating accounts even if not modified in user sourse.
        -e, --empty         Empty user structures if no more users in it.
        -r, --role          Role to process if not empty : (eleve,enseignant,administration).
        -v, --verbose       More output.
        -H, --horodate      Horodate log files.
        -d, --debug         Turn debug on.
        -s, --fullstop      Stop on first error.
        -M, --mail          0, 1, 2. If not 0, sends mail to admin when process finishes at its level. 1 : worker, 2 : task

        "; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['logmode'])) {
    $options['logmode'] = 'w';
}

$force = '';
if (!empty($options['force'])) {
    $force = ' --force ';
}

$empty = '';
if (!empty($options['empty'])) {
    $empty = ' --empty ';
}

$role = '';
if (!empty($options['role']) && in_array($options['role'], array('eleve', 'enseignant', 'administration'))) {
    $role = ' --role='.$options['role'];
}

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$verbose = '';
if (!empty($options['verbose'])) {
    $verbose = ' --verbose ';
}

$mail = '';
if (!empty($options['mail']) && $options['mail'] > 1) {
    $mail = ' --mail='.$options['mail'];
}

// Fire sequential synchronisation.
mtrace("Starting worker for nodes ".$options['nodes']);

$fulldelete = '';
if (!empty($options['fulldelete'])) {
    $fulldelete = ' --fulldelete ';
}

$runtime = strftime('%Y%m%d%H%s', time());

/**
 * We run all tools on all nodes. At this level we do not know wether
 * we can or annot run each task. the task will know with its local host level
 * configuration of ent_installer processes.
 */

$mailmess = '';

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {

    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));

    $LOG = false;
    if (!empty($options['logroot'])) {
        $logfile = $options['logroot'].'/ent_sync_'.$host->shortname;
        if (!empty($options['horodate'])) {
            $logfile .= '_'.$runtime;
        }
        $logfile .= '.log';
        $LOG = fopen($logfile, $options['logmode']);
    }

    if ($LOG) {
        fputs($LOG, "Starting Host (all syncs) worker for node {$host->shortname}\n");
    };

    mtrace("Starting Users process for node $nodeid");

    if ($LOG) {
        fputs($LOG, "\nStarting user process for node $nodeid\n");
    }
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_users.php {$debug} --host={$host->vhostname} ";
    $cmd .= " {$force} {$role} {$fulldelete} {$verbose}  {$mail}";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    $mailmess .= "Executing $cmd\n";
    exec($cmd, $output, $return);
    if ($LOG) {
        fputs($LOG, "\n$cmd\n#-------------------\n");
        fputs($LOG, implode("\n", $output));
    };
    if ($return) {
        if ($LOG) {
            fputs($LOG, "Process failure. No output of user feeder.\n");
        }
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            $mailmess .= "Full stopping on child error\n";
            if ($options['mail'] >= 1) {
                local_ent_installer_send_mail_checkpoint('sync_hosts_worker', $mailmess);
            }
            die ("User Worker failed");
        } else {
            $mess = "Users Worker execution error on {$host->vhostname}... Continuing anyway\n";
            echo $mess;
            $mailmess .= $mess;
        }
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("Starting Cohorts process for node $nodeid");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_cohorts.php {$debug} --host={$host->vhostname}";
    $cmd .= " {$verbose} {$force} {$empty} {$mail} ";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    exec($cmd, $output, $return);
    if ($LOG) {
        fputs($LOG, "\n$cmd\n#-------------------\n");
        fputs($LOG, implode("\n", $output));
    };
    if ($return) {
        if ($LOG) {
            fputs($LOG, 'Process failure. No output of cohort feeder.');
        }
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            $mailmess .= "Full stopping on child error\n";
            if ($options['mail'] >= 1) {
                local_ent_installer_send_mail_checkpoint('sync_hosts_worker', $mailmess);
            }
            die ("Cohort Worker failed");
        } else {
            $mess = "Cohort Worker execution error on {$host->vhostname}:\n";
            echo implode("\n", $output)."\n";
            $mailmess .= $mess;
            echo $mess;
            echo "Continuing anyway.\n";
        }
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }

    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("Starting Role assignments process for node $nodeid");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_roleassigns.php {$debug} --host={$host->vhostname}";
    $cmd .= " {$verbose} {$force}  {$mail}";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    exec($cmd, $output, $return);
    if ($LOG) {
        fputs($LOG, "\n$cmd\n#-------------------\n");
        fputs($LOG, implode("\n", $output));
    };
    if ($return) {
        if ($LOG) {
            fputs($LOG, 'Process failure. No output of cohort feeder.');
        }
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            $mailmess .= "Full stopping on child error\n";
            if ($options['mail'] >= 1) {
                local_ent_installer_send_mail_checkpoint('sync_hosts_worker', $mailmess);
            }
            die ("Role assignment Worker failed\n");
        } else {
            $mess = "role assignment execution error on {$host->vhostname}:\n";
            echo $mess;
            $mailmess .= $mess;
            echo implode("\n", $output)."\n";
            echo "Pursuing anyway.\n";
        }
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("Starting Course group process for node $nodeid");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_groups.php {$debug} --host={$host->vhostname}";
    $cmd .= " {$force} {$empty} {$verbose}  {$mail} ";
    $return = 0;
    $output = array();
    mtrace("\n".$cmd);
    exec($cmd, $output, $return);
    if ($LOG) {
        fputs($LOG, "\n$cmd\n#-------------------\n");
        fputs($LOG, implode("\n", $output));
    };
    if ($return) {
        if ($LOG) {
            fputs($LOG, 'Process failure. No output of coursegroups feeder.');
        }
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            $mailmess .= "Full stopping on child error\n";
            if ($options['mail'] >= 1) {
                local_ent_installer_send_mail_checkpoint('sync_hosts_worker', $mailmess);
            }
            die ("Course groups Worker failed");
        } else {
            $mess = "Course Groups Worker execution error on {$host->vhostname}:\n";
            echo $mess;
            $mailmess .= $mess;
            echo implode("\n", $output)."\n";
            echo "Pursuing anyway.\n";
        }
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }

    if ($LOG) {
        fclose($LOG);
    }

    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

if ($options['mail'] >= 1) {
    local_ent_installer_send_mail_checkpoint('sync_hosts_worker', $mailmess);
}

return 0;