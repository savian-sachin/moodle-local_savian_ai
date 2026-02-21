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
 * Writing practice submission and feedback page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$courseid       = required_param('courseid', PARAM_INT);
$action         = optional_param('action', '', PARAM_ALPHA);
$submissionuuid = optional_param('submissionuuid', '', PARAM_ALPHANUMEXT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:use', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/writing_submit.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('writing_practice', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// POST: submit writing.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === '') {
    require_sesskey();

    $taskid = required_param('writing_task_id', PARAM_INT);
    $text   = required_param('writing_text', PARAM_TEXT);

    $taskrecord = $DB->get_record(
        'local_savian_ai_writing_tasks',
        ['id' => $taskid, 'course_id' => $courseid, 'is_active' => 1],
        '*',
        MUST_EXIST
    );

    // Guard: task must be synced with the API (api_task_id > 0).
    if (empty($taskrecord->api_task_id)) {
        \core\notification::error(get_string('writing_practice_task_not_synced', 'local_savian_ai'));
        redirect(new moodle_url('/local/savian_ai/writing_submit.php', ['courseid' => $courseid]));
    }

    $text = clean_param(trim($text), PARAM_TEXT);
    $wordcount = str_word_count($text);

    $apidata = [
        'task_id'           => (int) $taskrecord->api_task_id,
        'submission_text'   => $text,
        'moodle_user_id'    => (string) $USER->id,
        'word_count'        => $wordcount,
    ];

    $client   = new \local_savian_ai\api\client();
    $response = $client->submit_writing($apidata);

    $uuid = $response->submission_id ?? $response->uuid ?? \core\uuid::generate();

    $record                   = new stdClass();
    $record->submission_uuid  = $uuid;
    $record->writing_task_id  = $taskrecord->id;
    $record->api_task_id      = $taskrecord->api_task_id;
    $record->moodle_user_id   = $USER->id;
    $record->status           = 'pending';
    $record->progress         = 0;
    $record->stage            = '';
    $record->word_count       = $wordcount;
    $record->submission_text  = $text;
    $record->feedback_json    = null;
    $record->error_message    = null;
    $record->timecreated      = time();
    $record->timemodified     = time();

    $DB->insert_record('local_savian_ai_writing_submissions', $record);

    $cache = \cache::make('local_savian_ai', 'session_data');
    $cache->set('wp_submission_uuid_' . $courseid, $uuid);

    $pollurl = new moodle_url('/local/savian_ai/writing_submit.php', [
        'courseid'       => $courseid,
        'action'         => 'poll',
        'submissionuuid' => $uuid,
    ]);
    redirect($pollurl);
}

// Poll action.
if ($action === 'poll') {
    if (empty($submissionuuid)) {
        $cache          = \cache::make('local_savian_ai', 'session_data');
        $submissionuuid = $cache->get('wp_submission_uuid_' . $courseid) ?: '';
    }

    $feedbackurl = new moodle_url('/local/savian_ai/writing_submit.php', [
        'courseid'       => $courseid,
        'action'         => 'feedback',
        'submissionuuid' => $submissionuuid,
    ]);
    $timeoutmsg = get_string('writing_practice_timeout', 'local_savian_ai');

    echo $OUTPUT->header();
    echo local_savian_ai_render_header(
        get_string('writing_practice_polling_heading', 'local_savian_ai'),
        $course->fullname
    );
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag(
        'p',
        get_string('writing_practice_polling_desc', 'local_savian_ai'),
        ['class' => 'mb-4']
    );
    echo html_writer::start_div('progress mb-2');
    echo html_writer::div(
        '0%',
        'progress-bar progress-bar-striped progress-bar-animated bg-success',
        [
            'id'              => 'savian-wp-progress-bar',
            'role'            => 'progressbar',
            'aria-valuenow'   => '0',
            'aria-valuemin'   => '0',
            'aria-valuemax'   => '100',
            'style'           => 'width: 0%',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::tag('p', '', ['id' => 'savian-wp-stage-label', 'class' => 'savian-wp-progress-stage']);
    echo html_writer::tag('span', $timeoutmsg, ['id' => 'savian-wp-timeout-msg', 'class' => 'd-none']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    $PAGE->requires->js_call_amd('local_savian_ai/writing_practice', 'init', [[
        'mode'           => 'poll',
        'submissionuuid' => $submissionuuid,
        'courseid'       => (int) $courseid,
        'feedbackurl'    => $feedbackurl->out(false),
    ]]);

    echo local_savian_ai_render_footer();
    echo $OUTPUT->footer();
    die;
}

// Feedback action.
if ($action === 'feedback') {
    if (empty($submissionuuid)) {
        $cache          = \cache::make('local_savian_ai', 'session_data');
        $submissionuuid = $cache->get('wp_submission_uuid_' . $courseid) ?: '';
    }

    $submission = null;
    if (!empty($submissionuuid)) {
        $submission = $DB->get_record(
            'local_savian_ai_writing_submissions',
            ['submission_uuid' => $submissionuuid, 'moodle_user_id' => $USER->id]
        );
    }

    echo $OUTPUT->header();
    echo local_savian_ai_render_header(
        get_string('writing_practice_feedback_heading', 'local_savian_ai'),
        $course->fullname
    );

    if (!$submission || empty($submission->feedback_json)) {
        echo html_writer::tag('p', get_string('nothingtodisplay'), ['class' => 'alert alert-info']);
    } else {
        $feedback = json_decode($submission->feedback_json);

        // CEFR badge.
        $cefrlevel = $feedback->cefr->level ?? '';
        if ($cefrlevel) {
            echo html_writer::start_div('card mb-4');
            echo html_writer::div(get_string('writing_practice_cefr_level', 'local_savian_ai'), 'card-header');
            echo html_writer::start_div('card-body text-center');
            echo html_writer::tag('div', s($cefrlevel), ['class' => 'savian-wp-cefr-badge']);
            $reasoning = $feedback->cefr->reasoning ?? '';
            if ($reasoning) {
                echo html_writer::tag('p', s($reasoning), ['class' => 'mt-2 text-muted']);
            }
            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        // IELTS bands.
        $ielts = $feedback->ielts ?? null;
        if ($ielts) {
            echo html_writer::start_div('card mb-4');
            echo html_writer::div(get_string('writing_practice_ielts_scores', 'local_savian_ai'), 'card-header');
            echo html_writer::start_div('card-body');

            // Overall band — large prominent display.
            if (isset($ielts->overall_band)) {
                $overallval = (float) $ielts->overall_band;
                echo html_writer::start_div('text-center mb-4');
                $overalllabel = get_string('writing_practice_ielts_overall', 'local_savian_ai');
                echo html_writer::tag('div', $overalllabel, ['class' => 'savian-wp-ielts-overall-label']);
                echo html_writer::tag('div', s((string) $overallval), ['class' => 'savian-wp-ielts-overall-badge']);
                $outofstr = get_string('writing_practice_ielts_outof', 'local_savian_ai');
                echo html_writer::tag('div', $outofstr, ['class' => 'savian-wp-ielts-outof']);
                echo html_writer::end_div();
            }

            // Sub-scores with progress bars.
            $subscores = [
                'task_achievement'   => get_string('writing_practice_ielts_task_response', 'local_savian_ai'),
                'coherence_cohesion' => get_string('writing_practice_ielts_coherence', 'local_savian_ai'),
                'lexical_resource'   => get_string('writing_practice_ielts_lexical', 'local_savian_ai'),
                'grammar_accuracy'   => get_string('writing_practice_ielts_grammar', 'local_savian_ai'),
            ];
            foreach ($subscores as $key => $label) {
                if (!isset($ielts->$key)) {
                    continue;
                }
                $val = (float) $ielts->$key;
                $pct = round(($val / 9) * 100);
                echo html_writer::start_div('savian-wp-ielts-row mb-2');
                echo html_writer::start_div('d-flex justify-content-between mb-1');
                echo html_writer::tag('span', $label, ['class' => 'savian-wp-ielts-sublabel']);
                echo html_writer::tag('span', s((string) $val), ['class' => 'savian-wp-ielts-subval']);
                echo html_writer::end_div();
                echo html_writer::start_div('progress savian-wp-ielts-progress');
                echo html_writer::div('', 'progress-bar savian-wp-ielts-bar', [
                    'role'          => 'progressbar',
                    'style'         => 'width:' . $pct . '%',
                    'aria-valuenow' => $pct,
                    'aria-valuemin' => '0',
                    'aria-valuemax' => '100',
                ]);
                echo html_writer::end_div();
                echo html_writer::end_div();
            }

            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        // Original text with grammar highlighting.
        $errors = $feedback->grammar_errors ?? [];
        echo html_writer::start_div('card mb-4');
        echo html_writer::div(get_string('writing_practice_your_writing', 'local_savian_ai'), 'card-header');
        echo html_writer::start_div('card-body');
        // Show original text + grammar highlights via JS.
        $submissiontext = (string) ($submission->submission_text ?? '');
        echo html_writer::tag('p', s($submissiontext), [
            'id'    => 'savian-wp-submission-text',
            'class' => 'savian-wp-textarea',
            'style' => 'white-space: pre-wrap;',
        ]);
        // Hidden element with errors data.
        echo html_writer::tag('span', '', [
            'id'          => 'savian-wp-errors',
            'class'       => 'd-none',
            'data-errors' => json_encode($errors),
        ]);
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Main feedback.
        $fb = $feedback->feedback ?? null;
        if ($fb) {
            echo html_writer::start_div('card mb-4');
            echo html_writer::div(get_string('writing_practice_feedback_heading', 'local_savian_ai'), 'card-header');
            echo html_writer::start_div('card-body');
            $overall = is_object($fb->overall ?? null) ? '' : (string) ($fb->overall ?? '');
            if (!empty($overall)) {
                echo html_writer::tag('p', s($overall));
            }
            if (!empty($fb->strengths)) {
                echo html_writer::tag('h6', get_string('writing_practice_strengths', 'local_savian_ai'));
                $strengthshtml = '';
                foreach ((array) $fb->strengths as $strength) {
                    $strengthshtml .= html_writer::tag('li', s(is_string($strength) ? $strength : ''));
                }
                echo html_writer::tag('ul', $strengthshtml);
            }
            if (!empty($fb->improvements)) {
                echo html_writer::tag('h6', get_string('writing_practice_improvements', 'local_savian_ai'));
                $imphtml = '';
                foreach ((array) $fb->improvements as $improvement) {
                    $imphtml .= html_writer::tag('li', s(is_string($improvement) ? $improvement : ''));
                }
                echo html_writer::tag('ul', $imphtml);
            }
            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        // AI-improved version.
        $rawimproved = $feedback->improved_writing ?? '';
        if (is_object($rawimproved)) {
            $rawimproved = $rawimproved->text ?? $rawimproved->content ?? '';
        }
        $improved = (string) $rawimproved;
        if ($improved) {
            echo html_writer::start_div('card mb-4');
            echo html_writer::div(
                get_string('writing_practice_improved_writing', 'local_savian_ai'),
                'card-header'
            );
            echo html_writer::start_div('card-body');
            echo html_writer::tag(
                'p',
                s($improved),
                ['class' => 'savian-wp-diff-improved', 'style' => 'white-space: pre-wrap;']
            );
            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        // Grade.
        $gradeinfo = '';
        $ieltstypes = ['ielts_task1', 'ielts_task2'];
        $taskrecord = $DB->get_record(
            'local_savian_ai_writing_tasks',
            ['id' => $submission->writing_task_id]
        );
        if ($taskrecord) {
            if (in_array($taskrecord->exam_type, $ieltstypes)) {
                $band = $ielts->overall_band ?? null;
                if ($band !== null) {
                    $gradeinfo = s((string) $band) . ' / 9.0';
                }
            } else {
                $gradenum = local_savian_ai_cefr_to_grade($cefrlevel);
                if ($gradenum > 0) {
                    $gradeinfo = s((string) $gradenum) . ' / 6.0';
                }
            }
        }
        if ($gradeinfo) {
            echo html_writer::start_div('alert alert-success');
            echo html_writer::tag(
                'strong',
                get_string('writing_practice_your_grade', 'local_savian_ai') . ': '
            );
            echo $gradeinfo;
            echo html_writer::end_div();
        }
    }

    // Initialise JS for grammar highlighting (feedback mode).
    $PAGE->requires->js_call_amd('local_savian_ai/writing_practice', 'init', [[
        'mode' => 'feedback',
    ]]);

    // Submit another button.
    $submitanotherurl = new moodle_url('/local/savian_ai/writing_submit.php', ['courseid' => $courseid]);
    echo html_writer::div(
        html_writer::link(
            $submitanotherurl,
            get_string('writing_practice_submit', 'local_savian_ai'),
            ['class' => 'btn btn-primary']
        ),
        'mt-3'
    );

    echo local_savian_ai_render_footer();
    echo $OUTPUT->footer();
    die;
}

// Default: task selector + writing form.
$tasks = $DB->get_records(
    'local_savian_ai_writing_tasks',
    ['course_id' => $courseid, 'is_active' => 1],
    'timecreated DESC'
);

// Check for the user's most recent completed submission in this course.
$latestsub = $DB->get_record_sql(
    'SELECT ws.submission_uuid
       FROM {local_savian_ai_writing_submissions} ws
       JOIN {local_savian_ai_writing_tasks} wt ON wt.id = ws.writing_task_id
      WHERE ws.moodle_user_id = :uid
        AND wt.course_id = :cid
        AND ws.status = :status
   ORDER BY ws.timecreated DESC',
    ['uid' => $USER->id, 'cid' => $courseid, 'status' => 'completed'],
    IGNORE_MULTIPLE
);

echo $OUTPUT->header();
echo local_savian_ai_render_header(get_string('writing_practice', 'local_savian_ai'), $course->fullname);

// Banner linking back to most recent feedback.
if ($latestsub) {
    $lastfeedbackurl = new moodle_url('/local/savian_ai/writing_submit.php', [
        'courseid'       => $courseid,
        'action'         => 'feedback',
        'submissionuuid' => $latestsub->submission_uuid,
    ]);
    $linkhtml = html_writer::link(
        $lastfeedbackurl,
        get_string('writing_practice_view_last_feedback', 'local_savian_ai'),
        ['class' => 'btn btn-outline-primary btn-sm ml-2']
    );
    echo html_writer::div(
        get_string('writing_practice_has_feedback', 'local_savian_ai') . ' ' . $linkhtml,
        'alert alert-success d-flex align-items-center'
    );
}

if (empty($tasks)) {
    echo html_writer::tag('p', get_string('writing_practice_no_tasks', 'local_savian_ai'), ['class' => 'alert alert-info']);
    if (has_capability('local/savian_ai:generate', $context)) {
        $createtaskurl = new moodle_url('/local/savian_ai/writing_practice_task.php', ['courseid' => $courseid]);
        echo html_writer::link(
            $createtaskurl,
            get_string('writing_practice_create_task', 'local_savian_ai'),
            ['class' => 'btn btn-primary']
        );
    }
} else {
    $submiturl = new moodle_url('/local/savian_ai/writing_submit.php', ['courseid' => $courseid]);
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $submiturl->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => '']);

    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('writing_practice_select_task', 'local_savian_ai'), ['for' => 'wp-task-sel']);
    $taskopthtml = '';
    foreach ($tasks as $task) {
        $taskopthtml .= html_writer::tag('option', s($task->title), ['value' => $task->id]);
    }
    echo html_writer::tag(
        'select',
        $taskopthtml,
        ['id' => 'wp-task-sel', 'name' => 'writing_task_id', 'class' => 'form-control mb-3']
    );
    echo html_writer::end_div();

    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('writing_practice_your_writing', 'local_savian_ai'), ['for' => 'wp-text']);
    echo html_writer::tag('textarea', '', [
        'id'          => 'wp-text',
        'name'        => 'writing_text',
        'class'       => 'form-control savian-wp-textarea',
        'rows'        => '15',
        'required'    => 'required',
        'placeholder' => get_string('writing_practice_your_writing', 'local_savian_ai') . '...',
    ]);
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', [
        'type'  => 'submit',
        'class' => 'btn btn-primary mt-2',
        'value' => get_string('writing_practice_submit', 'local_savian_ai'),
    ]);
    echo html_writer::end_tag('form');
}

echo local_savian_ai_render_footer();
echo $OUTPUT->footer();
