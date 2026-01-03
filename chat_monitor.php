<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/savian_ai:manage', context_system::instance());

$PAGE->set_url(new moodle_url('/local/savian_ai/chat_monitor.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('chat_monitoring', 'local_savian_ai'));
$PAGE->set_heading(get_string('chat_monitoring', 'local_savian_ai'));

$analytics = new \local_savian_ai\chat\analytics();

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header(
    get_string('chat_monitoring', 'local_savian_ai'),
    get_string('chat_monitoring_desc', 'local_savian_ai')
);

// === SYSTEM-WIDE STATISTICS ===
$stats = $analytics->get_system_stats();

echo html_writer::tag('h3', get_string('system_wide_stats', 'local_savian_ai'), ['class' => 'mt-4']);

echo html_writer::start_div('row mb-4');

// Total Conversations
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $stats->total_conversations, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', get_string('conversation_count', 'local_savian_ai'), ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Messages
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $stats->total_messages, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', get_string('message_count', 'local_savian_ai'), ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Unique Users
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $stats->unique_users, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', get_string('unique_users', 'local_savian_ai'), ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Average Response Time
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $stats->avg_response_time . 'ms', ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', get_string('response_time_avg', 'local_savian_ai'), ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row

// === TOP COURSES ===
echo html_writer::tag('h3', get_string('top_courses', 'local_savian_ai'), ['class' => 'mt-4']);

$top_courses = $analytics->get_top_courses(10);

if (!empty($top_courses)) {
    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('conversation_count', 'local_savian_ai'),
        get_string('unique_users', 'local_savian_ai'),
        get_string('message_count', 'local_savian_ai')
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($top_courses as $course) {
        $row = [];
        $row[] = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $course->course_id]),
            s($course->fullname)
        );
        $row[] = $course->conversation_count;
        $row[] = $course->user_count;
        $row[] = $course->message_count;

        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(
        get_string('no_chat_activity', 'local_savian_ai'),
        'alert alert-info'
    );
}

// === RECENT CONVERSATIONS ===
echo html_writer::tag('h3', get_string('recent_conversations', 'local_savian_ai'), ['class' => 'mt-4']);

$recent = $analytics->get_recent_conversations(20);

if (!empty($recent)) {
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('course'),
        get_string('message_count', 'local_savian_ai'),
        get_string('last_active', 'local_savian_ai')
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($recent as $conv) {
        $row = [];
        $row[] = s($conv->firstname . ' ' . $conv->lastname);
        $row[] = $conv->course_name ? s($conv->course_name) : get_string('globalchat', 'local_savian_ai');
        $row[] = $conv->message_count;
        $row[] = userdate($conv->last_message_at, get_string('strftimedatetime', 'langconfig'));

        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(
        get_string('no_conversations', 'local_savian_ai'),
        'alert alert-info'
    );
}

// Back button
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/index.php'),
        '← Back to Dashboard',
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
