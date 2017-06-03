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

/**
 * An accessory script allowing to query the ENT annuary 
 * for school IDs
 *
 * Implementation Specific : ATOS / ENT Atrium Paca, Toutatice ENT Bretagne
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/ent_installer/getid_form.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib.php');

$url = new moodle_url('/local/ent_installer/getid.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$getidstr = get_string('getinstitutionidservice', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($getidstr);
$PAGE->set_pagelayout('admin');

$form = new GetIdForm();

// Get ldap params from real ldap plugin.
$ldapauth = get_auth_plugin('ldap');

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', array('section' => 'local_ent_installer')));
}

$results = array();
if ($data = $form->get_data()) {
    if (!isset($data->searchby)) {
        $data->searchby = 'name';
    }
    $results = local_ent_installer_ldap_search_institution_id($ldapauth, $data->search, $data->searchby);

    $form->set_data($data);
}

echo $OUTPUT->header();

echo $OUTPUT->heading($getidstr);

if (!empty($results)) {
    $table = new html_table();
    $table->head = array(get_string('id', 'local_ent_installer'), '', get_string('name'), get_string('city'));
    $table->width = '90%';
    $table->size = array('20%', '10%', '50%', '20%');
    $table->align = array('left', 'center', 'left', 'left');

    foreach ($results as $result) {
        preg_match('/(\d+)(\D)/', $result->id, $matches);
        $numid = $matches[1];
        $keychar = $matches[2];
        $table->data[] = array($numid, $keychar, $result->name, $result->city);
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->box(get_string('noresults', 'local_ent_installer'));
}

if (get_config('showbenches', 'local_ent_installer')) {
    global $LDAPQUERYTRACE;
    echo $OUTPUT->box($LDAPQUERYTRACE, 'technical-output');
}

$form->display();

echo $OUTPUT->footer();
