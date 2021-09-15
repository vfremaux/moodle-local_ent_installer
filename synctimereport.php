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

require('../../config.php');
require_once($CFG->dirroot.'/local/ent_installer/getid_form.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib_users.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');

$url = new moodle_url('/local/ent_installer/synctimereport.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);
local_vflibs_require_jqplot_libs();

// Process controller.
$reset = optional_param('reset', 0, PARAM_INT);
if ($reset) {
    $DB->delete_records('local_ent_installer', array());
}

$titlestr = get_string('synctimetitle', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($titlestr);
$PAGE->set_pagelayout('admin');
$murl = new moodle_url('/admin/category.php', array('category' => 'local_ent_installer'));
$PAGE->navbar->add(get_string('pluginname', 'local_ent_installer'), $murl);
$PAGE->navbar->add(get_string('syncbench', 'local_ent_installer'));

// Three month horizon.
$horizon = time() - DAYSECS * 90;

$renderer = $PAGE->get_renderer('local_ent_installer');

echo $OUTPUT->header();

// Users drawing.

$select = " synctype = 'users' AND timestart > ? ";
$usersdata = $DB->get_records_select('local_ent_installer', $select, [$horizon]);
echo $renderer->print_time_report('users', $usersdata);

// Cohorts drawing

$select = " synctype = 'cohorts' AND timestart > ? ";
$cohortdata = $DB->get_records_select('local_ent_installer', $select, [$horizon]);
echo $renderer->print_time_report('cohorts', $cohortdata);

// End of page.

echo '<center>';
$url = new moodle_url('/local/ent_installer/synctimereport.php', array('reset' => 1));
echo $OUTPUT->single_button($url, get_string('reset', 'local_ent_installer'));
echo '</center>';

echo $OUTPUT->footer();
