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
 * Analytics CSV export page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$reportid = required_param('reportid', PARAM_INT);

$report = $DB->get_record('local_savian_ai_analytics_reports', ['id' => $reportid], '*', MUST_EXIST);

$course = $DB->get_record('course', ['id' => $report->course_id], '*', MUST_EXIST);
$context = context_course::instance($report->course_id);

require_capability('local/savian_ai:generate', $context);

// Prepare CSV filename.
$filename = 'analytics_' . $course->shortname . '_' . date('Y-m-d', $report->timecreated) . '.csv';

// Set headers for CSV download.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream.
$output = fopen('php://output', 'w');

// Add BOM for UTF-8.
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Parse API response.
$response = json_decode($report->api_response);

// Write header information.
fputcsv($output, [get_string('csv_title', 'local_savian_ai')]);
fputcsv($output, [get_string('csv_course', 'local_savian_ai'), $course->fullname]);
fputcsv($output, [get_string('csv_course_id', 'local_savian_ai'), $report->course_id]);
fputcsv($output, [get_string('csv_report_date', 'local_savian_ai'), userdate($report->timecreated, '%d %B %Y %H:%M')]);
fputcsv($output, [get_string('csv_report_type', 'local_savian_ai'), ucfirst(str_replace('_', ' ', $report->report_type))]);
fputcsv($output, [get_string('csv_students_analyzed', 'local_savian_ai'), $report->student_count]);
fputcsv($output, [get_string('csv_status', 'local_savian_ai'), ucfirst($report->status)]);
fputcsv($output, []);

// If insights available, export them.
if ($response && isset($response->insights)) {
    $insights = $response->insights;

    // At-risk students section.
    if (isset($insights->at_risk_students) && !empty($insights->at_risk_students)) {
        fputcsv($output, [get_string('csv_at_risk_students', 'local_savian_ai')]);
        fputcsv($output, [
            get_string('csv_student_id_anon', 'local_savian_ai'),
            get_string('csv_risk_level', 'local_savian_ai'),
            get_string('csv_risk_score', 'local_savian_ai'),
            get_string('csv_risk_factors', 'local_savian_ai'),
            get_string('csv_recommended_actions', 'local_savian_ai'),
        ]);

        foreach ($insights->at_risk_students as $student) {
            $riskfactors = isset($student->risk_factors) && is_array($student->risk_factors) ?
                implode('; ', $student->risk_factors) : '';
            $actions = isset($student->recommended_actions) && is_array($student->recommended_actions) ?
                implode('; ', $student->recommended_actions) : '';

            fputcsv($output, [
                substr($student->anon_id, 0, 16) . '...',
                strtoupper($student->risk_level),
                round($student->risk_score * 100) . '%',
                $riskfactors,
                $actions,
            ]);
        }
        fputcsv($output, []);
    }

    // Course recommendations.
    if (isset($insights->course_recommendations) && !empty($insights->course_recommendations)) {
        fputcsv($output, [get_string('csv_course_recommendations', 'local_savian_ai')]);
        fputcsv($output, [get_string('csv_recommendation', 'local_savian_ai')]);

        foreach ($insights->course_recommendations as $recommendation) {
            fputcsv($output, [$recommendation]);
        }
        fputcsv($output, []);
    }

    // Struggling topics.
    if (isset($insights->struggling_topics) && !empty($insights->struggling_topics)) {
        fputcsv($output, [get_string('csv_struggling_topics', 'local_savian_ai')]);
        fputcsv($output, [get_string('csv_topic_module', 'local_savian_ai'), get_string('csv_students_struggling', 'local_savian_ai'), get_string('csv_average_grade', 'local_savian_ai'), get_string('csv_recommended_action', 'local_savian_ai')]);

        foreach ($insights->struggling_topics as $topic) {
            fputcsv($output, [
                $topic->topic ?? $topic->module_name ?? get_string('csv_unknown', 'local_savian_ai'),
                $topic->students_struggling ?? 0,
                isset($topic->avg_grade) ? round($topic->avg_grade, 1) . '%' : get_string('csv_na', 'local_savian_ai'),
                $topic->recommended_action ?? '',
            ]);
        }
        fputcsv($output, []);
    }

    // Engagement insights.
    if (isset($insights->engagement_insights)) {
        $engagement = $insights->engagement_insights;

        fputcsv($output, [get_string('csv_engagement_insights', 'local_savian_ai')]);
        fputcsv($output, [get_string('csv_metric', 'local_savian_ai'), get_string('csv_value', 'local_savian_ai')]);
        fputcsv($output, [
            get_string('csv_average_engagement', 'local_savian_ai'),
            round($engagement->average_engagement_score * 100) . '%',
        ]);
        fputcsv($output, [get_string('csv_low_engagement_count', 'local_savian_ai'), $engagement->low_engagement_count ?? 0]);

        if (isset($engagement->peak_activity_days) && is_array($engagement->peak_activity_days)) {
            fputcsv($output, [get_string('csv_peak_activity_days', 'local_savian_ai'), implode(', ', $engagement->peak_activity_days)]);
        }

        if (isset($engagement->peak_activity_hours) && is_array($engagement->peak_activity_hours)) {
            fputcsv($output, [
                get_string('csv_peak_activity_hours', 'local_savian_ai'),
                implode(', ', $engagement->peak_activity_hours),
            ]);
        }

        fputcsv($output, []);
    }

    // Summary statistics.
    fputcsv($output, [get_string('csv_summary', 'local_savian_ai')]);
    fputcsv($output, [get_string('csv_metric', 'local_savian_ai'), get_string('csv_value', 'local_savian_ai')]);
    fputcsv($output, [get_string('csv_students_processed', 'local_savian_ai'), $insights->processed_students ?? $report->student_count]);

    if (isset($insights->at_risk_students)) {
        $atriskcount = is_array($insights->at_risk_students) ?
            count($insights->at_risk_students) : 0;
        fputcsv($output, [get_string('at_risk_students', 'local_savian_ai'), $atriskcount]);
    }
} else {
    // No insights available.
    fputcsv($output, [get_string('csv_no_insights', 'local_savian_ai')]);
    fputcsv($output, [get_string('csv_status', 'local_savian_ai'), $report->status]);
    if (!empty($report->error_message)) {
        fputcsv($output, [get_string('csv_error', 'local_savian_ai'), $report->error_message]);
    }
}

fputcsv($output, []);
fputcsv($output, [get_string('csv_generated_by', 'local_savian_ai')]);
fputcsv($output, [get_string('csv_report_id', 'local_savian_ai'), $report->id]);

fclose($output);
exit;
