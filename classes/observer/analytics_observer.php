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
 * Analytics event observer.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\observer;

/**
 * Event observer for analytics real-time data capture.
 *
 * Listens to significant Moodle events and triggers analytics updates:
 * - Quiz attempts submitted
 * - Assignments submitted
 * - Course modules completed
 * - Course completed
 *
 * Uses batching to avoid API spam - sends analytics after threshold of events.
 * All API calls are offloaded to ad-hoc tasks to avoid blocking event processing.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_observer {
    /**
     * @var int Event threshold before sending analytics.
     */
    const EVENT_THRESHOLD = 10;

    /**
     * Observer for quiz attempt submitted.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function quiz_submitted(\mod_quiz\event\attempt_submitted $event) {
        self::handle_event($event, 'quiz_submitted');
    }

    /**
     * Observer for assignment submitted.
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assignment_submitted(\mod_assign\event\assessable_submitted $event) {
        self::handle_event($event, 'assignment_submitted');
    }

    /**
     * Observer for activity completion updated.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function completion_updated(\core\event\course_module_completion_updated $event) {
        self::handle_event($event, 'completion_updated');
    }

    /**
     * Observer for course completed (triggers end-of-course report via ad-hoc task).
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        // Check if analytics enabled.
        $enabled = get_config('local_savian_ai', 'analytics_enabled');
        if (empty($enabled)) {
            return;
        }

        $courseid = $event->courseid;

        // Queue ad-hoc task instead of calling API directly.
        $task = new \local_savian_ai\task\send_analytics_adhoc();
        $task->set_custom_data(
            [
                'courseid' => $courseid,
                'report_type' => 'end_of_course',
                'trigger_type' => 'completion',
                'date_from' => 0,
                'date_to' => time(),
            ]
        );
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Generic event handler - stores event and checks threshold.
     *
     * @param \core\event\base $event
     * @param string $eventtype
     */
    private static function handle_event(\core\event\base $event, $eventtype) {
        global $DB;

        // Check if analytics enabled.
        $enabled = get_config('local_savian_ai', 'analytics_enabled');
        if (empty($enabled)) {
            return;
        }

        // Check if real-time analytics enabled.
        $realtimeenabled = get_config('local_savian_ai', 'analytics_realtime_enabled');
        if (empty($realtimeenabled)) {
            return;
        }

        $courseid = $event->courseid;
        $userid = $event->userid;

        // Do not track if no course context.
        if (empty($courseid) || $courseid == SITEID) {
            return;
        }

        try {
            // Store event.
            $eventrecord = new \stdClass();
            $eventrecord->course_id = $courseid;
            $eventrecord->user_id = $userid;
            $eventrecord->event_name = $event::class;
            $eventrecord->event_context = json_encode(
                [
                    'event_type' => $eventtype,
                    'context_id' => $event->contextid,
                    'time' => $event->timecreated,
                ]
            );
            $eventrecord->processed = 0;
            $eventrecord->timecreated = time();

            $DB->insert_record('local_savian_ai_analytics_events', $eventrecord);

            // Check if threshold reached for this course.
            $unprocessedcount = $DB->count_records(
                'local_savian_ai_analytics_events',
                [
                    'course_id' => $courseid,
                    'processed' => 0,
                ]
            );

            if ($unprocessedcount >= self::EVENT_THRESHOLD) {
                self::queue_batched_events($courseid);
            }
        } catch (\Exception $e) {
            // Do not break course functionality if analytics fails.
            debugging('Analytics event observer error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Queue batched events for processing via ad-hoc task.
     *
     * @param int $courseid Course ID.
     */
    private static function queue_batched_events($courseid) {
        global $DB;

        try {
            // Get unprocessed events.
            $events = $DB->get_records(
                'local_savian_ai_analytics_events',
                [
                    'course_id' => $courseid,
                    'processed' => 0,
                ],
                'timecreated ASC'
            );

            if (empty($events)) {
                return;
            }

            // Get date range from events.
            $eventarray = array_values($events);
            $datefrom = $eventarray[0]->timecreated;
            $dateto = end($eventarray)->timecreated;
            $eventids = array_keys($events);

            // Queue ad-hoc task instead of calling API directly.
            $task = new \local_savian_ai\task\send_analytics_adhoc();
            $task->set_custom_data(
                [
                    'courseid' => $courseid,
                    'report_type' => 'real_time',
                    'trigger_type' => 'event',
                    'date_from' => $datefrom,
                    'date_to' => $dateto,
                    'event_ids' => $eventids,
                ]
            );
            \core\task\manager::queue_adhoc_task($task, true);
        } catch (\Exception $e) {
            debugging('Analytics queue_batched_events error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Clean up old processed events (run periodically).
     *
     * @param int $daysold Delete events older than this many days.
     */
    public static function cleanup_old_events($daysold = 30) {
        global $DB;

        $cutoff = time() - ($daysold * 86400);

        $deleted = $DB->delete_records_select(
            'local_savian_ai_analytics_events',
            'processed = 1 AND timecreated < ?',
            [$cutoff]
        );

        if ($deleted) {
            mtrace("Cleaned up {$deleted} old analytics events");
        }
    }
}
