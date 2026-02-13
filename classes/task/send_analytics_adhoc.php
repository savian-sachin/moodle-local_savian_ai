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
 * Ad-hoc task for sending analytics reports
 *
 * Offloads analytics API calls from event observers to prevent
 * blocking Moodle's event processing with sleep()/retry calls.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_analytics_adhoc extends \core\task\adhoc_task {

    /**
     * Get component name
     *
     * @return string
     */
    public function get_component() {
        return 'local_savian_ai';
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

        $data = $this->get_custom_data();

        if (empty($data->courseid) || empty($data->report_type)) {
            mtrace("  send_analytics_adhoc: Missing required data (courseid or report_type)");
            return;
        }

        $courseid = $data->courseid;
        $report_type = $data->report_type;
        $trigger_type = $data->trigger_type ?? 'event';
        $date_from = $data->date_from ?? 0;
        $date_to = $data->date_to ?? time();

        mtrace("  send_analytics_adhoc: Sending {$report_type} report for course {$courseid}");

        try {
            $builder = new \local_savian_ai\analytics\report_builder();
            $result = $builder->build_and_send_report(
                $courseid,
                $report_type,
                $trigger_type,
                $date_from,
                $date_to,
                null
            );

            if ($result->success) {
                mtrace("  ✓ Report sent successfully (Report ID: {$result->report_id})");

                // Mark batched events as processed if this was a real_time report.
                if ($report_type === 'real_time' && !empty($data->event_ids)) {
                    global $DB;
                    $event_ids = $data->event_ids;
                    list($in_sql, $params) = $DB->get_in_or_equal($event_ids);
                    $DB->execute(
                        "UPDATE {local_savian_analytics_events} SET processed = 1 WHERE id {$in_sql}",
                        $params
                    );
                    mtrace("  ✓ Marked " . count($event_ids) . " events as processed");
                }
            } else {
                mtrace("  ✗ Report failed: " . ($result->error ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            mtrace("  ✗ Error sending report: " . $e->getMessage());
        }
    }
}
