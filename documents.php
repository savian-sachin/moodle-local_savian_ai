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
 * Document management page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// Get course context if courseid provided, otherwise system context.
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid > 0) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}

require_capability('local/savian_ai:use', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$docid = optional_param('docid', 0, PARAM_INT);
// The courseid is already loaded above for context.

$PAGE->set_url(new moodle_url('/local/savian_ai/documents.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('documents', 'local_savian_ai'));
$PAGE->set_heading(get_string('documents', 'local_savian_ai'));

$client = new \local_savian_ai\api\client();

// Handle actions.
if ($action === 'delete' && $docid && confirm_sesskey()) {
    require_capability('local/savian_ai:generate', $context);

    $response = $client->delete_document($docid);

    if ($response->http_code === 200 && isset($response->success) && $response->success) {
        // Update local record.
        $DB->set_field('local_savian_documents', 'is_active', 0, ['savian_doc_id' => $docid]);
        redirect(new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]),
                 get_string('document_deleted', 'local_savian_ai'), null, 'success');
    } else {
        $error = $response->error ?? $response->message ?? 'Unknown error';
        redirect(new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]),
                 get_string('document_delete_failed', 'local_savian_ai', $error), null, 'error');
    }
}

// Handle form submission.
$mform = new \local_savian_ai\form\upload_document_form(null, ['courseid' => $courseid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]));
} else if ($data = $mform->get_data()) {
    // Get courseid from form data if available.
    $courseid = $data->courseid ?? $courseid;
    require_capability('local/savian_ai:generate', $context);

    // Save uploaded file to temp location.
    $fs = get_file_storage();
    $context = context_system::instance();
    $file = $mform->get_file_content('document');

    if ($file) {
        $filename = $mform->get_new_filename('document');
        $filepath = $CFG->tempdir . '/' . $filename;
        $mform->save_file('document', $filepath, true);

        // Prepare metadata.
        $tags = !empty($data->tags) ? array_map('trim', explode(',', $data->tags)) : [];

        // Get course name if uploading to a course.
        $coursename = null;
        if ($courseid > 0) {
            $courserecord = $DB->get_record('course', ['id' => $courseid], 'fullname', IGNORE_MISSING);
            $coursename = $courserecord ? $courserecord->fullname : null;
        }

        $metadata = [
            'description' => $data->description ?? '',
            'subject_area' => $data->subject_area ?? '',
            'tags' => $tags,
            'course_id' => $courseid > 0 ? $courseid : null,
            'course_name' => $coursename,
        ];

        // Prepare document upload with course context.

        // Upload to Savian API.
        $response = $client->upload_document($filepath, $data->title, $metadata);

        // Clean up temp file.
        @unlink($filepath);

        if ($response->http_code === 200 && isset($response->success) && $response->success) {
            // Save to local database.
            $record = new stdClass();
            $record->savian_doc_id = $response->document_id;
            $record->course_id = $courseid > 0 ? $courseid : null;
            $record->title = $data->title;
            $record->description = $data->description ?? '';
            $record->subject_area = $data->subject_area ?? '';
            $record->status = $response->status ?? 'pending';
            $record->progress = 0;
            $record->tags = json_encode($tags);
            $record->is_active = 1;
            $record->timecreated = time();
            $record->timemodified = time();
            $record->usermodified = $USER->id;

            $DB->insert_record('local_savian_documents', $record);

            redirect(new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]),
                     get_string('document_uploaded', 'local_savian_ai'), null, 'success');
        } else {
            $error = $response->error ?? $response->message ?? 'Unknown error';
            echo $OUTPUT->notification(
                get_string('document_upload_failed', 'local_savian_ai', $error),
                'error'
            );
        }
    }
}

echo $OUTPUT->header();

// Consistent header.
echo local_savian_ai_render_header('Documents', 'Upload and manage documents for AI generation');

// Display current course context.
if ($courseid > 0) {
    $courserecord = $DB->get_record('course', ['id' => $courseid], 'fullname', IGNORE_MISSING);
    if ($courserecord) {
        echo html_writer::start_div('alert alert-info mb-4');
        echo html_writer::tag('strong', '📚 Course: ');
        echo html_writer::tag('span', s($courserecord->fullname), ['class' => 'badge badge-primary badge-lg ml-2']);
        echo html_writer::tag('p', 'Documents uploaded here are available only in this course.', ['class' => 'mb-0 mt-2 small']);
        echo html_writer::end_div();
    }
}

// Sync documents from API (always sync to get current org's documents).
$syncresponse = $client->get_documents(['per_page' => 100]);

if ($syncresponse->http_code === 200 && isset($syncresponse->documents)) {
    // Update local cache with documents from current organization.
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

        // Get course_id from API (field is moodle_course_id in API response).
        $apicourseid = $doc->moodle_course_id ?? $doc->course_id ?? null;

        if ($existing) {
            $record->id = $existing->id;
            // Keep existing course_id, but if it is NULL and API has one, use the API value.
            $record->course_id = $existing->course_id ?: $apicourseid;
            $record->timecreated = $existing->timecreated;
            $record->usermodified = $existing->usermodified;
            $DB->update_record('local_savian_documents', $record);
        } else {
            // For new documents synced from API, use the course_id from the API response.
            $record->course_id = $apicourseid;
            $record->timecreated = time();
            $record->usermodified = 0;
            $DB->insert_record('local_savian_documents', $record);
        }
    }
}

// Display upload form (collapsible) - teachers can upload.
if (has_capability('local/savian_ai:generate', $context)) {
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header');
    echo html_writer::tag('a', '+ ' . get_string('upload_document', 'local_savian_ai'), [
        'href' => '#uploadForm',
        'data-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'class' => 'btn btn-savian btn-sm',
    ]);
    echo html_writer::end_div();
    echo html_writer::start_div('collapse', ['id' => 'uploadForm']);
    echo html_writer::start_div('card-body');
    $mform->display();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Display document list.
echo html_writer::tag('h3', get_string('document_list', 'local_savian_ai'), ['class' => 'mt-4']);

// Show only current course documents (no global library).
$documents = $DB->get_records('local_savian_documents', [
    'is_active' => 1,
    'course_id' => $courseid
], 'timecreated DESC');

if (empty($documents)) {
    echo html_writer::div(
        get_string('no_documents', 'local_savian_ai'),
        'alert alert-info'
    );
} else {
    $table = new html_table();
    $table->head = [
        get_string('document_title', 'local_savian_ai'),
        get_string('document_subject', 'local_savian_ai'),
        get_string('document_status', 'local_savian_ai'),
        get_string('document_size', 'local_savian_ai'),
        get_string('timecreated', 'moodle'),
        get_string('actions', 'moodle'),
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($documents as $doc) {
        $row = [];

        // Title.
        $row[] = html_writer::tag('strong', s($doc->title)) .
                 ($doc->description ? html_writer::div(s($doc->description), 'small text-muted') : '');

        // Subject area.
        $row[] = s($doc->subject_area);

        // Status with progress - simplified for end users.
        $statusclass = '';
        $statusdisplay = '';

        switch ($doc->status) {
            case 'completed':
                $statusclass = 'badge-success';
                $statusdisplay = get_string('status_ready', 'local_savian_ai');
                break;
            case 'failed':
                $statusclass = 'badge-danger';
                $statusdisplay = get_string('status_failed', 'local_savian_ai');
                break;
            case 'pending':
            case 'uploading':
                $statusclass = 'badge-secondary';
                $statusdisplay = get_string('status_uploading', 'local_savian_ai');
                break;
            default:
                // All processing states: processing, embedding, generating_questions, generating_qnas.
                $statusclass = 'badge-savian-processing';
                $statusdisplay = get_string('status_processing_simple', 'local_savian_ai');
                if ($doc->progress > 0) {
                    $statusdisplay .= " ({$doc->progress}%)";
                }
        }

        $row[] = html_writer::tag('span', $statusdisplay, ['class' => "badge {$statusclass}"]);

        // File size instead of chunks (more meaningful to users).
        if ($doc->file_size > 0) {
            $sizemb = round($doc->file_size / 1024 / 1024, 1);
            $row[] = $sizemb > 0 ? $sizemb . ' MB' : round($doc->file_size / 1024) . ' KB';
        } else {
            $row[] = '-';
        }

        // Created date.
        $row[] = userdate($doc->timecreated, get_string('strftimedatetime', 'langconfig'));

        // Actions (teachers can delete their own documents).
        $actions = [];
        $canmanage = has_capability('local/savian_ai:generate', $context);
        $isowner = ($doc->usermodified == $USER->id);

        if ($canmanage || $isowner) {
            $actions[] = html_writer::link(
                new moodle_url('/local/savian_ai/documents.php', [
                    'action' => 'delete',
                    'docid' => $doc->savian_doc_id,
                    'courseid' => $courseid,
                    'sesskey' => sesskey(),
                ]),
                get_string('delete'),
                ['class' => 'btn btn-sm btn-danger', 'onclick' => 'return confirm("Are you sure?");']
            );
        }
        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Add auto-refresh for processing documents.
    $hasprocessing = false;
    foreach ($documents as $doc) {
        if (in_array($doc->status, ['processing', 'pending', 'embedding'])) {
            $hasprocessing = true;
            break;
        }
    }

    if ($hasprocessing) {
        echo html_writer::div(
            '🔄 ' . get_string('auto_refresh_notice', 'local_savian_ai'),
            'alert alert-info small mt-3'
        );
        $PAGE->requires->js_amd_inline("
            setTimeout(function() {
                window.location.reload();
            }, 30000);
        ");
    }
}

// Back button.
$backurl = $courseid > 0
    ? new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid])
    : new moodle_url('/local/savian_ai/index.php');

echo html_writer::div(
    html_writer::link($backurl, '← Back', ['class' => 'btn btn-secondary']),
    'mt-4'
);

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
