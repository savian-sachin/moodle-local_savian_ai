<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

require_login();

$savian_cache = cache::make('local_savian_ai', 'session_data');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('learning_analytics', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Handle form submission (from send form)
$report_result = null;
if ($action === 'send' && confirm_sesskey()) {
    $date_from = optional_param('date_from', 0, PARAM_INT);
    $date_to = optional_param('date_to', time(), PARAM_INT);

    try {
        $builder = new \local_savian_ai\analytics\report_builder();
        $report_result = $builder->build_and_send_report(
            $courseid,
            'on_demand',
            'manual',
            $date_from,
            $date_to,
            $USER->id
        );

        // Check if async processing (Django processing started)
        if ($report_result->success && !isset($report_result->insights)) {
            // Async processing - use /latest/ endpoint to poll
            $savian_cache->set('analytics_polling_course', $courseid);
            $savian_cache->set('analytics_polling_started', time());

            redirect(new moodle_url('/local/savian_ai/analytics_reports.php', [
                'courseid' => $courseid,
                'action' => 'poll'
            ]), 'Generating analytics...', null, 'info');
        }
    } catch (Exception $e) {
        $report_result = (object)[
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Handle polling for async processing
if ($action === 'poll' && $savian_cache->get('analytics_polling_course')) {
    $polling_start_time = $savian_cache->get('analytics_polling_started') ?: time();

    // Use /latest/ endpoint
    $client = new \local_savian_ai\api\client();
    $latest_response = $client->get_latest_analytics($courseid);

    if ($latest_response->http_code === 200) {
        // Check if report is completed and has insights
        if (isset($latest_response->status) && $latest_response->status === 'completed' && isset($latest_response->insights)) {
            // Report is ready!
            $moodle_report = $DB->get_record_sql(
                "SELECT * FROM {local_savian_analytics_reports}
                 WHERE course_id = ? AND status IN ('pending', 'sending', 'sent')
                 ORDER BY timecreated DESC LIMIT 1",
                [$courseid]
            );

            // Update Moodle report with Django results
            if ($moodle_report) {
                $update = new stdClass();
                $update->id = $moodle_report->id;
                $update->status = 'sent';
                $update->api_response = json_encode($latest_response);
                $update->student_count = $latest_response->student_count ?? $moodle_report->student_count;
                $update->timemodified = time();
                $DB->update_record('local_savian_analytics_reports', $update);
            }

            $savian_cache->delete('analytics_polling_course');
            $savian_cache->delete('analytics_polling_started');

            redirect(new moodle_url('/local/savian_ai/analytics_reports.php', [
                'courseid' => $courseid
            ]), 'Analytics insights generated! Scroll down to view.', null, 'success');
        }
    } else if ($latest_response->http_code >= 400) {
        $savian_cache->delete('analytics_polling_course');
        $savian_cache->delete('analytics_polling_started');

        redirect(new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
                 'Error retrieving analytics: ' . ($latest_response->error ?? 'Unknown error'), null, 'error');
    }

    // Check timeout (5 minutes max)
    if (time() - $polling_start_time > 300) {
        $savian_cache->delete('analytics_polling_course');
        $savian_cache->delete('analytics_polling_started');

        redirect(new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
                 'Analytics generation timeout. Please try again or contact support.', null, 'warning');
    }
}

echo $OUTPUT->header();

// Define JavaScript function at the top (before any onclick events)
$PAGE->requires->js_amd_inline("
require(['jquery'], function($) {
    window.toggleInsights = function(id) {
        var element = document.getElementById(id);
        if (element) {
            if (element.style.display === 'none') {
                element.style.display = 'table-row';
            } else {
                element.style.display = 'none';
            }
        }
    };
});
");

// Consistent header
echo local_savian_ai_render_header('Learning Analytics Dashboard', 'Generate new reports and view insights');

// Show polling status with progress (if action=poll)
if ($action === 'poll' && $savian_cache->get('analytics_polling_course')) {
    $polling_start_time = $savian_cache->get('analytics_polling_started') ?: time();
    $elapsed_seconds = time() - $polling_start_time;

    // Get student count
    $student_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {enrol} e ON e.id = ue.enrolid
         JOIN {role_assignments} ra ON ra.userid = ue.userid
         JOIN {role} r ON r.id = ra.roleid
         WHERE e.courseid = ? AND r.shortname = 'student'",
        [$courseid]
    );

    // Try to get progress from Django /latest/ endpoint
    $client = new \local_savian_ai\api\client();
    $latest_response = $client->get_latest_analytics($courseid);

    $progress_percent = 0;
    $status_message = 'Initializing AI analysis...';
    $students_processed = 0;

    if ($latest_response->http_code === 200 && isset($latest_response->status)) {
        // Calculate progress based on status
        switch ($latest_response->status) {
            case 'pending':
                $progress_percent = 5;
                $status_message = 'Queued for processing...';
                break;
            case 'processing':
                // Estimate based on elapsed time (3-4 min for 50 students = ~4-5 sec/student)
                $estimated_total_time = $student_count * 4.5; // seconds per student
                $progress_percent = min(95, round(($elapsed_seconds / $estimated_total_time) * 100));

                // Estimate students processed
                $students_processed = min($student_count, floor($elapsed_seconds / 4.5));

                $status_message = "Analyzing student {$students_processed}/{$student_count}...";
                break;
            case 'completed':
                $progress_percent = 100;
                $status_message = 'Analysis complete!';
                break;
        }
    }

    echo html_writer::start_div('card mt-4 mb-4');
    echo html_writer::start_div('card-body text-center p-4');

    // Spinner
    echo html_writer::tag('div', '', [
        'class' => 'spinner-border text-primary mb-3',
        'role' => 'status',
        'style' => 'width: 4rem; height: 4rem;'
    ]);

    // Main heading
    echo html_writer::tag('h4', 'AI-Powered Analytics Processing');

    // Progress bar
    echo html_writer::start_div('progress mb-3', ['style' => 'height: 25px;']);
    echo html_writer::div(
        $progress_percent . '%',
        'progress-bar bg-primary progress-bar-striped progress-bar-animated',
        [
            'role' => 'progressbar',
            'style' => "width: {$progress_percent}%",
            'aria-valuenow' => $progress_percent,
            'aria-valuemin' => '0',
            'aria-valuemax' => '100'
        ]
    );
    echo html_writer::end_div();

    // Status message
    echo html_writer::tag('p', $status_message, ['class' => 'text-primary font-weight-bold mb-2']);

    // Student progress
    if ($students_processed > 0) {
        echo html_writer::tag('p',
            "Students Analyzed: {$students_processed} / {$student_count}",
            ['class' => 'text-muted']
        );
    }

    // Time elapsed
    $minutes = floor($elapsed_seconds / 60);
    $seconds = $elapsed_seconds % 60;
    echo html_writer::tag('p',
        sprintf('Time Elapsed: %dm %ds', $minutes, $seconds),
        ['class' => 'text-muted small']
    );

    // Estimated total time
    $estimated_total_minutes = ceil(($student_count * 4.5) / 60);
    echo html_writer::tag('p',
        "⏱️ Estimated Total Time: {$estimated_total_minutes} minutes ({$student_count} students × ~4-5 sec each)",
        ['class' => 'badge badge-info']
    );

    // What's happening
    echo html_writer::start_div('mt-4 p-3 bg-light rounded');
    echo html_writer::tag('small', '<strong>What\'s happening:</strong>', ['class' => 'text-muted']);
    echo html_writer::start_tag('ul', ['class' => 'text-left text-muted small mt-2 mb-0']);
    echo html_writer::tag('li', 'Extracting engagement metrics (logins, views, time spent)');
    echo html_writer::tag('li', 'Analyzing quiz and assignment performance');
    echo html_writer::tag('li', 'AI analyzing each student individually for personalized insights');
    echo html_writer::tag('li', 'Identifying at-risk patterns and risk factors');
    echo html_writer::tag('li', 'Generating course recommendations');
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();

    // Auto-refresh message
    echo html_writer::tag('p',
        'This page will automatically refresh every 5 seconds.',
        ['class' => 'text-muted small mt-3']
    );

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Auto-refresh every 5 seconds
    $PAGE->requires->js_amd_inline("
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    ");
}

// Show generate form (if not polling)
if ($action !== 'poll') {
    echo html_writer::start_div('card mb-4');
    echo html_writer::div('📊 Generate New Analytics Report', 'card-header bg-primary text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p',
        'Generate an AI-powered analytics report for this course. The system will analyze student engagement, ' .
        'performance, and identify at-risk students who need intervention.'
    );

    // Check for enrolled students
    $student_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {enrol} e ON e.id = ue.enrolid
         JOIN {role_assignments} ra ON ra.userid = ue.userid
         JOIN {role} r ON r.id = ra.roleid
         WHERE e.courseid = ? AND r.shortname = 'student'",
        [$courseid]
    );

    if ($student_count == 0) {
        echo html_writer::start_div('alert alert-warning mt-3');
        echo html_writer::tag('strong', 'No Students Enrolled');
        echo html_writer::tag('p', 'There are no students enrolled in this course. Please enroll students before generating analytics.');
        echo html_writer::end_div();
    } else {
        echo html_writer::start_div('alert alert-info mt-3');
        echo html_writer::tag('p',
            '<strong>' . $student_count . ' students</strong> enrolled in this course will be analyzed.'
        );
        echo html_writer::end_div();

        // Form
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
            'class' => 'mt-3'
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'courseid',
            'value' => $courseid
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'send'
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);

        // Date range selector
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', 'Report Period');
        echo html_writer::start_tag('select', ['name' => 'date_from', 'class' => 'form-control']);
        echo html_writer::tag('option', 'All Time (Recommended)', ['value' => '0', 'selected' => 'selected']);
        echo html_writer::tag('option', 'Last 30 Days', ['value' => (time() - 30 * 86400)]);
        echo html_writer::tag('option', 'Last 60 Days', ['value' => (time() - 60 * 86400)]);
        echo html_writer::tag('option', 'Last 90 Days', ['value' => (time() - 90 * 86400)]);
        echo html_writer::end_tag('select');
        echo html_writer::tag('small',
            'Select the time period for activity analysis. "All Time" is recommended for most accurate insights.',
            ['class' => 'form-text text-muted']
        );
        echo html_writer::end_div();

        // Submit button
        echo html_writer::start_div('text-center mt-4');
        echo html_writer::tag('button',
            '📊 Generate Analytics Report',
            ['type' => 'submit', 'class' => 'btn btn-savian btn-lg']
        );
        echo html_writer::end_div();

        echo html_writer::end_tag('form');
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Divider between generate and history
if ($action !== 'poll') {
    echo html_writer::tag('hr', '', ['class' => 'my-4']);
    echo html_writer::tag('h3', 'Report History', ['class' => 'mt-4 mb-3']);
}

// Fetch latest report from Django API and sync
$client = new \local_savian_ai\api\client();
$latest_response = $client->get_latest_analytics($courseid);

// Sync latest Django report with Moodle database
if ($latest_response->http_code === 200 && isset($latest_response->insights) && $latest_response->status === 'completed') {
    // Get most recent Moodle report without insights
    $moodle_report = $DB->get_record_sql(
        "SELECT * FROM {local_savian_analytics_reports}
         WHERE course_id = ?
         ORDER BY timecreated DESC
         LIMIT 1",
        [$courseid]
    );

    if ($moodle_report) {
        // Check if this report already has insights
        $existing_response = !empty($moodle_report->api_response) ? json_decode($moodle_report->api_response) : null;
        $has_insights = $existing_response && isset($existing_response->insights);

        if (!$has_insights) {
            // Update with Django results
            $update = new stdClass();
            $update->id = $moodle_report->id;
            $update->status = 'sent';
            $update->api_response = json_encode($latest_response);
            $update->student_count = $latest_response->student_count ?? $moodle_report->student_count;
            $update->timemodified = time();
            $DB->update_record('local_savian_analytics_reports', $update);
        }
    }
}

// Get reports from Moodle database (now synced)
$reports = $DB->get_records('local_savian_analytics_reports',
    ['course_id' => $courseid],
    'timecreated DESC'
);

if (empty($reports)) {
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('h4', 'No Reports Yet');
    echo html_writer::tag('p', get_string('no_reports', 'local_savian_ai'));
    echo html_writer::div(
        html_writer::tag('button',
            'Generate Your First Analytics Report',
            [
                'class' => 'btn btn-savian mt-2',
                'onclick' => 'window.scrollTo({top: 0, behavior: \'smooth\'});'
            ]
        ),
        'text-center'
    );
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('card');
    echo html_writer::div(
        count($reports) . ' Analytics Reports',
        'card-header'
    );
    echo html_writer::start_div('card-body p-0');

    // Reports table
    echo html_writer::start_tag('table', ['class' => 'table table-hover mb-0']);
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Date');
    echo html_writer::tag('th', 'Type');
    echo html_writer::tag('th', 'Students');
    echo html_writer::tag('th', 'Status');
    echo html_writer::tag('th', 'Actions');
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($reports as $report) {
        echo html_writer::start_tag('tr');

        // Date
        echo html_writer::start_tag('td');
        echo html_writer::tag('div', userdate($report->timecreated, '%d %b %Y'));
        echo html_writer::tag('small', userdate($report->timecreated, '%H:%M'), ['class' => 'text-muted d-block']);
        echo html_writer::end_tag('td');

        // Type
        echo html_writer::start_tag('td');
        $type_badge = '';
        switch ($report->report_type) {
            case 'on_demand':
                $type_badge = 'primary';
                break;
            case 'scheduled':
                $type_badge = 'info';
                break;
            case 'real_time':
                $type_badge = 'warning';
                break;
            case 'end_of_course':
                $type_badge = 'success';
                break;
        }
        echo html_writer::tag('span',
            ucfirst(str_replace('_', ' ', $report->report_type)),
            ['class' => "badge badge-{$type_badge}"]
        );
        echo html_writer::end_tag('td');

        // Students
        echo html_writer::tag('td', $report->student_count . ' students');

        // Status - check if we have insights or still processing
        echo html_writer::start_tag('td');
        $status_class = '';
        $status_icon = '';
        $status_text = '';
        $is_processing = false;

        if ($report->status == 'sent') {
            // Check if we actually have insights
            $response = !empty($report->api_response) ? json_decode($report->api_response) : null;

            if ($response && isset($response->insights)) {
                // Completed with insights
                $status_class = 'success';
                $status_icon = '✓';
                $status_text = 'Completed';
            } else {
                // Sent but still processing (async)
                $status_class = 'info';
                $status_icon = '⟳';
                $status_text = 'Processing';
                $is_processing = true;
            }
        } else {
            switch ($report->status) {
                case 'sending':
                    $status_class = 'info';
                    $status_icon = '⟳';
                    $status_text = 'Sending';
                    $is_processing = true;
                    break;
                case 'pending':
                    $status_class = 'warning';
                    $status_icon = '⏱';
                    $status_text = 'Pending';
                    $is_processing = true;
                    break;
                case 'failed':
                    $status_class = 'danger';
                    $status_icon = '✗';
                    $status_text = 'Failed';
                    break;
                default:
                    $status_class = 'secondary';
                    $status_icon = '?';
                    $status_text = ucfirst($report->status);
            }
        }

        echo html_writer::tag('span',
            $status_icon . ' ' . $status_text,
            ['class' => "badge badge-{$status_class}", 'id' => "status-badge-{$report->id}"]
        );
        echo html_writer::end_tag('td');

        // Actions
        echo html_writer::start_tag('td');

        if ($report->status == 'sent' && !empty($report->api_response)) {
            $response = json_decode($report->api_response);
            if ($response && isset($response->insights)) {
                // View insights button
                echo html_writer::start_tag('button', [
                    'class' => 'btn btn-sm btn-outline-primary mr-1',
                    'onclick' => "toggleInsights('insights-{$report->id}')"
                ]);
                echo 'View';
                echo html_writer::end_tag('button');

                // CSV export button
                echo html_writer::link(
                    new moodle_url('/local/savian_ai/export_analytics_csv.php', ['reportid' => $report->id]),
                    '📥',
                    ['class' => 'btn btn-sm btn-outline-success', 'title' => 'Export CSV']
                );
            }
        } else if ($report->status == 'failed') {
            // Show error
            echo html_writer::tag('small', substr($report->error_message, 0, 50) . '...', ['class' => 'text-danger']);
        }

        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');

        // Insights row (hidden by default)
        if ($report->status == 'sent' && !empty($report->api_response)) {
            $response = json_decode($report->api_response);
            if ($response && isset($response->insights)) {
                echo html_writer::start_tag('tr', [
                    'id' => "insights-{$report->id}",
                    'style' => 'display: none;'
                ]);
                echo html_writer::start_tag('td', ['colspan' => '5']);
                echo html_writer::start_div('p-4 bg-white border');

                $insights = $response->insights;

                // Get enrolled students for reverse lookup
                $enrolled_students = $DB->get_records_sql(
                    "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email,
                            u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                     FROM {user} u
                     JOIN {user_enrolments} ue ON ue.userid = u.id
                     JOIN {enrol} e ON e.id = ue.enrolid
                     JOIN {role_assignments} ra ON ra.userid = u.id
                     JOIN {role} r ON r.id = ra.roleid
                     WHERE e.courseid = ? AND r.shortname = 'student'",
                    [$courseid]
                );

                $student_ids = array_keys($enrolled_students);

                // Create anonymizer for reverse lookup
                $anonymizer = new \local_savian_ai\analytics\anonymizer();

                // Report metadata header
                echo html_writer::start_div('mb-4 pb-3 border-bottom');
                echo html_writer::tag('h4', '📊 Detailed Analytics Report');
                echo html_writer::tag('p',
                    'Report ID: ' . ($response->report_id ?? $report->id) . ' | ' .
                    'Generated: ' . userdate($report->timecreated, '%d %B %Y at %H:%M') . ' | ' .
                    'Students: ' . $report->student_count,
                    ['class' => 'text-muted small mb-0']
                );
                echo html_writer::end_div();

                // At-Risk Students Section
                if (isset($insights->at_risk_students) && !empty($insights->at_risk_students)) {
                    echo html_writer::tag('h5', '🚨 At-Risk Students (' . count($insights->at_risk_students) . ')', ['class' => 'text-danger mb-3']);

                    // Show first 5 at-risk students, or all if ≤ 5
                    $students_to_show = array_slice($insights->at_risk_students, 0, 5);
                    $remaining_count = count($insights->at_risk_students) - count($students_to_show);

                    foreach ($students_to_show as $student) {
                        // Reverse lookup to find actual student
                        $user_id = $anonymizer->reverse_lookup($student->anon_id, $student_ids);
                        $user = $user_id ? $enrolled_students[$user_id] : null;

                        echo html_writer::start_div('card mb-2 border-danger');
                        echo html_writer::start_div('card-body p-2');

                        // Risk badge
                        $badge_class = $student->risk_level == 'high' ? 'danger' : ($student->risk_level == 'medium' ? 'warning' : 'info');
                        echo html_writer::tag('span',
                            strtoupper($student->risk_level) . ' RISK',
                            ['class' => "badge badge-{$badge_class} float-right"]
                        );

                        // Show actual student name if found
                        if ($user) {
                            $student_name = fullname($user);
                            $profile_url = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $courseid]);

                            echo html_writer::link($profile_url, $student_name, ['class' => 'font-weight-bold small', 'target' => '_blank']);
                            echo html_writer::empty_tag('br');
                            echo html_writer::tag('small', $user->email, ['class' => 'text-muted']);
                            echo html_writer::tag('span', ' | Risk Score: ' . round($student->risk_score * 100) . '%', ['class' => 'text-muted small']);
                        } else {
                            // Fallback if reverse lookup fails
                            echo html_writer::tag('strong', 'Student ' . substr($student->anon_id, 0, 12) . '...', ['class' => 'small']);
                            echo html_writer::tag('span', ' Risk Score: ' . round($student->risk_score * 100) . '%', ['class' => 'text-muted small']);
                        }

                        // Risk factors (show first 3)
                        if (!empty($student->risk_factors)) {
                            echo html_writer::start_tag('ul', ['class' => 'small mb-1 mt-1']);
                            $factors_to_show = array_slice($student->risk_factors, 0, 3);
                            foreach ($factors_to_show as $factor) {
                                echo html_writer::tag('li', $factor, ['class' => 'text-danger']);
                            }
                            if (count($student->risk_factors) > 3) {
                                echo html_writer::tag('li', '+ ' . (count($student->risk_factors) - 3) . ' more factors...', ['class' => 'text-muted']);
                            }
                            echo html_writer::end_tag('ul');
                        }

                        echo html_writer::end_div();
                        echo html_writer::end_div();
                    }

                    if ($remaining_count > 0) {
                        echo html_writer::div(
                            "+ {$remaining_count} more at-risk students - Export CSV for full list",
                            'alert alert-info small mb-3'
                        );
                    }
                } else {
                    echo html_writer::tag('p', '✓ No at-risk students identified', ['class' => 'text-success']);
                }

                // Course Recommendations
                if (isset($insights->course_recommendations) && !empty($insights->course_recommendations)) {
                    echo html_writer::tag('h5', '💡 Course Recommendations (' . count($insights->course_recommendations) . ')', ['class' => 'text-info mt-4 mb-3']);
                    echo html_writer::start_tag('ol', ['class' => 'small']);
                    $recs_to_show = array_slice($insights->course_recommendations, 0, 6);
                    foreach ($recs_to_show as $rec) {
                        echo html_writer::tag('li', $rec, ['class' => 'mb-2']);
                    }
                    if (count($insights->course_recommendations) > 6) {
                        echo html_writer::tag('li', '+ ' . (count($insights->course_recommendations) - 6) . ' more recommendations...', ['class' => 'text-muted']);
                    }
                    echo html_writer::end_tag('ol');
                }

                // Engagement Insights
                if (isset($insights->engagement_insights)) {
                    $engagement = $insights->engagement_insights;
                    echo html_writer::tag('h5', '📈 Engagement Insights', ['class' => 'text-primary mt-4 mb-3']);

                    echo html_writer::start_div('row');
                    if (isset($engagement->average_engagement_score)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        echo html_writer::tag('div', round($engagement->average_engagement_score * 100) . '%', ['class' => 'h4 text-primary']);
                        echo html_writer::tag('small', 'Avg Engagement', ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    if (isset($engagement->low_engagement_count)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        echo html_writer::tag('div', $engagement->low_engagement_count, ['class' => 'h4 text-warning']);
                        echo html_writer::tag('small', 'Low Engagement', ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    if (isset($engagement->peak_activity_days)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        echo html_writer::tag('div', implode(', ', $engagement->peak_activity_days), ['class' => 'small font-weight-bold']);
                        echo html_writer::tag('small', 'Peak Days', ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    echo html_writer::end_div();
                }

                // Action buttons
                echo html_writer::start_div('text-center mt-3 pt-3 border-top');
                echo html_writer::link(
                    new moodle_url('/local/savian_ai/export_analytics_csv.php', ['reportid' => $report->id]),
                    '📥 Export Full Report (CSV)',
                    ['class' => 'btn btn-sm btn-success']
                );
                echo html_writer::end_div();

                echo html_writer::end_div();
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            }
        }
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Auto-refresh for processing reports
$has_processing = false;
foreach ($reports as $report) {
    $response = !empty($report->api_response) ? json_decode($report->api_response) : null;
    if ($report->status == 'sent' && (!$response || !isset($response->insights))) {
        $has_processing = true;
        break;
    }
    if (in_array($report->status, ['sending', 'pending'])) {
        $has_processing = true;
        break;
    }
}

if ($has_processing) {
    $PAGE->requires->js_amd_inline("
        // Auto-refresh every 5 seconds if reports are processing
        setTimeout(function() {
            console.log('Auto-refreshing to check report status...');
            window.location.reload();
        }, 5000);
    ");
}

// Scroll to top button (to access generate form)
if ($action !== 'poll') {
    echo html_writer::start_div('text-center mt-4');
    echo html_writer::tag('button',
        '⬆️ Generate New Report',
        [
            'class' => 'btn btn-outline-primary',
            'onclick' => 'window.scrollTo({top: 0, behavior: \'smooth\'});'
        ]
    );
    echo html_writer::end_div();
}

// Back link
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← Back to Dashboard',
        ['class' => 'btn btn-secondary']
    ),
    'mt-4'
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
