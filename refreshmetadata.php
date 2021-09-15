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
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_structures.php');

$url = new moodle_url('/local/ent_installer/refreshmetadata.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$getidstr = get_string('getinstitutionidservice', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($getidstr);
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sitemetadata', 'local_ent_installer'));

$config = get_config('local_ent_installer');

if (!empty($config->institution_id)) {
    $unaliased = local_ent_installer_strip_alias($config->institution_id)[0];
    $ids = explode(',', $unaliased);

    $metadatas = array();
    foreach ($ids as $iid) {
        $metadatas = local_ent_installer_ldap_search_institution_id($ldapauth, $search, $searchby = 'name');
    }

    $metadatastr = serialize($metadatas);

    $DB->set_field('local_vmoodle', 'metadata', $metadatastr, array('vhostname' => $CFG->wwwroot));
    echo $OUTPUT->notification(get_string('metadataupdated', 'local_ent_installer'), 'notifysuccess');
}

echo '<div class="return-button">';
$buttonurl = new moodle_url('');
echo $OUTPUT->single_button($buttonurl, get_string('backtosettings', 'local_ent_sintaller'));
echo '</div>';

echo $OUTPUT->footer();