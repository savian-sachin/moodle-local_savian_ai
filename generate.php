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
$mode = optional_param('mode', 'topic', PARAM_ALPHA); // 'topic' or 'documents'
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'mode' => $mode]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('generate', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

$client = new \local_savian_ai\api\client();
$qbank_creator = new \local_savian_ai\content\qbank_creator();

// Initialize form
$customdata = ['courseid' => $courseid, 'mode' => $mode];
$mform = new \local_savian_ai\form\generate_questions_form(null, $customdata);

// Handle form submission
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid]));
} else if ($data = $mform->get_data()) {
    // Prepare generation options
    $options = [
        'learning_objectives' => !empty($data->learning_objectives) ?
            array_filter(array_map('trim', explode("\n", $data->learning_objectives))) : [],
        'question_types' => $data->question_types,
        'count' => $data->count,
        'difficulty' => $data->difficulty,
        'bloom_level' => $data->bloom_level,
        'language' => $data->language,
    ];

    // Generate questions
    if ($mode === 'documents' && !empty($data->document_ids)) {
        // RAG-based generation
        $response = $client->generate_questions_from_docs($data->document_ids, $data->topic, $options);
    } else {
        // Topic-based generation
        $response = $client->generate_questions($data->topic, $options);
    }

    if ($response->http_code === 200 && isset($response->success) && $response->success) {
        // Check if async (has request_id)
        if (isset($response->request_id) && isset($response->status) && $response->status === 'pending') {
            // Async generation - need to poll
            $SESSION->savian_ai_pending_request = $response->request_id;
            redirect(new moodle_url('/local/savian_ai/generate.php', [
                'courseid' => $courseid,
                'mode' => $mode,
                'action' => 'poll',
            ]), get_string('generation_pending', 'local_savian_ai'), null, 'info');
        } else if (isset($response->questions) && count($response->questions) > 0) {
            // Synchronous generation - questions ready
            $SESSION->savian_ai_questions = json_encode($response->questions);
            $SESSION->savian_ai_metadata = isset($response->metadata) ? json_encode($response->metadata) : null;
            $SESSION->savian_ai_usage = isset($response->usage) ? json_encode($response->usage) : null;

            redirect(new moodle_url('/local/savian_ai/generate.php', [
                'courseid' => $courseid,
                'action' => 'preview',
            ]), get_string('questions_generated', 'local_savian_ai', count($response->questions)), null, 'success');
        }
    } else {
        $error = $response->error ?? $response->message ?? 'Unknown error';
        echo $OUTPUT->notification(get_string('generation_failed', 'local_savian_ai', $error), 'error');
    }
}

// Handle adding questions to question bank
if ($action === 'add' && confirm_sesskey()) {
    if (!empty($SESSION->savian_ai_questions)) {
        $questions = json_decode($SESSION->savian_ai_questions);

        $results = $qbank_creator->add_to_question_bank($questions, $courseid);

        // Log generation
        $log = new stdClass();
        $log->request_id = 'local_' . time();
        $log->generation_type = $mode === 'documents' ? 'questions_from_documents' : 'questions';
        $log->course_id = $courseid;
        $log->user_id = $USER->id;
        $log->questions_count = count($results['success']);
        $log->status = 'completed';
        $log->timecreated = time();
        $log->timemodified = time();
        $DB->insert_record('local_savian_generations', $log);

        // Clear session
        unset($SESSION->savian_ai_questions);
        unset($SESSION->savian_ai_metadata);
        unset($SESSION->savian_ai_usage);

        $success_count = count($results['success']);
        $failed_count = count($results['failed']);

        $message = get_string('questions_added', 'local_savian_ai', $success_count);
        if ($failed_count > 0) {
            $message .= " ({$failed_count} failed)";
        }

        redirect(new moodle_url('/question/edit.php', ['courseid' => $courseid]),
                 $message, null, 'success');
    }
}

// Handle polling for async generation
if ($action === 'poll' && !empty($SESSION->savian_ai_pending_request)) {
    $request_id = $SESSION->savian_ai_pending_request;
    $status_response = $client->get_generation_status($request_id);

    if ($status_response->http_code === 200) {
        if (isset($status_response->status) && $status_response->status === 'completed') {
            // Generation completed!
            if (isset($status_response->questions) && count($status_response->questions) > 0) {
                $SESSION->savian_ai_questions = json_encode($status_response->questions);
                $SESSION->savian_ai_metadata = isset($status_response->metadata) ? json_encode($status_response->metadata) : null;
                $SESSION->savian_ai_usage = isset($status_response->usage) ? json_encode($status_response->usage) : null;
                unset($SESSION->savian_ai_pending_request);

                redirect(new moodle_url('/local/savian_ai/generate.php', [
                    'courseid' => $courseid,
                    'action' => 'preview',
                ]), get_string('questions_generated', 'local_savian_ai', count($status_response->questions)), null, 'success');
            }
        } else if (isset($status_response->status) && $status_response->status === 'failed') {
            // Generation failed
            $error = $status_response->error ?? 'Generation failed';
            unset($SESSION->savian_ai_pending_request);
            redirect(new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid]),
                     get_string('generation_failed', 'local_savian_ai', $error), null, 'error');
        }
        // else status is still pending/processing - page will auto-refresh
    }
}

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header('Generate Questions', 'Create quiz questions from documents or topics');

// Tab navigation
$tabs = [];
$tabs[] = new tabobject('topic',
    new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'mode' => 'topic']),
    get_string('generate_from_topic', 'local_savian_ai'));
$tabs[] = new tabobject('documents',
    new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'mode' => 'documents']),
    get_string('generate_from_documents', 'local_savian_ai'));

echo $OUTPUT->tabtree($tabs, $mode);

// Show preview if questions are in session
if ($action === 'preview' && !empty($SESSION->savian_ai_questions)) {
    $questions = json_decode($SESSION->savian_ai_questions);

    echo html_writer::tag('h3', get_string('preview_questions', 'local_savian_ai') . ' (' . count($questions) . ')', ['class' => 'mt-4']);

    // Display usage if available
    if (!empty($SESSION->savian_ai_usage)) {
        $usage = json_decode($SESSION->savian_ai_usage);
        if (isset($usage->questions)) {
            $percentage = ($usage->questions->limit > 0) ? ($usage->questions->used / $usage->questions->limit * 100) : 0;
            echo html_writer::start_div('card mb-3');
            echo html_writer::start_div('card-body');
            echo html_writer::tag('strong', 'Quota Usage');
            echo html_writer::div(
                html_writer::div('', 'progress-bar', [
                    'style' => "width: {$percentage}%; background-color: #6C3BAA",
                    'role' => 'progressbar'
                ]),
                'progress mt-2 mb-2'
            );
            echo html_writer::tag('small', "{$usage->questions->used}/{$usage->questions->limit} questions ({$usage->questions->remaining} remaining)", ['class' => 'text-muted']);
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
    }

    // Display questions
    foreach ($questions as $idx => $q) {
        echo html_writer::start_div('card mb-3');
        echo html_writer::div('Question ' . ($idx + 1), 'card-header bg-light');
        echo html_writer::start_div('card-body');

        echo html_writer::tag('div', format_text($q->questiontext, FORMAT_HTML), ['class' => 'mb-3']);
        echo html_writer::tag('div', '<small class="text-muted">Type: ' . ucfirst($q->type ?? 'unknown') . '</small>');

        if (isset($q->answers)) {
            echo html_writer::start_tag('ul', ['class' => 'list-unstyled ml-3']);
            foreach ($q->answers as $answer) {
                $icon = ($answer->fraction ?? 0) > 0 ? '✓' : '○';
                $class = ($answer->fraction ?? 0) > 0 ? 'text-success' : '';
                echo html_writer::tag('li', "{$icon} " . s($answer->text), ['class' => $class]);
            }
            echo html_writer::end_tag('ul');
        }

        if (!empty($q->generalfeedback)) {
            echo html_writer::div(
                html_writer::tag('small', html_writer::tag('strong', 'Feedback: ') . format_text($q->generalfeedback, FORMAT_HTML)),
                'text-muted mt-2 p-2 bg-light rounded'
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Action buttons
    echo html_writer::start_div('text-center mt-4');
    echo html_writer::link(
        new moodle_url('/local/savian_ai/edit_questions.php', ['courseid' => $courseid]),
        'Edit Questions',
        ['class' => 'btn btn-outline-secondary btn-lg mr-2']
    );
    echo html_writer::link(
        new moodle_url('/local/savian_ai/generate.php', [
            'courseid' => $courseid,
            'action' => 'add',
            'sesskey' => sesskey(),
        ]),
        get_string('add_to_question_bank', 'local_savian_ai'),
        ['class' => 'btn btn-savian btn-lg']
    );
    echo html_writer::end_div();
} else if ($action === 'poll' && !empty($SESSION->savian_ai_pending_request)) {
    // Show polling status
    echo html_writer::start_div('card mt-4');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('div', '', ['class' => 'spinner-border text-primary mb-3', 'role' => 'status', 'style' => 'width: 3rem; height: 3rem;']);
    echo html_writer::tag('h4', 'Generating Questions...');
    echo html_writer::tag('p', 'This page will automatically refresh every 5 seconds.', ['class' => 'text-muted']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Auto-refresh every 5 seconds
    $PAGE->requires->js_amd_inline("
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    ");
} else {
    // Show generation form
    $mform->display();
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

// Coming Soon Features (Collapsible)
echo html_writer::start_div('mt-5 mb-4');
echo html_writer::start_tag('details', ['class' => 'border rounded p-3 bg-light']);
echo html_writer::tag('summary', '🔮 Coming Soon: AI Assessment Evaluation', ['class' => 'font-weight-bold text-success', 'style' => 'cursor: pointer;']);
echo html_writer::start_div('mt-3');

echo html_writer::tag('h6', '🤖 Automatic Grading of Short Text & Essays', ['class' => 'text-success']);
echo html_writer::tag('p', 'AI will evaluate student responses that currently require manual grading:', ['class' => 'small']);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag('li', '✨ Automatic grading of short answer questions');
echo html_writer::tag('li', '📝 Detailed feedback on essay responses');
echo html_writer::tag('li', '📊 Rubric-based assessment with suggestions');
echo html_writer::tag('li', '⏱️ Save 70% of grading time');
echo html_writer::end_tag('ul');

echo html_writer::tag('p', '<strong>Use case:</strong> Students submit essays → AI provides draft grades + feedback → You review and finalize', ['class' => 'small text-muted']);

echo html_writer::end_div();
echo html_writer::end_tag('details');
echo html_writer::end_div();

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
