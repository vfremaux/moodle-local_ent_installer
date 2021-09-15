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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

$cohortid = required_param('id', PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);

require_login();

$cohort = $DB->get_record('cohort', array('id' => $cohortid));

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
}

$manager = has_capability('moodle/cohort:manage', $context);
if (!$manager) {
    require_capability('moodle/cohort:view', $context);
}

$strcohorts = get_string('automatedcohortmembers', 'local_ent_installer');

if ($category || ($context->contextlevel == CONTEXT_SYSTEM)) {
    $PAGE->set_pagelayout('admin');
    $PAGE->set_context($context);
    $PAGE->set_url('/local/ent_installer/cohort/list.php', array('id' => $cohortid, 'contextid' => $contextid));
    $PAGE->navbar->add(get_string('cohorts', 'cohort'), new moodle_url('/cohort/index.php', array('contextid' => $contextid)));
    $PAGE->set_title($strcohorts);
    $PAGE->set_heading($COURSE->fullname);
} else {
    admin_externalpage_setup('cohorts', '', null, '', array('pagelayout' => 'report'));
}

echo $OUTPUT->header();

$sql = "
    SELECT
        u.*,
        cm.timeadded as cohorttimeadded
    FROM
        {cohort_members} cm,
        {user} u
    WHERE
        cm.userid = u.id AND
        cm.cohortid = ?
    ORDER BY u.lastname,u.firstname
";

$members = $DB->get_records_sql($sql, array($cohort->id));

if (!empty($members)) {
    $table = new html_table();
    $table->head = array('', '', '', '');
    $table->size = array('10%', '25%', '25%', '40%');
    $table->align = array('left', 'left', 'left', 'right');
    $table->width = '90%';
    $i = 0;
    foreach ($members as $u) {
        $table->data[] = array($OUTPUT->user_picture($u), $u->lastname, $u->firstname, userdate($u->cohorttimeadded));
        if ($u->deleted) {
            $rowclasses = 'deleted-user';
        } else if ($u->suspended) {
            $rowclasses = 'suspended-user';
        } else {
            $rowclasses = 'normal-user';
        }
        $rowclasses .= local_ent_installer_get_profile_classes($u);

        $table->rowclasses[$i] = $rowclasses;
        $i++;
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nousers', 'local_ent_installer'));
}

$buttonurl = new moodle_url('/cohort/index.php', array('cohortid' => $contextid));
echo $OUTPUT->single_button($buttonurl, get_string('backtocohorts', 'local_ent_installer'));

echo $OUTPUT->footer();