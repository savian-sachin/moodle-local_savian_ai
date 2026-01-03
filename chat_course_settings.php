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

$PAGE->set_url(new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('chat_course_settings', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Handle form submission
if ($action === 'save' && confirm_sesskey()) {
    $chat_enabled = optional_param('chat_enabled', 0, PARAM_INT);
    $students_can_chat = optional_param('students_can_chat', 0, PARAM_INT);
    $welcome_message = optional_param('welcome_message', '', PARAM_TEXT);
    $auto_include_docs = optional_param('auto_include_docs', 0, PARAM_INT);

    $config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $courseid]);

    if ($config) {
        $config->chat_enabled = $chat_enabled;
        $config->students_can_chat = $students_can_chat;
        $config->welcome_message = $welcome_message;
        $config->auto_include_docs = $auto_include_docs;
        $config->timemodified = time();
        $config->usermodified = $USER->id;
        $DB->update_record('local_savian_chat_course_config', $config);
    } else {
        $config = new stdClass();
        $config->course_id = $courseid;
        $config->chat_enabled = $chat_enabled;
        $config->students_can_chat = $students_can_chat;
        $config->welcome_message = $welcome_message;
        $config->auto_include_docs = $auto_include_docs;
        $config->timemodified = time();
        $config->usermodified = $USER->id;
        $DB->insert_record('local_savian_chat_course_config', $config);
    }

    redirect(new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]),
             get_string('settings_saved', 'local_savian_ai'), null, 'success');
}

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header(
    get_string('chat_course_settings', 'local_savian_ai'),
    get_string('chat_course_settings_desc', 'local_savian_ai')
);

// Get current settings
$config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $courseid]);
if (!$config) {
    $config = new stdClass();
    $config->chat_enabled = 1;
    $config->students_can_chat = 1;
    $config->welcome_message = '';
    $config->auto_include_docs = 1;
}

// Settings form
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid])
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('chat_settings', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');

// Enable chat checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'chat_enabled',
    'id' => 'chat_enabled',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->chat_enabled ? 'checked' : null
]);
echo html_writer::tag('label', get_string('enable_chat_for_course', 'local_savian_ai'), [
    'for' => 'chat_enabled',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('enable_chat_for_course_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Students can chat checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'students_can_chat',
    'id' => 'students_can_chat',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->students_can_chat ? 'checked' : null
]);
echo html_writer::tag('label', get_string('students_can_chat', 'local_savian_ai'), [
    'for' => 'students_can_chat',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('students_can_chat_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Auto-include documents checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'auto_include_docs',
    'id' => 'auto_include_docs',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->auto_include_docs ? 'checked' : null
]);
echo html_writer::tag('label', get_string('auto_include_docs', 'local_savian_ai'), [
    'for' => 'auto_include_docs',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('auto_include_docs_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Welcome message textarea
echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('course_welcome_message', 'local_savian_ai'), [
    'for' => 'welcome_message',
    'class' => 'font-weight-bold'
]);
echo html_writer::tag('textarea', s($config->welcome_message ?? ''), [
    'name' => 'welcome_message',
    'id' => 'welcome_message',
    'class' => 'form-control',
    'rows' => 3,
    'placeholder' => get_string('course_welcome_message_placeholder', 'local_savian_ai')
]);
echo html_writer::tag('small', get_string('course_welcome_message_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

// Save button
echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('savechanges'),
        'class' => 'btn btn-primary'
    ]),
    'text-center mt-3'
);

echo html_writer::end_tag('form');

// Back button
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← ' . get_string('back'),
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
