<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();

$savian_cache = cache::make('local_savian_ai', 'session_data');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/edit_questions.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title('Edit Questions');
$PAGE->set_heading($course->fullname);

// Handle save
if ($action === 'save' && confirm_sesskey()) {
    $questions = json_decode($savian_cache->get('questions'));

    // Update questions from form data
    foreach ($questions as $idx => $q) {
        $q->questiontext = optional_param("questiontext_{$idx}", $q->questiontext, PARAM_RAW);
        $q->generalfeedback = optional_param("generalfeedback_{$idx}", $q->generalfeedback ?? '', PARAM_RAW);

        if (isset($q->answers)) {
            foreach ($q->answers as $aidx => $answer) {
                $answer->text = optional_param("answer_{$idx}_{$aidx}", $answer->text, PARAM_RAW);
                $answer->feedback = optional_param("feedback_{$idx}_{$aidx}", $answer->feedback ?? '', PARAM_RAW);
            }
        }
    }

    // Save back to cache
    $savian_cache->set('questions', json_encode($questions));

    redirect(new moodle_url('/local/savian_ai/generate.php', [
        'courseid' => $courseid,
        'action' => 'preview'
    ]), 'Questions updated', null, 'success');
}

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header('Edit Questions', 'Modify generated questions before adding to Question Bank');

if ($savian_cache->get('questions')) {
    $questions = json_decode($savian_cache->get('questions'));

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/savian_ai/edit_questions.php', ['courseid' => $courseid])
    ]);

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    foreach ($questions as $idx => $q) {
        echo html_writer::start_div('card mb-4');
        echo html_writer::div('Question ' . ($idx + 1) . ' of ' . count($questions), 'card-header bg-light');
        echo html_writer::start_div('card-body');

        // Question text (editable)
        echo html_writer::div('Question Text:', 'font-weight-bold mb-2');
        echo html_writer::tag('textarea', s($q->questiontext ?? ''), [
            'name' => "questiontext_{$idx}",
            'rows' => 3,
            'class' => 'form-control mb-3',
            'style' => 'width: 100%;'
        ]);

        // Answers (editable)
        if (isset($q->answers) && is_array($q->answers)) {
            echo html_writer::div('Answers:', 'font-weight-bold mb-2');
            foreach ($q->answers as $aidx => $answer) {
                $is_correct = ($answer->fraction ?? 0) > 0;
                $badge = $is_correct ? '<span class="badge badge-success">Correct</span>' : '';

                echo html_writer::start_div('input-group mb-2');
                echo html_writer::tag('div', ($aidx + 1) . '. ' . $badge, ['class' => 'input-group-prepend input-group-text']);
                echo html_writer::tag('input', '', [
                    'type' => 'text',
                    'name' => "answer_{$idx}_{$aidx}",
                    'value' => s($answer->text ?? ''),
                    'class' => 'form-control'
                ]);
                echo html_writer::end_div();
            }
        }

        // Feedback (editable)
        echo html_writer::div('General Feedback:', 'font-weight-bold mb-2 mt-3');
        echo html_writer::tag('textarea', s($q->generalfeedback ?? ''), [
            'name' => "generalfeedback_{$idx}",
            'rows' => 2,
            'class' => 'form-control',
            'style' => 'width: 100%;'
        ]);

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Action buttons
    echo html_writer::start_div('text-center mt-4');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => 'Save Changes',
        'class' => 'btn btn-primary btn-lg mr-2'
    ]);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'action' => 'preview']),
        'Cancel',
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
} else {
    echo $OUTPUT->notification('No questions to edit', 'error');
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        'Back to course',
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

echo $OUTPUT->footer();
