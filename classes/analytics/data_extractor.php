<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\analytics;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Data extractor class for extracting analytics data from Moodle database
 *
 * Queries various Moodle tables to extract:
 * - User activity logs (logstore_standard_log)
 * - Grade data (grade_grades, grade_items)
 * - Completion data (course_modules_completion)
 * - Quiz attempts (quiz_attempts)
 * - Assignment submissions (assign_submission)
 * - Forum activity (forum_posts, forum_discussions)
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_extractor {

    /**
     * @var \moodle_database Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * Get all enrolled students in a course
     *
     * @param int $course_id Course ID
     * @return array Array of user objects with enrollment info
     */
    public function get_enrolled_students($course_id) {
        $context = \context_course::instance($course_id);

        $sql = "SELECT u.id, u.username, u.email, u.firstname, u.lastname,
                       ue.timecreated as enrollment_date
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {role} r ON r.id = ra.roleid
                WHERE e.courseid = :courseid
                  AND ra.contextid = :contextid
                  AND r.shortname = 'student'
                  AND u.deleted = 0
                  AND u.suspended = 0
                ORDER BY u.id";

        return $this->db->get_records_sql($sql, [
            'courseid' => $course_id,
            'contextid' => $context->id
        ]);
    }

    /**
     * Get user activity logs for a course
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @param int $date_from Start timestamp (optional)
     * @param int $date_to End timestamp (optional)
     * @return object Activity statistics
     */
    public function get_user_activity($course_id, $user_id, $date_from = 0, $date_to = 0) {
        $params = [
            'courseid' => $course_id,
            'userid' => $user_id
        ];

        $where_time = '';
        if ($date_from > 0) {
            $where_time .= ' AND timecreated >= :datefrom';
            $params['datefrom'] = $date_from;
        }
        if ($date_to > 0) {
            $where_time .= ' AND timecreated <= :dateto';
            $params['dateto'] = $date_to;
        }

        // Get activity counts (PostgreSQL compatible)
        $sql = "SELECT COUNT(*) as total_actions,
                       SUM(CASE WHEN action = 'viewed' THEN 1 ELSE 0 END) as total_views,
                       SUM(CASE WHEN crud = 'c' THEN 1 ELSE 0 END) as create_actions,
                       SUM(CASE WHEN crud = 'u' THEN 1 ELSE 0 END) as update_actions,
                       MIN(timecreated) as first_access,
                       MAX(timecreated) as last_access,
                       COUNT(DISTINCT to_char(to_timestamp(timecreated), 'YYYY-MM-DD')) as active_days
                FROM {logstore_standard_log}
                WHERE courseid = :courseid AND userid = :userid
                $where_time";

        $stats = $this->db->get_record_sql($sql, $params);

        // Get unique login count
        $login_sql = "SELECT COUNT(*) as login_count
                      FROM {logstore_standard_log}
                      WHERE courseid = :courseid
                        AND userid = :userid
                        AND eventname = :eventname
                        $where_time";

        $login_params = $params;
        $login_params['eventname'] = '\core\event\user_loggedin';
        $logins = $this->db->get_record_sql($login_sql, $login_params);

        $stats->total_logins = $logins->login_count ?? 0;
        $stats->days_since_last_access = $stats->last_access > 0 ?
            floor((time() - $stats->last_access) / 86400) : null;

        return $stats;
    }

    /**
     * Get user grades for a course
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object Grade statistics
     */
    public function get_user_grades($course_id, $user_id) {
        $sql = "SELECT AVG(gg.finalgrade) as avg_grade,
                       COUNT(gg.id) as graded_items,
                       MAX(gg.finalgrade) as highest_grade,
                       MIN(gg.finalgrade) as lowest_grade,
                       SUM(CASE WHEN gg.finalgrade >= gi.gradepass THEN 1 ELSE 0 END) as passed_items
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gi.courseid = :courseid
                  AND gg.userid = :userid
                  AND gi.itemtype != 'course'
                  AND gg.finalgrade IS NOT NULL";

        $params = ['courseid' => $course_id, 'userid' => $user_id];
        $stats = $this->db->get_record_sql($sql, $params);

        // Get course grade
        $course_grade_sql = "SELECT gg.finalgrade, gi.grademax
                             FROM {grade_grades} gg
                             JOIN {grade_items} gi ON gi.id = gg.itemid
                             WHERE gi.courseid = :courseid
                               AND gi.itemtype = 'course'
                               AND gg.userid = :userid";

        $course_grade = $this->db->get_record_sql($course_grade_sql, $params);

        if ($course_grade && $course_grade->grademax > 0) {
            $stats->current_grade = round(($course_grade->finalgrade / $course_grade->grademax) * 100, 2);
        } else {
            $stats->current_grade = null;
        }

        return $stats;
    }

    /**
     * Get quiz-specific performance data
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object Quiz statistics
     */
    public function get_quiz_performance($course_id, $user_id) {
        $sql = "SELECT COUNT(qa.id) as quiz_attempts,
                       AVG(qa.sumgrades / q.sumgrades * 100) as quiz_average,
                       COUNT(DISTINCT qa.quiz) as quizzes_attempted
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                WHERE q.course = :courseid
                  AND qa.userid = :userid
                  AND qa.state = 'finished'";

        return $this->db->get_record_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);
    }

    /**
     * Get assignment submission data
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object Assignment statistics
     */
    public function get_assignment_performance($course_id, $user_id) {
        $sql = "SELECT COUNT(asub.id) as total_submissions,
                       SUM(CASE WHEN asub.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
                       SUM(CASE WHEN asub.timecreated > a.duedate AND a.duedate > 0 THEN 1 ELSE 0 END) as late_submissions,
                       AVG(ag.grade / a.grade * 100) as assignment_average
                FROM {assign_submission} asub
                JOIN {assign} a ON a.id = asub.assignment
                LEFT JOIN {assign_grades} ag ON ag.assignment = a.id AND ag.userid = asub.userid
                WHERE a.course = :courseid
                  AND asub.userid = :userid
                  AND asub.latest = 1";

        return $this->db->get_record_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);
    }

    /**
     * Get forum participation data
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object Forum statistics
     */
    public function get_forum_participation($course_id, $user_id) {
        // Get post counts
        $sql = "SELECT COUNT(fp.id) as total_posts,
                       COUNT(DISTINCT fd.id) as discussions_started,
                       COUNT(CASE WHEN fp.parent != 0 THEN 1 END) as replies
                FROM {forum_posts} fp
                JOIN {forum_discussions} fd ON fd.id = fp.discussion
                JOIN {forum} f ON f.id = fd.forum
                WHERE f.course = :courseid
                  AND fp.userid = :userid";

        return $this->db->get_record_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);
    }

    /**
     * Get activity completion status
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object Completion statistics
     */
    public function get_completion_status($course_id, $user_id) {
        // Get all course modules
        $total_sql = "SELECT COUNT(*) as total_activities
                      FROM {course_modules} cm
                      WHERE cm.course = :courseid
                        AND cm.deletioninprogress = 0
                        AND cm.completion > 0";

        $total = $this->db->get_record_sql($total_sql, ['courseid' => $course_id]);

        // Get completed modules
        $completed_sql = "SELECT COUNT(*) as completed_activities
                          FROM {course_modules_completion} cmc
                          JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                          WHERE cm.course = :courseid
                            AND cmc.userid = :userid
                            AND cmc.completionstate > 0";

        $completed = $this->db->get_record_sql($completed_sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);

        $completion_rate = $total->total_activities > 0 ?
            round(($completed->completed_activities / $total->total_activities) * 100, 2) / 100 : 0;

        return (object)[
            'total_activities' => $total->total_activities,
            'completed_activities' => $completed->completed_activities,
            'completion_rate' => $completion_rate
        ];
    }

    /**
     * Get course completion status
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return object|null Course completion data
     */
    public function get_course_completion($course_id, $user_id) {
        $sql = "SELECT cc.timecompleted, cc.timestarted, cc.timeenrolled
                FROM {course_completions} cc
                WHERE cc.course = :courseid
                  AND cc.userid = :userid";

        return $this->db->get_record_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);
    }

    /**
     * Get course metadata and summary
     *
     * @param int $course_id Course ID
     * @return object Course information
     */
    public function get_course_info($course_id) {
        $course = $this->db->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Count activities
        $activity_sql = "SELECT COUNT(*) as activity_count
                         FROM {course_modules}
                         WHERE course = :courseid
                           AND deletioninprogress = 0";

        $activity_count = $this->db->get_record_sql($activity_sql, ['courseid' => $course_id]);

        // Get enrollment count
        $enrollment_sql = "SELECT COUNT(DISTINCT ue.userid) as student_count
                           FROM {user_enrolments} ue
                           JOIN {enrol} e ON e.id = ue.enrolid
                           JOIN {role_assignments} ra ON ra.userid = ue.userid
                           JOIN {role} r ON r.id = ra.roleid
                           JOIN {context} ctx ON ctx.id = ra.contextid
                           WHERE e.courseid = :courseid
                             AND r.shortname = 'student'
                             AND ctx.contextlevel = 50
                             AND ctx.instanceid = :courseid2";

        $enrollment_count = $this->db->get_record_sql($enrollment_sql, [
            'courseid' => $course_id,
            'courseid2' => $course_id
        ]);

        return (object)[
            'course_id' => $course->id,
            'course_name' => $course->fullname,
            'course_code' => $course->shortname,
            'start_date' => $course->startdate,
            'end_date' => $course->enddate,
            'total_students' => $enrollment_count->student_count,
            'total_activities' => $activity_count->activity_count,
            'created' => $course->timecreated,
        ];
    }

    /**
     * Get estimated time spent by user in course
     *
     * Estimates time by analyzing session gaps in access logs
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return int Estimated minutes spent
     */
    public function estimate_time_spent($course_id, $user_id) {
        // Get all access timestamps ordered
        $sql = "SELECT timecreated
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                  AND userid = :userid
                ORDER BY timecreated ASC";

        $logs = $this->db->get_records_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id
        ]);

        if (empty($logs)) {
            return 0;
        }

        $total_seconds = 0;
        $prev_time = null;
        $session_timeout = 1800; // 30 minutes = new session

        foreach ($logs as $log) {
            if ($prev_time !== null) {
                $gap = $log->timecreated - $prev_time;
                // Only count gaps less than session timeout
                if ($gap < $session_timeout) {
                    $total_seconds += $gap;
                }
            }
            $prev_time = $log->timecreated;
        }

        // Convert to minutes
        return round($total_seconds / 60);
    }

    /**
     * Get grade percentile for a user in the course
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @param float $user_grade User's grade
     * @return float Percentile (0.0 to 1.0)
     */
    public function get_grade_percentile($course_id, $user_id, $user_grade) {
        if ($user_grade === null) {
            return 0.0;
        }

        // Count how many students have lower grades
        $sql = "SELECT COUNT(*) as count_below
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gi.courseid = :courseid
                  AND gi.itemtype = 'course'
                  AND gg.finalgrade < :usergrade
                  AND gg.finalgrade IS NOT NULL";

        $below = $this->db->get_record_sql($sql, [
            'courseid' => $course_id,
            'usergrade' => $user_grade
        ]);

        // Get total students with grades
        $total_sql = "SELECT COUNT(*) as total
                      FROM {grade_grades} gg
                      JOIN {grade_items} gi ON gi.id = gg.itemid
                      WHERE gi.courseid = :courseid
                        AND gi.itemtype = 'course'
                        AND gg.finalgrade IS NOT NULL";

        $total = $this->db->get_record_sql($total_sql, ['courseid' => $course_id]);

        if ($total->total == 0) {
            return 0.0;
        }

        return round($below->count_below / $total->total, 2);
    }
}
