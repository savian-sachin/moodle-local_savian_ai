<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Metrics calculator class for calculating analytics metrics
 *
 * Transforms raw data from data_extractor into meaningful metrics:
 * - Engagement scores
 * - Risk indicators
 * - Grade trends
 * - Performance predictions
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metrics_calculator {

    /**
     * @var data_extractor Data extractor instance
     */
    private $extractor;

    /**
     * Constructor
     */
    public function __construct() {
        $this->extractor = new data_extractor();
    }

    /**
     * Calculate comprehensive engagement metrics for a student
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @param int $date_from Start timestamp (optional)
     * @param int $date_to End timestamp (optional)
     * @return array Engagement metrics
     */
    public function calculate_engagement_metrics($course_id, $user_id, $date_from = 0, $date_to = 0) {
        $activity = $this->extractor->get_user_activity($course_id, $user_id, $date_from, $date_to);
        $completion = $this->extractor->get_completion_status($course_id, $user_id);
        $quiz = $this->extractor->get_quiz_performance($course_id, $user_id);
        $assignment = $this->extractor->get_assignment_performance($course_id, $user_id);
        $forum = $this->extractor->get_forum_participation($course_id, $user_id);
        $time_spent = $this->extractor->estimate_time_spent($course_id, $user_id);

        return [
            'total_logins' => $activity->total_logins ?? 0,
            'total_views' => $activity->total_views ?? 0,
            'total_actions' => $activity->total_actions ?? 0,
            'create_actions' => $activity->create_actions ?? 0,
            'update_actions' => $activity->update_actions ?? 0,
            'time_spent_minutes' => $time_spent,
            'last_access' => $activity->last_access ? date('Y-m-d\TH:i:s\Z', $activity->last_access) : null,
            'days_since_last_access' => $activity->days_since_last_access ?? null,
            'active_days' => $activity->active_days ?? 0,
            'forum_posts' => $forum->total_posts ?? 0,
            'forum_replies' => $forum->replies ?? 0,
            'discussions_started' => $forum->discussions_started ?? 0,
            'assignment_submissions' => $assignment->submitted_count ?? 0,
            'assignment_submissions_late' => $assignment->late_submissions ?? 0,
            'quiz_attempts' => $quiz->quiz_attempts ?? 0,
            'quizzes_attempted' => $quiz->quizzes_attempted ?? 0,
            'resources_accessed' => $activity->total_views ?? 0,
            'activity_completion_rate' => $completion->completion_rate ?? 0.0,
            'completed_activities' => $completion->completed_activities ?? 0,
            'total_activities' => $completion->total_activities ?? 0,
        ];
    }

    /**
     * Calculate grade-related metrics
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @return array Grade metrics
     */
    public function calculate_grade_metrics($course_id, $user_id) {
        $grades = $this->extractor->get_user_grades($course_id, $user_id);
        $quiz = $this->extractor->get_quiz_performance($course_id, $user_id);
        $assignment = $this->extractor->get_assignment_performance($course_id, $user_id);

        $current_grade = $grades->current_grade ?? null;
        $grade_percentile = $current_grade !== null ?
            $this->extractor->get_grade_percentile($course_id, $user_id, $current_grade) : 0.0;

        // Determine grade trend (requires historical data - simplified here)
        $grade_trend = 'stable';
        if ($current_grade !== null && $grades->avg_grade !== null) {
            if ($current_grade > $grades->avg_grade * 1.1) {
                $grade_trend = 'improving';
            } else if ($current_grade < $grades->avg_grade * 0.9) {
                $grade_trend = 'declining';
            }
        }

        return [
            'current_grade' => $current_grade,
            'quiz_average' => round($quiz->quiz_average ?? 0, 2),
            'assignment_average' => round($assignment->assignment_average ?? 0, 2),
            'highest_grade' => round($grades->highest_grade ?? 0, 2),
            'lowest_grade' => round($grades->lowest_grade ?? 0, 2),
            'grade_percentile' => $grade_percentile,
            'grade_trend' => $grade_trend,
            'graded_items' => $grades->graded_items ?? 0,
            'passed_items' => $grades->passed_items ?? 0,
        ];
    }

    /**
     * Calculate risk indicators for at-risk student identification
     *
     * Risk score is calculated based on multiple factors:
     * - Low engagement (inactivity, low login count)
     * - Poor performance (low grades)
     * - Incomplete work (missing submissions, low completion rate)
     * - Declining trends
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @param array $engagement_metrics Engagement metrics array
     * @param array $grade_metrics Grade metrics array
     * @return array Risk indicators
     */
    public function calculate_risk_indicators($course_id, $user_id, $engagement_metrics, $grade_metrics) {
        $risk_score = 0.0;
        $risk_factors = [];

        // Factor 1: Inactivity (0-30 points)
        $days_since_access = $engagement_metrics['days_since_last_access'] ?? 0;
        if ($days_since_access > 14) {
            $risk_score += 30;
            $risk_factors[] = 'No access in ' . $days_since_access . ' days';
        } else if ($days_since_access > 7) {
            $risk_score += 15;
            $risk_factors[] = 'Low recent activity';
        }

        // Factor 2: Low engagement (0-20 points)
        if ($engagement_metrics['total_logins'] < 5) {
            $risk_score += 20;
            $risk_factors[] = 'Very few logins';
        } else if ($engagement_metrics['total_logins'] < 10) {
            $risk_score += 10;
        }

        // Factor 3: Low completion rate (0-25 points)
        $completion_rate = $engagement_metrics['activity_completion_rate'];
        if ($completion_rate < 0.3) {
            $risk_score += 25;
            $risk_factors[] = 'Low completion rate (' . round($completion_rate * 100) . '%)';
        } else if ($completion_rate < 0.5) {
            $risk_score += 12;
        }

        // Factor 4: Poor grades (0-25 points)
        $current_grade = $grade_metrics['current_grade'];
        if ($current_grade !== null) {
            if ($current_grade < 50) {
                $risk_score += 25;
                $risk_factors[] = 'Failing grade (' . round($current_grade) . '%)';
            } else if ($current_grade < 60) {
                $risk_score += 15;
                $risk_factors[] = 'Low grade (' . round($current_grade) . '%)';
            }
        }

        // Factor 5: Declining trend (0-10 points)
        if ($grade_metrics['grade_trend'] === 'declining') {
            $risk_score += 10;
            $risk_factors[] = 'Declining grade trend';
        }

        // Factor 6: Low quiz performance (0-10 points)
        if ($grade_metrics['quiz_average'] < 50) {
            $risk_score += 10;
            $risk_factors[] = 'Low quiz performance';
        }

        // Normalize to 0-1 scale
        $risk_score = min($risk_score / 100, 1.0);

        // Determine at-risk status
        $at_risk = $risk_score >= 0.5;
        $risk_level = 'low';
        if ($risk_score >= 0.7) {
            $risk_level = 'high';
        } else if ($risk_score >= 0.5) {
            $risk_level = 'medium';
        }

        // Calculate prediction confidence (based on data availability)
        $data_points = 0;
        if ($engagement_metrics['total_logins'] > 0) $data_points++;
        if ($engagement_metrics['activity_completion_rate'] > 0) $data_points++;
        if ($current_grade !== null) $data_points++;
        if ($engagement_metrics['quiz_attempts'] > 0) $data_points++;
        if ($engagement_metrics['assignment_submissions'] > 0) $data_points++;

        $prediction_confidence = round(min($data_points / 5, 1.0), 2);

        return [
            'at_risk' => $at_risk,
            'risk_score' => round($risk_score, 2),
            'risk_level' => $risk_level,
            'risk_factors' => $risk_factors,
            'prediction_confidence' => $prediction_confidence,
        ];
    }

    /**
     * Calculate aggregated insights for the entire course
     *
     * @param int $course_id Course ID
     * @param array $students_data Array of student metrics
     * @return array Aggregated insights
     */
    public function calculate_aggregated_insights($course_id, $students_data) {
        if (empty($students_data)) {
            return [
                'average_engagement' => 0,
                'at_risk_count' => 0,
                'high_performers_count' => 0,
                'struggling_topics' => [],
                'popular_resources' => [],
            ];
        }

        $total_students = count($students_data);
        $at_risk_count = 0;
        $high_performers_count = 0;
        $total_engagement = 0;

        foreach ($students_data as $student) {
            // Count at-risk students
            if ($student['risk_indicators']['at_risk']) {
                $at_risk_count++;
            }

            // Count high performers (grade > 80 and completion > 70%)
            $grade = $student['grade_metrics']['current_grade'] ?? 0;
            $completion = $student['engagement_metrics']['activity_completion_rate'] ?? 0;
            if ($grade > 80 && $completion > 0.7) {
                $high_performers_count++;
            }

            // Calculate engagement score (0-100)
            $logins = $student['engagement_metrics']['total_logins'];
            $completion_rate = $student['engagement_metrics']['activity_completion_rate'];
            $engagement_score = min(($logins * 5 + $completion_rate * 50), 100);
            $total_engagement += $engagement_score;
        }

        $average_engagement = $total_students > 0 ?
            round($total_engagement / $total_students / 100, 2) : 0;

        return [
            'average_engagement' => $average_engagement,
            'at_risk_count' => $at_risk_count,
            'high_performers_count' => $high_performers_count,
            'struggling_topics' => [], // TODO: Implement topic analysis
            'popular_resources' => [], // TODO: Implement resource popularity analysis
        ];
    }

    /**
     * Calculate activity timeline for a student
     *
     * Groups activity by date for visualization
     *
     * @param int $course_id Course ID
     * @param int $user_id User ID
     * @param int $days Number of days to look back (default: 30)
     * @return array Daily activity data
     */
    public function calculate_activity_timeline($course_id, $user_id, $days = 30) {
        global $DB;

        $date_from = time() - ($days * 86400);

        // PostgreSQL compatible date grouping
        $sql = "SELECT to_char(to_timestamp(timecreated), 'YYYY-MM-DD') as date,
                       COUNT(*) as actions,
                       COUNT(DISTINCT EXTRACT(HOUR FROM to_timestamp(timecreated))) as active_hours
                FROM {logstore_standard_log}
                WHERE courseid = :courseid
                  AND userid = :userid
                  AND timecreated >= :datefrom
                GROUP BY to_char(to_timestamp(timecreated), 'YYYY-MM-DD')
                ORDER BY date DESC
                LIMIT :limit";

        $records = $DB->get_records_sql($sql, [
            'courseid' => $course_id,
            'userid' => $user_id,
            'datefrom' => $date_from,
            'limit' => $days
        ]);

        $timeline = [];
        foreach ($records as $record) {
            // Estimate time spent (active hours * 30 minutes average)
            $estimated_minutes = $record->active_hours * 30;

            $timeline[] = [
                'date' => $record->date,
                'logins' => 0, // Simplified - could count distinct sessions
                'actions' => $record->actions,
                'time_spent_minutes' => $estimated_minutes,
            ];
        }

        return $timeline;
    }

    /**
     * Get completion percentage as a decimal
     *
     * @param int $completed Completed count
     * @param int $total Total count
     * @return float Completion rate (0.0 to 1.0)
     */
    private function calculate_completion_percentage($completed, $total) {
        return $total > 0 ? round($completed / $total, 2) : 0.0;
    }
}
