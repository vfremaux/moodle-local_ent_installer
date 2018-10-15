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
 * Form for activating manual resync of a single course.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/ent_installer/sync_course_form.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_courses.php');

$url = new moodle_url('/local/ent_installer/synccourse.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('local/ent_installer:sync', $systemcontext);

$syncstr = get_string('synchronisemoodle', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($syncstr);
$PAGE->set_pagelayout('admin');
$PAGE->requires->js_call_amd('local_ent_installer/synccourse', 'init');

$mform = new SyncCourseForm($url, null, 'get');

// Get ldap params from real ldap plugin.
$ldapauth = get_auth_plugin('ldap');

if ($mform->is_cancelled()) {
    if (has_capability('moodle/site:config', $systemcontext)) {
        redirect(new moodle_url('/local/ent_installer/sync.php', array()));
    } else {
        redirect($CFG->wwwroot);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($syncstr);

if ($data = $mform->get_data()) {

    require_sesskey();

    // Secure the reception of cid.
    $data->cid = $_REQUEST['cid'];

    // Get ldap params from real ldap plugin.
    $ldapauth = get_auth_plugin('ldap');

    // Run the customised synchro.
    $options['force'] = false;
    $options['simulate'] = @$data->simulate;
    $options['verbose'] = @$data->verbose;
    $options['cid'] = @$data->cid;
    $options['operation'] = @$data->operation;

    echo '<div class="console">';
    echo '<pre>';
    local_ent_installer_sync_courses($ldapauth, $options);
    echo '</pre>';
    echo '</div>';

} else {
    $mform->display();
}

echo '<p><center>';
if (has_capability('moodle/site:config', $systemcontext)) {
    $buttonurl = new moodle_url('/admin/category.php', array('category' => 'local_ent_installer'));
    echo $OUTPUT->single_button($buttonurl, get_string('backtosettings', 'local_ent_installer'));
} else {
    echo $OUTPUT->single_button($CFG->wwwroot, get_string('backtosite', 'local_ent_installer'));
}
echo '</center></p>';
echo $OUTPUT->footer();
