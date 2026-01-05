<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title('Generate Course Content');
$PAGE->set_heading($course->fullname);

$client = new \local_savian_ai\api\client();
$course_builder = new \local_savian_ai\content\course_builder();

// Handle create action
if ($action === 'create' && confirm_sesskey() && !empty($SESSION->savian_ai_course_structure)) {
    $course_structure = json_decode($SESSION->savian_ai_course_structure);

    $results = $course_builder->add_content_to_course($courseid, $course_structure);

    // Log the course content generation
    $log = new stdClass();
    $log->request_id = $SESSION->savian_ai_pending_request ?? ('local_' . time());
    $log->generation_type = 'course_content';
    $log->course_id = $courseid;
    $log->user_id = $USER->id;
    $log->questions_count = 0; // Not questions, but course content
    $log->status = 'completed';
    $log->response_data = json_encode($results);
    $log->timecreated = time();
    $log->timemodified = time();
    $DB->insert_record('local_savian_generations', $log);

    // Store generation data for knowledge feedback loop (v2.2)
    $SESSION->savian_kb_save_data = [
        'course_structure' => $SESSION->savian_ai_course_structure,
        'course_title' => $course->fullname,
        'course_id' => $courseid,
        'request_id' => $SESSION->savian_ai_pending_request ?? null,
        'results' => $results
    ];

    unset($SESSION->savian_ai_course_structure);
    unset($SESSION->savian_ai_pending_request);
    unset($SESSION->savian_ai_sources);

    // Redirect to success page with save option
    redirect(new moodle_url('/local/savian_ai/create_course.php', [
        'courseid' => $courseid,
        'action' => 'success'
    ]));
}

// Handle polling
if ($action === 'poll' && !empty($SESSION->savian_ai_pending_request)) {
    $request_id = $SESSION->savian_ai_pending_request;
    $status_response = $client->get_generation_status($request_id);

    if ($status_response->http_code === 200) {
        if (isset($status_response->status) && $status_response->status === 'completed') {
            if (isset($status_response->course_structure)) {
                $SESSION->savian_ai_course_structure = json_encode($status_response->course_structure);
                $SESSION->savian_ai_sources = isset($status_response->sources) ? json_encode($status_response->sources) : null;
                unset($SESSION->savian_ai_pending_request);

                redirect(new moodle_url('/local/savian_ai/create_course.php', [
                    'courseid' => $courseid,
                    'action' => 'preview',
                ]), 'Course structure generated!', null, 'success');
            }
        } else if (isset($status_response->status) && $status_response->status === 'failed') {
            $error = $status_response->error ?? 'Generation failed';
            unset($SESSION->savian_ai_pending_request);
            redirect(new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
                     'Generation failed: ' . $error, null, 'error');
        }
    }
}

// Handle form submission
if (data_submitted() && confirm_sesskey()) {
    $doc_ids = optional_param_array('document_ids', [], PARAM_INT);
    $title = $course->fullname;  // Use existing course name
    $description = optional_param('description', '', PARAM_TEXT);
    $target_audience = optional_param('target_audience', '', PARAM_TEXT);
    $duration = optional_param('duration_weeks', 4, PARAM_INT);
    $content_types = optional_param_array('content_types', ['sections', 'pages', 'quiz_questions'], PARAM_TEXT);

    // ADDIE v2.0 parameters
    $age_group = optional_param('age_group', 'undergrad', PARAM_ALPHA);
    $industry = optional_param('industry', 'general', PARAM_ALPHA);
    $prior_knowledge = optional_param('prior_knowledge_level', 'beginner', PARAM_ALPHA);

    // Validate required fields
    if (empty($doc_ids)) {
        redirect(new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
                 get_string('no_documents_selected', 'local_savian_ai'), null, 'error');
    }

    if (!empty($doc_ids)) {
        // RAG-based generation with ADDIE v2.0
        $response = $client->generate_course_from_documents($doc_ids, $title, [
            'course_id' => $courseid,
            'description' => $description,
            'target_audience' => $target_audience,
            'duration_weeks' => $duration,
            'age_group' => $age_group,              // ADDIE v2.0
            'industry' => $industry,                // ADDIE v2.0
            'prior_knowledge_level' => $prior_knowledge, // ADDIE v2.0
            'content_types' => $content_types,
            'language' => 'en'
        ]);

        if ($response->http_code === 200 && isset($response->success) && $response->success) {
            if (isset($response->request_id) && isset($response->status) && $response->status === 'pending') {
                $SESSION->savian_ai_pending_request = $response->request_id;
                $SESSION->savian_ai_duration = $duration; // For estimated time display
                redirect(new moodle_url('/local/savian_ai/create_course.php', [
                    'courseid' => $courseid,
                    'action' => 'poll',
                ]), 'Generating course content...', null, 'info');
            }
        } else {
            // Error or synchronous response
            $error = $response->error ?? $response->message ?? 'Unknown error (HTTP: ' . $response->http_code . ')';
            redirect(new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
                     'Generation failed: ' . $error, null, 'error');
        }
    }
}

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header('Generate Course Content', 'Create course sections, pages, and quizzes from documents');

if ($action === 'poll' && !empty($SESSION->savian_ai_pending_request)) {
    // Show progress bar with real-time updates
    echo $OUTPUT->heading(get_string('generating_course_content', 'local_savian_ai'), 3);

    // Progress card
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body');

    // Progress title (dynamic)
    echo html_writer::tag('h5', get_string('progress_unknown', 'local_savian_ai'), ['id' => 'progress-title', 'class' => 'mb-3']);

    // Progress bar (30px height)
    echo html_writer::start_div('progress mb-3', ['style' => 'height: 30px;']);
    echo html_writer::div('0%', 'progress-bar bg-primary progress-bar-striped progress-bar-animated', [
        'id' => 'progress-bar',
        'role' => 'progressbar',
        'style' => 'width: 0%',
        'aria-valuenow' => '0',
        'aria-valuemin' => '0',
        'aria-valuemax' => '100'
    ]);
    echo html_writer::end_div();

    // Progress details (current section)
    echo html_writer::tag('p', '', ['id' => 'progress-details', 'class' => 'text-muted mb-2']);

    // Estimated time
    $duration = $SESSION->savian_ai_duration ?? 4;
    if ($duration <= 4) {
        $est_time = get_string('estimated_time_4weeks', 'local_savian_ai');
    } else if ($duration <= 8) {
        $est_time = get_string('estimated_time_8weeks', 'local_savian_ai');
    } else {
        $est_time = get_string('estimated_time_12weeks', 'local_savian_ai');
    }
    echo html_writer::tag('p', $est_time, ['class' => 'small text-muted']);

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Cancel button
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/savian_ai/create_course.php', [
                'courseid' => $courseid,
                'action' => 'cancel'
            ]),
            get_string('cancel_generation', 'local_savian_ai'),
            ['class' => 'btn btn-outline-danger']
        ),
        'text-center'
    );

    // AJAX polling with progress updates
    $request_id = $SESSION->savian_ai_pending_request;
    $PAGE->requires->js_amd_inline("
require(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    var requestId = '{$request_id}';
    var pollInterval = 2500; // 2.5 seconds
    var courseid = {$courseid};

    function updateProgress() {
        Ajax.call([{
            methodname: 'local_savian_ai_get_generation_status',
            args: {requestid: requestId}
        }])[0].done(function(response) {
            if (response.success) {
                // Update progress bar
                $('#progress-bar')
                    .css('width', response.progress + '%')
                    .attr('aria-valuenow', response.progress)
                    .text(response.progress + '%');

                // Update stage text
                var stageText = getStageText(response.details.stage, response.progress);
                $('#progress-title').text(stageText);

                // Update details (current section if available)
                if (response.details.current_section) {
                    $('#progress-details').text(response.details.current_section);
                } else {
                    $('#progress-details').text('');
                }

                // Check if completed
                if (response.status === 'completed') {
                    window.location.href = '{$CFG->wwwroot}/local/savian_ai/create_course.php?courseid=' + courseid + '&action=preview';
                } else if (response.status === 'failed') {
                    Notification.alert('Generation Failed', response.error || 'Unknown error', 'OK');
                    window.location.href = '{$CFG->wwwroot}/local/savian_ai/create_course.php?courseid=' + courseid;
                } else {
                    // Continue polling
                    setTimeout(updateProgress, pollInterval);
                }
            } else {
                // Error in response
                Notification.alert('Error', response.error || 'Failed to get status', 'OK');
                setTimeout(updateProgress, pollInterval * 2); // Retry with longer interval
            }
        }).fail(function(error) {
            console.error('AJAX error:', error);
            setTimeout(updateProgress, pollInterval * 2); // Retry
        });
    }

    function getStageText(stage, progress) {
        var stages = {
            'pending': 'Queued for processing...',
            // ADDIE v2.0 stages
            'addie_analysis': '" . get_string('progress_addie_analysis', 'local_savian_ai') . "',
            'addie_design_outline': '" . get_string('progress_addie_design_outline', 'local_savian_ai') . "',
            'addie_design_completed': '" . get_string('progress_addie_design_completed', 'local_savian_ai') . "',
            'addie_development_completed': '" . get_string('progress_addie_development_completed', 'local_savian_ai') . "',
            'addie_implementation': '" . get_string('progress_addie_implementation', 'local_savian_ai') . "',
            'addie_evaluation': '" . get_string('progress_addie_evaluation', 'local_savian_ai') . "',
            'addie_completed': '" . get_string('progress_addie_completed', 'local_savian_ai') . "',
            'completed': '" . get_string('progress_complete', 'local_savian_ai') . "'
        };

        // Handle addie_dev_section_N pattern (ADDIE v2.0)
        if (stage && stage.indexOf('addie_dev_section_') === 0) {
            var sectionNum = stage.replace('addie_dev_section_', '');
            return 'Creating Week ' + sectionNum + ' content...';
        }

        // Handle old generating_section pattern (backward compatibility)
        if (stage && stage.indexOf('generating_section_') === 0) {
            var sectionNum = stage.replace('generating_section_', '');
            return 'Creating section ' + sectionNum + ' content...';
        }

        return stages[stage] || '" . get_string('progress_unknown', 'local_savian_ai') . " (' + progress + '%)';
    }

    // Start polling after 1 second
    setTimeout(updateProgress, 1000);
});
");

} else if ($action === 'preview' && !empty($SESSION->savian_ai_course_structure)) {
    // Show enhanced preview
    $structure = json_decode($SESSION->savian_ai_course_structure);

    echo html_writer::tag('h3', get_string('preview_course_structure', 'local_savian_ai') . ' (' . count($structure->sections) . ' sections)', ['class' => 'mt-4']);

    // ADDIE v2.0: AI Transparency Notice
    if (isset($structure->ai_transparency_notice)) {
        echo $structure->ai_transparency_notice;
    }

    // ADDIE v2.0: Quality Matters Alignment
    if (isset($structure->quality_markers->qm_alignment)) {
        $qm = $structure->quality_markers->qm_alignment;
        $score_class = $qm->qm_certified_ready ? 'success' : 'warning';

        echo html_writer::start_div('card mb-3 border-' . $score_class);
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📊 ' . get_string('qm_alignment', 'local_savian_ai'), ['class' => 'card-title']);
        echo html_writer::tag('p', get_string('qm_score', 'local_savian_ai') . ': ' .
             html_writer::tag('strong', $qm->total_score . '%') .
             ' (' . $qm->standards_met . '/' . $qm->standards_total . ' standards met)');

        if ($qm->qm_certified_ready) {
            echo html_writer::div('✅ ' . get_string('qm_certified_ready', 'local_savian_ai'), 'alert alert-success mb-2');
        } else {
            echo html_writer::div('⚠️ ' . get_string('qm_below_threshold', 'local_savian_ai'), 'alert alert-warning mb-2');
        }

        if (isset($qm->recommendations) && !empty($qm->recommendations)) {
            echo html_writer::tag('strong', get_string('qm_recommendations', 'local_savian_ai') . ':', ['class' => 'd-block mt-2 mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-0']);
            foreach ($qm->recommendations as $rec) {
                echo html_writer::tag('li', s($rec));
            }
            echo html_writer::end_tag('ul');
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // ADDIE v2.1: Quality Report (NEW)
    if (isset($structure->quality_report)) {
        $report = $structure->quality_report;

        echo html_writer::start_div('card mb-3 border-success');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📊 Course Quality Report', ['class' => 'card-title text-success']);

        // Scores in row
        echo html_writer::start_div('row text-center mb-3');

        // Overall Score
        $overall_score = $report->overall_score ?? 0;
        $score_color = $overall_score >= 80 ? 'success' : ($overall_score >= 60 ? 'warning' : 'danger');
        echo html_writer::div(
            html_writer::tag('div', $overall_score . '/100', ['class' => "h2 mb-0 text-{$score_color}"]) .
            html_writer::tag('div', 'Overall Score', ['class' => 'small text-muted']),
            'col-md-3'
        );

        // Source Coverage
        if (isset($report->source_coverage_average)) {
            $coverage_pct = round($report->source_coverage_average * 100);
            $coverage_color = $coverage_pct >= 80 ? 'success' : ($coverage_pct >= 60 ? 'info' : 'warning');
            echo html_writer::div(
                html_writer::tag('div', $coverage_pct . '%', ['class' => "h2 mb-0 text-{$coverage_color}"]) .
                html_writer::tag('div', 'Source Coverage', ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        // Learning Depth
        if (isset($report->learning_depth_average)) {
            $depth_score = $report->learning_depth_average;
            $depth_color = $depth_score >= 75 ? 'success' : ($depth_score >= 50 ? 'primary' : 'secondary');
            echo html_writer::div(
                html_writer::tag('div', $depth_score . '/100', ['class' => "h2 mb-0 text-{$depth_color}"]) .
                html_writer::tag('div', 'Learning Depth', ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        // Hallucination Risk (v2.1)
        if (isset($report->hallucination_risk)) {
            $risk = strtolower($report->hallucination_risk);
            $risk_color = $risk === 'low' ? 'success' : ($risk === 'medium' ? 'warning' : 'danger');
            $risk_icon = $risk === 'low' ? '✓' : ($risk === 'medium' ? '⚠️' : '❗');
            echo html_writer::div(
                html_writer::tag('div', $risk_icon, ['class' => "h2 mb-0 text-{$risk_color}"]) .
                html_writer::tag('div', ucfirst($risk) . ' Risk', ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        echo html_writer::end_div(); // row

        // Strengths (v2.1)
        if (isset($report->instructor_summary->strengths) && !empty($report->instructor_summary->strengths)) {
            echo html_writer::start_div('alert alert-success mb-2');
            echo html_writer::tag('strong', '✅ Strengths:', ['class' => 'd-block mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'mb-0 small']);
            foreach ($report->instructor_summary->strengths as $strength) {
                echo html_writer::tag('li', s($strength));
            }
            echo html_writer::end_tag('ul');
            echo html_writer::end_div();
        }

        // Priority Reviews
        if (isset($report->instructor_summary->priority_reviews) && !empty($report->instructor_summary->priority_reviews)) {
            echo html_writer::tag('strong', '📝 Focus Your Review On:', ['class' => 'd-block mb-2']);
            echo html_writer::start_tag('ul', ['class' => 'mb-2']);
            foreach ($report->instructor_summary->priority_reviews as $item) {
                echo html_writer::tag('li', s($item));
            }
            echo html_writer::end_tag('ul');
        }

        // Recommended review time
        if (isset($report->instructor_summary->recommended_review_time)) {
            echo html_writer::div(
                '⏱️ <em>Estimated review time: ' . s($report->instructor_summary->recommended_review_time) . '</em>',
                'text-muted small'
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // ADDIE v2.0: Pedagogical Metadata (Updated field names)
    if (isset($structure->pedagogical_metadata)) {
        $meta = $structure->pedagogical_metadata;

        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📚 ' . get_string('pedagogical_metadata', 'local_savian_ai'), ['class' => 'card-title']);

        echo html_writer::start_tag('dl', ['class' => 'row mb-0 small']);

        // Designed For (replaces age_group_name)
        if (isset($meta->designed_for)) {
            echo html_writer::tag('dt', 'Designed For:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->designed_for), ['class' => 'col-sm-7']);
        }

        // Subject Area (replaces industry_name)
        if (isset($meta->subject_area)) {
            echo html_writer::tag('dt', 'Subject Area:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->subject_area), ['class' => 'col-sm-7']);
        }

        // Content Level (replaces reading_level)
        if (isset($meta->content_level)) {
            echo html_writer::tag('dt', 'Content Level:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->content_level), ['class' => 'col-sm-7']);
        }

        // Instructional Approach (replaces pedagogy_approach)
        if (isset($meta->instructional_approach)) {
            echo html_writer::tag('dt', 'Instructional Approach:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->instructional_approach), ['class' => 'col-sm-7']);
        }

        // Thinking Skills (NEW field)
        if (isset($meta->thinking_skills)) {
            echo html_writer::tag('dt', 'Thinking Skills:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->thinking_skills), ['class' => 'col-sm-7']);
        }

        // Generation Method (still available)
        if (isset($meta->generation_method)) {
            echo html_writer::tag('dt', 'Generation Method:', ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->generation_method), ['class' => 'col-sm-7']);
        }

        echo html_writer::end_tag('dl');

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Show sources if available
    if (!empty($SESSION->savian_ai_sources)) {
        $sources = json_decode($SESSION->savian_ai_sources);
        echo html_writer::start_div('card mb-3 savian-accent-card');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('strong', '📚 ' . get_string('based_on_documents', 'local_savian_ai') . ': ');
        $source_names = array_map(function($s) {
            $title = $s->title ?? 'Unknown';
            $chunks = $s->chunks_used ?? 0;
            return "{$title} (" . get_string('chunks_used', 'local_savian_ai', $chunks) . ")";
        }, $sources);
        echo html_writer::tag('div', implode(', ', $source_names), ['class' => 'text-muted mt-1']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Enhanced summary statistics card
    $total_pages = 0;
    $total_activities = 0;
    $total_discussions = 0;
    $total_quizzes = 0;
    $total_assignments = 0;

    foreach ($structure->sections as $section) {
        if (isset($section->content)) {
            foreach ($section->content as $item) {
                switch ($item->type) {
                    case 'page': $total_pages++; break;
                    case 'activity': $total_activities++; break;
                    case 'discussion': $total_discussions++; break;
                    case 'quiz': $total_quizzes++; break;
                    case 'assignment': $total_assignments++; break;
                }
            }
        }
    }

    echo html_writer::start_div('card mb-4 savian-accent-card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('content_summary', 'local_savian_ai'), ['class' => 'card-title mb-3']);
    echo html_writer::start_div('row text-center');

    // Sections
    echo html_writer::div(
        html_writer::tag('div', count($structure->sections), ['class' => 'h3 mb-0 savian-text-primary']) .
        html_writer::tag('div', get_string('summary_sections', 'local_savian_ai'), ['class' => 'text-muted small']),
        'col-md-2'
    );

    // Pages
    echo html_writer::div(
        html_writer::tag('div', $total_pages, ['class' => 'h3 mb-0 savian-text-primary']) .
        html_writer::tag('div', get_string('summary_pages', 'local_savian_ai'), ['class' => 'text-muted small']),
        'col-md-2'
    );

    // Activities
    if ($total_activities > 0) {
        echo html_writer::div(
            html_writer::tag('div', $total_activities, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_activities', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Discussions
    if ($total_discussions > 0) {
        echo html_writer::div(
            html_writer::tag('div', $total_discussions, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_discussions', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Quizzes
    if ($total_quizzes > 0) {
        echo html_writer::div(
            html_writer::tag('div', $total_quizzes, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_quizzes', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Assignments
    if ($total_assignments > 0) {
        echo html_writer::div(
            html_writer::tag('div', $total_assignments, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_assignments', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Expand/Collapse controls
    echo html_writer::start_div('mb-3 text-right');
    echo html_writer::tag('button', get_string('expand_all', 'local_savian_ai'), [
        'id' => 'expand-all',
        'class' => 'btn btn-sm btn-outline-secondary mr-2'
    ]);
    echo html_writer::tag('button', get_string('collapse_all', 'local_savian_ai'), [
        'id' => 'collapse-all',
        'class' => 'btn btn-sm btn-outline-secondary'
    ]);
    echo html_writer::end_div();

    // Helper function for content icons
    $get_icon = function($type) {
        $icons = [
            'page' => '📄',
            'activity' => '🎯',
            'discussion' => '💬',
            'formative' => '✓',    // ADDIE v2.0
            'quiz' => '❓',
            'assignment' => '📝'
        ];
        return $icons[$type] ?? '📌';
    };

    // Show structure with enhanced features
    foreach ($structure->sections as $idx => $section) {
        $section_id = 'section_' . $idx;
        echo html_writer::start_div('card mb-3');

        // Card header with checkbox and toggle
        echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');

        echo html_writer::start_div('d-flex align-items-center flex-wrap');
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'include_section_' . $idx,
            'checked' => 'checked',
            'class' => 'mr-2',
            'title' => get_string('include_item', 'local_savian_ai')
        ]);
        echo html_writer::tag('strong', "📖 Section " . ($idx + 1) . ": " . s($section->title ?? ''));

        // ADDIE v2.1: Section coverage badge
        if (isset($section->coverage_info)) {
            $coverage = $section->coverage_info;
            $score_pct = round(($coverage->coverage_score ?? 0) * 100);
            $status = $coverage->status ?? 'unknown';

            if ($status === 'excellent' || $score_pct >= 80) {
                $badge_class = 'success';
                $badge_text = "✅ {$score_pct}%";
            } elseif ($status === 'good' || $score_pct >= 60) {
                $badge_class = 'info';
                $badge_text = "📊 {$score_pct}%";
            } else {
                $badge_class = 'warning';
                $badge_text = "⚠️ {$score_pct}%";
            }

            echo html_writer::tag('span', $badge_text, [
                'class' => "badge badge-{$badge_class} ml-2",
                'title' => 'Source coverage for this section'
            ]);
        }

        // ADDIE v2.1: Learning depth badge
        if (isset($section->learning_depth)) {
            $depth = $section->learning_depth;
            $depth_level = $depth->depth_level ?? 'moderate';
            $depth_score = $depth->depth_score ?? 0;

            $depth_icon = $depth_level === 'deep' ? '🎯' : ($depth_level === 'moderate' ? '📚' : '📖');
            echo html_writer::tag('span', "{$depth_icon} {$depth_score}", [
                'class' => 'badge badge-primary ml-1',
                'title' => "Learning depth: {$depth_level}"
            ]);
        }

        echo html_writer::end_div();

        // Toggle button
        echo html_writer::tag('button', '<i class="fa fa-chevron-down"></i>', [
            'class' => 'btn btn-sm btn-link text-dark',
            'data-toggle' => 'collapse',
            'data-target' => '#' . $section_id,
            'aria-expanded' => 'true',
            'aria-controls' => $section_id
        ]);

        echo html_writer::end_div();

        // Collapsible body
        echo html_writer::start_div('collapse show', ['id' => $section_id]);
        echo html_writer::start_div('card-body');

        // Section summary
        if (isset($section->summary)) {
            echo html_writer::tag('div', format_text($section->summary, FORMAT_HTML), ['class' => 'text-muted mb-3 small']);
        }

        // ADDIE v2.0: Prerequisites
        if (isset($section->prerequisites) && !empty($section->prerequisites)) {
            echo html_writer::tag('strong', '⚠️ ' . get_string('prerequisites', 'local_savian_ai') . ':', ['class' => 'd-block mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-2']);
            foreach ($section->prerequisites as $prereq) {
                echo html_writer::tag('li', s($prereq));
            }
            echo html_writer::end_tag('ul');
        }

        // ADDIE v2.0: Estimated hours
        if (isset($section->estimated_hours)) {
            echo html_writer::tag('p', '⏱️ ' . get_string('estimated_hours', 'local_savian_ai', $section->estimated_hours),
                ['class' => 'small text-muted mb-2']);
        }

        // ADDIE v2.0: QM alignment notes
        if (isset($section->qm_alignment_notes)) {
            echo html_writer::div(
                '<small><strong>QM:</strong> ' . s($section->qm_alignment_notes) . '</small>',
                'text-muted mb-2'
            );
        }

        // ADDIE v2.0: Source documents used
        if (isset($section->source_documents_used) && !empty($section->source_documents_used)) {
            echo html_writer::div(
                '<small><strong>📚 Sources:</strong> ' . implode(', ', array_map('s', $section->source_documents_used)) . '</small>',
                'text-muted mb-2'
            );
        }

        // Learning objectives
        if (isset($section->learning_objectives) && !empty($section->learning_objectives)) {
            echo html_writer::tag('strong', get_string('learning_objectives', 'local_savian_ai') . ':', ['class' => 'd-block mb-2']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
            foreach ($section->learning_objectives as $obj) {
                echo html_writer::tag('li', s($obj));
            }
            echo html_writer::end_tag('ul');
        }

        // Content items with checkboxes and edit buttons
        if (isset($section->content) && !empty($section->content)) {
            echo html_writer::div(get_string('section_content', 'local_savian_ai') . ':', 'font-weight-bold mb-2 mt-3');
            echo html_writer::start_div('list-group list-group-flush');

            foreach ($section->content as $item_idx => $item) {
                $icon = $get_icon($item->type);
                $type_label = ucfirst(str_replace('_', ' ', $item->type));
                $type_badge = html_writer::tag('span', $type_label, ['class' => 'badge badge-info badge-sm']);

                // Default titles for items without explicit titles
                $default_titles = [
                    'formative' => 'Self-Check Questions',
                    'page' => 'Content Page',
                    'activity' => 'Learning Activity',
                    'discussion' => 'Discussion Topic',
                    'quiz' => 'Section Quiz',
                    'assignment' => 'Assignment'
                ];
                $display_title = $item->title ?? ($default_titles[$item->type] ?? 'Untitled');

                echo html_writer::start_div('list-group-item d-flex justify-content-between align-items-center');

                // Checkbox + icon + title + badge + quality tag
                echo html_writer::start_div('d-flex align-items-center flex-wrap');
                echo html_writer::empty_tag('input', [
                    'type' => 'checkbox',
                    'name' => "include_item_{$idx}_{$item_idx}",
                    'checked' => 'checked',
                    'class' => 'mr-2',
                    'title' => get_string('include_item', 'local_savian_ai')
                ]);
                echo "{$icon} " . html_writer::tag('span', s($display_title), ['class' => 'mr-2']);
                echo $type_badge;

                // ADDIE v2.1: Quality tags for pages
                if ($item->type === 'page' && isset($item->quality_tags)) {
                    $tags = $item->quality_tags;
                    $confidence = $tags->source_confidence ?? 'medium';

                    if ($confidence === 'high') {
                        echo html_writer::tag('span', '✓ Verified', [
                            'class' => 'badge badge-success ml-2',
                            'title' => $tags->instructor_note ?? 'High confidence - well-grounded in sources'
                        ]);
                    } elseif ($confidence === 'medium') {
                        echo html_writer::tag('span', '⚠️ Review', [
                            'class' => 'badge badge-warning ml-2',
                            'title' => $tags->instructor_note ?? 'Medium confidence - recommended review'
                        ]);
                    } else {
                        echo html_writer::tag('span', '❗ Priority', [
                            'class' => 'badge badge-danger ml-2',
                            'title' => $tags->instructor_note ?? 'Low confidence - priority review needed'
                        ]);
                    }

                    // Supplemented content indicator
                    if (isset($tags->supplemented_content) && $tags->supplemented_content) {
                        echo html_writer::tag('span', 'ℹ️ Supplemented', [
                            'class' => 'badge badge-info ml-1',
                            'title' => 'Includes AI-supplemented content - verify against your context'
                        ]);
                    }
                }

                echo html_writer::end_div();

                // View and Edit buttons
                echo html_writer::start_div('btn-group btn-group-sm');
                echo html_writer::tag('button', '<i class="fa fa-eye"></i> View', [
                    'class' => 'btn btn-outline-info',
                    'data-action' => 'view-item',
                    'data-section' => $idx,
                    'data-item' => $item_idx,
                    'title' => 'View full content'
                ]);
                echo html_writer::tag('button', '<i class="fa fa-edit"></i> Edit', [
                    'class' => 'btn btn-outline-primary',
                    'data-action' => 'edit-item',
                    'data-section' => $idx,
                    'data-item' => $item_idx,
                    'title' => 'Edit before adding to course'
                ]);
                echo html_writer::end_div();

                echo html_writer::end_div();
            }

            echo html_writer::end_div();
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Action buttons
    echo html_writer::start_div('text-center mt-4');

    echo html_writer::link(
        new moodle_url('/local/savian_ai/create_course.php', [
            'courseid' => $courseid,
            'action' => 'create',
            'sesskey' => sesskey(),
        ]),
        get_string('add_to_this_course', 'local_savian_ai'),
        ['class' => 'btn btn-savian btn-lg mr-2']
    );

    echo html_writer::link(
        new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
        get_string('regenerate', 'local_savian_ai'),
        ['class' => 'btn btn-outline-secondary']
    );

    echo html_writer::end_div();

    // Store course structure in data attribute for JavaScript access
    echo html_writer::div('', '', [
        'id' => 'course-structure-data',
        'data-structure' => json_encode($structure),
        'style' => 'display:none;'
    ]);

    // Initialize view/edit functionality
    $PAGE->requires->js_call_amd('local_savian_ai/course_content_editor', 'init');

} else if ($action === 'success' && !empty($SESSION->savian_kb_save_data)) {
    // Success page with Knowledge Feedback Loop option (v2.2)
    $save_data = $SESSION->savian_kb_save_data;
    $results = $save_data['results'];

    // Build success message
    $parts = [];
    if ($results['sections_created'] > 0) $parts[] = "{$results['sections_created']} sections";
    if ($results['pages_created'] > 0) $parts[] = "{$results['pages_created']} pages";
    if (isset($results['activities_created']) && $results['activities_created'] > 0) {
        $parts[] = "{$results['activities_created']} activities";
    }
    if (isset($results['discussions_created']) && $results['discussions_created'] > 0) {
        $parts[] = "{$results['discussions_created']} discussions";
    }
    if (isset($results['formative_created']) && $results['formative_created'] > 0) {
        $parts[] = "{$results['formative_created']} self-checks";
    }
    if ($results['quizzes_created'] > 0) $parts[] = "{$results['quizzes_created']} quizzes";
    if ($results['assignments_created'] > 0) $parts[] = "{$results['assignments_created']} assignments";

    echo html_writer::start_div('alert alert-success', ['style' => 'border-left: 4px solid #28a745;']);
    echo html_writer::tag('h4', '✅ Course Created Successfully!');
    echo html_writer::tag('p', 'Your course has been imported with: ' . implode(', ', $parts));
    echo html_writer::end_div();

    // Knowledge Feedback Loop Prompt
    echo html_writer::start_div('card border-primary mt-4 mb-4');
    echo html_writer::start_div('card-body p-4');

    echo html_writer::tag('h5', '💡 Save to Knowledge Base', ['class' => 'card-title text-primary']);
    echo html_writer::tag('p', '<strong>Build your institutional knowledge base!</strong>', ['class' => 'mb-3']);

    echo html_writer::tag('p', 'By saving this approved course, you enable:');
    echo html_writer::start_tag('ul', ['class' => 'mb-3']);
    echo html_writer::tag('li', '✓ Future courses can build on this approved content');
    echo html_writer::tag('li', '✓ Students can chat with this course material');
    echo html_writer::tag('li', '✓ Reduces review time for similar courses');
    echo html_writer::tag('li', '✓ Preserves your teaching expertise');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('p', '<small class="text-muted">The course will be processed (chunked and embedded) and available in 2-3 minutes.</small>');

    // Buttons
    echo html_writer::start_div('mt-4');
    echo html_writer::link(
        new moodle_url('/local/savian_ai/save_to_knowledge_base.php', [
            'courseid' => $courseid,
            'sesskey' => sesskey()
        ]),
        '<i class="fa fa-save mr-2"></i>Save to Knowledge Base',
        ['class' => 'btn btn-primary btn-lg mr-2']
    );
    echo html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        'Skip and Go to Course',
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();

} else {
    // Show enhanced form with Moodle standards
    echo html_writer::div(
        '<strong>🚀 AI-Powered Course Generator</strong> - Create professional-grade course content using the ADDIE instructional design framework.',
        'alert alert-info mb-4'
    );

    // Filter documents: course-specific + global library only
    $sql = "SELECT savian_doc_id, title FROM {local_savian_documents}
            WHERE is_active = 1 AND status = 'completed'
              AND (course_id = ? OR course_id IS NULL OR course_id = 0)
            ORDER BY title ASC";
    $documents = $DB->get_records_sql_menu($sql, [$courseid]);

    if (empty($documents)) {
        echo $OUTPUT->notification('No completed documents available. Upload documents first.', 'warning');
    } else {
        echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        // FIELDSET 1: Basic Information
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '📚 Basic Information', ['class' => 'font-weight-bold text-primary']);

        // Display course name (not editable - use existing course)
        echo html_writer::start_div('alert alert-light border mb-3');
        echo html_writer::tag('strong', 'Target Course: ', ['class' => 'd-block mb-1']);
        echo html_writer::tag('div', '📖 ' . s($course->fullname), ['class' => 'h5 mb-0 text-primary']);
        echo html_writer::end_div();

        echo html_writer::div(
            html_writer::tag('label', 'Description: <span class="text-muted">(Optional)</span>', ['class' => 'font-weight-bold', 'for' => 'course-desc']) .
            html_writer::tag('textarea', '', [
                'id' => 'course-desc',
                'name' => 'description',
                'class' => 'form-control',
                'rows' => 2,
                'placeholder' => 'Optional: Add specific learning goals or scope details...'
            ]),
            'form-group'
        );

        echo html_writer::div(
            html_writer::tag('label', 'Additional Context: <span class="text-muted">(Optional)</span>', ['class' => 'font-weight-bold', 'for' => 'target-aud']) .
            html_writer::empty_tag('input', [
                'type' => 'text',
                'id' => 'target-aud',
                'name' => 'target_audience',
                'class' => 'form-control',
                'placeholder' => 'E.g., "First-year medical students" or "Entry-level developers"'
            ]) .
            html_writer::tag('small', 'Provides extra context beyond the structured Learner Profile below', ['class' => 'form-text text-muted']),
            'form-group'
        );

        echo html_writer::end_tag('fieldset');

        // FIELDSET 2: Learner Profile (ADDIE v2.0) - Improved Alignment
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '👥 Learner Profile', ['class' => 'font-weight-bold text-primary']);
        echo html_writer::div(
            '<small class="text-muted">These settings adapt content to your learners\' age, background, and industry context.</small>',
            'mb-3'
        );

        echo html_writer::start_div('row');

        // Age Group
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('age_group', 'local_savian_ai') . ': <span class="text-danger">*</span>',
            ['class' => 'font-weight-bold d-block', 'for' => 'age-group']);
        echo html_writer::select([
            'k5' => '📝 Elementary (K-5)',
            'middle' => '📚 Middle School (6-8)',
            'high' => '🎓 High School (9-12)',
            'undergrad' => '🎯 Undergraduate',
            'graduate' => '👨‍🎓 Graduate',
            'professional' => '💼 Professional'
        ], 'age_group', 'undergrad', false, [
            'class' => 'form-control',
            'id' => 'age-group',
            'required' => true
        ]);
        echo html_writer::tag('small', get_string('age_group_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Industry
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('industry', 'local_savian_ai') . ': <span class="text-danger">*</span>',
            ['class' => 'font-weight-bold d-block', 'for' => 'industry']);
        echo html_writer::select([
            'general' => '🎓 General Academic',
            'k12' => '🏫 K-12 Education',
            'higher_ed' => '🏛️ Higher Education',
            'healthcare' => '⚕️ Healthcare',
            'technology' => '💻 Technology/IT',
            'business' => '💼 Business',
            'corporate' => '🏢 Corporate Training'
        ], 'industry', 'general', false, [
            'class' => 'form-control',
            'id' => 'industry',
            'required' => true
        ]);
        echo html_writer::tag('small', get_string('industry_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Prior Knowledge
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('prior_knowledge', 'local_savian_ai') . ':',
            ['class' => 'font-weight-bold d-block', 'for' => 'prior-knowledge']);
        echo html_writer::select([
            'beginner' => '🌱 Beginner',
            'intermediate' => '📈 Intermediate',
            'advanced' => '⭐ Advanced'
        ], 'prior_knowledge_level', 'beginner', false, [
            'class' => 'form-control',
            'id' => 'prior-knowledge'
        ]);
        echo html_writer::tag('small', get_string('prior_knowledge_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::end_div(); // row
        echo html_writer::end_tag('fieldset');

        // FIELDSET 3: Source Documents & Duration
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '📄 Source Documents & Course Settings', ['class' => 'font-weight-bold text-primary']);

        echo html_writer::start_div('row');

        // Documents (left side - 8 columns)
        echo html_writer::start_div('col-md-8');
        echo html_writer::div(
            html_writer::tag('label', 'Select Documents: <span class="text-danger">*</span>', ['class' => 'font-weight-bold', 'for' => 'documents']) .
            html_writer::tag('small', ' (Hold Ctrl/Cmd to select multiple)', ['class' => 'text-muted ml-2']) .
            html_writer::select($documents, 'document_ids[]', null, false, [
                'multiple' => true,
                'size' => 5,
                'class' => 'form-control',
                'id' => 'documents',
                'required' => true,
                'aria-required' => 'true'
            ]) .
            html_writer::tag('small', count($documents) . ' completed documents available', ['class' => 'form-text text-muted']),
            'form-group'
        );
        echo html_writer::end_div();

        // Duration (right side - 4 columns)
        echo html_writer::start_div('col-md-4');
        echo html_writer::div(
            html_writer::tag('label', 'Course Duration: <span class="text-danger">*</span>', ['class' => 'font-weight-bold', 'for' => 'duration']) .
            html_writer::start_div('input-group') .
            html_writer::select(array_combine(range(1, 12), range(1, 12)), 'duration_weeks', 4, false, [
                'class' => 'form-control',
                'id' => 'duration',
                'required' => true
            ]) .
            html_writer::div(
                html_writer::tag('span', 'weeks', ['class' => 'input-group-text']),
                'input-group-append'
            ) .
            html_writer::end_div() .
            html_writer::tag('small', 'Recommended: 4-8 weeks', ['class' => 'form-text text-muted']),
            'form-group'
        );
        echo html_writer::end_div();

        echo html_writer::end_div(); // row

        echo html_writer::end_tag('fieldset');

        // FIELDSET 4: Content Types
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '🎨 Content Types', ['class' => 'font-weight-bold text-primary']);
        echo html_writer::div(
            '<small class="text-muted">Select which types of content to generate. Sections and pages are always included.</small>',
            'mb-3'
        );

        $content_types_def = [
            'sections' => ['label' => '📖 Course Sections', 'desc' => 'Weekly or topical sections with summaries', 'default' => true, 'required' => true],
            'pages' => ['label' => '📄 Teaching Pages', 'desc' => '400-800 words of instructional content', 'default' => true, 'required' => true],
            'activities' => ['label' => '🎯 Hands-on Activities', 'desc' => 'Practice exercises and learning tasks', 'default' => false],
            'discussions' => ['label' => '💬 Discussion Forums', 'desc' => 'Critical thinking prompts', 'default' => false],
            'quiz_questions' => ['label' => '❓ Section Quizzes', 'desc' => 'Graded assessments with feedback', 'default' => true],
            'assignments' => ['label' => '📝 Assignments', 'desc' => 'Projects with rubrics', 'default' => false]
        ];

        echo html_writer::start_div('row');
        foreach ($content_types_def as $type => $info) {
            $is_required = $info['required'] ?? false;
            echo html_writer::start_div('col-md-6 mb-2');
            echo html_writer::start_div('card h-100' . ($is_required ? ' border-primary' : ''));
            echo html_writer::start_div('card-body p-3');
            echo html_writer::start_div('custom-control custom-checkbox');
            echo html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'name' => 'content_types[]',
                'value' => $type,
                'id' => 'content_' . $type,
                'class' => 'custom-control-input',
                'checked' => ($info['default'] ?? false) ? 'checked' : null,
                'disabled' => $is_required ? 'disabled' : null
            ]);
            if ($is_required) {
                echo html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'name' => 'content_types[]',
                    'value' => $type
                ]);
            }
            echo html_writer::tag('label', $info['label'], [
                'for' => 'content_' . $type,
                'class' => 'custom-control-label font-weight-bold'
            ]);
            echo html_writer::tag('small', $info['desc'], ['class' => 'd-block text-muted mt-1']);
            echo html_writer::end_div(); // custom-control
            echo html_writer::end_div(); // card-body
            echo html_writer::end_div(); // card
            echo html_writer::end_div(); // col
        }
        echo html_writer::end_div(); // row

        echo html_writer::end_tag('fieldset');

        // Enhanced Submit Section
        echo html_writer::start_div('card border-primary mt-5 mb-4');
        echo html_writer::start_div('card-body text-center p-4');

        // Title
        echo html_writer::tag('h5', '🚀 Ready to Generate?', ['class' => 'card-title mb-3']);

        // Description
        echo html_writer::div(
            'The AI will analyze your documents and create professional course content using the ADDIE framework.',
            'text-muted mb-3'
        );

        // Estimated time with icon
        echo html_writer::start_div('alert alert-info d-inline-block mb-3');
        echo '<i class="fa fa-clock-o"></i> <strong>Estimated Time:</strong> 3-8 minutes based on duration and content types';
        echo html_writer::end_div();

        // Main generate button with gradient effect
        echo html_writer::div(
            html_writer::tag('button',
                '<i class="fa fa-magic mr-2"></i> Generate Course Content',
                [
                    'type' => 'submit',
                    'class' => 'btn btn-savian btn-lg px-5 py-3',
                    'style' => 'font-size: 1.2em; box-shadow: 0 4px 6px rgba(108, 59, 170, 0.3);'
                ]
            ),
            'd-block mb-2'
        );

        // Subtitle
        echo html_writer::tag('small',
            'You\'ll be able to preview and edit all content before adding to your course',
            ['class' => 'text-muted']
        );

        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card

        echo html_writer::end_tag('form');
    }
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← Back to Dashboard',
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
