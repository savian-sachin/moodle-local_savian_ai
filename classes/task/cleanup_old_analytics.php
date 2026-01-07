<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to cleanup old analytics data (GDPR data retention)
 *
 * Runs daily to delete analytics reports and events older than the
 * configured retention period (default: 365 days).
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_old_analytics extends \core\task\scheduled_task {

    /**
     * Get task name
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup_old_analytics', 'local_savian_ai');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        mtrace('Starting analytics data cleanup task...');

        // Get retention period from config (default: 365 days)
        $retention_days = get_config('local_savian_ai', 'analytics_retention_days');
        if (empty($retention_days)) {
            $retention_days = 365;
        }

        $cutoff_time = time() - ($retention_days * 86400);

        mtrace("Retention period: {$retention_days} days");
        mtrace("Deleting data older than: " . userdate($cutoff_time));

        // Delete old analytics reports
        $deleted_reports = $DB->delete_records_select(
            'local_savian_analytics_reports',
            'timecreated < ?',
            [$cutoff_time]
        );

        if ($deleted_reports) {
            mtrace("  ✓ Deleted {$deleted_reports} old analytics reports");
        }

        // Delete old processed analytics events (keep unprocessed)
        $deleted_events = $DB->delete_records_select(
            'local_savian_analytics_events',
            'processed = 1 AND timecreated < ?',
            [$cutoff_time]
        );

        if ($deleted_events) {
            mtrace("  ✓ Deleted {$deleted_events} old processed events");
        }

        // Delete old analytics cache (stale data)
        $cache_cutoff = time() - (7 * 86400); // 7 days for cache
        $deleted_cache = $DB->delete_records_select(
            'local_savian_analytics_cache',
            'timemodified < ?',
            [$cache_cutoff]
        );

        if ($deleted_cache) {
            mtrace("  ✓ Deleted {$deleted_cache} stale cache entries");
        }

        // Clean up orphaned events (user or course deleted)
        $orphaned_events = $DB->execute("
            DELETE FROM {local_savian_analytics_events}
            WHERE user_id NOT IN (SELECT id FROM {user})
               OR course_id NOT IN (SELECT id FROM {course})
        ");

        if ($orphaned_events) {
            mtrace("  ✓ Cleaned up orphaned event records");
        }

        mtrace('Analytics cleanup task completed.');

        // Return summary
        return [
            'reports_deleted' => $deleted_reports ?? 0,
            'events_deleted' => $deleted_events ?? 0,
            'cache_deleted' => $deleted_cache ?? 0,
        ];
    }
}
