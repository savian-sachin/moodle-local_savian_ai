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

$report = $DB->get_record('local_savian_analytics_reports', ['id' => $reportid], '*', MUST_EXIST);

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
fputcsv($output, ['Savian AI Learning Analytics Report']);
fputcsv($output, ['Course', $course->fullname]);
fputcsv($output, ['Course ID', $report->course_id]);
fputcsv($output, ['Report Date', userdate($report->timecreated, '%d %B %Y %H:%M')]);
fputcsv($output, ['Report Type', ucfirst(str_replace('_', ' ', $report->report_type))]);
fputcsv($output, ['Students Analyzed', $report->student_count]);
fputcsv($output, ['Status', ucfirst($report->status)]);
fputcsv($output, []);

// If insights available, export them.
if ($response && isset($response->insights)) {
    $insights = $response->insights;

    // At-risk students section.
    if (isset($insights->at_risk_students) && !empty($insights->at_risk_students)) {
        fputcsv($output, ['AT-RISK STUDENTS']);
        fputcsv($output, [
            'Student ID (Anonymized)',
            'Risk Level',
            'Risk Score',
            'Risk Factors',
            'Recommended Actions',
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
        fputcsv($output, ['COURSE RECOMMENDATIONS']);
        fputcsv($output, ['Recommendation']);

        foreach ($insights->course_recommendations as $recommendation) {
            fputcsv($output, [$recommendation]);
        }
        fputcsv($output, []);
    }

    // Struggling topics.
    if (isset($insights->struggling_topics) && !empty($insights->struggling_topics)) {
        fputcsv($output, ['STRUGGLING TOPICS']);
        fputcsv($output, ['Topic/Module', 'Students Struggling', 'Average Grade', 'Recommended Action']);

        foreach ($insights->struggling_topics as $topic) {
            fputcsv($output, [
                $topic->topic ?? $topic->module_name ?? 'Unknown',
                $topic->students_struggling ?? 0,
                isset($topic->avg_grade) ? round($topic->avg_grade, 1) . '%' : 'N/A',
                $topic->recommended_action ?? '',
            ]);
        }
        fputcsv($output, []);
    }

    // Engagement insights.
    if (isset($insights->engagement_insights)) {
        $engagement = $insights->engagement_insights;

        fputcsv($output, ['ENGAGEMENT INSIGHTS']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, [
            'Average Engagement',
            round($engagement->average_engagement_score * 100) . '%',
        ]);
        fputcsv($output, ['Low Engagement Count', $engagement->low_engagement_count ?? 0]);

        if (isset($engagement->peak_activity_days) && is_array($engagement->peak_activity_days)) {
            fputcsv($output, ['Peak Activity Days', implode(', ', $engagement->peak_activity_days)]);
        }

        if (isset($engagement->peak_activity_hours) && is_array($engagement->peak_activity_hours)) {
            fputcsv($output, [
                'Peak Activity Hours',
                implode(', ', $engagement->peak_activity_hours),
            ]);
        }

        fputcsv($output, []);
    }

    // Summary statistics.
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Students Processed', $insights->processed_students ?? $report->student_count]);

    if (isset($insights->at_risk_students)) {
        $atriskcount = is_array($insights->at_risk_students) ?
            count($insights->at_risk_students) : 0;
        fputcsv($output, ['At-Risk Students', $atriskcount]);
    }
} else {
    // No insights available.
    fputcsv($output, ['No insights data available for this report.']);
    fputcsv($output, ['Status', $report->status]);
    if (!empty($report->error_message)) {
        fputcsv($output, ['Error', $report->error_message]);
    }
}

fputcsv($output, []);
fputcsv($output, ['Generated by Savian AI Learning Analytics']);
fputcsv($output, ['Report ID', $report->id]);

fclose($output);
exit;
