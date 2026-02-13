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
 * Analytics metrics calculator.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Metrics calculator class for calculating analytics metrics.
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
     * @var data_extractor Data extractor instance.
     */
    private $extractor;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->extractor = new data_extractor();
    }

    /**
     * Calculate comprehensive engagement metrics for a student.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $datefrom Start timestamp (optional).
     * @param int $dateto End timestamp (optional).
     * @return array Engagement metrics.
     */
    public function calculate_engagement_metrics($courseid, $userid, $datefrom = 0, $dateto = 0) {
        $activity = $this->extractor->get_user_activity($courseid, $userid, $datefrom, $dateto);
        $completion = $this->extractor->get_completion_status($courseid, $userid);
        $quiz = $this->extractor->get_quiz_performance($courseid, $userid);
        $assignment = $this->extractor->get_assignment_performance($courseid, $userid);
        $forum = $this->extractor->get_forum_participation($courseid, $userid);
        $timespent = $this->extractor->estimate_time_spent($courseid, $userid);

        return [
            'total_logins' => $activity->total_logins ?? 0,
            'total_views' => $activity->total_views ?? 0,
            'total_actions' => $activity->total_actions ?? 0,
            'create_actions' => $activity->create_actions ?? 0,
            'update_actions' => $activity->update_actions ?? 0,
            'time_spent_minutes' => $timespent,
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
     * Calculate grade-related metrics.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return array Grade metrics.
     */
    public function calculate_grade_metrics($courseid, $userid) {
        $grades = $this->extractor->get_user_grades($courseid, $userid);
        $quiz = $this->extractor->get_quiz_performance($courseid, $userid);
        $assignment = $this->extractor->get_assignment_performance($courseid, $userid);

        $currentgrade = $grades->current_grade ?? null;
        $gradepercentile = $currentgrade !== null ?
            $this->extractor->get_grade_percentile($courseid, $userid, $currentgrade) : 0.0;

        // Determine grade trend (requires historical data - simplified here).
        $gradetrend = 'stable';
        if ($currentgrade !== null && $grades->avg_grade !== null) {
            if ($currentgrade > $grades->avg_grade * 1.1) {
                $gradetrend = 'improving';
            } else if ($currentgrade < $grades->avg_grade * 0.9) {
                $gradetrend = 'declining';
            }
        }

        return [
            'current_grade' => $currentgrade,
            'quiz_average' => round($quiz->quiz_average ?? 0, 2),
            'assignment_average' => round($assignment->assignment_average ?? 0, 2),
            'highest_grade' => round($grades->highest_grade ?? 0, 2),
            'lowest_grade' => round($grades->lowest_grade ?? 0, 2),
            'grade_percentile' => $gradepercentile,
            'grade_trend' => $gradetrend,
            'graded_items' => $grades->graded_items ?? 0,
            'passed_items' => $grades->passed_items ?? 0,
        ];
    }

    /**
     * Calculate risk indicators for at-risk student identification.
     *
     * Risk score is calculated based on multiple factors:
     * - Low engagement (inactivity, low login count)
     * - Poor performance (low grades)
     * - Incomplete work (missing submissions, low completion rate)
     * - Declining trends
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param array $engagementmetrics Engagement metrics array.
     * @param array $grademetrics Grade metrics array.
     * @return array Risk indicators.
     */
    public function calculate_risk_indicators($courseid, $userid, $engagementmetrics, $grademetrics) {
        $riskscore = 0.0;
        $riskfactors = [];

        // Factor 1: Inactivity (0-30 points).
        $dayssinceaccess = $engagementmetrics['days_since_last_access'] ?? 0;
        if ($dayssinceaccess > 14) {
            $riskscore += 30;
            $riskfactors[] = 'No access in ' . $dayssinceaccess . ' days';
        } else if ($dayssinceaccess > 7) {
            $riskscore += 15;
            $riskfactors[] = 'Low recent activity.';
        }

        // Factor 2: Low engagement (0-20 points).
        if ($engagementmetrics['total_logins'] < 5) {
            $riskscore += 20;
            $riskfactors[] = 'Very few logins.';
        } else if ($engagementmetrics['total_logins'] < 10) {
            $riskscore += 10;
        }

        // Factor 3: Low completion rate (0-25 points).
        $completionrate = $engagementmetrics['activity_completion_rate'];
        if ($completionrate < 0.3) {
            $riskscore += 25;
            $riskfactors[] = 'Low completion rate (' . round($completionrate * 100) . '%)';
        } else if ($completionrate < 0.5) {
            $riskscore += 12;
        }

        // Factor 4: Poor grades (0-25 points).
        $currentgrade = $grademetrics['current_grade'];
        if ($currentgrade !== null) {
            if ($currentgrade < 50) {
                $riskscore += 25;
                $riskfactors[] = 'Failing grade (' . round($currentgrade) . '%)';
            } else if ($currentgrade < 60) {
                $riskscore += 15;
                $riskfactors[] = 'Low grade (' . round($currentgrade) . '%)';
            }
        }

        // Factor 5: Declining trend (0-10 points).
        if ($grademetrics['grade_trend'] === 'declining') {
            $riskscore += 10;
            $riskfactors[] = 'Declining grade trend.';
        }

        // Factor 6: Low quiz performance (0-10 points).
        if ($grademetrics['quiz_average'] < 50) {
            $riskscore += 10;
            $riskfactors[] = 'Low quiz performance.';
        }

        // Normalize to 0-1 scale.
        $riskscore = min($riskscore / 100, 1.0);

        // Determine at-risk status.
        $atrisk = $riskscore >= 0.5;
        $risklevel = 'low';
        if ($riskscore >= 0.7) {
            $risklevel = 'high';
        } else if ($riskscore >= 0.5) {
            $risklevel = 'medium';
        }

        // Calculate prediction confidence (based on data availability).
        $datapoints = 0;
        if ($engagementmetrics['total_logins'] > 0) {
            $datapoints++;
        }
        if ($engagementmetrics['activity_completion_rate'] > 0) {
            $datapoints++;
        }
        if ($currentgrade !== null) {
            $datapoints++;
        }
        if ($engagementmetrics['quiz_attempts'] > 0) {
            $datapoints++;
        }
        if ($engagementmetrics['assignment_submissions'] > 0) {
            $datapoints++;
        }

        $predictionconfidence = round(min($datapoints / 5, 1.0), 2);

        return [
            'at_risk' => $atrisk,
            'risk_score' => round($riskscore, 2),
            'risk_level' => $risklevel,
            'risk_factors' => $riskfactors,
            'prediction_confidence' => $predictionconfidence,
        ];
    }

    /**
     * Calculate aggregated insights for the entire course.
     *
     * @param int $courseid Course ID.
     * @param array $studentsdata Array of student metrics.
     * @return array Aggregated insights.
     */
    public function calculate_aggregated_insights($courseid, $studentsdata) {
        if (empty($studentsdata)) {
            return [
                'average_engagement' => 0,
                'at_risk_count' => 0,
                'high_performers_count' => 0,
                'struggling_topics' => [],
                'popular_resources' => [],
            ];
        }

        $totalstudents = count($studentsdata);
        $atriskcount = 0;
        $highperformerscount = 0;
        $totalengagement = 0;

        foreach ($studentsdata as $student) {
            // Count at-risk students.
            if ($student['risk_indicators']['at_risk']) {
                $atriskcount++;
            }

            // Count high performers (grade > 80 and completion > 70%).
            $grade = $student['grade_metrics']['current_grade'] ?? 0;
            $completion = $student['engagement_metrics']['activity_completion_rate'] ?? 0;
            if ($grade > 80 && $completion > 0.7) {
                $highperformerscount++;
            }

            // Calculate engagement score (0-100).
            $logins = $student['engagement_metrics']['total_logins'];
            $completionrate = $student['engagement_metrics']['activity_completion_rate'];
            $engagementscore = min(($logins * 5 + $completionrate * 50), 100);
            $totalengagement += $engagementscore;
        }

        $averageengagement = $totalstudents > 0 ?
            round($totalengagement / $totalstudents / 100, 2) : 0;

        return [
            'average_engagement' => $averageengagement,
            'at_risk_count' => $atriskcount,
            'high_performers_count' => $highperformerscount,
            'struggling_topics' => [], // TODO: Implement topic analysis.
            'popular_resources' => [], // TODO: Implement resource popularity analysis.
        ];
    }

    /**
     * Calculate activity timeline for a student.
     *
     * Groups activity by date for visualization.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $days Number of days to look back (default: 30).
     * @return array Daily activity data.
     */
    public function calculate_activity_timeline($courseid, $userid, $days = 30) {
        global $DB;

        $datefrom = time() - ($days * 86400);

        // PostgreSQL compatible date grouping.
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
            'courseid' => $courseid,
            'userid' => $userid,
            'datefrom' => $datefrom,
            'limit' => $days,
        ]);

        $timeline = [];
        foreach ($records as $record) {
            // Estimate time spent (active hours * 30 minutes average).
            $estimatedminutes = $record->active_hours * 30;

            $timeline[] = [
                'date' => $record->date,
                'logins' => 0, // Simplified - could count distinct sessions.
                'actions' => $record->actions,
                'time_spent_minutes' => $estimatedminutes,
            ];
        }

        return $timeline;
    }

    /**
     * Get completion percentage as a decimal.
     *
     * @param int $completed Completed count.
     * @param int $total Total count.
     * @return float Completion rate (0.0 to 1.0).
     */
    private function calculate_completion_percentage($completed, $total) {
        return $total > 0 ? round($completed / $total, 2) : 0.0;
    }
}
