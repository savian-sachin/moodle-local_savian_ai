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
 * External API for writing practice.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External functions for writing practice.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class writing extends external_api {

    /**
     * Returns parameter definitions for get_submission_status.
     *
     * @return external_function_parameters
     */
    public static function get_submission_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'submissionuuid' => new external_value(PARAM_ALPHANUMEXT, 'Submission UUID'),
            'courseid'       => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Poll the status of a writing submission and update the DB.
     *
     * If the submission is newly completed, fetches feedback and posts a grade.
     *
     * @param string $submissionuuid Submission UUID.
     * @param int $courseid Course ID.
     * @return array Status, progress, and stage.
     */
    public static function get_submission_status(string $submissionuuid, int $courseid): array {
        global $DB, $CFG;

        $params = self::validate_parameters(
            self::get_submission_status_parameters(),
            ['submissionuuid' => $submissionuuid, 'courseid' => $courseid]
        );
        $submissionuuid = $params['submissionuuid'];
        $courseid = $params['courseid'];

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/savian_ai:use', $context);

        $submission = $DB->get_record(
            'local_savian_ai_writing_submissions',
            ['submission_uuid' => $submissionuuid],
            '*',
            MUST_EXIST
        );

        // Already in terminal state — return cached result.
        if ($submission->status === 'completed' || $submission->status === 'failed') {
            return [
                'status'   => $submission->status,
                'progress' => (int) $submission->progress,
                'stage'    => (string) ($submission->stage ?? ''),
            ];
        }

        // Poll the API.
        $client = new \local_savian_ai\api\client();
        $response = $client->get_writing_submission_status($submissionuuid);

        $newstatus   = $response->status ?? $submission->status;
        $newprogress = (int) ($response->progress ?? $submission->progress);
        $newstage    = (string) ($response->stage ?? $submission->stage ?? '');

        // For failed status, surface the error field so JS can display it.
        if ($newstatus === 'failed' && empty($newstage) && !empty($response->error)) {
            $newstage = (string) $response->error;
        }

        // Persist updated status.
        $DB->set_field(
            'local_savian_ai_writing_submissions',
            'status',
            $newstatus,
            ['submission_uuid' => $submissionuuid]
        );
        $DB->set_field(
            'local_savian_ai_writing_submissions',
            'progress',
            $newprogress,
            ['submission_uuid' => $submissionuuid]
        );
        $DB->set_field(
            'local_savian_ai_writing_submissions',
            'stage',
            $newstage,
            ['submission_uuid' => $submissionuuid]
        );
        $DB->set_field(
            'local_savian_ai_writing_submissions',
            'timemodified',
            time(),
            ['submission_uuid' => $submissionuuid]
        );

        // If newly completed: fetch feedback and post grade.
        if ($newstatus === 'completed') {
            $feedback = $client->get_writing_submission_feedback($submissionuuid);

            if (isset($feedback->http_code) && $feedback->http_code === 200) {
                $feedbackjson = json_encode($feedback);
                $DB->set_field(
                    'local_savian_ai_writing_submissions',
                    'feedback_json',
                    $feedbackjson,
                    ['submission_uuid' => $submissionuuid]
                );

                // Determine grade value.
                $ieltstypes = ['ielts_task1', 'ielts_task2'];
                $task = $DB->get_record(
                    'local_savian_ai_writing_tasks',
                    ['id' => $submission->writing_task_id],
                    '*',
                    MUST_EXIST
                );

                if (in_array($task->exam_type, $ieltstypes)) {
                    $gradevalue = (float) ($feedback->ielts->overall_band ?? 0);
                } else {
                    $cefrlevel = $feedback->cefr->level ?? '';
                    $gradevalue = local_savian_ai_cefr_to_grade($cefrlevel);
                }

                if ($gradevalue > 0) {
                    $gradeobj = [
                        'userid'     => (int) $submission->moodle_user_id,
                        'rawgrade'   => $gradevalue,
                        'dategraded' => time(),
                    ];
                    local_savian_ai_grade_item_update($task, [$submission->moodle_user_id => (object) $gradeobj]);
                }
            }
        }

        return [
            'status'   => $newstatus,
            'progress' => $newprogress,
            'stage'    => $newstage,
        ];
    }

    /**
     * Returns the return value definition for get_submission_status.
     *
     * @return external_single_structure
     */
    public static function get_submission_status_returns(): external_single_structure {
        return new external_single_structure([
            'status'   => new external_value(PARAM_ALPHA, 'Submission status'),
            'progress' => new external_value(PARAM_INT, 'Progress 0-100'),
            'stage'    => new external_value(PARAM_TEXT, 'Current processing stage'),
        ]);
    }
}
