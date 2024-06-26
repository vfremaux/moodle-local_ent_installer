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
 * Assign roles to users.
 *
 * @package    core_role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// require_once(dirname(__FILE__) . '/../../config.php'); // Customisation
require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');
require_once($CFG->dirroot.'/customscripts/admin/roles/user_selector.class.php');
require_once($CFG->dirroot.'/customscripts/admin/roles/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');

define("MAX_USERS_TO_LIST_PER_ROLE", 10);
define("MAX_USERS_PER_PAGE", 200);

$contextid = required_param('contextid', PARAM_INT);
$roleid    = optional_param('roleid', 0, PARAM_INT);
$returnto  = optional_param('return', null, PARAM_ALPHANUMEXT);

list($context, $course, $cm) = get_context_info_array($contextid);

$url = new moodle_url('/admin/roles/assign.php', array('contextid' => $contextid));

if ($course) {
    $isfrontpage = ($course->id == SITEID);
} else {
    $isfrontpage = false;
    if ($context->contextlevel == CONTEXT_USER) {
        $course = $DB->get_record('course', array('id'=>optional_param('courseid', SITEID, PARAM_INT)), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('id'=>$context->instanceid), '*', MUST_EXIST);
        $url->param('courseid', $course->id);
        $url->param('userid', $user->id);
    } else {
        $course = $SITE;
    }
}


// Security.
require_login($course, false, $cm);
require_capability('moodle/role:assign', $context);

if ($reloading = optional_param('userfilter_reload', false, PARAM_TEXT)){
    $SESSION->userfilter_eleve = optional_param('userfilter_eleve', false, PARAM_BOOL);
    $SESSION->userfilter_enseignant = optional_param('userfilter_enseignant', false, PARAM_BOOL);
    $SESSION->userfilter_cdt = optional_param('userfilter_cdt', false, PARAM_BOOL);
    $SESSION->userfilter_administration = optional_param('userfilter_administration', false, PARAM_BOOL);
    $SESSION->userfilter_parent = optional_param('userfilter_parent', false, PARAM_BOOL);
    $SESSION->userfilter_all = optional_param('userfilter_all', false, PARAM_BOOL);
    $SESSION->userfilter_cohort = optional_param('userfilter_cohort', 0, PARAM_TEXT);
}

if (!isset($SESSION->userfilter_eleve)) {
    $SESSION->userfilter_eleve = true;
    $SESSION->userfilter_enseignant = true;
    $SESSION->userfilter_cdt = true;
    $SESSION->userfilter_administration = true;
    $SESSION->userfilter_parent = false;
    $SESSION->userfilter_all = false;
    $SESSION->userfilter_cohort = '';
}

$PAGE->set_url($url);
$PAGE->set_context($context);

$contextname = $context->get_context_name();
$courseid = $course->id;

// These are needed early because of tabs.php.
list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
$overridableroles = get_overridable_roles($context, ROLENAME_BOTH);

// Make sure this user can assign this role.
if ($roleid && !isset($assignableroles[$roleid])) {
    $a = new stdClass;
    $a->roleid = $roleid;
    $a->context = $contextname;
    print_error('cannotassignrolehere', '', $context->get_url(), $a);
}

// Work out an appropriate page title.
if ($roleid) {
    $a = new stdClass;
    $a->role = $assignableroles[$roleid];
    $a->context = $contextname;
    $title = get_string('assignrolenameincontext', 'core_role', $a);
} else {
    if ($isfrontpage) {
        $title = get_string('frontpageroles', 'admin');
    } else {
        $title = get_string('assignrolesin', 'core_role', $contextname);
    }
}

// Process any incoming role assignments before printing the header.
if ($roleid) {

    // Create the user selector objects.
    $options = array('context' => $context, 'roleid' => $roleid);

    $potentialuserselector = roles_get_potential_user_selector_filtered($context, 'addselect', $options);
    $currentuserselector = new core_role_existing_role_holders('removeselect', $options);

    // Process incoming role assignments.
    $errors = array();
    if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
        $userstoassign = $potentialuserselector->get_selected_users();
        if (!empty($userstoassign)) {

            foreach ($userstoassign as $adduser) {
                $allow = true;

                if ($allow) {
                    role_assign($roleid, $adduser->id, $context->id);
                }
            }

            $potentialuserselector->invalidate_selected_users();
            $currentuserselector->invalidate_selected_users();

            // Counts have changed, so reload.
            list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
        }
    }

    // Process incoming role unassignments.
    if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
        $userstounassign = $currentuserselector->get_selected_users();
        if (!empty($userstounassign)) {

            foreach ($userstounassign as $removeuser) {
                // Unassign only roles that are added manually, no messing with other components!!!
                role_unassign($roleid, $removeuser->id, $context->id, '');
            }

            $potentialuserselector->invalidate_selected_users();
            $currentuserselector->invalidate_selected_users();

            // Counts have changed, so reload.
            list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
        }
    }
}

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);

switch ($context->contextlevel) {
    case CONTEXT_SYSTEM:
        require_once($CFG->libdir.'/adminlib.php');
        admin_externalpage_setup('assignroles', '', array('contextid' => $contextid, 'roleid' => $roleid));
        break;
    case CONTEXT_USER:
        $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));
        $PAGE->set_heading($fullname);
        $showroles = 1;
        break;
    case CONTEXT_COURSECAT:
        $PAGE->set_heading($SITE->fullname);
        break;
    case CONTEXT_COURSE:
        if ($isfrontpage) {
            $PAGE->set_heading(get_string('frontpage', 'admin'));
        } else {
            $PAGE->set_heading($course->fullname);
        }
        break;
    case CONTEXT_MODULE:
        $PAGE->set_heading($context->get_context_name(false));
        $PAGE->set_cacheable(false);
        break;
    case CONTEXT_BLOCK:
        $PAGE->set_heading($PAGE->course->fullname);
        break;
}

echo $OUTPUT->header();

// Print heading.
echo $OUTPUT->heading_with_help($title, 'assignroles', 'core_role');

if ($roleid) {
    // Show UI for assigning a particular role to users.
    // Print a warning if we are assigning system roles.
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        echo $OUTPUT->notification(get_string('globalroleswarning', 'core_role'));
    }

    // Print the form.
    $assignurl = new moodle_url($PAGE->url, array('roleid'=>$roleid));
    if ($returnto !== null) {
        $assignurl->param('return', $returnto);
    }
?>
<form id="assignform" method="post" action="<?php echo $assignurl ?>"><div>
  <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />

  <table id="assigningrole" summary="" class="admintable roleassigntable generaltable" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php print_string('extusers', 'core_role'); ?></label></p>
          <?php $currentuserselector->display() ?>
      </td>
      <td id="buttonscell">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
          </div>

          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php print_string('potusers', 'core_role'); ?></label></p>
          <?php $potentialuserselector->display() ?>
          <?php
             // Specific Edunao EE version, usertype
             $userfilter_eleve_checked = (@$SESSION->userfilter_eleve || !empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '';
             $userfilter_enseignant_checked = (@$SESSION->userfilter_enseignant && empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '';
             $userfilter_cdt_checked = (@$SESSION->userfilter_cdt && empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '' ;
             $userfilter_administration_checked = (@$SESSION->userfilter_administration && empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '';
             $userfilter_parent_checked = (@$SESSION->userfilter_parent && empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '';
             $userfilter_all_checked = (@$SESSION->userfilter_all && empty($SESSION->userfilter_cohort)) ? 'checked = "checked"' : '';

             $nonstudentdisabled = (empty($SESSION->userfilter_cohort)) ? '' : ' disabled="disabled" '; 
             $alldisabled = (empty($SESSION->userfilter_all)) ? '' : ' disabled="disabled" '; 

             $reloadstr = get_string('reload', 'local_ent_installer');
             $PAGE->requires->js('/customscripts/admin/roles/role_assign.js');
             echo get_string('eleve', 'local_ent_installer')." : <input type=\"checkbox\" id=\"chk_userfilter_eleve\" name=\"userfilter_eleve\" value=\"1\" $userfilter_eleve_checked $alldisabled /> - ";
             echo get_string('enseignant', 'local_ent_installer')." : <input type=\"checkbox\" id=\"chk_userfilter_enseignant\" name=\"userfilter_enseignant\" value=\"1\" $userfilter_enseignant_checked $nonstudentdisabled $alldisabled /> - ";
             echo get_string('cdt', 'local_ent_installer')." : <input type=\"checkbox\" name=\"userfilter_cdt\" id=\"chk_userfilter_cdt\" value=\"1\" $userfilter_cdt_checked $nonstudentdisabled $alldisabled /> - ";
             echo get_string('administration', 'local_ent_installer')." : <input type=\"checkbox\" id=\"chk_userfilter_administration\" name=\"userfilter_administration\" value=\"1\" $userfilter_administration_checked $nonstudentdisabled $alldisabled />";
             echo get_string('parent', 'local_ent_installer')." : <input type=\"checkbox\" id=\"chk_userfilter_parent\" name=\"userfilter_parent\" value=\"1\" $userfilter_parent_checked $nonstudentdisabled $alldisabled />";
             echo get_string('all', 'local_ent_installer')." : <input type=\"checkbox\" id=\"chk_userfilter_all\" name=\"userfilter_all\" value=\"1\" $userfilter_all_checked $nonstudentdisabled onclick=\"all_exclude(this)\" />";
            if ($cohortfield = $DB->get_record('user_info_field', array('shortname' => 'cohort'))) {
                echo '<br/>';
                 echo get_string('cohort', 'local_ent_installer')." : ";
                 print_cohort_filter_html();
            }

             echo "<input type=\"submit\" name=\"userfilter_reload\" value=\"{$reloadstr}\" />";
             // /CHANGE
            ?>
      </td>
    </tr>
  </table>
</div></form>

<?php
    $PAGE->requires->js_init_call('M.core_role.init_add_assign_page');

    if (!empty($errors)) {
        $msg = '<p>';
        foreach ($errors as $e) {
            $msg .= $e.'<br />';
        }
        $msg .= '</p>';
        echo $OUTPUT->box_start();
        echo $OUTPUT->notification($msg);
        echo $OUTPUT->box_end();
    }

    // Print a form to swap roles, and a link back to the all roles list.
    echo '<div class="backlink">';

    $newroleurl = new moodle_url($PAGE->url);
    if ($returnto !== null) {
        $newroleurl->param('return', $returnto);
    }
    $select = new single_select($newroleurl, 'roleid', $nameswithcounts, $roleid, null);
    $select->label = get_string('assignanotherrole', 'core_role');
    echo $OUTPUT->render($select);
    $backurl = new moodle_url('/admin/roles/assign.php', array('contextid' => $contextid));
    if ($returnto !== null) {
        $backurl->param('return', $returnto);
    }
    echo '<p><a href="' . $backurl->out() . '">' . get_string('backtoallroles', 'core_role') . '</a></p>';
    echo '</div>';

} else if (empty($assignableroles)) {
    // Print a message that there are no roles that can me assigned here.
    echo $OUTPUT->heading(get_string('notabletoassignroleshere', 'core_role'), 3);

} else {
    // Show UI for choosing a role to assign.

    // Print a warning if we are assigning system roles.
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        echo $OUTPUT->notification(get_string('globalroleswarning', 'core_role'));
    }

    // Print instruction.
    echo $OUTPUT->heading(get_string('chooseroletoassign', 'core_role'), 3);

    // Get the names of role holders for roles with between 1 and MAX_USERS_TO_LIST_PER_ROLE users,
    // and so determine whether to show the extra column.
    $roleholdernames = array();
    $strmorethanmax = get_string('morethan', 'core_role', MAX_USERS_TO_LIST_PER_ROLE);
    $showroleholders = false;
    foreach ($assignableroles as $roleid => $notused) {
        $roleusers = '';
        if (0 < $assigncounts[$roleid] && $assigncounts[$roleid] <= MAX_USERS_TO_LIST_PER_ROLE) {
            // M4.
            $fields = \core_user\fields::for_name()->with_userpic()->excluding('id')->get_required_fields();
            $userfields = 'u.id,'.implode(',', $fields);
            $roleusers = get_role_users($roleid, $context, false, $userfields);
            if (!empty($roleusers)) {
                $strroleusers = array();
                foreach ($roleusers as $user) {
                    $strroleusers[] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '" >' . fullname($user) . '</a>';
                }
                $roleholdernames[$roleid] = implode('<br />', $strroleusers);
                $showroleholders = true;
            }
        } else if ($assigncounts[$roleid] > MAX_USERS_TO_LIST_PER_ROLE) {
            $assignurl = new moodle_url($PAGE->url, array('roleid'=>$roleid));
            if ($returnto !== null) {
                $assignurl->param('return', $returnto);
            }
            $roleholdernames[$roleid] = '<a href="'.$assignurl.'">'.$strmorethanmax.'</a>';
        } else {
            $roleholdernames[$roleid] = '';
        }
    }

    // Print overview table.
    $table = new html_table();
    $table->id = 'assignrole';
    $table->head = array(get_string('role'), get_string('description'), get_string('userswiththisrole', 'core_role'));
    $table->colclasses = array('leftalign role', 'leftalign', 'centeralign userrole');
    $table->attributes['class'] = 'admintable generaltable';
    if ($showroleholders) {
        $table->headspan = array(1, 1, 2);
        $table->colclasses[] = 'leftalign roleholder';
    }

    foreach ($assignableroles as $roleid => $rolename) {
        $description = format_string($DB->get_field('role', 'description', array('id'=>$roleid)));
        $assignurl = new moodle_url($PAGE->url, array('roleid'=>$roleid));
        if ($returnto !== null) {
            $assignurl->param('return', $returnto);
        }
        $row = array('<a href="'.$assignurl.'">'.$rolename.'</a>',
                $description, $assigncounts[$roleid]);
        if ($showroleholders) {
            $row[] = $roleholdernames[$roleid];
        }
        $table->data[] = $row;
    }

    echo html_writer::table($table);

    if ($context->contextlevel > CONTEXT_USER) {

        if ($context->contextlevel === CONTEXT_COURSECAT && $returnto === 'management') {
            $url = new moodle_url('/course/management.php', array('categoryid' => $context->instanceid));
        } else {
            $url = $context->get_url();
        }

        echo html_writer::start_tag('div', array('class'=>'backlink'));
        echo html_writer::tag('a', get_string('backto', '', $contextname), array('href' => $url));
        echo html_writer::end_tag('div');
    }
}

echo $OUTPUT->footer();

die; // Customisation