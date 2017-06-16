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
 * Form for activating manual resync.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This allows 2 minutes synchronisation before trigerring an overtime.
define('OVERTIME_THRESHOLD', 120);

/**
 * get strings from a special install file, whatever
 * moodle active language is on
 * @return the string or the marked key if missing
 *
 */
function ent_installer_string($stringkey) {
    global $CFG;
    static $installstrings = null;

    if (empty($installstrings)) {
        include_once($CFG->dirroot.'/local/ent_installer/db/install_strings.php');
        $installstrings = $string; // Loads string array once.
    }

    if (!array_key_exists($stringkey, $installstrings)) {
        return "[[install::$stringkey]]";
    }
    return $installstrings[$stringkey];
}

function local_ent_installer_generate_email($user) {
    global $CFG;

    $fullname = strtolower($user->firstname.'.'.$user->lastname);
    $fakedomain = get_config('local_ent_installer', 'fake_email_domain');

    if (empty($fakedomain)) {
        $fakedomain = 'foomail.com';
    }

    return $fullname.'@'.$fakedomain;
}

/**
 * If an input data has the term 'alias' in the textual value, then
 * extracts the aloas and returns both parts separated.
 * @param string $data the input data
 */
function local_ent_installer_strip_alias($data) {

    $parts = explode('alias', $data);
    if (count($parts) == 1) {
        $data = $parts[0];
        $alias = '';
    } else {
        $data = trim($parts[0]);
        $alias = trim($parts[1]);
    }

    return array($data, $alias);
}

function local_ent_installer_relocate_courses($simulate = false) {
    global $DB;

    $courses = $DB->get_records('course', array(), 'shortname', 'id, shortname, fullname');

    if ($courses) {
        foreach ($courses as $c) {
            mtrace("relocating course {$c->shortname} {$c->fullname} ");
            $result = local_ent_installer_relocate_course($courseid, $simulate);
        }
    }
}

/**
 * Provides an uniform scheme for a teacher category identifier.
 * @param object $user a user object
 * @return string
 */
function local_ent_installer_get_teacher_cat_idnumber($user) {
    $teachercatidnum = strtoupper($user->lastname).'_'.substr(strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';

    return $teachercatidnum;
}

/**
 * Provides an uniforme naming scheme for a teacher category
 * @param object $user
 * @return string
 */
function local_ent_installer_teacher_category_name($user) {
    static $USERFIELDS;
    global $DB;

    if (empty($USERFIELDS)) {
        // Initialise once.
        $USERFIELDS = local_ent_installer_load_user_fields();
    }

    $config = get_config('local_ent_installer');

    if (!empty($config->teacher_mask_firstname)) {
        // Initialize firstname
        preg_match_all('/[\wéèöëêôÏîàùç]+/u', $user->firstname, $matches);
        $firstnameinitials = '';
        foreach (array_values($matches) as $res) {
            $firstnameinitials .= core_text::strtoupper(substr($res[0], 0, 1)).'.';
        }

        if (empty($user->personalTitle)) {
            $personaltitle = $DB->get_field('user_info_data', 'data', array('fieldid' => $USERFIELDS['personaltitle'], 'userid' => $user->id));
        }

        $name = $personaltitle.' '.$firstnameinitials.' '.$user->lastname;
    } else {
        $name = fullname($user);
    }
    return $name;
}

/**
 * Fix categories idnumbers to help reordering. We find teacher owner of category interrogating
 * the role assignements
 */
function local_ent_installer_fix_teacher_categories() {
    global $DB;

    $config = get_config('local_ent_installer');

    if (empty($config->teacher_stub_category)) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Teacher category unconfigured");
        }
        return;
    }

    // Get all categories in the teacher's stub.
    if (!$DB->get_record('course_categories', array('id' => $config->teacher_stub_category))) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Teacher category missing");
        }
        return;
    }

    $allcats = $DB->get_records('course_categories', array('parent' => $config->teacher_stub_category), 'sortorder', 'id,idnumber,sortorder');
    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
    if ($allcats) {
        foreach ($allcats as $cat) {
            $catcontext = context_coursecat::instance($cat->id);
            $managers = $DB->get_records('role_assignments', array('roleid' => $managerrole->id, 'contextid' => $catcontext->id));
            if (count($managers) > 1) {
                mtrace("Warning : More than one in category $cat->id : $cat->name");
            }
            if ($managers) {
                // We usually expect one manager here
                $first = array_shift($managers);
                $user = $DB->get_record('user', array('id' => $first->userid));
                $teachercatidnum = core_text::strtoupper($user->lastname).'_'.core_text::substr(core_text::strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';
                $DB->set_field('course_categories', 'idnumber', $teachercatidnum, array('id' => $cat->id));
                $DB->set_field('course_categories', 'name', local_ent_installer_teacher_category_name($user), array('id' => $cat->id));
            }
        }
    }
    local_ent_installer_reorder_teacher_categories();
}

/**
 * Reorders teachers category based on teacher name stored in idnumber
 */
function local_ent_installer_reorder_teacher_categories() {
    global $DB;

    $teacherrootcat = get_config('local_ent_installer', 'teacher_stub_category');

    if (empty($teacherrootcat)) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Ordering : Teacher category unconfigured");
        }
        return;
    }

    if (!$teacherstub = $DB->get_record('course_categories', array('id' => $teacherrootcat))) {
        if (defined('CLI_SCRIPT')) {
            mtrace("Ordering : Teacher category missing");
        }
        return;
    }

    $sort = 'idnumber';
    $cattosort = coursecat::get($teacherrootcat, MUST_EXIST, true);
    \core_course\management\helper::action_category_resort_subcategories($cattosort, $sort, true);
}

/**
 * Relocates the course in the teacher's own category if :
 * - he is single editing teacher
 * - he is oldest editing teacher
 */
function local_ent_installer_relocate_course($courseid, $simulate = false) {
    global $DB;

    // Set this to true to enable the "first enrolled teachers owns" additional method.
    $hardconfigguessfirstteacher = false;

    $context = context_course::instance($courseid);

    // True teachers
    if (!$teachers = get_users_by_capability($context, 'moodle/course:manageactivities', '*')) {
        return false;
    }

    if (count($teachers) == 1) {
        // We have a single editing teacher. He is owner of the course.
        $teachercatidnum = local_ent_installer_get_teacher_cat_idnumber($user);
        if ($teachercat = $DB->get_record('course_categories', array('idnumber' => $teachercatidnum))) {
            // Relocate if teacher cat exists.
            if (!$simulate) {
                $DB->set_field('course', 'category', $teachercat->id, array('id' => $courseid));
            } else {
                mtrace("[SIMULATION] Relocating course $courseid to category $teachercatidnun");
            }
            return true;
        }
    } else if ($hardconfigguessfirstteacher) {

        list($insql, $params) = $DB->get_in_or_equal('id', array_keys($teachers));
        $params[] = $courseid;

        $oldest = null;
        foreach ($teachers as $t) {
            // Seek for the oldest enrolment.
            $sql = "
                SELECT
                    id,
                    ue.userid,
                    timestart
                FROM
                    {user_enrolments} ue,
                    {enrol} e
                WHERE
                    ue.enrolid = e.id AND
                    ue.userid $insql AND
                    e.course = ?
            ";

            $first = null;
            if ($teacherenrols = $DB->get_records_sql($sql, $params)) {
                // Get the oldest one.
                foreach ($teacherenrols as $te) {
                    if (is_null($first)) {
                        $first = $te;
                    }
                    if ($te->timestart < $first->timestart) {
                        $first = $te;
                    }
                }
            }

            if (!is_null($first)) {
                $firstuser = $DB->get_record('user', array('id' => $first->userid));
                $teachercatidnum = local_ent_get_teacher_cat_idnumber($firstuser);
                if ($teachercat = $DB->get_record('course_categories', array('idnumber' => $teachercatidnum))) {
                    // Relocate if teacher cat exists.
                    if (!$simulate) {
                        $DB->set_field('course', 'category', $teachercat->id, array('id' => $courseid));
                    } else {
                        mtrace("[SIMULATION] Relocating course $courseid to category $teachercatidnun as oldest enrolled");
                    }
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Fix cohorts that are NOT prefixed to the N-1 prefix (old cohorts)
 */
function local_ent_installer_fix_unprefixed_cohorts() {
    global $DB;

    $config = get_config('local_ent_installer');

    if (!empty($config->cohort_ix)) {
        // Remove eventual alias
        $institutionids = preg_replace('/alias.*$/', '', $config->institution_id);
        $iids = explode(',', $institutionids);

        foreach ($iids as $iid) {
            // Usually one.

            echo "\tProcessing institution id $iid\n";
            $cohorts = $DB->get_records('cohort');

            if ($cohorts) {
                $oldprefix = $config->cohort_ix - 1;

                foreach ($cohorts as $ch) {
                    if ((strpos($ch->name, $oldprefix.'_') === 0) ||
                        (strpos($ch->name, $config->cohort_ix.'_') === 0)) {
                        // If already formated with yearly prefix, ignore.
                        continue;
                    }
                    // Remove possible trailer in name.
                    $name = preg_replace('/\\('.$iid.'\\)$/', '', $ch->name);
                    // Prefix the name.
                    $name = $oldprefix.'_'.$ch->name;
                    $DB->set_field('cohort', 'name', $name, array('id' => $ch->id));

                    // Prefix the idnumber.
                    $idnumber = $oldprefix.'_'.$ch->idnumber;
                    $DB->set_field('cohort', 'idnumber', $idnumber, array('id' => $ch->id));
                }
            }
        }
    } else {
        echo "No cohort prefix defined. Nothing done.\n";
    }

}