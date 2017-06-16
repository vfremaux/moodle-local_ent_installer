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
        'fulldelete'        => false,
        'force'             => false,
        'empty'             => false,
        'role'              => false,
        'verbose'           => false,
        'logroot'           => false,
        'horodate'          => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'l' => 'logroot',
        'D' => 'fulldelete',
        'm' => 'logmode',
        'v' => 'verbose',
        'f' => 'force',
        'e' => 'empty',
        'r' => 'role',
        'H' => 'horodate',
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
        -D, --fulldelete    propagates a full delete option to final workers
        -m, --logmode       'append' or 'overwrite'
        -f, --force         Force updating accounts even if not modified in user sourse.
        -e, --empty         Empty user structures if no more users in it.
        -r, --role          Role to process if not empty : (eleve,enseignant,administration).
        -v, --verbose       More output.
        -H, --horodate      Horodate log files.

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

$verbose = '';
if (!empty($options['verbose'])) {
    $verbose = ' --verbose ';
}

// Fire sequential synchronisation.
mtrace("Starting worker for nodes ".$options['nodes']);

$fulldelete = '';
if (!empty($options['fulldelete'])) {
    $fulldelete = ' --fulldelete ';
}

$runtime = strftime('%Y%m%d%H%i', time());

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {

    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));

    if (!empty($options['logroot'])) {
        $logfile = $options['logroot'].'/ent_sync_'.$host->shortname;
        if (!empty($options['horodate'])) {
            $logfile .= '_'.$runtime;
        }
        $logfile .= '.log';
        $LOG = fopen($logfile, $options['logmode']);
    }

    if (isset($LOG)) {
        fputs($LOG, "Starting worker for node {$host->shortname}\n");
    };

    if (isset($LOG)) {
        fputs($LOG, "\nStarting user process for node $nodeid\n");
    }
    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_users.php --host={$host->vhostname} {$force} {$role} {$fulldelete}";
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
            die ("User Worker failed");
        } else {
            echo "Cohort Worker execution error on {$host->vhostname}... Continuing anyway\n";
        }
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("\nStarting cohort process for node $nodeid\n");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_cohorts.php --host={$host->vhostname} {$force} {$empty}";
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
            fputs($LOG, 'Process failure. No output of cohort feeder.');
        }
        if (!empty($options['hardstop'])) {
            die ("Cohort Worker failed");
        } else {
            echo "Cohort Worker execution error on {$host->vhostname}... Continuing anyway\n";
        }
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("\nStarting role assignments process for node $nodeid\n");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_roleassigns.php --host={$host->vhostname} {$force}";
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
            fputs($LOG, 'Process failure. No output of cohort feeder.');
        }
        if (!empty($options['hardstop'])) {
            die ("Role assignment Worker failed");
        } else {
            echo "role assignment execution error on {$host->vhostname}... Continuing anyway\n";
        }
    }
    sleep(ENT_INSTALLER_SYNC_INTERHOST);

    mtrace("\nStarting coursegroup process for node $nodeid\n");

    $cmd = "php {$CFG->dirroot}/local/ent_installer/cli/sync_groups.php --host={$host->vhostname} {$force} {$empty}";
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
            fputs($LOG, 'Process failure. No output of coursegroups feeder.');
        }
        if (!empty($options['hardstop'])) {
            die ("Course groups Worker failed");
        } else {
            echo "Course Groups Worker execution error on {$host->vhostname}... Continuing anyway\n";
        }
    }

    fclose($LOG);

    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

return 0;