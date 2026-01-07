<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

/**
 * Weekly scheduled task to send analytics reports
 *
 * Runs weekly on configured day (default: Sunday 3:00 AM) to send
 * comprehensive analytics reports for all courses.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_analytics_weekly extends \core\task\scheduled_task {

    /**
     * Get task name
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_analytics_weekly', 'local_savian_ai');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        // Check if analytics is globally enabled
        $enabled = get_config('local_savian_ai', 'analytics_enabled');
        if (empty($enabled)) {
            mtrace('Analytics is globally disabled. Skipping weekly task.');
            return;
        }

        // Check if weekly frequency is enabled
        $frequency = get_config('local_savian_ai', 'analytics_frequency');
        if ($frequency !== 'weekly' && $frequency !== 'both') {
            mtrace('Weekly analytics not enabled. Current frequency: ' . ($frequency ?: 'manual'));
            return;
        }

        mtrace('Starting weekly analytics task...');

        // Get all active courses with enrolled students
        $sql = "SELECT DISTINCT c.id, c.fullname, c.startdate
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                JOIN {role_assignments} ra ON ra.userid = ue.userid
                JOIN {role} r ON r.id = ra.roleid
                WHERE c.id > 1
                  AND r.shortname = 'student'
                  AND c.visible = 1
                ORDER BY c.id";

        $courses = $DB->get_records_sql($sql);

        if (empty($courses)) {
            mtrace('No courses with enrolled students found.');
            return;
        }

        mtrace('Found ' . count($courses) . ' courses to process.');

        $builder = new \local_savian_ai\analytics\report_builder();
        $success_count = 0;
        $error_count = 0;
        $skipped_count = 0;

        foreach ($courses as $course) {
            try {
                mtrace("Processing course: {$course->fullname} (ID: {$course->id})");

                // Count enrolled students
                $student_count = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT ue.userid)
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON e.id = ue.enrolid
                     JOIN {role_assignments} ra ON ra.userid = ue.userid
                     JOIN {role} r ON r.id = ra.roleid
                     WHERE e.courseid = ? AND r.shortname = 'student'",
                    [$course->id]
                );

                if ($student_count == 0) {
                    mtrace("  Skipping - no students enrolled");
                    $skipped_count++;
                    continue;
                }

                // Get last weekly report
                $last_report = $DB->get_record_sql(
                    "SELECT MAX(date_to) as last_date
                     FROM {local_savian_analytics_reports}
                     WHERE course_id = ?
                       AND status = 'sent'
                       AND report_type = 'scheduled'",
                    [$course->id]
                );

                $date_from = $last_report && $last_report->last_date ? $last_report->last_date : 0;
                $date_to = time();

                // Build and send comprehensive report
                $result = $builder->build_and_send_report(
                    $course->id,
                    'scheduled',
                    'cron',
                    $date_from,
                    $date_to,
                    null // System triggered
                );

                if ($result->success) {
                    mtrace("  ✓ Report sent successfully");
                    mtrace("    Report ID: {$result->report_id}");
                    mtrace("    Students analyzed: {$student_count}");

                    // Log insights summary if available
                    if (isset($result->insights->at_risk_students)) {
                        $at_risk_count = is_array($result->insights->at_risk_students) ?
                            count($result->insights->at_risk_students) : 0;
                        if ($at_risk_count > 0) {
                            mtrace("    ⚠ At-risk students: {$at_risk_count}");
                        }
                    }

                    $success_count++;
                } else {
                    mtrace("  ✗ Report failed: " . ($result->error ?? 'Unknown error'));
                    $error_count++;
                }

            } catch (\Exception $e) {
                mtrace("  ✗ Error processing course: " . $e->getMessage());
                $error_count++;
            }

            // Delay between courses to avoid API rate limits
            sleep(3);
        }

        mtrace('Weekly analytics task completed.');
        mtrace("  Success: {$success_count}");
        mtrace("  Errors: {$error_count}");
        mtrace("  Skipped: {$skipped_count}");
    }
}
