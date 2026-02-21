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
 * Writing practice teacher dashboard.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$action   = optional_param('action', '', PARAM_ALPHA);
$taskid   = optional_param('taskid', 0, PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

if (!has_capability('local/savian_ai:generate', $context)) {
    if (has_capability('local/savian_ai:use', $context)) {
        redirect(new moodle_url('/local/savian_ai/writing_submit.php', ['courseid' => $courseid]));
    }
    require_capability('local/savian_ai:generate', $context);
}

$PAGE->set_url(new moodle_url('/local/savian_ai/writing_practice.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('writing_practice', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Handle soft-delete.
if ($action === 'deletetask' && $taskid > 0) {
    require_sesskey();
    $taskrecord = $DB->get_record('local_savian_ai_writing_tasks', ['id' => $taskid, 'course_id' => $courseid]);
    if ($taskrecord) {
        $client = new \local_savian_ai\api\client();
        if (!empty($taskrecord->api_task_id)) {
            $client->delete_writing_task($taskrecord->api_task_id);
        }
        $DB->set_field('local_savian_ai_writing_tasks', 'is_active', 0, ['id' => $taskid]);
        local_savian_ai_grade_item_delete($taskrecord);
        \core\notification::success(get_string('writing_practice_delete_task', 'local_savian_ai'));
    }
    redirect(new moodle_url('/local/savian_ai/writing_practice.php', ['courseid' => $courseid]));
}

echo $OUTPUT->header();
echo local_savian_ai_render_header(get_string('writing_practice', 'local_savian_ai'), $course->fullname);

// Create New Task button.
$createurl = new moodle_url('/local/savian_ai/writing_practice_task.php', ['courseid' => $courseid]);
$createlabel = get_string('writing_practice_create_task', 'local_savian_ai');
echo html_writer::div(
    html_writer::link($createurl, $createlabel, ['class' => 'btn btn-primary mb-3']),
    'mb-3'
);

// Task list.
$tasks = $DB->get_records(
    'local_savian_ai_writing_tasks',
    ['course_id' => $courseid, 'is_active' => 1],
    'timecreated DESC'
);

echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('writing_practice_manage_tasks', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');

if (empty($tasks)) {
    echo html_writer::tag('p', get_string('writing_practice_no_tasks', 'local_savian_ai'), ['class' => 'text-muted']);
} else {
    echo html_writer::start_tag('table', ['class' => 'table table-striped']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('title', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('writing_practice_task_type', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('writing_practice_exam_type', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('writing_practice_submission_count', 'local_savian_ai'));
    echo html_writer::tag('th', get_string('date'));
    echo html_writer::tag('th', get_string('actions'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($tasks as $task) {
        $subcount = $DB->count_records('local_savian_ai_writing_submissions', ['writing_task_id' => $task->id]);
        $deleteurl = new moodle_url('/local/savian_ai/writing_practice.php', [
            'courseid' => $courseid,
            'action'   => 'deletetask',
            'taskid'   => $task->id,
            'sesskey'  => sesskey(),
        ]);
        $deletelink = html_writer::link(
            $deleteurl,
            get_string('writing_practice_delete_task', 'local_savian_ai'),
            ['class' => 'btn btn-danger btn-sm']
        );
        $titledisplay = s($task->title);
        if (empty($task->api_task_id)) {
            $titledisplay .= ' ' . html_writer::tag(
                'span',
                get_string('writing_practice_task_invalid', 'local_savian_ai'),
                ['class' => 'badge badge-warning']
            );
        }
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $titledisplay);
        echo html_writer::tag('td', s($task->task_type));
        echo html_writer::tag('td', s($task->exam_type));
        echo html_writer::tag('td', $subcount);
        echo html_writer::tag('td', userdate($task->timecreated, get_string('strftimedate', 'langconfig')));
        echo html_writer::tag('td', $deletelink);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo html_writer::end_div();
echo html_writer::end_div();

// Class report + at-risk (only when tasks exist).
if (!empty($tasks)) {
    $client = new \local_savian_ai\api\client();

    // Class report.
    $reportresponse = $client->get_writing_class_report($courseid);
    if (isset($reportresponse->http_code) && $reportresponse->http_code === 200) {
        echo html_writer::start_div('card mb-4');
        echo html_writer::div(get_string('writing_practice_class_report', 'local_savian_ai'), 'card-header');
        echo html_writer::start_div('card-body');

        $summary = $reportresponse->summary ?? null;
        if ($summary) {
            echo html_writer::start_div('row mb-3');
            $stats = [
                'total_submissions' => 'Total Submissions',
                'unique_students'   => 'Unique Students',
                'avg_cefr'          => 'Avg CEFR',
                'avg_errors'        => 'Avg Errors',
            ];
            foreach ($stats as $key => $label) {
                if (isset($summary->$key)) {
                    echo html_writer::start_div('col-md-3');
                    echo html_writer::tag('div', s((string) $summary->$key), ['class' => 'h4 savian-text-primary']);
                    echo html_writer::tag('small', $label, ['class' => 'text-muted']);
                    echo html_writer::end_div();
                }
            }
            echo html_writer::end_div();
        }

        $students = $reportresponse->students ?? [];
        if (!empty($students)) {
            // Collect all moodle_user_ids and pre-load full names.
            $stuuserids = array_filter(array_map(function ($s) {
                return (int) ($s->moodle_user_id ?? 0);
            }, $students));
            $namefields = 'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename';
            $stuurecords = empty($stuuserids)
                ? []
                : $DB->get_records_list('user', 'id', $stuuserids, '', $namefields);

            echo html_writer::start_tag('table', ['class' => 'table table-sm']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('fullname'));
            echo html_writer::tag('th', get_string('writing_practice_cefr_level', 'local_savian_ai'));
            echo html_writer::tag('th', get_string('writing_practice_submission_count', 'local_savian_ai'));
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            foreach ($students as $stu) {
                $uid = (int) ($stu->moodle_user_id ?? 0);
                $urec = $stuurecords[$uid] ?? null;
                $fullname = $urec ? fullname($urec) : ($stu->name ?? s((string) $uid));
                $cefr = $stu->cefr_level ?? $stu->latest_cefr ?? '';
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', s($fullname));
                echo html_writer::tag('td', s($cefr));
                echo html_writer::tag('td', (int) ($stu->submission_count ?? 0));
                echo html_writer::end_tag('tr');
            }
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // At-risk students.
    $atriskresponse = $client->get_writing_at_risk_students($courseid);
    if (isset($atriskresponse->http_code) && $atriskresponse->http_code === 200) {
        $atrisk = $atriskresponse->at_risk_students ?? $atriskresponse->students ?? [];
        if (!empty($atrisk)) {
            // Pre-load user names.
            $riskuserids = array_filter(array_map(function ($s) {
                return (int) ($s->moodle_user_id ?? 0);
            }, $atrisk));
            $riskfields = 'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename';
            $riskurecords = empty($riskuserids)
                ? []
                : $DB->get_records_list('user', 'id', $riskuserids, '', $riskfields);

            echo html_writer::start_div('card mb-4 border-warning');
            echo html_writer::div(get_string('writing_practice_atrisk', 'local_savian_ai'), 'card-header');
            echo html_writer::start_div('card-body');
            echo html_writer::tag('p', get_string('writing_practice_atrisk_desc', 'local_savian_ai'), ['class' => 'text-muted']);

            echo html_writer::start_tag('table', ['class' => 'table table-sm']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('fullname'));
            echo html_writer::tag('th', get_string('writing_practice_risk_factors', 'local_savian_ai'));
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            foreach ($atrisk as $stu) {
                $uid = (int) ($stu->moodle_user_id ?? 0);
                $urec = $riskurecords[$uid] ?? null;
                $fullname = $urec ? fullname($urec) : ($stu->name ?? s((string) $uid));
                $riskfactors = is_array($stu->risk_factors ?? null)
                    ? implode('; ', array_map('strval', $stu->risk_factors))
                    : (string) ($stu->risk_factors ?? '');
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', s($fullname));
                echo html_writer::tag('td', s($riskfactors));
                echo html_writer::end_tag('tr');
            }
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');

            echo html_writer::end_div();
            echo html_writer::end_div();
        }
    }
}

echo local_savian_ai_render_footer();
echo $OUTPUT->footer();
