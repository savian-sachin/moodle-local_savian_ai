<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/savian_ai:use', context_system::instance());

$PAGE->set_url(new moodle_url('/local/savian_ai/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_savian_ai'));
$PAGE->set_heading(get_string('dashboard', 'local_savian_ai'));

$api_key = get_config('local_savian_ai', 'api_key');
$org_code = get_config('local_savian_ai', 'org_code');

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header('Savian AI Dashboard', 'AI-powered content generation for Moodle');

if (empty($api_key)) {
    echo $OUTPUT->notification(get_string('error_no_api_key', 'local_savian_ai'), 'error');
    if (has_capability('local/savian_ai:manage', context_system::instance())) {
        echo html_writer::link(
            new moodle_url('/admin/settings.php', ['section' => 'local_savian_ai']),
            'Configure API Settings',
            ['class' => 'btn btn-savian']
        );
    }
    echo $OUTPUT->footer();
    exit;
}

// Get API connection status
$client = new \local_savian_ai\api\client();
$api_response = $client->validate();
$api_connected = ($api_response->http_code === 200 && isset($api_response->valid) && $api_response->valid);

// Sync documents from API (to keep local cache updated)
if ($api_connected) {
    $sync_response = $client->get_documents(['per_page' => 100]);
    if ($sync_response->http_code === 200 && isset($sync_response->documents)) {
        foreach ($sync_response->documents as $doc) {
            $existing = $DB->get_record('local_savian_documents', ['savian_doc_id' => $doc->id]);

            $record = new stdClass();
            $record->savian_doc_id = $doc->id;
            $record->title = $doc->title;
            $record->description = $doc->description ?? '';
            $record->subject_area = $doc->subject_area ?? '';
            $record->status = $doc->processing_status;
            $record->progress = $doc->processing_progress ?? 0;
            $record->chunk_count = $doc->chunk_count ?? 0;
            $record->qna_count = $doc->qna_count ?? 0;
            $record->file_size = $doc->file_size ?? 0;
            $record->file_type = $doc->source_file_type ?? '';
            $record->tags = json_encode($doc->tags ?? []);
            $record->is_active = $doc->is_active ? 1 : 0;
            $record->last_synced = time();
            $record->timemodified = time();

            $api_course_id = $doc->moodle_course_id ?? $doc->course_id ?? null;

            if ($existing) {
                $record->id = $existing->id;
                $record->course_id = $existing->course_id ?: $api_course_id;
                $record->timecreated = $existing->timecreated;
                $record->usermodified = $existing->usermodified;
                $DB->update_record('local_savian_documents', $record);
            } else {
                $record->course_id = $api_course_id;
                $record->timecreated = time();
                $record->usermodified = 0;
                $DB->insert_record('local_savian_documents', $record);
            }
        }
    }
}

// === ORGANIZATION-WIDE STATISTICS ===
echo html_writer::start_div('row mb-4');

// Total Documents
$total_docs = $DB->count_records('local_savian_documents', ['is_active' => 1]);
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $total_docs, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Total Documents', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Questions
$total_questions = $DB->get_field_sql(
    'SELECT COALESCE(SUM(questions_count), 0) FROM {local_savian_generations} WHERE generation_type IN (?, ?)',
    ['questions', 'questions_from_documents']
);
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $total_questions, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Questions Generated', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Course Content
$course_content_gens = $DB->get_records('local_savian_generations', ['generation_type' => 'course_content']);
$total_sections = 0;
$total_activities = 0;
foreach ($course_content_gens as $gen) {
    if (!empty($gen->response_data)) {
        $data = json_decode($gen->response_data);
        $total_sections += $data->sections_created ?? 0;
        $total_activities += ($data->pages_created ?? 0) + ($data->quizzes_created ?? 0) + ($data->assignments_created ?? 0);
    }
}
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $total_sections, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Course Sections', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Activities
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $total_activities, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Activities Created', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row

// === API STATUS & QUOTA ===
if ($api_connected) {
    echo html_writer::start_div('card mb-4 savian-accent-card');
    echo html_writer::div('📊 API Usage & Quota', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::div('✓ Connected to ' . ($api_response->organization->name ?? $org_code), 'alert alert-success mb-3');

    if (isset($api_response->quota)) {
        echo html_writer::start_div('row');

        // Questions quota with progress bar
        if (isset($api_response->quota->questions)) {
            $q = $api_response->quota->questions;
            $percentage = ($q->limit > 0) ? ($q->used / $q->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Questions');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-primary', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar'
                ]),
                'progress mt-2'
            );
            echo html_writer::tag('small', "{$q->used} / {$q->limit} used ({$q->remaining} remaining)", ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        // Documents quota
        if (isset($api_response->quota->documents)) {
            $d = $api_response->quota->documents;
            $percentage = ($d->limit > 0) ? ($d->used / $d->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Documents');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-info', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar'
                ]),
                'progress mt-2'
            );
            echo html_writer::tag('small', "{$d->used} / {$d->limit} used ({$d->remaining} remaining)", ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        // Course Content quota
        if (isset($api_response->quota->course_content)) {
            $c = $api_response->quota->course_content;
            $percentage = ($c->limit > 0) ? ($c->used / $c->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Course Content');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-success', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar'
                ]),
                'progress mt-2'
            );
            echo html_writer::tag('small', "{$c->used} / {$c->limit} used ({$c->remaining} remaining)", ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        echo html_writer::end_div(); // row
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo $OUTPUT->notification('API connection failed', 'warning');
}

// === YOUR COURSES ===
echo html_writer::tag('h3', 'Your Courses', ['class' => 'mt-4']);

// Get user's courses
$user_courses = enrol_get_users_courses($USER->id, true);

if (!empty($user_courses)) {
    $table = new html_table();
    $table->head = ['Course', 'Documents', 'Questions', 'Sections', 'Activities', 'Actions'];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($user_courses as $course) {
        $course_docs = $DB->count_records('local_savian_documents', ['course_id' => $course->id, 'is_active' => 1]);
        $course_questions = $DB->get_field_sql(
            'SELECT COALESCE(SUM(questions_count), 0) FROM {local_savian_generations}
             WHERE course_id = ? AND generation_type IN (?, ?)',
            [$course->id, 'questions', 'questions_from_documents']
        );

        $course_content = $DB->get_records('local_savian_generations', ['course_id' => $course->id, 'generation_type' => 'course_content']);
        $sections = 0;
        $activities = 0;
        foreach ($course_content as $gen) {
            if (!empty($gen->response_data)) {
                $data = json_decode($gen->response_data);
                $sections += $data->sections_created ?? 0;
                $activities += ($data->pages_created ?? 0) + ($data->quizzes_created ?? 0) + ($data->assignments_created ?? 0);
            }
        }

        $row = [];
        $row[] = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            s($course->fullname)
        );
        $row[] = $course_docs;
        $row[] = $course_questions;
        $row[] = $sections;
        $row[] = $activities;
        $row[] = html_writer::link(
            new moodle_url('/local/savian_ai/course.php', ['courseid' => $course->id]),
            'Open Dashboard',
            ['class' => 'btn btn-sm btn-savian']
        );

        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div('No courses found', 'alert alert-info');
}

// === RECENT ACTIVITY ===
echo html_writer::tag('h3', 'Recent Activity', ['class' => 'mt-4']);

$recent = $DB->get_records('local_savian_generations', null, 'timecreated DESC', '*', 0, 10);

if (!empty($recent)) {
    echo html_writer::start_div('list-group');

    foreach ($recent as $activity) {
        $course_name = $DB->get_field('course', 'fullname', ['id' => $activity->course_id]);
        $user_name = $DB->get_field('user', 'firstname', ['id' => $activity->user_id]) . ' ' .
                     $DB->get_field('user', 'lastname', ['id' => $activity->user_id]);

        $icon = '❓';
        $description = '';
        switch ($activity->generation_type) {
            case 'questions':
                $icon = '❓';
                $description = "{$activity->questions_count} questions generated";
                break;
            case 'questions_from_documents':
                $icon = '📄❓';
                $description = "{$activity->questions_count} questions from documents";
                break;
            case 'course_content':
                $icon = '📚';
                $data = json_decode($activity->response_data ?? '{}');
                $sections = $data->sections_created ?? 0;
                $description = "{$sections} course sections created";
                break;
        }

        echo html_writer::start_div('list-group-item');
        echo html_writer::tag('div',
            "{$icon} <strong>{$description}</strong> in {$course_name}",
            ['class' => 'mb-1']
        );
        echo html_writer::tag('small',
            "by {$user_name} • " . userdate($activity->timecreated, '%d %b %Y, %H:%M'),
            ['class' => 'text-muted']
        );
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
} else {
    echo html_writer::div('No recent activity', 'alert alert-info');
}

// Help & Tutorials Link
echo html_writer::start_div('text-center mt-5 mb-4');
echo html_writer::link(
    new moodle_url('/local/savian_ai/tutorials.php'),
    '<i class="fa fa-question-circle mr-2"></i>' . get_string('tutorials', 'local_savian_ai'),
    ['class' => 'btn btn-info btn-lg']
);
echo html_writer::tag('p', 'Need help getting started? Check out our tutorials!', ['class' => 'text-muted mt-2']);
echo html_writer::end_div();

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
