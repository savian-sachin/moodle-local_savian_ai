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
 * Create writing task page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/writing_practice_task.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('writing_practice_create_task', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Handle POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $title = required_param('title', PARAM_TEXT);
    $prompt = required_param('prompt', PARAM_TEXT);
    $tasktype = optional_param('task_type', 'essay', PARAM_ALPHANUMEXT);
    $examtype = optional_param('exam_type', 'general', PARAM_ALPHANUMEXT);
    $targetcefr = optional_param('target_cefr_level', '', PARAM_ALPHANUMEXT);
    $wordcountmin = optional_param('word_count_min', 150, PARAM_INT);
    $wordcountmax = optional_param('word_count_max', 300, PARAM_INT);
    $timelimit = optional_param('time_limit_minutes', 0, PARAM_INT);
    $includeimproved = optional_param('include_improved_writing', 0, PARAM_INT);
    $language = optional_param('language', 'en', PARAM_ALPHANUMEXT);

    $title = clean_param(trim($title), PARAM_TEXT);
    $prompt = clean_param(trim($prompt), PARAM_TEXT);

    if (empty($title)) {
        \core\notification::error(get_string('required'));
    } else if (core_text::strlen($title) > 200) {
        \core\notification::error(get_string('required'));
    } else {
        $apidata = [
            'title'                       => $title,
            'prompt'                      => $prompt,
            'task_type'                   => $tasktype,
            'exam_type'                   => $examtype,
            'target_cefr_level'           => $targetcefr ?: null,
            'word_count_min'              => $wordcountmin,
            'word_count_max'              => $wordcountmax,
            'time_limit_minutes'          => $timelimit > 0 ? $timelimit : null,
            'include_improved_writing'    => (bool) $includeimproved,
            'language'                    => $language,
            'moodle_course_id'            => (int) $courseid,
            'created_by_moodle_user_id'   => (string) $USER->id,
        ];

        $client = new \local_savian_ai\api\client();
        $response = $client->create_writing_task($apidata);

        // Validate the API response before saving to DB.
        $apitaskid = (int) ($response->task_id ?? $response->id ?? 0);
        $apierror = $response->error ?? '';
        $apisuccess = !empty($response->success) && $apitaskid > 0;

        if (!$apisuccess) {
            $errmsg = $apierror ?: get_string('writing_practice_api_error', 'local_savian_ai');
            \core\notification::error($errmsg);
        } else {
            $record = new stdClass();
            $record->api_task_id              = $apitaskid;
            $record->course_id                = $courseid;
            $record->teacher_user_id          = $USER->id;
            $record->title                    = $title;
            $record->prompt                   = $prompt;
            $record->task_type                = $tasktype;
            $record->exam_type                = $examtype;
            $record->target_cefr_level        = $targetcefr ?: null;
            $record->word_count_min           = $wordcountmin;
            $record->word_count_max           = $wordcountmax;
            $record->time_limit_minutes       = $timelimit > 0 ? $timelimit : null;
            $record->include_improved_writing = (int) $includeimproved;
            $record->language                 = $language;
            $record->is_active                = 1;
            $record->timecreated              = time();
            $record->timemodified             = time();

            $newid = $DB->insert_record('local_savian_ai_writing_tasks', $record);
            $taskrecord = $DB->get_record('local_savian_ai_writing_tasks', ['id' => $newid], '*', MUST_EXIST);
            local_savian_ai_grade_item_update($taskrecord);

            \core\notification::success(get_string('writing_practice_create_task', 'local_savian_ai'));
            redirect(new moodle_url('/local/savian_ai/writing_practice.php', ['courseid' => $courseid]));
        }
    }
}

echo $OUTPUT->header();
echo local_savian_ai_render_header(
    get_string('writing_practice_create_task', 'local_savian_ai'),
    $course->fullname
);

// Form.
$formurl = new moodle_url('/local/savian_ai/writing_practice_task.php', ['courseid' => $courseid]);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');

// Title.
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('title', 'local_savian_ai') . ' *', ['for' => 'wp-title']);
echo html_writer::empty_tag('input', [
    'type'        => 'text',
    'id'          => 'wp-title',
    'name'        => 'title',
    'class'       => 'form-control',
    'maxlength'   => '200',
    'required'    => 'required',
    'placeholder' => get_string('writing_practice_title_placeholder', 'local_savian_ai'),
]);
echo html_writer::end_div();

// Prompt.
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('writing_practice_prompt', 'local_savian_ai') . ' *', ['for' => 'wp-prompt']);
echo html_writer::tag('textarea', '', [
    'id'          => 'wp-prompt',
    'name'        => 'prompt',
    'class'       => 'form-control savian-wp-textarea',
    'rows'        => '5',
    'required'    => 'required',
    'placeholder' => get_string('writing_practice_prompt_placeholder', 'local_savian_ai'),
]);
echo html_writer::end_div();

// Task type.
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('writing_practice_task_type', 'local_savian_ai'), ['for' => 'wp-task-type']);
$tasktypes = [
    'essay'  => get_string('writing_practice_task_type_essay', 'local_savian_ai'),
    'email'  => get_string('writing_practice_task_type_email', 'local_savian_ai'),
    'letter' => get_string('writing_practice_task_type_letter', 'local_savian_ai'),
    'report' => get_string('writing_practice_task_type_report', 'local_savian_ai'),
    'review' => get_string('writing_practice_task_type_review', 'local_savian_ai'),
    'story'  => get_string('writing_practice_task_type_story', 'local_savian_ai'),
];
$tthtml = '';
foreach ($tasktypes as $val => $label) {
    $attrs = ['value' => $val];
    if ($val === 'essay') {
        $attrs['selected'] = 'selected';
    }
    $tthtml .= html_writer::tag('option', $label, $attrs);
}
echo html_writer::tag('select', $tthtml, ['id' => 'wp-task-type', 'name' => 'task_type', 'class' => 'form-control']);
echo html_writer::end_div();

// Exam type.
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('writing_practice_exam_type', 'local_savian_ai'), ['for' => 'wp-exam-type']);
$examtypes = [
    'general'        => get_string('writing_practice_exam_type_general', 'local_savian_ai'),
    'ielts_task1'    => get_string('writing_practice_exam_type_ielts_task1', 'local_savian_ai'),
    'ielts_task2'    => get_string('writing_practice_exam_type_ielts_task2', 'local_savian_ai'),
    'cambridge_b1'   => get_string('writing_practice_exam_type_cambridge_b1', 'local_savian_ai'),
    'cambridge_b2'   => get_string('writing_practice_exam_type_cambridge_b2', 'local_savian_ai'),
];
$ethtml = '';
foreach ($examtypes as $val => $label) {
    $attrs = ['value' => $val];
    if ($val === 'general') {
        $attrs['selected'] = 'selected';
    }
    $ethtml .= html_writer::tag('option', $label, $attrs);
}
echo html_writer::tag('select', $ethtml, ['id' => 'wp-exam-type', 'name' => 'exam_type', 'class' => 'form-control']);
echo html_writer::end_div();

// Target CEFR.
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('writing_practice_target_cefr', 'local_savian_ai'), ['for' => 'wp-cefr']);
$cefrlevels = ['' => '—', 'A1' => 'A1', 'A2' => 'A2', 'B1' => 'B1', 'B2' => 'B2', 'C1' => 'C1', 'C2' => 'C2'];
$cefrhtml = '';
foreach ($cefrlevels as $val => $label) {
    $cefrhtml .= html_writer::tag('option', $label, ['value' => $val]);
}
echo html_writer::tag('select', $cefrhtml, ['id' => 'wp-cefr', 'name' => 'target_cefr_level', 'class' => 'form-control']);
echo html_writer::end_div();

// Word count row.
echo html_writer::start_div('form-group row');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('writing_practice_word_count_min', 'local_savian_ai'), ['for' => 'wp-wc-min']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'id' => 'wp-wc-min', 'name' => 'word_count_min',
    'class' => 'form-control', 'value' => '150', 'min' => '50', 'max' => '2000',
]);
echo html_writer::end_div();
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('writing_practice_word_count_max', 'local_savian_ai'), ['for' => 'wp-wc-max']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'id' => 'wp-wc-max', 'name' => 'word_count_max',
    'class' => 'form-control', 'value' => '300', 'min' => '50', 'max' => '5000',
]);
echo html_writer::end_div();
echo html_writer::end_div();

// Include improved writing.
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 'id' => 'wp-improved', 'name' => 'include_improved_writing',
    'class' => 'form-check-input', 'value' => '1', 'checked' => 'checked',
]);
echo html_writer::tag('label',
    get_string('writing_practice_include_improved', 'local_savian_ai'),
    ['for' => 'wp-improved', 'class' => 'form-check-label']
);
echo html_writer::end_div();

// Buttons.
$submitlabel = get_string('writing_practice_create_task', 'local_savian_ai');
$cancellabel = get_string('cancel');
$cancelurl = new moodle_url('/local/savian_ai/writing_practice.php', ['courseid' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary mr-2', 'value' => $submitlabel]);
echo html_writer::link($cancelurl, $cancellabel, ['class' => 'btn btn-secondary']);

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo local_savian_ai_render_footer();
echo $OUTPUT->footer();
