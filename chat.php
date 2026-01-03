<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$conversationid = optional_param('conversationid', 0, PARAM_INT);

// Determine context
if ($courseid) {
    $context = context_course::instance($courseid);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $PAGE->set_course($course);
} else {
    $context = context_system::instance();
}

require_capability('local/savian_ai:use', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/chat.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('chat', 'local_savian_ai'));
$PAGE->set_heading(get_string('chat', 'local_savian_ai'));

// Load chat AMD module
$PAGE->requires->js_call_amd('local_savian_ai/chat_interface', 'init', [
    'courseid' => $courseid,
    'conversationid' => $conversationid,
    'fullpage' => true
]);


echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header(
    get_string('chat', 'local_savian_ai'),
    $courseid ? $course->fullname : get_string('globalchat', 'local_savian_ai')
);

// Chat interface container (populated by JS)
echo html_writer::div('', 'savian-chat-container', ['id' => 'savian-chat-main']);

// Back button
$back_url = $courseid
    ? new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid])
    : new moodle_url('/local/savian_ai/index.php');

echo html_writer::div(
    html_writer::link($back_url, '← Back', ['class' => 'btn btn-secondary mt-3']),
    ''
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
