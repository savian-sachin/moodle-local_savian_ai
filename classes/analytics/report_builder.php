<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\analytics;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/savian_ai/classes/api/client.php');

/**
 * Report builder class - orchestrates analytics data collection and API submission
 *
 * This class:
 * - Extracts data using data_extractor
 * - Anonymizes user IDs using anonymizer
 * - Calculates metrics using metrics_calculator
 * - Assembles JSON payload for API
 * - Sends to Savian AI API
 * - Stores reports in database
 * - Handles retry logic and batch processing
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {

    /**
     * @var data_extractor Data extractor instance
     */
    private $extractor;

    /**
     * @var anonymizer Anonymizer instance
     */
    private $anonymizer;

    /**
     * @var metrics_calculator Metrics calculator instance
     */
    private $calculator;

    /**
     * @var \local_savian_ai\api\client API client instance
     */
    private $api_client;

    /**
     * @var \moodle_database Database instance
     */
    private $db;

    /**
     * @var int Maximum retries for failed API calls
     */
    const MAX_RETRIES = 3;

    /**
     * @var int Batch size for large courses
     */
    const BATCH_SIZE = 50;

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;

        $this->extractor = new data_extractor();
        $this->anonymizer = new anonymizer();
        $this->calculator = new metrics_calculator();
        $this->api_client = new \local_savian_ai\api\client();
        $this->db = $DB;
    }

    /**
     * Build and send analytics report for a course
     *
     * @param int $course_id Course ID
     * @param string $report_type Type: on_demand, scheduled, real_time, end_of_course
     * @param string $trigger_type Trigger: manual, cron, event, completion
     * @param int $date_from Start timestamp (0 = all time)
     * @param int $date_to End timestamp (0 = now)
     * @param int|null $user_id User who triggered (if manual)
     * @return object Result with success status and report_id
     */
    public function build_and_send_report($course_id, $report_type = 'on_demand', $trigger_type = 'manual',
                                         $date_from = 0, $date_to = 0, $user_id = null) {
        global $USER;

        $date_to = $date_to > 0 ? $date_to : time();

        if ($user_id === null) {
            $user_id = $USER->id;
        }

        // Create report record
        $report = new \stdClass();
        $report->course_id = $course_id;
        $report->report_type = $report_type;
        $report->trigger_type = $trigger_type;
        $report->date_from = $date_from;
        $report->date_to = $date_to;
        $report->status = 'pending';
        $report->user_id = $user_id;
        $report->timecreated = time();
        $report->timemodified = time();
        $report->retry_count = 0;

        $report_id = $this->db->insert_record('local_savian_analytics_reports', $report);

        try {
            // Build report data
            $report_data = $this->build_report_data($course_id, $report_type, $date_from, $date_to);

            // Update student and activity counts
            $this->db->set_field('local_savian_analytics_reports', 'student_count',
                                count($report_data['students']), ['id' => $report_id]);
            $this->db->set_field('local_savian_analytics_reports', 'activity_count',
                                $report_data['course_summary']['total_activities'], ['id' => $report_id]);

            // Send to API with retry logic
            $result = $this->send_with_retry($report_id, $report_data);

            return (object)[
                'success' => $result->success,
                'report_id' => $report_id,
                'insights' => $result->insights ?? null,
                'message' => $result->success ? 'Report sent successfully' : ($result->error ?? 'Unknown error')
            ];

        } catch (\Exception $e) {
            // Mark as failed
            $this->mark_report_failed($report_id, $e->getMessage());

            return (object)[
                'success' => false,
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build report data structure matching API format
     *
     * @param int $course_id Course ID
     * @param string $report_type Report type
     * @param int $date_from Start timestamp
     * @param int $date_to End timestamp
     * @return array Complete report data
     */
    public function build_report_data($course_id, $report_type, $date_from = 0, $date_to = 0) {
        // Get course info
        $course_info = $this->extractor->get_course_info($course_id);

        // Get enrolled students
        $students = $this->extractor->get_enrolled_students($course_id);

        if (empty($students)) {
            throw new \moodle_exception('No enrolled students found');
        }

        // Check if we need batch processing
        $student_count = count($students);
        $use_batching = $student_count > self::BATCH_SIZE;

        // Process students (with optional batching)
        $students_data = [];
        if ($use_batching) {
            $students_data = $this->process_students_batched($course_id, $students, $date_from, $date_to);
        } else {
            $students_data = $this->process_students($course_id, $students, $date_from, $date_to);
        }

        // Calculate aggregated insights
        $aggregated_insights = $this->calculator->calculate_aggregated_insights($course_id, $students_data);

        // Calculate completion data
        $completion_data = $this->calculate_course_completion_data($students_data);

        // Assemble report
        return [
            'course_id' => (string)$course_id,
            'course_name' => $course_info->course_name,
            'course_code' => $course_info->course_code,
            'report_metadata' => [
                'report_type' => $report_type,
                'trigger_type' => 'manual', // TODO: Pass from parameter
                'date_from' => $date_from > 0 ? date('Y-m-d\TH:i:s\Z', $date_from) : null,
                'date_to' => date('Y-m-d\TH:i:s\Z', $date_to),
                'generated_at' => date('Y-m-d\TH:i:s\Z'),
                'moodle_version' => $this->get_moodle_version(),
                'plugin_version' => get_config('local_savian_ai', 'version') ?? '1.1.0'
            ],
            'course_summary' => [
                'start_date' => $course_info->start_date > 0 ? date('Y-m-d\TH:i:s\Z', $course_info->start_date) : null,
                'end_date' => $course_info->end_date > 0 ? date('Y-m-d\TH:i:s\Z', $course_info->end_date) : null,
                'total_students' => $student_count,
                'total_activities' => $course_info->total_activities,
                'total_assessments' => 0, // TODO: Count quizzes + assignments
                'completion_rate' => $completion_data['completion_rate']
            ],
            'students' => $students_data,
            'aggregated_insights' => $aggregated_insights,
            'completion_data' => $completion_data
        ];
    }

    /**
     * Process students data (non-batched)
     *
     * @param int $course_id Course ID
     * @param array $students Array of student records
     * @param int $date_from Start timestamp
     * @param int $date_to End timestamp
     * @return array Array of student data
     */
    private function process_students($course_id, $students, $date_from, $date_to) {
        $students_data = [];

        foreach ($students as $student) {
            $student_data = $this->process_single_student($course_id, $student, $date_from, $date_to);
            if ($student_data) {
                $students_data[] = $student_data;
            }
        }

        return $students_data;
    }

    /**
     * Process students in batches (for large courses)
     *
     * @param int $course_id Course ID
     * @param array $students Array of student records
     * @param int $date_from Start timestamp
     * @param int $date_to End timestamp
     * @return array Array of student data
     */
    private function process_students_batched($course_id, $students, $date_from, $date_to) {
        $students_data = [];
        $batches = array_chunk($students, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            foreach ($batch as $student) {
                $student_data = $this->process_single_student($course_id, $student, $date_from, $date_to);
                if ($student_data) {
                    $students_data[] = $student_data;
                }
            }
            // Small delay between batches to avoid overloading database
            usleep(100000); // 0.1 seconds
        }

        return $students_data;
    }

    /**
     * Process single student's data
     *
     * @param int $course_id Course ID
     * @param object $student Student record
     * @param int $date_from Start timestamp
     * @param int $date_to End timestamp
     * @return array|null Student data array or null if error
     */
    private function process_single_student($course_id, $student, $date_from, $date_to) {
        try {
            // Anonymize user ID
            $anon_id = $this->anonymizer->anonymize_user_id($student->id);

            // Calculate engagement metrics
            $engagement_metrics = $this->calculator->calculate_engagement_metrics(
                $course_id, $student->id, $date_from, $date_to
            );

            // Calculate grade metrics
            $grade_metrics = $this->calculator->calculate_grade_metrics($course_id, $student->id);

            // Calculate risk indicators
            $risk_indicators = $this->calculator->calculate_risk_indicators(
                $course_id, $student->id, $engagement_metrics, $grade_metrics
            );

            // Get activity timeline (last 30 days)
            $activity_timeline = $this->calculator->calculate_activity_timeline($course_id, $student->id, 30);

            return [
                'anon_id' => $anon_id,
                'enrollment_date' => date('Y-m-d\TH:i:s\Z', $student->enrollment_date),
                'role' => 'student',
                'engagement_metrics' => $engagement_metrics,
                'grade_metrics' => $grade_metrics,
                'risk_indicators' => $risk_indicators,
                'activity_timeline' => $activity_timeline,
                'module_performance' => [] // TODO: Implement per-module performance
            ];

        } catch (\Exception $e) {
            // Log error but continue with other students
            debugging('Error processing student ' . $student->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Calculate course completion data
     *
     * @param array $students_data Array of student data
     * @return array Completion data
     */
    private function calculate_course_completion_data($students_data) {
        $completed_count = 0;
        $in_progress_count = 0;
        $not_started_count = 0;
        $total_completion_times = [];

        foreach ($students_data as $student) {
            $completion_rate = $student['engagement_metrics']['activity_completion_rate'];

            if ($completion_rate >= 1.0) {
                $completed_count++;
                // TODO: Calculate actual completion time
            } else if ($completion_rate > 0) {
                $in_progress_count++;
            } else {
                $not_started_count++;
            }
        }

        $avg_completion_time = !empty($total_completion_times) ?
            array_sum($total_completion_times) / count($total_completion_times) : 0;

        return [
            'completed_count' => $completed_count,
            'in_progress_count' => $in_progress_count,
            'not_started_count' => $not_started_count,
            'avg_completion_time_days' => round($avg_completion_time),
            'completion_rate' => count($students_data) > 0 ?
                round($completed_count / count($students_data), 2) : 0
        ];
    }

    /**
     * Send report to API with retry logic
     *
     * @param int $report_id Report ID
     * @param array $report_data Report data
     * @return object API response
     */
    private function send_with_retry($report_id, $report_data) {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                // Update status to sending
                $this->db->set_field('local_savian_analytics_reports', 'status', 'sending', ['id' => $report_id]);
                $this->db->set_field('local_savian_analytics_reports', 'retry_count', $attempt, ['id' => $report_id]);

                // Send to API
                $response = $this->api_client->send_analytics($report_data);

                // Accept both 200 (sync) and 202 (async) as success
                if (($response->http_code === 200 || $response->http_code === 202) &&
                    isset($response->success) && $response->success) {
                    // Success - mark as sent (or pending for async)
                    $this->mark_report_sent($report_id, $response);
                    return $response;
                }

                // Client error (4xx) - don't retry
                if ($response->http_code >= 400 && $response->http_code < 500) {
                    $this->mark_report_failed($report_id, $response->error ?? 'Client error');
                    return $response;
                }

                // Server error (5xx) - retry
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    $this->mark_report_failed($report_id, $response->error ?? 'Max retries exceeded');
                    return $response;
                }

                // Exponential backoff
                sleep(pow(2, $attempt));

            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    $this->mark_report_failed($report_id, $e->getMessage());
                    throw $e;
                }
                sleep(pow(2, $attempt));
            }
        }

        // Should not reach here
        $this->mark_report_failed($report_id, 'Failed after retries');
        return (object)['success' => false, 'error' => 'Failed after retries'];
    }

    /**
     * Mark report as sent successfully
     *
     * @param int $report_id Report ID
     * @param object $response API response
     */
    private function mark_report_sent($report_id, $response) {
        $update = new \stdClass();
        $update->id = $report_id;
        $update->status = 'sent';
        $update->api_response = json_encode($response);
        $update->timemodified = time();

        $this->db->update_record('local_savian_analytics_reports', $update);
    }

    /**
     * Mark report as failed
     *
     * @param int $report_id Report ID
     * @param string $error_message Error message
     */
    private function mark_report_failed($report_id, $error_message) {
        $update = new \stdClass();
        $update->id = $report_id;
        $update->status = 'failed';
        $update->error_message = $error_message;
        $update->timemodified = time();

        $this->db->update_record('local_savian_analytics_reports', $update);
    }

    /**
     * Get Moodle version
     *
     * @return string Moodle version
     */
    private function get_moodle_version() {
        global $CFG;
        return $CFG->version ?? 'unknown';
    }
}
