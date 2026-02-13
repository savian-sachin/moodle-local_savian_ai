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
 * Scheduled task to cleanup old analytics.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\task;

/**
 * Scheduled task to cleanup old analytics data (GDPR data retention).
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
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup_old_analytics', 'local_savian_ai');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting analytics data cleanup task...');

        // Get retention period from config (default: 365 days).
        $retentiondays = get_config('local_savian_ai', 'analytics_retention_days');
        if (empty($retentiondays)) {
            $retentiondays = 365;
        }

        $cutofftime = time() - ($retentiondays * 86400);

        mtrace("Retention period: {$retentiondays} days");
        mtrace("Deleting data older than: " . userdate($cutofftime));

        // Delete old analytics reports.
        $deletedreports = $DB->delete_records_select(
            'local_savian_ai_analytics_reports',
            'timecreated < ?',
            [$cutofftime]
        );

        if ($deletedreports) {
            mtrace("  Deleted {$deletedreports} old analytics reports.");
        }

        // Delete old processed analytics events (keep unprocessed).
        $deletedevents = $DB->delete_records_select(
            'local_savian_ai_analytics_events',
            'processed = 1 AND timecreated < ?',
            [$cutofftime]
        );

        if ($deletedevents) {
            mtrace("  Deleted {$deletedevents} old processed events.");
        }

        // Delete old analytics cache (stale data).
        $cachecutoff = time() - (7 * 86400); // 7 days for cache.
        $deletedcache = $DB->delete_records_select(
            'local_savian_ai_analytics_cache',
            'timemodified < ?',
            [$cachecutoff]
        );

        if ($deletedcache) {
            mtrace("  Deleted {$deletedcache} stale cache entries.");
        }

        // Clean up orphaned events (user or course deleted).
        $sql = "DELETE FROM {local_savian_ai_analytics_events}
                WHERE user_id NOT IN (SELECT id FROM {user})
                   OR course_id NOT IN (SELECT id FROM {course})";
        $orphanedevents = $DB->execute($sql);

        if ($orphanedevents) {
            mtrace("  Cleaned up orphaned event records.");
        }

        mtrace('Analytics cleanup task completed.');

        // Return summary.
        return [
            'reports_deleted' => $deletedreports ?? 0,
            'events_deleted' => $deletedevents ?? 0,
            'cache_deleted' => $deletedcache ?? 0,
        ];
    }
}
