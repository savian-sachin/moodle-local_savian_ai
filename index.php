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
 * Savian AI dashboard page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/savian_ai:use', context_system::instance());

$PAGE->set_url(new moodle_url('/local/savian_ai/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_savian_ai'));
$PAGE->set_heading(get_string('dashboard', 'local_savian_ai'));

$apikey = get_config('local_savian_ai', 'api_key');
$orgcode = get_config('local_savian_ai', 'org_code');

echo $OUTPUT->header();

// Consistent header.
echo local_savian_ai_render_header(
    'Savian AI Dashboard',
    'AI-powered content generation for Moodle'
);

if (empty($apikey)) {
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

// Get API connection status.
$client = new \local_savian_ai\api\client();
$apiresponse = $client->validate();
$apiconnected = ($apiresponse->http_code === 200 && isset($apiresponse->valid) && $apiresponse->valid);

// Sync documents from API (to keep local cache updated).
if ($apiconnected) {
    $syncresponse = $client->get_documents(['per_page' => 100]);
    if ($syncresponse->http_code === 200 && isset($syncresponse->documents)) {
        foreach ($syncresponse->documents as $doc) {
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

            $apicourseid = $doc->moodle_course_id ?? $doc->course_id ?? null;

            if ($existing) {
                $record->id = $existing->id;
                $record->course_id = $existing->course_id ?: $apicourseid;
                $record->timecreated = $existing->timecreated;
                $record->usermodified = $existing->usermodified;
                $DB->update_record('local_savian_documents', $record);
            } else {
                $record->course_id = $apicourseid;
                $record->timecreated = time();
                $record->usermodified = 0;
                $DB->insert_record('local_savian_documents', $record);
            }
        }
    }
}

// Organization-wide statistics.
echo html_writer::start_div('row mb-4');

// Total Documents.
$totaldocs = $DB->count_records('local_savian_documents', ['is_active' => 1]);
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $totaldocs, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Total Documents', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Questions.
$totalquestions = $DB->get_field_sql(
    'SELECT COALESCE(SUM(questions_count), 0)
       FROM {local_savian_generations}
      WHERE generation_type IN (?, ?)',
    ['questions', 'questions_from_documents']
);
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $totalquestions, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Questions Generated', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Course Content.
$coursecontentgens = $DB->get_records('local_savian_generations', ['generation_type' => 'course_content']);
$totalsections = 0;
$totalactivities = 0;
foreach ($coursecontentgens as $gen) {
    if (!empty($gen->response_data)) {
        $data = json_decode($gen->response_data);
        $totalsections += $data->sections_created ?? 0;
        $totalactivities += ($data->pages_created ?? 0)
            + ($data->quizzes_created ?? 0)
            + ($data->assignments_created ?? 0);
    }
}
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $totalsections, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Course Sections', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Total Activities.
echo html_writer::start_div('col-md-3 col-6 mb-3');
echo html_writer::start_div('card text-center');
echo html_writer::start_div('card-body');
echo html_writer::tag('div', $totalactivities, ['class' => 'h2 mb-0 savian-text-primary']);
echo html_writer::tag('div', 'Activities Created', ['class' => 'text-muted small']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End row.

// API status and quota.
if ($apiconnected) {
    echo html_writer::start_div('card mb-4 savian-accent-card');
    echo html_writer::div('API Usage & Quota', 'card-header');
    echo html_writer::start_div('card-body');

    $orgname = $apiresponse->organization->name ?? $orgcode;
    echo html_writer::div(
        'Connected to ' . $orgname,
        'alert alert-success mb-3'
    );

    if (isset($apiresponse->quota)) {
        echo html_writer::start_div('row');

        // Questions quota with progress bar.
        if (isset($apiresponse->quota->questions)) {
            $q = $apiresponse->quota->questions;
            $percentage = ($q->limit > 0) ? ($q->used / $q->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Questions');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-primary', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar',
                ]),
                'progress mt-2'
            );
            $quotatext = "{$q->used} / {$q->limit} used ({$q->remaining} remaining)";
            echo html_writer::tag('small', $quotatext, ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        // Documents quota.
        if (isset($apiresponse->quota->documents)) {
            $d = $apiresponse->quota->documents;
            $percentage = ($d->limit > 0) ? ($d->used / $d->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Documents');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-info', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar',
                ]),
                'progress mt-2'
            );
            $quotatext = "{$d->used} / {$d->limit} used ({$d->remaining} remaining)";
            echo html_writer::tag('small', $quotatext, ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        // Course Content quota.
        if (isset($apiresponse->quota->course_content)) {
            $c = $apiresponse->quota->course_content;
            $percentage = ($c->limit > 0) ? ($c->used / $c->limit * 100) : 0;

            echo html_writer::start_div('col-md-4 mb-3');
            echo html_writer::tag('strong', 'Course Content');
            echo html_writer::div(
                html_writer::div('', 'progress-bar bg-success', [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar',
                ]),
                'progress mt-2'
            );
            $quotatext = "{$c->used} / {$c->limit} used ({$c->remaining} remaining)";
            echo html_writer::tag('small', $quotatext, ['class' => 'text-muted']);
            echo html_writer::end_div();
        }

        echo html_writer::end_div(); // End row.
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo $OUTPUT->notification('API connection failed', 'warning');
}

// Your courses.
echo html_writer::tag('h3', 'Your Courses', ['class' => 'mt-4']);

// Get user's courses.
$usercourses = enrol_get_users_courses($USER->id, true);

if (!empty($usercourses)) {
    $table = new html_table();
    $table->head = ['Course', 'Documents', 'Questions', 'Sections', 'Activities', 'Actions'];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($usercourses as $course) {
        $coursedocs = $DB->count_records(
            'local_savian_documents',
            ['course_id' => $course->id, 'is_active' => 1]
        );
        $coursequestions = $DB->get_field_sql(
            'SELECT COALESCE(SUM(questions_count), 0)
               FROM {local_savian_generations}
              WHERE course_id = ? AND generation_type IN (?, ?)',
            [$course->id, 'questions', 'questions_from_documents']
        );

        $coursecontent = $DB->get_records('local_savian_generations', [
            'course_id' => $course->id,
            'generation_type' => 'course_content',
        ]);
        $sections = 0;
        $activities = 0;
        foreach ($coursecontent as $gen) {
            if (!empty($gen->response_data)) {
                $data = json_decode($gen->response_data);
                $sections += $data->sections_created ?? 0;
                $activities += ($data->pages_created ?? 0)
                    + ($data->quizzes_created ?? 0)
                    + ($data->assignments_created ?? 0);
            }
        }

        $row = [];
        $row[] = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            s($course->fullname)
        );
        $row[] = $coursedocs;
        $row[] = $coursequestions;
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

// Recent activity.
echo html_writer::tag('h3', 'Recent Activity', ['class' => 'mt-4']);

$recent = $DB->get_records('local_savian_generations', null, 'timecreated DESC', '*', 0, 10);

if (!empty($recent)) {
    echo html_writer::start_div('list-group');

    foreach ($recent as $activity) {
        $coursename = $DB->get_field('course', 'fullname', ['id' => $activity->course_id]);
        $firstname = $DB->get_field('user', 'firstname', ['id' => $activity->user_id]);
        $lastname = $DB->get_field('user', 'lastname', ['id' => $activity->user_id]);
        $username = $firstname . ' ' . $lastname;

        $icon = '?';
        $description = '';
        switch ($activity->generation_type) {
            case 'questions':
                $icon = '?';
                $description = "{$activity->questions_count} questions generated";
                break;
            case 'questions_from_documents':
                $icon = 'Q';
                $description = "{$activity->questions_count} questions from documents";
                break;
            case 'course_content':
                $icon = 'C';
                $data = json_decode($activity->response_data ?? '{}');
                $sections = $data->sections_created ?? 0;
                $description = "{$sections} course sections created";
                break;
        }

        echo html_writer::start_div('list-group-item');
        echo html_writer::tag(
            'div',
            "{$icon} <strong>{$description}</strong> in {$coursename}",
            ['class' => 'mb-1']
        );
        $timetext = "by {$username} &bull; "
            . userdate($activity->timecreated, '%d %b %Y, %H:%M');
        echo html_writer::tag('small', $timetext, ['class' => 'text-muted']);
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
} else {
    echo html_writer::div('No recent activity', 'alert alert-info');
}

// Help and tutorials link.
echo html_writer::start_div('text-center mt-5 mb-4');
echo html_writer::link(
    new moodle_url('/local/savian_ai/tutorials.php'),
    '<i class="fa fa-question-circle mr-2"></i>'
        . get_string('tutorials', 'local_savian_ai'),
    ['class' => 'btn btn-info btn-lg']
);
echo html_writer::tag(
    'p',
    'Need help getting started? Check out our tutorials!',
    ['class' => 'text-muted mt-2']
);
echo html_writer::end_div();

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
