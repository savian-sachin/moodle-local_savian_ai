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

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Only teachers can view conversation history
require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/chat_history.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('chat_history', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Load chat history AMD module
$PAGE->requires->js_call_amd('local_savian_ai/chat_history', 'init', [
    'courseid' => $courseid
]);

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header(
    get_string('chat_history', 'local_savian_ai'),
    get_string('chat_history_desc', 'local_savian_ai')
);

// Chat history container (populated by JS)
echo html_writer::div('', 'savian-chat-history-container', ['id' => 'chat-history-main']);

// Back button
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
