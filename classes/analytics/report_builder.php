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
 * Analytics report builder.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\analytics;

/**
 * Report builder class - orchestrates analytics data collection and API submission.
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
     * @var data_extractor Data extractor instance.
     */
    private $extractor;

    /**
     * @var anonymizer Anonymizer instance.
     */
    private $anonymizer;

    /**
     * @var metrics_calculator Metrics calculator instance.
     */
    private $calculator;

    /**
     * @var \local_savian_ai\api\client API client instance.
     */
    private $apiclient;

    /**
     * @var \moodle_database Database instance.
     */
    private $db;

    /**
     * @var int Maximum retries for failed API calls.
     */
    const MAX_RETRIES = 3;

    /**
     * @var int Batch size for large courses.
     */
    const BATCH_SIZE = 50;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;

        $this->extractor = new data_extractor();
        $this->anonymizer = new anonymizer();
        $this->calculator = new metrics_calculator();
        $this->apiclient = new \local_savian_ai\api\client();
        $this->db = $DB;
    }

    /**
     * Build and send analytics report for a course.
     *
     * @param int $courseid Course ID.
     * @param string $reporttype Type: on_demand, scheduled, real_time, end_of_course.
     * @param string $triggertype Trigger: manual, cron, event, completion.
     * @param int $datefrom Start timestamp (0 = all time).
     * @param int $dateto End timestamp (0 = now).
     * @param int|null $userid User who triggered (if manual).
     * @return object Result with success status and report_id.
     */
    public function build_and_send_report(
        $courseid,
        $reporttype = 'on_demand',
        $triggertype = 'manual',
        $datefrom = 0,
        $dateto = 0,
        $userid = null
    ) {
        global $USER;

        $dateto = $dateto > 0 ? $dateto : time();

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Create report record.
        $report = new \stdClass();
        $report->course_id = $courseid;
        $report->report_type = $reporttype;
        $report->trigger_type = $triggertype;
        $report->date_from = $datefrom;
        $report->date_to = $dateto;
        $report->status = 'pending';
        $report->user_id = $userid;
        $report->timecreated = time();
        $report->timemodified = time();
        $report->retry_count = 0;

        $reportid = $this->db->insert_record('local_savian_analytics_reports', $report);

        try {
            // Build report data.
            $reportdata = $this->build_report_data($courseid, $reporttype, $datefrom, $dateto);

            // Update student and activity counts.
            $this->db->set_field(
                'local_savian_analytics_reports',
                'student_count',
                count($reportdata['students']),
                ['id' => $reportid]
            );
            $this->db->set_field(
                'local_savian_analytics_reports',
                'activity_count',
                $reportdata['course_summary']['total_activities'],
                ['id' => $reportid]
            );

            // Send to API with retry logic.
            $result = $this->send_with_retry($reportid, $reportdata);

            return (object)[
                'success' => $result->success,
                'report_id' => $reportid,
                'insights' => $result->insights ?? null,
                'message' => $result->success ? 'Report sent successfully' : ($result->error ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            // Mark as failed.
            $this->mark_report_failed($reportid, $e->getMessage());

            return (object)[
                'success' => false,
                'report_id' => $reportid,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build report data structure matching API format.
     *
     * @param int $courseid Course ID.
     * @param string $reporttype Report type.
     * @param int $datefrom Start timestamp.
     * @param int $dateto End timestamp.
     * @return array Complete report data.
     */
    public function build_report_data($courseid, $reporttype, $datefrom = 0, $dateto = 0) {
        // Get course info.
        $courseinfo = $this->extractor->get_course_info($courseid);

        // Get enrolled students.
        $students = $this->extractor->get_enrolled_students($courseid);

        if (empty($students)) {
            throw new \moodle_exception('No enrolled students found');
        }

        // Check if we need batch processing.
        $studentcount = count($students);
        $usebatching = $studentcount > self::BATCH_SIZE;

        // Process students (with optional batching).
        $studentsdata = [];
        if ($usebatching) {
            $studentsdata = $this->process_students_batched($courseid, $students, $datefrom, $dateto);
        } else {
            $studentsdata = $this->process_students($courseid, $students, $datefrom, $dateto);
        }

        // Calculate aggregated insights.
        $aggregatedinsights = $this->calculator->calculate_aggregated_insights($courseid, $studentsdata);

        // Calculate completion data.
        $completiondata = $this->calculate_course_completion_data($studentsdata);

        // Assemble report.
        return [
            'course_id' => (string)$courseid,
            'course_name' => $courseinfo->course_name,
            'course_code' => $courseinfo->course_code,
            'report_metadata' => [
                'report_type' => $reporttype,
                'trigger_type' => 'manual', // TODO: Pass from parameter.
                'date_from' => $datefrom > 0 ? date('Y-m-d\TH:i:s\Z', $datefrom) : null,
                'date_to' => date('Y-m-d\TH:i:s\Z', $dateto),
                'generated_at' => date('Y-m-d\TH:i:s\Z'),
                'moodle_version' => $this->get_moodle_version(),
                'plugin_version' => get_config('local_savian_ai', 'version') ?? '1.1.0',
            ],
            'course_summary' => [
                'start_date' => $courseinfo->start_date > 0
                    ? date('Y-m-d\TH:i:s\Z', $courseinfo->start_date) : null,
                'end_date' => $courseinfo->end_date > 0
                    ? date('Y-m-d\TH:i:s\Z', $courseinfo->end_date) : null,
                'total_students' => $studentcount,
                'total_activities' => $courseinfo->total_activities,
                'total_assessments' => 0, // TODO: Count quizzes + assignments.
                'completion_rate' => $completiondata['completion_rate'],
            ],
            'students' => $studentsdata,
            'aggregated_insights' => $aggregatedinsights,
            'completion_data' => $completiondata,
        ];
    }

    /**
     * Process students data (non-batched).
     *
     * @param int $courseid Course ID.
     * @param array $students Array of student records.
     * @param int $datefrom Start timestamp.
     * @param int $dateto End timestamp.
     * @return array Array of student data.
     */
    private function process_students($courseid, $students, $datefrom, $dateto) {
        $studentsdata = [];

        foreach ($students as $student) {
            $studentdata = $this->process_single_student($courseid, $student, $datefrom, $dateto);
            if ($studentdata) {
                $studentsdata[] = $studentdata;
            }
        }

        return $studentsdata;
    }

    /**
     * Process students in batches (for large courses).
     *
     * @param int $courseid Course ID.
     * @param array $students Array of student records.
     * @param int $datefrom Start timestamp.
     * @param int $dateto End timestamp.
     * @return array Array of student data.
     */
    private function process_students_batched($courseid, $students, $datefrom, $dateto) {
        $studentsdata = [];
        $batches = array_chunk($students, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            foreach ($batch as $student) {
                $studentdata = $this->process_single_student($courseid, $student, $datefrom, $dateto);
                if ($studentdata) {
                    $studentsdata[] = $studentdata;
                }
            }
            // Small delay between batches to avoid overloading database.
            usleep(100000); // 0.1 seconds.
        }

        return $studentsdata;
    }

    /**
     * Process single student's data.
     *
     * @param int $courseid Course ID.
     * @param object $student Student record.
     * @param int $datefrom Start timestamp.
     * @param int $dateto End timestamp.
     * @return array|null Student data array or null if error.
     */
    private function process_single_student($courseid, $student, $datefrom, $dateto) {
        try {
            // Anonymize user ID.
            $anonid = $this->anonymizer->anonymize_user_id($student->id);

            // Calculate engagement metrics.
            $engagementmetrics = $this->calculator->calculate_engagement_metrics(
                $courseid,
                $student->id,
                $datefrom,
                $dateto
            );

            // Calculate grade metrics.
            $grademetrics = $this->calculator->calculate_grade_metrics($courseid, $student->id);

            // Calculate risk indicators.
            $riskindicators = $this->calculator->calculate_risk_indicators(
                $courseid,
                $student->id,
                $engagementmetrics,
                $grademetrics
            );

            // Get activity timeline (last 30 days).
            $activitytimeline = $this->calculator->calculate_activity_timeline($courseid, $student->id, 30);

            return [
                'anon_id' => $anonid,
                'enrollment_date' => date('Y-m-d\TH:i:s\Z', $student->enrollment_date),
                'role' => 'student',
                'engagement_metrics' => $engagementmetrics,
                'grade_metrics' => $grademetrics,
                'risk_indicators' => $riskindicators,
                'activity_timeline' => $activitytimeline,
                'module_performance' => [], // TODO: Implement per-module performance.
            ];
        } catch (\Exception $e) {
            // Log error but continue with other students.
            debugging('Error processing student ' . $student->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Calculate course completion data.
     *
     * @param array $studentsdata Array of student data.
     * @return array Completion data.
     */
    private function calculate_course_completion_data($studentsdata) {
        $completedcount = 0;
        $inprogresscount = 0;
        $notstartedcount = 0;
        $totalcompletiontimes = [];

        foreach ($studentsdata as $student) {
            $completionrate = $student['engagement_metrics']['activity_completion_rate'];

            if ($completionrate >= 1.0) {
                $completedcount++;
                // TODO: Calculate actual completion time.
            } else if ($completionrate > 0) {
                $inprogresscount++;
            } else {
                $notstartedcount++;
            }
        }

        $avgcompletiontime = !empty($totalcompletiontimes) ?
            array_sum($totalcompletiontimes) / count($totalcompletiontimes) : 0;

        return [
            'completed_count' => $completedcount,
            'in_progress_count' => $inprogresscount,
            'not_started_count' => $notstartedcount,
            'avg_completion_time_days' => round($avgcompletiontime),
            'completion_rate' => count($studentsdata) > 0 ?
                round($completedcount / count($studentsdata), 2) : 0,
        ];
    }

    /**
     * Send report to API with retry logic.
     *
     * @param int $reportid Report ID.
     * @param array $reportdata Report data.
     * @return object API response.
     */
    private function send_with_retry($reportid, $reportdata) {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                // Update status to sending.
                $this->db->set_field('local_savian_analytics_reports', 'status', 'sending', ['id' => $reportid]);
                $this->db->set_field('local_savian_analytics_reports', 'retry_count', $attempt, ['id' => $reportid]);

                // Send to API.
                $response = $this->apiclient->send_analytics($reportdata);

                // Accept both 200 (sync) and 202 (async) as success.
                if (
                    ($response->http_code === 200 || $response->http_code === 202) &&
                    isset($response->success) && $response->success
                ) {
                    // Success - mark as sent (or pending for async).
                    $this->mark_report_sent($reportid, $response);
                    return $response;
                }

                // Client error (4xx) - don't retry.
                if ($response->http_code >= 400 && $response->http_code < 500) {
                    $this->mark_report_failed($reportid, $response->error ?? 'Client error');
                    return $response;
                }

                // Server error (5xx) - retry.
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    $this->mark_report_failed($reportid, $response->error ?? 'Max retries exceeded');
                    return $response;
                }

                // Exponential backoff.
                sleep(pow(2, $attempt));
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    $this->mark_report_failed($reportid, $e->getMessage());
                    throw $e;
                }
                sleep(pow(2, $attempt));
            }
        }

        // Should not reach here.
        $this->mark_report_failed($reportid, 'Failed after retries');
        return (object)['success' => false, 'error' => 'Failed after retries'];
    }

    /**
     * Mark report as sent successfully.
     *
     * @param int $reportid Report ID.
     * @param object $response API response.
     */
    private function mark_report_sent($reportid, $response) {
        $update = new \stdClass();
        $update->id = $reportid;
        $update->status = 'sent';
        $update->api_response = json_encode($response);
        $update->timemodified = time();

        $this->db->update_record('local_savian_analytics_reports', $update);
    }

    /**
     * Mark report as failed.
     *
     * @param int $reportid Report ID.
     * @param string $errormessage Error message.
     */
    private function mark_report_failed($reportid, $errormessage) {
        $update = new \stdClass();
        $update->id = $reportid;
        $update->status = 'failed';
        $update->error_message = $errormessage;
        $update->timemodified = time();

        $this->db->update_record('local_savian_analytics_reports', $update);
    }

    /**
     * Get Moodle version.
     *
     * @return string Moodle version.
     */
    private function get_moodle_version() {
        global $CFG;
        return $CFG->version ?? 'unknown';
    }
}
