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
 * Ad-hoc task to send analytics.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\task;

/**
 * Ad-hoc task for sending analytics reports.
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
     * Get component name.
     *
     * @return string
     */
    public function get_component() {
        return 'local_savian_ai';
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

        $data = $this->get_custom_data();

        if (empty($data->courseid) || empty($data->report_type)) {
            mtrace("  send_analytics_adhoc: Missing required data (courseid or report_type).");
            return;
        }

        $courseid = $data->courseid;
        $reporttype = $data->report_type;
        $triggertype = $data->trigger_type ?? 'event';
        $datefrom = $data->date_from ?? 0;
        $dateto = $data->date_to ?? time();

        mtrace("  send_analytics_adhoc: Sending {$reporttype} report for course {$courseid}.");

        try {
            $builder = new \local_savian_ai\analytics\report_builder();
            $result = $builder->build_and_send_report(
                $courseid,
                $reporttype,
                $triggertype,
                $datefrom,
                $dateto,
                null
            );

            if ($result->success) {
                mtrace("  Report sent successfully (Report ID: {$result->report_id}).");

                // Mark batched events as processed if this was a real_time report.
                if ($reporttype === 'real_time' && !empty($data->event_ids)) {
                    $eventids = $data->event_ids;
                    list($insql, $params) = $DB->get_in_or_equal($eventids);
                    $DB->execute(
                        "UPDATE {local_savian_analytics_events} SET processed = 1 WHERE id {$insql}",
                        $params
                    );
                    mtrace("  Marked " . count($eventids) . " events as processed.");
                }
            } else {
                mtrace("  Report failed: " . ($result->error ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            mtrace("  Error sending report: " . $e->getMessage());
        }
    }
}
