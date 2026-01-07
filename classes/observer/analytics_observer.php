<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\observer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

/**
 * Event observer for analytics real-time data capture
 *
 * Listens to significant Moodle events and triggers analytics updates:
 * - Quiz attempts submitted
 * - Assignments submitted
 * - Course modules completed
 * - Course completed
 *
 * Uses batching to avoid API spam - sends analytics after threshold of events.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_observer {

    /**
     * @var int Event threshold before sending analytics
     */
    const EVENT_THRESHOLD = 10;

    /**
     * Observer for quiz attempt submitted
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function quiz_submitted(\mod_quiz\event\attempt_submitted $event) {
        self::handle_event($event, 'quiz_submitted');
    }

    /**
     * Observer for assignment submitted
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assignment_submitted(\mod_assign\event\assessable_submitted $event) {
        self::handle_event($event, 'assignment_submitted');
    }

    /**
     * Observer for activity completion updated
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function completion_updated(\core\event\course_module_completion_updated $event) {
        self::handle_event($event, 'completion_updated');
    }

    /**
     * Observer for course completed (triggers end-of-course report)
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;

        // Check if analytics enabled
        $enabled = get_config('local_savian_ai', 'analytics_enabled');
        if (empty($enabled)) {
            return;
        }

        $courseid = $event->courseid;

        mtrace("Course completion detected for course ID: {$courseid}");

        try {
            // Send comprehensive end-of-course report immediately
            $builder = new \local_savian_ai\analytics\report_builder();
            $result = $builder->build_and_send_report(
                $courseid,
                'end_of_course',
                'completion',
                0, // All time
                time(),
                null // System triggered
            );

            if ($result->success) {
                mtrace("  ✓ End-of-course report sent successfully (Report ID: {$result->report_id})");
            } else {
                mtrace("  ✗ End-of-course report failed: " . ($result->error ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            mtrace("  ✗ Error sending end-of-course report: " . $e->getMessage());
        }
    }

    /**
     * Generic event handler - stores event and checks threshold
     *
     * @param \core\event\base $event
     * @param string $event_type
     */
    private static function handle_event(\core\event\base $event, $event_type) {
        global $DB;

        // Check if analytics enabled
        $enabled = get_config('local_savian_ai', 'analytics_enabled');
        if (empty($enabled)) {
            return;
        }

        // Check if real-time analytics enabled
        $realtime_enabled = get_config('local_savian_ai', 'analytics_realtime_enabled');
        if (empty($realtime_enabled)) {
            return; // Real-time disabled
        }

        $courseid = $event->courseid;
        $userid = $event->userid;

        // Don't track if no course context
        if (empty($courseid) || $courseid == SITEID) {
            return;
        }

        try {
            // Store event
            $event_record = new \stdClass();
            $event_record->course_id = $courseid;
            $event_record->user_id = $userid;
            $event_record->event_name = $event::class;
            $event_record->event_context = json_encode([
                'event_type' => $event_type,
                'context_id' => $event->contextid,
                'time' => $event->timecreated
            ]);
            $event_record->processed = 0;
            $event_record->timecreated = time();

            $DB->insert_record('local_savian_analytics_events', $event_record);

            // Check if threshold reached for this course
            $unprocessed_count = $DB->count_records('local_savian_analytics_events', [
                'course_id' => $courseid,
                'processed' => 0
            ]);

            if ($unprocessed_count >= self::EVENT_THRESHOLD) {
                mtrace("Event threshold reached for course {$courseid}. Sending analytics...");
                self::process_batched_events($courseid);
            }

        } catch (\Exception $e) {
            // Don't break course functionality if analytics fails
            debugging('Analytics event observer error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Process batched events and send analytics
     *
     * @param int $courseid Course ID
     */
    private static function process_batched_events($courseid) {
        global $DB;

        try {
            // Get unprocessed events
            $events = $DB->get_records('local_savian_analytics_events', [
                'course_id' => $courseid,
                'processed' => 0
            ], 'timecreated ASC');

            if (empty($events)) {
                return;
            }

            // Get date range from events
            $event_array = array_values($events);
            $date_from = $event_array[0]->timecreated;
            $date_to = end($event_array)->timecreated;

            // Send analytics report
            $builder = new \local_savian_ai\analytics\report_builder();
            $result = $builder->build_and_send_report(
                $courseid,
                'real_time',
                'event',
                $date_from,
                $date_to,
                null // System triggered
            );

            if ($result->success) {
                // Mark events as processed
                $event_ids = array_keys($events);
                list($in_sql, $params) = $DB->get_in_or_equal($event_ids);
                $DB->execute("UPDATE {local_savian_analytics_events}
                             SET processed = 1
                             WHERE id {$in_sql}", $params);

                mtrace("  ✓ Real-time analytics sent for course {$courseid}");
            } else {
                mtrace("  ✗ Real-time analytics failed for course {$courseid}: " . ($result->error ?? 'Unknown'));
            }

        } catch (\Exception $e) {
            mtrace("  ✗ Error processing batched events: " . $e->getMessage());
        }
    }

    /**
     * Clean up old processed events (run periodically)
     *
     * @param int $days_old Delete events older than this many days
     */
    public static function cleanup_old_events($days_old = 30) {
        global $DB;

        $cutoff = time() - ($days_old * 86400);

        $deleted = $DB->delete_records_select(
            'local_savian_analytics_events',
            'processed = 1 AND timecreated < ?',
            [$cutoff]
        );

        if ($deleted) {
            mtrace("Cleaned up {$deleted} old analytics events");
        }
    }
}
