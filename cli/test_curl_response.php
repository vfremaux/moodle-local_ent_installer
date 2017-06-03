<?php

// We may need to block Web use
define('CLI_SCRIPT', true);

// We need block evaluation of vconfig because possible not yet created !
global $CLI_VMOODLE_PRECHECK;
$CLI_VMOODLE_PRECHECK = true;

require '../../../config.php';

echo "Curl check on $CFG->wwwroot\n";

$ch = curl_init($CFG->wwwroot);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

$output = curl_exec($ch);
if ($output) {
    echo "Curl working\n";
    return 0;
}

echo curl_error($ch)."\n";
return -1;