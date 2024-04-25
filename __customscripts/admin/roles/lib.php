<?php

require_once($CFG->dirroot.'/admin/roles/classes/potential_assignees_below_course.php');
require_once($CFG->dirroot.'/admin/roles/classes/potential_assignees_course_and_above.php');

function post_filter_user_sql() {
    global $SESSION, $CFG, $DB;

    static $FILTERREC;

    $filters = array('eleve', 'enseignant', 'cdt', 'administration', 'parent');

    if (!isset($FILTERREC)) {
        // Gets filtering user attributes field definition and setup local cache.
        $FILTERREC = array();
        foreach ($filters as $f) {
            if ($filter = $DB->get_record('user_info_field', array('shortname' => $f))) {
                $FILTERREC[$f] = $filter;
            }
        }
        $FILTERREC['cohort'] = $DB->get_record('user_info_field', array('shortname' => 'cohort'));
    }

    // check in session if stored actual filtering requests
    $result = false;
    $sqlfilters = array();
    foreach (array_keys($FILTERREC) as $f) {
        if ($f != 'all' && $f != 'cohort') {
            $userfilterkey = 'userfilter_'.$f;
            if (@$SESSION->$userfilterkey) {
                $sqlfilters[] = "(uid.fieldid = {$FILTERREC[$f]->id} AND uid.data = 1)";
            }
        } else {
            if ($f == 'all') {
                $sqlfilters[] = 0 + @$SESSION->$userfilterkey; // get them all without restriction if all enabled
            }
        }
    }

    // add cohort filtering if defined
    if (!empty($SESSION->userfilter_cohort)) {
        $sql = "
            JOIN
                {user_info_data} uid
            ON
                uid.userid = u.id AND (uid.fieldid = {$FILTERREC['cohort']->id} AND uid.data = '{$SESSION->userfilter_cohort}' )
        ";
        return $sql;
    }

    $sql = '';
    if (!empty($sqlfilters)) {
        $sql = "
            JOIN
                {user_info_data} uid
            ON
                uid.userid = u.id AND
        ";
        $sql .= '('.implode(' OR ', $sqlfilters).')';
    }
    
    return $sql;
}

function print_cohort_filter_html() {
    global $SESSION, $DB;

    if ($cohortfield = $DB->get_record('user_info_field', array('shortname' => 'cohort'))) {

        $menuoptions[0] = get_string('allusers', 'local_admin');

        $sql = "
            SELECT
                DISTINCT data, fieldid
            FROM
                {user_info_data} uid,
                {user} u
            WHERE
                uid.userid = u.id AND
                u.deleted = 0 AND
                fieldid = ?
            ORDER BY
                data
        ";

        if ($availablecohorts = $DB->get_records_sql($sql, [$cohortfield->id])) {
            foreach ($availablecohorts as $ch) {
                if (empty($ch->data)) continue;
                $menuoptions[$ch->data] = $ch->data;
            }
        }
        echo html_writer::select($menuoptions, 'userfilter_cohort', @$SESSION->userfilter_cohort, array());
    }
}

/**
 * Get the potential assignees selector for a given context.
 *
 * If this context is a course context, or inside a course context (module or
 * some blocks) then return a potential_assignees_below_course object. Otherwise
 * return a potential_assignees_course_and_above.
 *
 * @param stdClass $context a context.
 * @param string $name passed to user selector constructor.
 * @param array $options to user selector constructor.
 * @return user_selector_base an appropriate user selector.
 */
function roles_get_potential_user_selector_filtered($context, $name, $options) {
        $blockinsidecourse = false;
        if ($context->contextlevel == CONTEXT_BLOCK) {
            $parentcontext = context::instance_by_id(get_parent_contextid($context));
            $blockinsidecourse = in_array($parentcontext->contextlevel, array(CONTEXT_MODULE, CONTEXT_COURSE));
        }

        if (($context->contextlevel == CONTEXT_MODULE || $blockinsidecourse) && !is_inside_frontpage($context)) {
            $potentialuserselector = new core_role_potential_assignees_below_course('addselect', $options);
        } else {
            $potentialuserselector = new potential_assignees_course_and_above_filtered('addselect', $options);
        }
    return $potentialuserselector;
}
