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
 * Analytics reports page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

require_login();

$saviancache = cache::make('local_savian_ai', 'session_data');

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

// Handle form submission (from send form).
$reportresult = null;
if ($action === 'send' && confirm_sesskey()) {
    $datefrom = optional_param('date_from', 0, PARAM_INT);
    $dateto = optional_param('date_to', time(), PARAM_INT);

    try {
        $builder = new \local_savian_ai\analytics\report_builder();
        $reportresult = $builder->build_and_send_report(
            $courseid,
            'on_demand',
            'manual',
            $datefrom,
            $dateto,
            $USER->id
        );

        // Check if async processing (Django processing started).
        if ($reportresult->success && !isset($reportresult->insights)) {
            // Async processing - use /latest/ endpoint to poll.
            $saviancache->set('analytics_polling_course', $courseid);
            $saviancache->set('analytics_polling_started', time());

            redirect(
                new moodle_url('/local/savian_ai/analytics_reports.php', [
                    'courseid' => $courseid,
                    'action' => 'poll',
                ]),
                get_string('generating_analytics_msg', 'local_savian_ai'),
                null,
                'info'
            );
        }
    } catch (Exception $e) {
        $reportresult = (object)[
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// Handle polling for async processing.
if ($action === 'poll' && $saviancache->get('analytics_polling_course')) {
    $pollingstarttime = $saviancache->get('analytics_polling_started') ?: time();

    // Use /latest/ endpoint.
    $client = new \local_savian_ai\api\client();
    $latestresponse = $client->get_latest_analytics($courseid);

    if ($latestresponse->http_code === 200) {
        // Check if report is completed and has insights.
        if (isset($latestresponse->status) && $latestresponse->status === 'completed' && isset($latestresponse->insights)) {
            // Report is ready.
            $moodlereport = $DB->get_record_sql(
                "SELECT * FROM {local_savian_ai_analytics_reports}
                 WHERE course_id = ? AND status IN ('pending', 'sending', 'sent')
                 ORDER BY timecreated DESC LIMIT 1",
                [$courseid]
            );

            // Update Moodle report with Django results.
            if ($moodlereport) {
                $update = new stdClass();
                $update->id = $moodlereport->id;
                $update->status = 'sent';
                $update->api_response = json_encode($latestresponse);
                $update->student_count = $latestresponse->student_count ?? $moodlereport->student_count;
                $update->timemodified = time();
                $DB->update_record('local_savian_ai_analytics_reports', $update);
            }

            $saviancache->delete('analytics_polling_course');
            $saviancache->delete('analytics_polling_started');

            redirect(
                new moodle_url('/local/savian_ai/analytics_reports.php', [
                    'courseid' => $courseid,
                ]),
                get_string('analytics_insights_generated', 'local_savian_ai'),
                null,
                'success'
            );
        }
    } else if ($latestresponse->http_code >= 400) {
        $saviancache->delete('analytics_polling_course');
        $saviancache->delete('analytics_polling_started');

        redirect(
            new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
            get_string('error_retrieving_analytics', 'local_savian_ai', $latestresponse->error ?? get_string('csv_unknown', 'local_savian_ai')),
            null,
            'error'
        );
    }

    // Check timeout (5 minutes max).
    if (time() - $pollingstarttime > 300) {
        $saviancache->delete('analytics_polling_course');
        $saviancache->delete('analytics_polling_started');

        redirect(
            new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
            get_string('analytics_timeout', 'local_savian_ai'),
            null,
            'warning'
        );
    }
}

echo $OUTPUT->header();

// Define JavaScript function at the top (before any onclick events).
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

// Consistent header.
echo local_savian_ai_render_header(get_string('learning_analytics', 'local_savian_ai'), get_string('analytics_dashboard_subtitle', 'local_savian_ai'));

// Show polling status with progress (if action=poll).
if ($action === 'poll' && $saviancache->get('analytics_polling_course')) {
    $pollingstarttime = $saviancache->get('analytics_polling_started') ?: time();
    $elapsedseconds = time() - $pollingstarttime;

    // Get student count.
    $studentcount = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {enrol} e ON e.id = ue.enrolid
         JOIN {role_assignments} ra ON ra.userid = ue.userid
         JOIN {role} r ON r.id = ra.roleid
         WHERE e.courseid = ? AND r.shortname = 'student'",
        [$courseid]
    );

    // Try to get progress from Django /latest/ endpoint.
    $client = new \local_savian_ai\api\client();
    $latestresponse = $client->get_latest_analytics($courseid);

    $progresspercent = 0;
    $statusmessage = get_string('initializing_analysis', 'local_savian_ai');
    $studentsprocessed = 0;

    if ($latestresponse->http_code === 200 && isset($latestresponse->status)) {
        // Calculate progress based on status.
        switch ($latestresponse->status) {
            case 'pending':
                $progresspercent = 5;
                $statusmessage = get_string('queued_processing', 'local_savian_ai');
                break;
            case 'processing':
                // Estimate based on elapsed time (3-4 min for 50 students = ~4-5 sec/student).
                $estimatedtotaltime = $studentcount * 4.5; // Seconds per student.
                $progresspercent = min(95, round(($elapsedseconds / $estimatedtotaltime) * 100));

                // Estimate students processed.
                $studentsprocessed = min($studentcount, floor($elapsedseconds / 4.5));

                $statusmessage = "Analyzing student {$studentsprocessed}/{$studentcount}...";
                break;
            case 'completed':
                $progresspercent = 100;
                $statusmessage = get_string('analysis_complete', 'local_savian_ai');
                break;
        }
    }

    echo html_writer::start_div('card mt-4 mb-4');
    echo html_writer::start_div('card-body text-center p-4');

    // Spinner.
    echo html_writer::tag(
        'div',
        '',
        [
            'class' => 'spinner-border text-primary mb-3',
            'role' => 'status',
            'style' => 'width: 4rem; height: 4rem;',
        ]
    );

    // Main heading.
    echo html_writer::tag('h4', get_string('ai_analytics_processing', 'local_savian_ai'));

    // Progress bar.
    echo html_writer::start_div('progress mb-3', ['style' => 'height: 25px;']);
    echo html_writer::div(
        $progresspercent . '%',
        'progress-bar bg-primary progress-bar-striped progress-bar-animated',
        [
            'role' => 'progressbar',
            'style' => "width: {$progresspercent}%",
            'aria-valuenow' => $progresspercent,
            'aria-valuemin' => '0',
            'aria-valuemax' => '100',
        ]
    );
    echo html_writer::end_div();

    // Status message.
    echo html_writer::tag('p', $statusmessage, ['class' => 'text-primary font-weight-bold mb-2']);

    // Student progress.
    if ($studentsprocessed > 0) {
        echo html_writer::tag(
            'p',
            get_string('students_analyzed_progress', 'local_savian_ai', (object)['processed' => $studentsprocessed, 'total' => $studentcount]),
            ['class' => 'text-muted']
        );
    }

    // Time elapsed.
    $minutes = floor($elapsedseconds / 60);
    $seconds = $elapsedseconds % 60;
    echo html_writer::tag(
        'p',
        get_string('time_elapsed', 'local_savian_ai', (object)['minutes' => $minutes, 'seconds' => $seconds]),
        ['class' => 'text-muted small']
    );

    // Estimated total time.
    $estimatedtotalminutes = ceil(($studentcount * 4.5) / 60);
    echo html_writer::tag(
        'p',
        get_string('estimated_total_time', 'local_savian_ai', (object)['minutes' => $estimatedtotalminutes, 'students' => $studentcount]),
        ['class' => 'badge badge-info']
    );

    // What is happening.
    echo html_writer::start_div('mt-4 p-3 bg-light rounded');
    echo html_writer::tag('small', '<strong>' . get_string('whats_happening', 'local_savian_ai') . '</strong>', ['class' => 'text-muted']);
    echo html_writer::start_tag('ul', ['class' => 'text-left text-muted small mt-2 mb-0']);
    echo html_writer::tag('li', get_string('step_extracting_metrics', 'local_savian_ai'));
    echo html_writer::tag('li', get_string('step_analyzing_performance', 'local_savian_ai'));
    echo html_writer::tag('li', get_string('step_ai_analyzing', 'local_savian_ai'));
    echo html_writer::tag('li', get_string('step_identifying_risk', 'local_savian_ai'));
    echo html_writer::tag('li', get_string('step_generating_recommendations', 'local_savian_ai'));
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();

    // Auto-refresh message.
    echo html_writer::tag(
        'p',
        get_string('auto_refresh_5s', 'local_savian_ai'),
        ['class' => 'text-muted small mt-3']
    );

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Auto-refresh every 5 seconds.
    $PAGE->requires->js_amd_inline("
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    ");
}

// Show generate form (if not polling).
if ($action !== 'poll') {
    echo html_writer::start_div('card mb-4');
    echo html_writer::div(get_string('generate_analytics_report', 'local_savian_ai'), 'card-header bg-primary text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag(
        'p',
        get_string('generate_report_desc', 'local_savian_ai')
    );

    // Check for enrolled students.
    $studentcount = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {enrol} e ON e.id = ue.enrolid
         JOIN {role_assignments} ra ON ra.userid = ue.userid
         JOIN {role} r ON r.id = ra.roleid
         WHERE e.courseid = ? AND r.shortname = 'student'",
        [$courseid]
    );

    if ($studentcount == 0) {
        echo html_writer::start_div('alert alert-warning mt-3');
        echo html_writer::tag('strong', get_string('no_students_enrolled', 'local_savian_ai'));
        echo html_writer::tag(
            'p',
            'There are no students enrolled in this course. '
            . 'Please enroll students before generating analytics.'
        );
        echo html_writer::end_div();
    } else {
        echo html_writer::start_div('alert alert-info mt-3');
        echo html_writer::tag(
            'p',
            get_string('enrolled_students_analyzed', 'local_savian_ai', $studentcount)
        );
        echo html_writer::end_div();

        // Form.
        echo html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
                'class' => 'mt-3',
            ]
        );

        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'courseid',
                'value' => $courseid,
            ]
        );
        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'action',
                'value' => 'send',
            ]
        );
        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
            ]
        );

        // Date range selector.
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('report_period', 'local_savian_ai'));
        echo html_writer::start_tag('select', ['name' => 'date_from', 'class' => 'form-control']);
        echo html_writer::tag('option', get_string('all_time_recommended', 'local_savian_ai'), ['value' => '0', 'selected' => 'selected']);
        echo html_writer::tag('option', get_string('last_30_days', 'local_savian_ai'), ['value' => (time() - 30 * 86400)]);
        echo html_writer::tag('option', get_string('last_60_days', 'local_savian_ai'), ['value' => (time() - 60 * 86400)]);
        echo html_writer::tag('option', get_string('last_90_days', 'local_savian_ai'), ['value' => (time() - 90 * 86400)]);
        echo html_writer::end_tag('select');
        echo html_writer::tag(
            'small',
            get_string('report_period_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted']
        );
        echo html_writer::end_div();

        // Submit button.
        echo html_writer::start_div('text-center mt-4');
        echo html_writer::tag(
            'button',
            get_string('generate_analytics_report', 'local_savian_ai'),
            ['type' => 'submit', 'class' => 'btn btn-savian btn-lg']
        );
        echo html_writer::end_div();

        echo html_writer::end_tag('form');
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Divider between generate and history.
if ($action !== 'poll') {
    echo html_writer::tag('hr', '', ['class' => 'my-4']);
    echo html_writer::tag('h3', get_string('report_history', 'local_savian_ai'), ['class' => 'mt-4 mb-3']);
}

// Fetch latest report from Django API and sync.
$client = new \local_savian_ai\api\client();
$latestresponse = $client->get_latest_analytics($courseid);

// Sync latest Django report with Moodle database.
if ($latestresponse->http_code === 200 && isset($latestresponse->insights) && $latestresponse->status === 'completed') {
    // Get most recent Moodle report without insights.
    $moodlereport = $DB->get_record_sql(
        "SELECT * FROM {local_savian_ai_analytics_reports}
         WHERE course_id = ?
         ORDER BY timecreated DESC
         LIMIT 1",
        [$courseid]
    );

    if ($moodlereport) {
        // Check if this report already has insights.
        $existingresponse = !empty($moodlereport->api_response) ? json_decode($moodlereport->api_response) : null;
        $hasinsights = $existingresponse && isset($existingresponse->insights);

        if (!$hasinsights) {
            // Update with Django results.
            $update = new stdClass();
            $update->id = $moodlereport->id;
            $update->status = 'sent';
            $update->api_response = json_encode($latestresponse);
            $update->student_count = $latestresponse->student_count ?? $moodlereport->student_count;
            $update->timemodified = time();
            $DB->update_record('local_savian_ai_analytics_reports', $update);
        }
    }
}

// Get reports from Moodle database (now synced).
$reports = $DB->get_records(
    'local_savian_ai_analytics_reports',
    ['course_id' => $courseid],
    'timecreated DESC'
);

if (empty($reports)) {
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('h4', get_string('no_reports_yet', 'local_savian_ai'));
    echo html_writer::tag('p', get_string('no_reports', 'local_savian_ai'));
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('generate_first_report', 'local_savian_ai'),
            [
                'class' => 'btn btn-savian mt-2',
                'onclick' => 'window.scrollTo({top: 0, behavior: \'smooth\'});',
            ]
        ),
        'text-center'
    );
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('card');
    echo html_writer::div(
        get_string('n_analytics_reports', 'local_savian_ai', count($reports)),
        'card-header'
    );
    echo html_writer::start_div('card-body p-0');

    // Reports table.
    echo html_writer::start_tag('table', ['class' => 'table table-hover mb-0']);
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('date_header', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('type_header', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('students_analyzed', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('status_header', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('actions', 'local_savian_ai'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($reports as $report) {
        echo html_writer::start_tag('tr');

        // Date.
        echo html_writer::start_tag('td');
        echo html_writer::tag('div', userdate($report->timecreated, '%d %b %Y'));
        echo html_writer::tag('small', userdate($report->timecreated, '%H:%M'), ['class' => 'text-muted d-block']);
        echo html_writer::end_tag('td');

        // Type.
        echo html_writer::start_tag('td');
        $typebadge = '';
        switch ($report->report_type) {
            case 'on_demand':
                $typebadge = 'primary';
                break;
            case 'scheduled':
                $typebadge = 'info';
                break;
            case 'real_time':
                $typebadge = 'warning';
                break;
            case 'end_of_course':
                $typebadge = 'success';
                break;
        }
        echo html_writer::tag(
            'span',
            ucfirst(str_replace('_', ' ', $report->report_type)),
            ['class' => "badge badge-{$typebadge}"]
        );
        echo html_writer::end_tag('td');

        // Students.
        echo html_writer::tag('td', get_string('n_students', 'local_savian_ai', $report->student_count));

        // Status - check if we have insights or still processing.
        echo html_writer::start_tag('td');
        $statusclass = '';
        $statusicon = '';
        $statustext = '';
        $isprocessing = false;

        if ($report->status == 'sent') {
            // Check if we actually have insights.
            $response = !empty($report->api_response) ? json_decode($report->api_response) : null;

            if ($response && isset($response->insights)) {
                // Completed with insights.
                $statusclass = 'success';
                $statusicon = '✓';
                $statustext = get_string('status_completed', 'local_savian_ai');
            } else {
                // Sent but still processing (async).
                $statusclass = 'info';
                $statusicon = '⟳';
                $statustext = get_string('status_processing', 'local_savian_ai');
                $isprocessing = true;
            }
        } else {
            switch ($report->status) {
                case 'sending':
                    $statusclass = 'info';
                    $statusicon = '⟳';
                    $statustext = get_string('report_sending', 'local_savian_ai');
                    $isprocessing = true;
                    break;
                case 'pending':
                    $statusclass = 'warning';
                    $statusicon = '⏱';
                    $statustext = get_string('report_pending', 'local_savian_ai');
                    $isprocessing = true;
                    break;
                case 'failed':
                    $statusclass = 'danger';
                    $statusicon = '✗';
                    $statustext = get_string('report_failed_status', 'local_savian_ai');
                    break;
                default:
                    $statusclass = 'secondary';
                    $statusicon = '?';
                    $statustext = ucfirst($report->status);
            }
        }

        echo html_writer::tag(
            'span',
            $statusicon . ' ' . $statustext,
            ['class' => "badge badge-{$statusclass}", 'id' => "status-badge-{$report->id}"]
        );
        echo html_writer::end_tag('td');

        // Actions.
        echo html_writer::start_tag('td');

        if ($report->status == 'sent' && !empty($report->api_response)) {
            $response = json_decode($report->api_response);
            if ($response && isset($response->insights)) {
                // View insights button.
                echo html_writer::start_tag(
                    'button',
                    [
                        'class' => 'btn btn-sm btn-outline-primary mr-1',
                        'onclick' => "toggleInsights('insights-{$report->id}')",
                    ]
                );
                echo get_string('view_conversation', 'local_savian_ai');
                echo html_writer::end_tag('button');

                // CSV export button.
                echo html_writer::link(
                    new moodle_url('/local/savian_ai/export_analytics_csv.php', ['reportid' => $report->id]),
                    '📥',
                    ['class' => 'btn btn-sm btn-outline-success', 'title' => 'Export CSV']
                );
            }
        } else if ($report->status == 'failed') {
            // Show error.
            echo html_writer::tag('small', substr($report->error_message, 0, 50) . '...', ['class' => 'text-danger']);
        }

        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');

        // Insights row (hidden by default).
        if ($report->status == 'sent' && !empty($report->api_response)) {
            $response = json_decode($report->api_response);
            if ($response && isset($response->insights)) {
                echo html_writer::start_tag(
                    'tr',
                    [
                        'id' => "insights-{$report->id}",
                        'style' => 'display: none;',
                    ]
                );
                echo html_writer::start_tag('td', ['colspan' => '5']);
                echo html_writer::start_div('p-4 bg-white border');

                $insights = $response->insights;

                // Get enrolled students for reverse lookup.
                $enrolledstudents = $DB->get_records_sql(
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

                $studentids = array_keys($enrolledstudents);

                // Create anonymizer for reverse lookup.
                $anonymizer = new \local_savian_ai\analytics\anonymizer();

                // Report metadata header.
                echo html_writer::start_div('mb-4 pb-3 border-bottom');
                echo html_writer::tag('h4', get_string('detailed_analytics_report', 'local_savian_ai'));
                echo html_writer::tag(
                    'p',
                    get_string('report_metadata_info', 'local_savian_ai', (object)[
                        'id' => $response->report_id ?? $report->id,
                        'date' => userdate($report->timecreated, '%d %B %Y at %H:%M'),
                        'students' => $report->student_count,
                    ]),
                    ['class' => 'text-muted small mb-0']
                );
                echo html_writer::end_div();

                // At-Risk Students Section.
                if (isset($insights->at_risk_students) && !empty($insights->at_risk_students)) {
                    $atriskcount = count($insights->at_risk_students);
                    echo html_writer::tag(
                        'h5',
                        get_string('at_risk_students_count', 'local_savian_ai', $atriskcount),
                        ['class' => 'text-danger mb-3']
                    );

                    // Show first 5 at-risk students, or all if 5 or fewer.
                    $studentstoshow = array_slice($insights->at_risk_students, 0, 5);
                    $remainingcount = count($insights->at_risk_students) - count($studentstoshow);

                    foreach ($studentstoshow as $student) {
                        // Reverse lookup to find actual student.
                        $userid = $anonymizer->reverse_lookup($student->anon_id, $studentids);
                        $user = $userid ? $enrolledstudents[$userid] : null;

                        echo html_writer::start_div('card mb-2 border-danger');
                        echo html_writer::start_div('card-body p-2');

                        // Risk badge.
                        $badgeclass = $student->risk_level == 'high'
                            ? 'danger'
                            : ($student->risk_level == 'medium' ? 'warning' : 'info');
                        echo html_writer::tag(
                            'span',
                            get_string('risk_label', 'local_savian_ai', strtoupper($student->risk_level)),
                            ['class' => "badge badge-{$badgeclass} float-right"]
                        );

                        // Show actual student name if found.
                        if ($user) {
                            $studentname = fullname($user);
                            $profileurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $courseid]);

                            echo html_writer::link(
                                $profileurl,
                                $studentname,
                                ['class' => 'font-weight-bold small', 'target' => '_blank']
                            );
                            echo html_writer::empty_tag('br');
                            echo html_writer::tag(
                                'small',
                                $user->email,
                                ['class' => 'text-muted']
                            );
                            $riskscorepct = round($student->risk_score * 100);
                            echo html_writer::tag(
                                'span',
                                ' | ' . get_string('risk_score_pct', 'local_savian_ai', $riskscorepct),
                                ['class' => 'text-muted small']
                            );
                        } else {
                            // Fallback if reverse lookup fails.
                            $anonlabel = substr($student->anon_id, 0, 12);
                            echo html_writer::tag(
                                'strong',
                                get_string('student_anon', 'local_savian_ai', $anonlabel),
                                ['class' => 'small']
                            );
                            $riskscorepct = round($student->risk_score * 100);
                            echo html_writer::tag(
                                'span',
                                get_string('risk_score_pct', 'local_savian_ai', $riskscorepct),
                                ['class' => 'text-muted small']
                            );
                        }

                        // Risk factors (show first 3).
                        if (!empty($student->risk_factors)) {
                            echo html_writer::start_tag('ul', ['class' => 'small mb-1 mt-1']);
                            $factorstoshow = array_slice($student->risk_factors, 0, 3);
                            foreach ($factorstoshow as $factor) {
                                echo html_writer::tag('li', $factor, ['class' => 'text-danger']);
                            }
                            if (count($student->risk_factors) > 3) {
                                $morefactors = count($student->risk_factors) - 3;
                                echo html_writer::tag(
                                    'li',
                                    get_string('more_factors', 'local_savian_ai', $morefactors),
                                    ['class' => 'text-muted']
                                );
                            }
                            echo html_writer::end_tag('ul');
                        }

                        echo html_writer::end_div();
                        echo html_writer::end_div();
                    }

                    if ($remainingcount > 0) {
                        echo html_writer::div(
                            get_string('more_at_risk_students', 'local_savian_ai', $remainingcount),
                            'alert alert-info small mb-3'
                        );
                    }
                } else {
                    echo html_writer::tag('p', get_string('no_at_risk_students', 'local_savian_ai'), ['class' => 'text-success']);
                }

                // Course Recommendations.
                if (isset($insights->course_recommendations) && !empty($insights->course_recommendations)) {
                    $reccount = count($insights->course_recommendations);
                    echo html_writer::tag(
                        'h5',
                        get_string('course_recommendations', 'local_savian_ai') . ' (' . $reccount . ')',
                        ['class' => 'text-info mt-4 mb-3']
                    );
                    echo html_writer::start_tag('ol', ['class' => 'small']);
                    $recstoshow = array_slice($insights->course_recommendations, 0, 6);
                    foreach ($recstoshow as $rec) {
                        echo html_writer::tag('li', $rec, ['class' => 'mb-2']);
                    }
                    if (count($insights->course_recommendations) > 6) {
                        $morerecs = count($insights->course_recommendations) - 6;
                        echo html_writer::tag(
                            'li',
                            get_string('more_recommendations', 'local_savian_ai', $morerecs),
                            ['class' => 'text-muted']
                        );
                    }
                    echo html_writer::end_tag('ol');
                }

                // Engagement Insights.
                if (isset($insights->engagement_insights)) {
                    $engagement = $insights->engagement_insights;
                    echo html_writer::tag('h5', get_string('engagement_insights', 'local_savian_ai'), ['class' => 'text-primary mt-4 mb-3']);

                    echo html_writer::start_div('row');
                    if (isset($engagement->average_engagement_score)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        $avgengagement = round($engagement->average_engagement_score * 100) . '%';
                        echo html_writer::tag(
                            'div',
                            $avgengagement,
                            ['class' => 'h4 text-primary']
                        );
                        echo html_writer::tag('small', get_string('avg_engagement_label', 'local_savian_ai'), ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    if (isset($engagement->low_engagement_count)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        echo html_writer::tag('div', $engagement->low_engagement_count, ['class' => 'h4 text-warning']);
                        echo html_writer::tag('small', get_string('low_engagement', 'local_savian_ai'), ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    if (isset($engagement->peak_activity_days)) {
                        echo html_writer::start_div('col-md-4 text-center mb-2');
                        $peakdays = implode(', ', $engagement->peak_activity_days);
                        echo html_writer::tag(
                            'div',
                            $peakdays,
                            ['class' => 'small font-weight-bold']
                        );
                        echo html_writer::tag('small', get_string('peak_days_label', 'local_savian_ai'), ['class' => 'text-muted']);
                        echo html_writer::end_div();
                    }
                    echo html_writer::end_div();
                }

                // Action buttons.
                echo html_writer::start_div('text-center mt-3 pt-3 border-top');
                echo html_writer::link(
                    new moodle_url('/local/savian_ai/export_analytics_csv.php', ['reportid' => $report->id]),
                    get_string('export_full_report_csv', 'local_savian_ai'),
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

// Auto-refresh for processing reports.
$hasprocessing = false;
foreach ($reports as $report) {
    $response = !empty($report->api_response) ? json_decode($report->api_response) : null;
    if ($report->status == 'sent' && (!$response || !isset($response->insights))) {
        $hasprocessing = true;
        break;
    }
    if (in_array($report->status, ['sending', 'pending'])) {
        $hasprocessing = true;
        break;
    }
}

if ($hasprocessing) {
    $PAGE->requires->js_amd_inline("
        // Auto-refresh every 5 seconds if reports are processing
        setTimeout(function() {
            console.log('Auto-refreshing to check report status...');
            window.location.reload();
        }, 5000);
    ");
}

// Scroll to top button (to access generate form).
if ($action !== 'poll') {
    echo html_writer::start_div('text-center mt-4');
    echo html_writer::tag(
        'button',
        get_string('generate_new_report', 'local_savian_ai'),
        [
            'class' => 'btn btn-outline-primary',
            'onclick' => 'window.scrollTo({top: 0, behavior: \'smooth\'});',
        ]
    );
    echo html_writer::end_div();
}

// Back link.
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        get_string('back_to_dashboard', 'local_savian_ai'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-4'
);

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
