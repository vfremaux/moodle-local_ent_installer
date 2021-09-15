<?php

require_once($CFG->dirroot.'/enrol/cohort/lib.php');

/**
 * Get all the cohorts defined in given context.
 *
 * @param int $contextid
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function cohort_get_sorted_cohorts($contextid, $page = 0, $perpage = 25, $search = '') {
    global $DB;

    $config = get_config('local_ent_installer');

    // Add some additional sensible conditions.
    $tests = array('contextid = ?');
    $params = array($contextid);

    if (!empty($search)) {
        $conditions = array('name', 'idnumber', 'description');
        $searchparam = '%' . $DB->sql_like_escape($search) . '%';
        foreach ($conditions as $key=>$condition) {
            $conditions[$key] = $DB->sql_like($condition, "?", false);
            $params[] = $searchparam;
        }
        $tests[] = '(' . implode(' OR ', $conditions) . ')';
    }
    $wherecondition = implode(' AND ', $tests);

    $fields = "SELECT *";
    $countfields = "SELECT COUNT(1)";
    $sql = " FROM {cohort}
             WHERE $wherecondition";
    $order = " ORDER BY SUBSTR(name, 1, {$config->cohort_sort_prefix_length}) DESC, idnumber ASC";
    $allcohorts = $DB->count_records('cohort', array('contextid'=>$contextid));
    $totalcohorts = $DB->count_records_sql($countfields . $sql, $params);
    $cohorts = $DB->get_records_sql($fields . $sql . $order, $params, $page*$perpage, $perpage);

    return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts'=>$allcohorts);
}

/**
<<<<<<< HEAD
=======
 * Get all the cohorts defined in given context.
 *
 * @param int $contextid
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function cohort_get_all_sorted_cohorts($page = 0, $perpage = 25, $search = '') {
    global $DB;

    $config = get_config('local_ent_installer');

    // Add some additional sensible conditions.
    $tests = ['1 = 1'];

    if (!empty($search)) {
        $conditions = array('name', 'idnumber', 'description');
        $searchparam = '%' . $DB->sql_like_escape($search) . '%';
        foreach ($conditions as $key=>$condition) {
            $conditions[$key] = $DB->sql_like($condition, "?", false);
            $params[] = $searchparam;
        }
        $tests[] = '(' . implode(' OR ', $conditions) . ')';
    }
    $wherecondition = implode(' AND ', $tests);

    $totalcountsql = "
        SELECT
            COUNT(*)
        FROM
            {cohort}
        WHERE
            $wherecondition
    ";

    $sql = "
        SELECT
            *
        FROM
            {cohort}
        WHERE
            $wherecondition
        ORDER BY
            SUBSTR(name, 1, {$config->cohort_sort_prefix_length}) DESC, idnumber ASC
    ";
    $allcohorts = $DB->count_records('cohort');
    $totalcohorts = $DB->count_records_sql($totalcountsql, []);
    $cohorts = $DB->get_records_sql($sql, [], $page * $perpage, $perpage);

    return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts'=>$allcohorts);
}

/**
>>>>>>> MOODLE_39_STABLE
 * Returns the list of cohorts visible to the current user in the given course.
 *
 * The following fields are returned in each record: id, name, contextid, idnumber, visible
 * Fields memberscnt and enrolledcnt will be also returned if requested
 *
 * @param context $currentcontext
 * @param int $withmembers one of the COHORT_XXX constants that allows to return non empty cohorts only
 *      or cohorts with enroled/not enroled users, or just return members count
 * @param int $offset
 * @param int $limit
 * @param string $search
 * @return array
 */
function cohort_get_available_sorted_cohorts($currentcontext, $withmembers = 0, $offset = 0, $limit = 25, $search = '') {
    global $DB;

    $config = get_config('local_ent_installer');

    $params = array();

    // Build context subquery. Find the list of parent context where user is able to see any or visible-only cohorts.
    // Since this method is normally called for the current course all parent contexts are already preloaded.
    $parentcontexts = $currentcontext->get_parent_context_ids();
    $contextsany = array();
    foreach ($parentcontexts as $parentctxid) {
        if (!$DB->record_exists('context', array('id' => $parentctxid))) {
            // Ignore errors.
            continue;
        }

        if ($ctx = context::instance_by_id($parentctxid)) {
            if (has_capability("moodle/cohort:view", $ctx)) {
                $contextsany[] = $parentctxid;
            }
        }
    }

    $contextsvisible = array_diff($currentcontext->get_parent_context_ids(), $contextsany);
    if (empty($contextsany) && empty($contextsvisible)) {
        // User does not have any permissions to view cohorts.
        return array();
    }
    $subqueries = array();
    if (!empty($contextsany)) {
        list($parentsql, $params1) = $DB->get_in_or_equal($contextsany, SQL_PARAMS_NAMED, 'ctxa');
        $subqueries[] = 'c.contextid ' . $parentsql;
        $params = array_merge($params, $params1);
    }
    if (!empty($contextsvisible)) {
        list($parentsql, $params1) = $DB->get_in_or_equal($contextsvisible, SQL_PARAMS_NAMED, 'ctxv');
        $subqueries[] = '(c.visible = 1 AND c.contextid ' . $parentsql. ')';
        $params = array_merge($params, $params1);
    }
    $wheresql = '(' . implode(' OR ', $subqueries) . ')';

    // Build the rest of the query.
    $fromsql = "";
    $fieldssql = 'c.id, c.name, c.contextid, c.idnumber, c.visible';
    $groupbysql = '';
    $havingsql = '';
    if ($withmembers) {
        $fieldssql .= ', s.memberscnt';
        $subfields = "c.id, COUNT(DISTINCT cm.userid) AS memberscnt";
        $groupbysql = " GROUP BY c.id";
        $fromsql = " LEFT JOIN {cohort_members} cm ON cm.cohortid = c.id ";
        if (in_array($withmembers,
                array(COHORT_COUNT_ENROLLED_MEMBERS, COHORT_WITH_ENROLLED_MEMBERS_ONLY, COHORT_WITH_NOTENROLLED_MEMBERS_ONLY))) {
            list($esql, $params2) = get_enrolled_sql($currentcontext);
            $fromsql .= " LEFT JOIN ($esql) u ON u.id = cm.userid ";
            $params = array_merge($params2, $params);
            $fieldssql .= ', s.enrolledcnt';
            $subfields .= ', COUNT(DISTINCT u.id) AS enrolledcnt';
        }
        if ($withmembers == COHORT_WITH_MEMBERS_ONLY) {
            $havingsql = " HAVING COUNT(DISTINCT cm.userid) > 0";
        } else if ($withmembers == COHORT_WITH_ENROLLED_MEMBERS_ONLY) {
            $havingsql = " HAVING COUNT(DISTINCT u.id) > 0";
        } else if ($withmembers == COHORT_WITH_NOTENROLLED_MEMBERS_ONLY) {
            $havingsql = " HAVING COUNT(DISTINCT cm.userid) > COUNT(DISTINCT u.id)";
        }
    }
    if ($search) {
        list($searchsql, $searchparams) = cohort_get_search_query($search);
        $wheresql .= ' AND ' . $searchsql;
        $params = array_merge($params, $searchparams);
    }

    $order = " ORDER BY SUBSTR(name, 1, {$config->cohort_sort_prefix_length}) DESC, idnumber ASC";

    if ($withmembers) {
        $sql = "SELECT " . str_replace('c.', 'cohort.', $fieldssql) . "
                  FROM {cohort} cohort
                  JOIN (SELECT $subfields
                          FROM {cohort} c $fromsql
                         WHERE $wheresql $groupbysql $havingsql
                        ) s ON cohort.id = s.id
              $order";
    } else {
        $sql = "SELECT $fieldssql
                  FROM {cohort} c $fromsql
                 WHERE $wheresql
              $order";
    }

    return $DB->get_records_sql($sql, $params, $offset, $limit);
}

function cohort_get_course_bindings($cohortid) {
    global $DB;

    $sql = "
        SELECT
            c.shortname,
            c.idnumber,
            e.status
        FROM
            {course} c,
            {enrol} e
        WHERE
            c.id = e.courseid AND
            e.customint1 = ?
    ";

    $enrols = $DB->get_records_sql($sql, array($cohortid));
    return $enrols;
}