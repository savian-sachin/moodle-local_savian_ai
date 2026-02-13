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
 * Save to knowledge base endpoint.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/save_to_knowledge_base.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title('Save to Knowledge Base');
$PAGE->set_heading($course->fullname);

// Get stored generation data from cache.
$saviancache = cache::make('local_savian_ai', 'session_data');
$savedata = $saviancache->get('kb_save_data');

if (!$savedata) {
    redirect(new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
             get_string('no_course_data', 'local_savian_ai'), null, 'error');
}

$coursestructurejson = $savedata['course_structure'];
$coursestructure = json_decode($coursestructurejson);
$coursetitle = $savedata['course_title'];
$requestid = $savedata['request_id'];
$results = $savedata['results'];

echo $OUTPUT->header();

// Consistent header.
echo local_savian_ai_render_header('Save to Knowledge Base', 'Contribute approved content to institutional knowledge');

// Initialize API client.
$client = new \local_savian_ai\api\client();

// Get instructor name.
$instructorname = fullname($USER);

// Call Savian API.
try {
    $response = $client->save_approved_course_to_knowledge_base(
        $coursestructure,
        $coursetitle,
        $courseid,
        $instructorname,
        $requestid
    );

    if ($response->http_code === 200 && isset($response->success) && $response->success) {
        // Success notification.
        echo html_writer::start_div('alert alert-success', ['style' => 'border-left: 4px solid #28a745;']);
        echo html_writer::tag('h4', '✅ Course Saved to Knowledge Base!');
        echo html_writer::tag('p', 'Your approved course content has been saved and is being processed.');
        echo html_writer::end_div();

        echo html_writer::start_div('card mb-4');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', 'What happens next:', ['class' => 'card-title']);

        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', '<strong>Processing:</strong> 2-3 minutes (chunking and embedding)');
        echo html_writer::tag('li', '<strong>Document Name:</strong> "' . s($coursetitle) . ' (Instructor Approved)"');
        echo html_writer::tag('li', '<strong>Availability:</strong> Will appear in document list');
        echo html_writer::tag('li', '<strong>Usage:</strong> Future course generations can use this content');
        echo html_writer::tag('li', '<strong>Chat:</strong> Students can ask questions about this course');
        echo html_writer::end_tag('ul');

        echo html_writer::start_div('mt-3');
        echo html_writer::tag('p', '<strong>Document ID:</strong> ' . ($response->document_id ?? 'N/A'));
        echo html_writer::tag('p', '<strong>Status:</strong> ' . ucfirst($response->status ?? 'Processing'));
        echo html_writer::end_div();

        echo html_writer::end_div();
        echo html_writer::end_div();

        // Clear cache.
        $saviancache->delete('kb_save_data');

        // Go to course button.
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/course/view.php', ['id' => $courseid]),
                'Go to Course',
                ['class' => 'btn btn-savian btn-lg']
            ),
            'text-center mt-4'
        );

    } else {
        // Error notification.
        $error = $response->error ?? $response->message ?? 'Unknown error';
        echo html_writer::start_div('alert alert-danger');
        echo html_writer::tag('h4', '❌ Save Failed');
        echo html_writer::tag('p', 'Error: ' . s($error));
        echo html_writer::link('javascript:history.back()', 'Go Back', ['class' => 'btn btn-secondary mt-2']);
        echo html_writer::end_div();
    }

} catch (Exception $e) {
    echo html_writer::start_div('alert alert-danger');
    echo html_writer::tag('h4', '❌ Error');
    echo html_writer::tag('p', s($e->getMessage()));
    echo html_writer::link('javascript:history.back()', 'Go Back', ['class' => 'btn btn-secondary mt-2']);
    echo html_writer::end_div();
}

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
