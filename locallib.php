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

require_once($CFG->dirroot.'/local/ent_installer/compatlib.php');
require_once($CFG->dirroot.'/local/ent_installer/lib.php');

// This allows 2 minutes synchronisation before trigering an overtime.
define('OVERTIME_THRESHOLD', 120);

// Alias operation on institution ids.
define('ALIAS_UNALIAS', 0); // Unalias and use individual institution ids.
define('ALIAS_USEALIAS', 1); // Use only the alias and forget individual institution ids
define('ALIAS_ADDALIAS', 2); // Add the alias expression to the scannable contexts

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
        $fakedomain = 'foomail.invalid';
    }

    return $fullname.'@'.$fakedomain;
}

/**
 * If an input data has the term 'alias' in the textual value, then
 * extracts the alias and returns both parts separated.
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

/**
 * Provides an uniform scheme for a teacher category identifier.
 * @param object $user a user object. If user is not given will return the cat identifier of
 * the current user.
 * @return string
 */
function local_ent_installer_get_teacher_cat_idnumber($user = null) {
    global $USER;

    if (is_null($user)) {
        $user = $USER;
    }

    $teachercatidnum = core_text::strtoupper($user->lastname).'_'.core_text::substr(core_text::strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';

    return $teachercatidnum;
}

/**
 * Provides an uniforme naming scheme for a teacher category
 * @param object $user
 * @return string
 */
function local_ent_installer_teacher_category_name($user) {
    static $userfields;
    global $DB;

    if (empty($userfields)) {
        // Initialise once.
        $userfields = local_ent_installer_load_user_fields();
    }

    $config = get_config('local_ent_installer');

    if (!empty($config->teacher_mask_firstname)) {
        // Initialize firstname.
        preg_match_all('/[\wéèöëêôÏîàùç]+/u', $user->firstname, $matches);
        $firstnameinitials = '';
        foreach (array_values($matches) as $res) {
            $firstnameinitials .= core_text::strtoupper(core_text::substr($res[0], 0, 1)).'.';
        }

        if (empty($user->personalTitle)) {
            $params = array('fieldid' => $userfields['personaltitle'], 'userid' => $user->id);
            $personaltitle = $DB->get_field('user_info_data', 'data', $params);
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

    $params = array('parent' => $config->teacher_stub_category);
    $allcats = $DB->get_records('course_categories', $params, 'sortorder', 'id,idnumber,sortorder');
    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
    if ($allcats) {
        foreach ($allcats as $cat) {
            $catcontext = context_coursecat::instance($cat->id);
            $managers = $DB->get_records('role_assignments', array('roleid' => $managerrole->id, 'contextid' => $catcontext->id));
            if (count($managers) > 1) {
                mtrace("Warning : More than one in category $cat->id : $cat->name");
            }
            if ($managers) {
                // We usually expect one manager here.
                $first = array_shift($managers);
                $user = $DB->get_record('user', array('id' => $first->userid));
                $teachercatidnum = core_text::strtoupper($user->lastname).'_';
                $teachercatidnum .= core_text::substr(core_text::strtoupper($user->firstname), 0, 1).'$'.$user->idnumber.'$CAT';
                $DB->set_field('course_categories', 'idnumber', $teachercatidnum, array('id' => $cat->id));
                $namevalue = local_ent_installer_teacher_category_name($user);
                $DB->set_field('course_categories', 'name', $namevalue, array('id' => $cat->id));
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
<<<<<<< HEAD
    $cattosort = \core_course_category::get($teacherrootcat, MUST_EXIST, true);
    \core_course\management\helper::action_category_resort_subcategories($cattosort, $sort, true);
=======
    $cattosort = local_ent_installer_coursecat_get($teacherrootcat, MUST_EXIST, true);
    if ($cattosort) {
        \core_course\management\helper::action_category_resort_subcategories($cattosort, $sort, true);
    }
>>>>>>> MOODLE_39_STABLE
}

/**
 * Relocates the course in the teacher's own category if :
 * - he is single editing teacher
 * - he is oldest editing teacher
 */
function local_ent_installer_relocate_course($courseid, $simulate = false) {
    global $DB;
    static $rolecache = array();
    static $usercache = array();

    /*
     * Set this to true to enable the "first enrolled teachers owns" additional method, Otherwise only courses
     * with only one editing teachers will be processed
     */
    $hardconfigguessfirstteacher = false;

    if ($courseid == SITEID) {
        // DO NOT relocate the site course !
        return;
    }

    $context = context_course::instance($courseid);
    if (empty($context) || empty($context->path)) {
        return;
    }
    // Need get this to ensure we are getting only the course context.
    $editorroles = get_roles_with_caps_in_context($context, array('moodle/course:manageactivities'));
    $ras = array();
    foreach ($editorroles as $rid) {
        if (!array_key_exists($rid, $rolecache)) {
            $rolecache[$rid] = $DB->get_record('role', array('id' => $rid));
        }
        $ra = get_users_from_role_on_context($rolecache[$rid], $context);
        if ($ra) {
            $ras = array_merge($ras, $ra);
        }
    }

    // True teachers in course context strictly.
    $teachers = array();
    foreach ($ras as $ra) {
        // Fill full teacher array.
        if (!array_key_exists($ra->userid, $usercache)) {
            $usercache[$ra->userid] = $DB->get_record('user', array('id' => $ra->userid));
        }
        $teachers[] = $usercache[$ra->userid];
    }

    if (empty($teachers)) {
        mtrace("No editing teacher for $courseid");
        return false;
    }

    $numteachers = count($teachers);

    if ($numteachers == 1) {
        // We have a single editing teacher. He is owner of the course.
        $teacher = array_pop($teachers);
        $teachercatidnum = local_ent_installer_get_teacher_cat_idnumber($teacher);
        if ($teachercat = $DB->get_record('course_categories', array('idnumber' => $teachercatidnum))) {
            // Relocate if teacher cat exists.
            if (!$simulate) {
                mtrace("Relocating course $courseid to category $teachercatidnum");
                $DB->set_field('course', 'category', $teachercat->id, array('id' => $courseid));
            } else {
                mtrace("[SIMULATION] Relocating course $courseid to category $teachercatidnum");
            }
            return true;
        } else {
            mtrace("Could not find teacher category $teachercatidnum");
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
                        mtrace("Relocating course $courseid to category $teachercatidnun as oldest enrolled");
                        $DB->set_field('course', 'category', $teachercat->id, array('id' => $courseid));
                    } else {
                        mtrace("[SIMULATION] Relocating course $courseid to category $teachercatidnun as oldest enrolled");
                    }
                    return true;
                } else {
                    mtrace("could not find teacher category $teachercatidnun");
                }
            }
        }
    } else {
        mtrace("No relocate conditions found. Too many ($numteachers) editing teachers");
    }

    return false;
}

function local_ent_installer_relocate_courses($simulate = false) {
    global $DB;

    $config = get_config('local_ent_installer');
    $protectedcats = explode(',', $config->protect_categories_from_relocate);

    $courses = $DB->get_records('course', array(), 'shortname', 'id, shortname, fullname, category');

    if ($courses) {
        foreach ($courses as $c) {
            if (!in_array($c->category, $protectedcats)) {
                mtrace(get_string('relocatingcourse', 'local_ent_installer', "{$c->shortname} {$c->fullname}"));
                $result = local_ent_installer_relocate_course($c->id, $simulate);
            } else {
                mtrace(get_string('relocatingcourseignored', 'local_ent_installer', "{$c->shortname} {$c->fullname}"));
            }
        }
    }
}

/**
 * Fix cohorts that are NOT prefixed to the N-1 prefix (old cohorts)
 */
function local_ent_installer_fix_unprefixed_cohorts() {
    global $DB;

    $config = get_config('local_ent_installer');

    if (!empty($config->cohort_ix)) {
        // Remove eventual alias.
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

/**
 * Installs a top category scheme for academic platforms.
 * @param boolean $simulate are we really doing it ?
 */
function local_ent_installer_install_categories($simulate = false) {
    global $CFG, $DB;

    $configcategories = get_config('local_ent_installer', 'initialcategories');
    $categories = (array) json_decode($configcategories);

    if (!empty($categories)) {
        foreach ($categories as $setting => $category) {

            list($plugin, $settingkey) = explode('/', $setting);

            preg_replace('#^/#', '', $category->name);
            $parts = explode('/', $category->name);
            $maxdepth = count($parts);
            $parentid = 0;
            $depth = 1;
            $path = '';
            $namepath = '';

            if (!isset($category->visible)) {
                $category->visible = 1;
            }

            /*
             * We explore by name from root for existing parts of path.
             * We create missing parts with no idnumber.
             * We may update an existing intermediary category if idnumber is available (last part).
             */
            foreach ($parts as $part) {
                $namepath .= '/'.$part;
                // Note that initial categories names and parent names should be unique in Moodle. Or this will fail.
                if (!$thiscat = $DB->get_record('course_categories', array('name' => $part))) {
                    if (!$simulate) {

                        if ($depth == $maxdepth) {
                            // This is the real category name to create.
                            // Pre check idnumber. We may already have one with this IDNum.
                            if (!empty($category->idnumber)) {
                                if ($oldcategory = $DB->get_record('course_categories', array('idnumber' => $category->idnumber))) {
                                    // Bind this category to plugin before leaving.
                                    $oldcategory->name = $part;
                                    $DB->update_record('course_categories', $oldrecord);
                                    local_ent_installer_bind_cat_to_plugin($plugin, $settingkey, $oldcategory, $simulate);
                                    continue 2;
                                }
                            }
                        }

                        // Do not try to create them twice or more times.
                        $catrec = new StdClass();
                        $catrec->parent = $parentid;
                        $catrec->visible = 1;
                        $catrec->visibleold = 1;
                        $catrec->timemodified = time();
                        $catrec->depth = $depth;
                        $catrec->name = $part;
                        if ($depth == $maxdepth) {
                            $catrec->idnumber = $category->idnumber;
                            // Fix category visibility on last node.
                            $catrec->visible = $category->visible;
                        }
<<<<<<< HEAD
                        $newcat = \core_course_category::create($catrec);
=======
                        $newcat = local_ent_installer_coursecat_create($catrec);
>>>>>>> MOODLE_39_STABLE
                        $parentid = $newcat->id;
                        if ($depth == $maxdepth) {
                            $category->id = $parentid;
                        }
                        $depth++;
                    } else {
                        mtrace("Category $namepath is missing at depth $depth. Will be created.");
                        $depth++;
                    }
                } else {
                    // We have a category of this name already.
<<<<<<< HEAD
                    $coursecat = \core_course_category::get($thiscat->id);
=======
                    $coursecat = local_ent_installer_coursecat_get($thiscat->id);
>>>>>>> MOODLE_39_STABLE
                    $parentid = $thiscat->id;
                    if (!$simulate) {
                        $thiscat->idnumber = $category->idnumber;
                        if ($depth == $maxdepth) {
                            // Fix category visibility on last node.
                            $thiscat->visible = $category->visible;
                            // Only update on last node.
                            $parentid = $coursecat->update($thiscat);
                            $category->id = $thiscat->id;
                        }
                    } else {
                        if ($depth == $maxdepth) {
                            mtrace("Category exists as $namepath. Will be updated. \n");
                        } else {
                            mtrace("Intermediary category exists as $namepath \n");
                        }
                    }
                    $depth++;
                }

                local_ent_installer_bind_cat_to_plugin($plugin, $settingkey, $category, $simulate);
            };
        }
    }
}

/**
 * Binds the category to a plugin setting
 * @param string $plugin plugin name
 * @param string $settingkey the setting  key
 * @param object $category the course category to bind
 * @param boolean $simulate are we really doing it ?
 */
function local_ent_installer_bind_cat_to_plugin($plugin, $settingkey, &$category, $simulate) {

    // Finally attempt to bind a setting if exists.
    $config = get_config($plugin);

    if (isset($config->$settingkey) && !empty($category->id)) {
        if (!$simulate) {
            set_config($settingkey, $category->id, $plugin);
        } else {
            mtrace("Will change setting $settingkey in $plugin \n");
        }
    } else {
        if ($simulate) {
            if (!isset($config->$settingkey)) {
                mtrace("Foo setting $settingkey in $plugin \n");
            } else {
                mtrace("Will change setting $settingkey in $plugin \n");
            }
        }
    }
}

function local_ent_installer_ensure_global_cohort_exists($type, $options) {
    global $DB;

    $config = get_config('local_ent_installer');
    $defaultidnums = array(
        'students' => 'ELE',
        'staff' => 'ENS',
        'adminstaff' => 'NENS',
        'admins' => 'ADM'
    );

    if (!in_array($type, array_keys($defaultidnums))) {
        return;
    }

    $key = $type.'_site_cohort_name';

    if (!empty($config->$key)) {

        // Getting site cohort idnumber.
        $list = local_ent_installer_strip_alias($config->institution_id);
        $institutionalias = @$list[1];
        if (empty($institutionalias)) {
            $idnumber = $config->cohort_ix.'_'.$config->institution_id.'_'.$defaultidnums[$type];
        } else {
            $idnumber = $config->cohort_ix.'_'.$institutionalias.'_'.$defaultidnums[$type];
        }

        if (!$oldcohort = $DB->get_record('cohort', array('idnumber' => $idnumber))) {

            $cohortname = $config->cohort_ix.' '.$config->$key;

            if (!empty($options['verbose'])) {
                mtrace("Creating missing site cohort $cohortname ");
            }

            $cohort = new StdClass;
            $cohort->name = $cohortname;
            $cohort->idnumber = $idnumber;
            $cohort->description = '';
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->timecreated = time();
            $cohort->timemodified = time();
            // Do not assign this cohort to local_ent_installer component.
            // We do not want these cohorts being droped by synchronisation.
            $cohort->component = 'local_ent_installer';
            $cohort->contextid = context_system::instance()->id;
            $cohort->id = $DB->insert_record('cohort', $cohort);
            if (!empty($options['verbose'])) {
                mtrace("Creating missing global cohort for $type");
            }
            return $cohort->id;
        } else {
            // Update site cohort name if name has changed in settings.
            $cohortname = $config->cohort_ix.' '.$config->$key;
            $oldname = $oldcohort->name;
            if ($oldname != $cohortname) {
                $oldcohort->name = $cohortname;

                if (!empty($options['verbose'])) {
                    // Only notify when rename.
                    mtrace("Renaming site cohort from \"$oldname\" to \"$cohortname\" ");
                }
            }
            $oldcohort->component = 'local_ent_installer';
            $DB->update_record('cohort', $oldcohort);

            return $oldcohort->id;
        }
    }
}

function convert_from_ad_timestamp($timestamp) {

    $config = get_config('local_ent_installer');

    if (preg_match('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/', $timestamp, $matches)) {
        $y = $matches[1];
        $m = $matches[2];
        $d = $matches[3];
        $h = $matches[4];
        $i = $matches[5];
        $s = $matches[6];

        $unixtime = mktime($h, $i, $s, $m, $d, $y);
        return $unixtime + (0 + @$config->timestamp_shift);
    }
    return time();
<<<<<<< HEAD
=======
}

/**
 * loads User Type special info fields definition
 * @return an array of info/custom field mappings
 */
function local_ent_installer_load_user_fields() {
    global $DB, $CFG;

    $userfields = array();

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'eleve'));
    assert($fieldid != 0);
    $userfields['eleve'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'parent'));
    assert($fieldid != 0);
    $userfields['parent'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'enseignant'));
    assert($fieldid != 0);
    $userfields['enseignant'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'administration'));
    assert($fieldid != 0);
    $userfields['administration'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'cdt'));
    assert($fieldid != 0);
    $userfields['cdt'] = $fieldid;

    // Academic info.

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'cohort'));
    assert($fieldid != 0);
    $userfields['cohort'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'transport'));
    assert($fieldid != 0);
    $userfields['transport'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'regime'));
    assert($fieldid != 0);
    $userfields['regime'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'fullage'));
    assert($fieldid != 0);
    $userfields['fullage'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'isprimaryassignation'));
    assert($fieldid != 0);
    $userfields['isprimaryassignation'] = $fieldid;

    $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'personaltitle'));
    if (!$fieldid) {

        // Try to add the field if missing.
        require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

        $categoryname = ent_installer_string('academicinfocategoryname');
        $academicinfocategoryid = $DB->get_field('user_info_category', 'id', array('name' => $categoryname));
        $lastorder = $DB->get_field('user_info_field', 'MAX(sortorder)', array('categoryid' => $academicinfocategoryid));

        // Adding primary assignation.
        /*
         * Primary assignation should be marked if the Moodle node
         * matches the registered primary facility of the user in ldap attributes.
         */
        $userfield = new StdClass;
        $userfield->name = ent_installer_string('personaltitle');
        $userfield->shortname = 'personaltitle';
        $userfield->datatype = 'text';
        $userfield->description = ent_installer_string('personaltitledesc');
        $userfield->descriptionformat = FORMAT_MOODLE;
        $userfield->categoryid = $academicinfocategoryid;
        $userfield->sortorder = $lastorder + 1;
        $userfield->required = 0;
        $userfield->locked = 1;
        $userfield->visible = 0;
        $userfield->forceunique = 0;
        $userfield->signup = 0;
        $userfield->param1 = 10;
        $userfield->param2 = 10;
        $fieldid = $DB->insert_record('user_info_field', $userfield);
    }
    assert($fieldid != 0);
    $userfields['personaltitle'] = $fieldid;

    return $userfields;
}

/**
 * Returns extended classes for a user depending on its profile.
 */
function local_ent_installer_get_profile_classes($u) {
    global $CFG, $DB;

    $isent = is_dir($CFG->dirroot.'/local/ent_access_point');

    if ($isent) {
        $entfields = local_ent_installer_load_user_fields();

        $eleve = $DB->get_field('user_info_data', 'data', ['fieldid' => $entfields['eleve'], 'userid' => $u->id]);
        $enseignant = $DB->get_field('user_info_data', 'data', ['fieldid' => $entfields['enseignant'], 'userid' => $u->id]);
        $cdt = $DB->get_field('user_info_data', 'data', ['fieldid' => $entfields['cdt'], 'userid' => $u->id]);
        $parent = $DB->get_field('user_info_data', 'data', ['fieldid' => $entfields['parent'], 'userid' => $u->id]);
        $administration = $DB->get_field('user_info_data', 'data', ['fieldid' => $entfields['administration'], 'userid' => $u->id]);

        $entclasses = ' is-ent-user';
        if ($eleve) {
            $entclasses .= ' eleve';
        }
        if ($enseignant) {
            $entclasses .= ' enseignant';
        }
        if ($cdt) {
            $entclasses .= ' cdt';
        }
        if ($parent) {
            $entclasses .= ' parent';
        }
        if ($administration) {
            $entclasses .= ' administration';
        }
        return $entclasses;
    }
    return '';
}

function local_ent_installer_get_aux_groups($ldapuser, $options) {

    $config = get_config('local_ent_installer');

    if (empty($ldapuser->entauxensgroupes) && empty($ldapuser->entelevegroupes)) {
        // Note : No groups at all. But a user cannot have both at the same time !
        return;
    }

    $ldapgroups = [];
    if (!empty($ldapuser->entauxensgroupes)) {
        $groups = $ldapuser->entauxensgroupes;
        if (is_array($groups)) {
            $ldapgroups = $ldapgroups + $ldapuser->entauxensgroupes;
        } else {
            $ldapgroups = $ldapgroups + [$ldapuser->entauxensgroupes];
        }
    }

    if (!empty($ldapuser->entelevegroupes)) {
        $groups = $ldapuser->entelevegroupes;
        if (is_array($groups)) {
            $ldapgroups = $ldapgroups + $ldapuser->entelevegroupes;
        } else {
            $ldapgroups = $ldapgroups + [$ldapuser->entelevegroupes];
        }
    }

    $groups = [];
    foreach ($ldapgroups as $group) {
        if (!empty($options['verbose'])) {
            mtrace("\tApplying filter \"/{$config->aux_groupname_filter}/\" to $group");
        }
        if (preg_match('/'.$config->aux_groupname_filter.'/', $group, $matches)) {
            $groups[] = $matches[1];
        }
    }

    return $groups;
}

/**
 * Process AuxEnsGroupes to forge disciplinar groups (cohorts).
 * AuxEnsGroupes contains auxiliary cohort assignations owned by a unique teacher.
 * If the current user is the teacher and the cohort does not exist :
 * - Create the cohort if not exists.
 * - Find the teacher's owned category.
 * - Move the cohort to this category context.
 * If the current user is a student (NOT a teacher):
 * - Create the cohort in system context if not exists (will be rebound later).
 * - assign the user as member of the cohort.
 *
 *
 * @param object $ldapuser the users ldap attributes
 * @return string
 */
function local_ent_installer_process_aux_groups($user, $options = []) {
    global $DB;
    static $auxcohorts = [];

    $config = get_config('local_ent_installer');

    // Get group values.
    $auxensgroups = local_ent_installer_get_aux_groups($user, $options);

    if (empty($user->entauxensgroupes) && empty($user->entelevegroupes)) {
        mtrace('Auxiliary groups : No auxiliary groups defined');
        return;
    } else {
        if (empty($auxensgroups)) {
            mtrace("User has auxiliary groups, but none retained by the filter.");
            return;
        } else {
            mtrace('Processing aux groups on '.implode(', ', $auxensgroups).' for usertype '.$user->usertype);
        }
    }

    $context = context_system::instance();

    if ($user->usertype != 'eleve') {
        /*
        $teachercatidnum = local_ent_installer_get_teacher_cat_idnumber($user);
        $context = context_coursecat::instance($teachercatidnum);
        */

        foreach ($auxensgroups as $auxgroup) {
            $auxcohorts[$auxgroup] = local_ent_installer_make_aux_group($auxgroup, $context, $options);
        }
        // At the moment, do NOT attach to context.
    } else {
        foreach ($auxensgroups as $auxgroup) {
            if (!array_key_exists($auxgroup, $auxcohorts)) {
                $cohort = $DB->get_record('cohort', ['idnumber' => $config->cohort_ix.'_GRP_'.$auxgroup]);
                if (!$cohort) {
                    // Cohort not yet created.
                    $auxcohorts[$auxgroup] = local_ent_installer_make_aux_group($auxgroup, $context, $options);
                } else {
                    $auxcohorts[$auxgroup] = $cohort;
                }
            }

            $cohort = $auxcohorts[$auxgroup];

            // Assign user to that cohort.
            cohort_add_member($cohort->id, $user->id);
            if (!empty($options['verbose'])) {
                mtrace("\t\tAdding user {$user->username} to cohort {$cohort->name} ({$cohort->idnumber})");
            }
        }
    }
}

/**
 * Makes a cohort based on ldap group
 * @param string $auxgroup cohort name.
 * @param object $context moodle context where to create the cohort.
 */
function local_ent_installer_make_aux_group($auxgroup, $context, $options) {
    global $DB;

    $config = get_config('local_ent_installer');
    $cohortname = $config->cohort_ix.'_GRP_'.$auxgroup;
    $params = ['name' => $cohortname, 'idnumber' => $cohortname];
    if (!$oldrecord = $DB->get_record('cohort', $params)) {
        $cohort = new StdClass;
        $cohort->contextid = $context->id;
        $cohort->name = $cohortname;
        $cohort->idnumber = $cohortname;
        $cohort->component = 'local_ent_installer';
        $cohort->description = '';
        $cohort->descriptionformat = FORMAT_MOODLE;
        $cohort->visible = 1;
        $cohort->timecreated = time();
        $cohort->timemodified = time();
        $cohort->theme = '';
        $cohort->id = $DB->insert_record('cohort', $cohort);
        if (!empty($options['verbose'])) {
            mtrace("\tCreating auxiliary group cohort $cohortname ($cohortname)");
        }
        return $cohort;
    } else {
        if (!empty($options['verbose'])) {
            mtrace("\tAuxiliary group cohort $cohortname ($cohortname) exists");
        }
        return $oldrecord;
    }
}

/**
 * Pro wrapper for sending mail processing checkpoints. Sends a mail to a set of users 
 * defined in local_ent_installer settings.
 * @param string $toolname Name of the script and processing level that sends the notification. 
 * @param string $mailmess Mail report message
 */
function local_ent_installer_send_mail_checkpoint($toolname, $mailmess) {
    global $CFG;

    if (local_ent_installer_supports_feature('clinotifs/mail')) {
        include_once($CFG->dirroot.'/local/ent_installer/pro/lib.php');
        \local_ent_installer\pro_api::send_mail_checkpoint($toolname, $mailmess);
    } else {
        mtrace("Unsupported notification outout");
    }
>>>>>>> MOODLE_39_STABLE
}