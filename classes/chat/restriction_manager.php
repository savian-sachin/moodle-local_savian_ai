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
 * Chat restriction manager.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\chat;

/**
 * Manager class for chat restrictions.
 *
 * Handles time-based chat restrictions for courses, including quiz-linked
 * and manual time range restrictions with per-group targeting.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restriction_manager {
    /**
     * Check if chat is restricted for a user in a course.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return object|null Restriction info or null if not restricted
     */
    public function get_active_restriction(int $courseid, int $userid): ?object {
        global $DB;

        $now = time();
        $usergroups = $this->get_user_group_ids($courseid, $userid);

        // Get all enabled restrictions for this course.
        $restrictions = $DB->get_records('local_savian_chat_restrictions', [
            'course_id' => $courseid,
            'is_enabled' => 1,
        ]);

        foreach ($restrictions as $restriction) {
            // Check if user is in a targeted group (or all groups if none specified).
            if (!$this->user_matches_restriction_groups($restriction->id, $usergroups)) {
                continue;
            }

            // Get effective time range.
            $times = $this->get_restriction_times($restriction, $userid, $courseid);

            // Check if currently active.
            if ($times->timestart <= $now && ($times->timeend == 0 || $times->timeend > $now)) {
                $restrictionname = $restriction->name ?: $this->get_restriction_display_name($restriction);
                return (object) [
                    'is_restricted' => true,
                    'message' => $restriction->restriction_message ?: $this->get_default_message($restriction),
                    'resumes_at' => $times->timeend,
                    'restriction_type' => $restriction->restriction_type,
                    'restriction_name' => $restrictionname,
                ];
            }
        }

        return null;
    }

    /**
     * Get restriction times, considering quiz overrides for quiz-linked restrictions.
     *
     * @param object $restriction The restriction record
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return object Object with timestart and timeend properties
     */
    private function get_restriction_times(object $restriction, int $userid, int $courseid): object {
        global $DB, $CFG;

        if ($restriction->restriction_type === 'quiz' && $restriction->quiz_id) {
            // Get quiz with user-specific overrides applied.
            $quiz = $DB->get_record('quiz', ['id' => $restriction->quiz_id]);
            if ($quiz) {
                require_once($CFG->dirroot . '/mod/quiz/lib.php');
                // Apply user-specific overrides (group and user level).
                $quiz = quiz_update_effective_access($quiz, $userid);
                return (object) [
                    'timestart' => $quiz->timeopen ?: 0,
                    'timeend' => $quiz->timeclose ?: 0,
                ];
            }
        }

        // Manual restriction or fallback.
        return (object) [
            'timestart' => $restriction->timestart ?: 0,
            'timeend' => $restriction->timeend ?: 0,
        ];
    }

    /**
     * Check if user is in any of the targeted groups.
     *
     * @param int $restrictionid Restriction ID
     * @param array $usergroups Array of group IDs the user belongs to
     * @return bool True if user matches restriction groups
     */
    private function user_matches_restriction_groups(int $restrictionid, array $usergroups): bool {
        global $DB;

        $restrictiongroups = $DB->get_fieldset_select(
            'local_savian_chat_restriction_groups',
            'group_id',
            'restriction_id = ?',
            [$restrictionid]
        );

        // If no groups specified, applies to all students.
        if (empty($restrictiongroups)) {
            return true;
        }

        // Check if user is in any targeted group.
        return !empty(array_intersect($usergroups, $restrictiongroups));
    }

    /**
     * Get user's group IDs for a course.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return array Array of group IDs
     */
    private function get_user_group_ids(int $courseid, int $userid): array {
        $groupings = groups_get_user_groups($courseid, $userid);
        return $groupings[0] ?? [];
    }

    /**
     * Get default restriction message.
     *
     * @param object $restriction The restriction record
     * @return string Default message
     */
    private function get_default_message(object $restriction): string {
        if ($restriction->restriction_type === 'quiz') {
            return get_string('chat_restricted_quiz_default', 'local_savian_ai');
        }
        return get_string('chat_restricted_manual_default', 'local_savian_ai');
    }

    /**
     * Get display name for a restriction.
     *
     * @param object $restriction The restriction record
     * @return string Display name
     */
    private function get_restriction_display_name(object $restriction): string {
        global $DB;

        if ($restriction->restriction_type === 'quiz' && $restriction->quiz_id) {
            $quiz = $DB->get_record('quiz', ['id' => $restriction->quiz_id], 'name');
            if ($quiz) {
                return $quiz->name;
            }
        }
        return $restriction->name ?: get_string('unnamed_restriction', 'local_savian_ai');
    }

    /**
     * Get all quizzes for a course with timing info.
     *
     * @param int $courseid Course ID
     * @return array Array of quiz objects
     */
    public function get_course_quizzes(int $courseid): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT q.id, q.name, q.timeopen, q.timeclose, cm.id as cmid
               FROM {quiz} q
               JOIN {course_modules} cm ON cm.instance = q.id
               JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
              WHERE q.course = ? AND cm.visible = 1
           ORDER BY q.timeopen ASC, q.name ASC",
            [$courseid]
        );
    }

    /**
     * Get all groups for a course.
     *
     * @param int $courseid Course ID
     * @return array Array of group objects
     */
    public function get_course_groups(int $courseid): array {
        return groups_get_all_groups($courseid, 0, 0, 'g.id, g.name');
    }

    /**
     * Get all restrictions for a course.
     *
     * @param int $courseid Course ID
     * @return array Array of restriction objects with additional info
     */
    public function get_restrictions(int $courseid): array {
        global $DB;

        $restrictions = $DB->get_records('local_savian_chat_restrictions', [
            'course_id' => $courseid,
        ], 'timecreated DESC');

        $now = time();

        foreach ($restrictions as $restriction) {
            // Get assigned groups.
            $groupids = $DB->get_fieldset_select(
                'local_savian_chat_restriction_groups',
                'group_id',
                'restriction_id = ?',
                [$restriction->id]
            );
            $restriction->group_ids = $groupids;

            // Get group names.
            if (!empty($groupids)) {
                [$insql, $params] = $DB->get_in_or_equal($groupids);
                $groups = $DB->get_records_select('groups', "id $insql", $params, '', 'id, name');
                $restriction->group_names = array_column($groups, 'name');
            } else {
                $restriction->group_names = [];
            }

            // Get effective times and quiz name.
            if ($restriction->restriction_type === 'quiz' && $restriction->quiz_id) {
                $quiz = $DB->get_record(
                    'quiz',
                    ['id' => $restriction->quiz_id],
                    'id, name, timeopen, timeclose'
                );
                if ($quiz) {
                    $restriction->quiz_name = $quiz->name;
                    $restriction->effective_timestart = $quiz->timeopen;
                    $restriction->effective_timeend = $quiz->timeclose;
                } else {
                    // Quiz was deleted.
                    $restriction->quiz_name = get_string('quiz_deleted', 'local_savian_ai');
                    $restriction->effective_timestart = 0;
                    $restriction->effective_timeend = 0;
                }
            } else {
                $restriction->quiz_name = null;
                $restriction->effective_timestart = $restriction->timestart;
                $restriction->effective_timeend = $restriction->timeend;
            }

            // Determine status.
            $restriction->status = $this->get_restriction_status($restriction, $now);
        }

        return $restrictions;
    }

    /**
     * Get restriction status string.
     *
     * @param object $restriction The restriction with effective times
     * @param int $now Current timestamp
     * @return string Status: 'active', 'scheduled', 'expired', 'disabled'
     */
    private function get_restriction_status(object $restriction, int $now): string {
        if (!$restriction->is_enabled) {
            return 'disabled';
        }

        $start = $restriction->effective_timestart;
        $end = $restriction->effective_timeend;

        if ($start == 0 && $end == 0) {
            return 'no_times';
        }

        if ($start > 0 && $start > $now) {
            return 'scheduled';
        }

        if ($start <= $now && ($end == 0 || $end > $now)) {
            return 'active';
        }

        if ($end > 0 && $end <= $now) {
            return 'expired';
        }

        return 'unknown';
    }

    /**
     * Save a restriction.
     *
     * @param object $data Restriction data
     * @param int $courseid Course ID
     * @param int $userid User ID (for usermodified)
     * @return int|bool Restriction ID on success, false on failure
     */
    public function save_restriction(object $data, int $courseid, int $userid) {
        global $DB;

        $now = time();

        $record = new \stdClass();
        $record->course_id = $courseid;
        $record->restriction_type = $data->restriction_type;
        $record->name = $data->name ?? null;
        $record->quiz_id = $data->quiz_id ?? null;
        $record->timestart = $data->timestart ?? null;
        $record->timeend = $data->timeend ?? null;
        $record->restriction_message = $data->restriction_message ?? null;
        $record->is_enabled = $data->is_enabled ?? 1;
        $record->timemodified = $now;
        $record->usermodified = $userid;

        if (!empty($data->id)) {
            // Update existing.
            $record->id = $data->id;
            $DB->update_record('local_savian_chat_restrictions', $record);
            $restrictionid = $record->id;
        } else {
            // Insert new.
            $record->timecreated = $now;
            $restrictionid = $DB->insert_record('local_savian_chat_restrictions', $record);
        }

        // Update group assignments.
        $this->update_restriction_groups($restrictionid, $data->group_ids ?? []);

        return $restrictionid;
    }

    /**
     * Update group assignments for a restriction.
     *
     * @param int $restrictionid Restriction ID
     * @param array $groupids Array of group IDs
     */
    private function update_restriction_groups(int $restrictionid, array $groupids): void {
        global $DB;

        // Delete existing assignments.
        $DB->delete_records('local_savian_chat_restriction_groups', ['restriction_id' => $restrictionid]);

        // Insert new assignments.
        foreach ($groupids as $groupid) {
            $record = new \stdClass();
            $record->restriction_id = $restrictionid;
            $record->group_id = $groupid;
            $DB->insert_record('local_savian_chat_restriction_groups', $record);
        }
    }

    /**
     * Delete a restriction.
     *
     * @param int $restrictionid Restriction ID
     * @param int $courseid Course ID (for validation)
     * @return bool Success
     */
    public function delete_restriction(int $restrictionid, int $courseid): bool {
        global $DB;

        // Verify restriction belongs to this course.
        $restriction = $DB->get_record('local_savian_chat_restrictions', [
            'id' => $restrictionid,
            'course_id' => $courseid,
        ]);

        if (!$restriction) {
            return false;
        }

        // Delete group assignments first.
        $DB->delete_records('local_savian_chat_restriction_groups', ['restriction_id' => $restrictionid]);

        // Delete restriction.
        $DB->delete_records('local_savian_chat_restrictions', ['id' => $restrictionid]);

        return true;
    }

    /**
     * Toggle restriction enabled status.
     *
     * @param int $restrictionid Restriction ID
     * @param int $courseid Course ID (for validation)
     * @param int $userid User ID (for usermodified)
     * @return bool New enabled status or false on failure
     */
    public function toggle_restriction(int $restrictionid, int $courseid, int $userid) {
        global $DB;

        $restriction = $DB->get_record('local_savian_chat_restrictions', [
            'id' => $restrictionid,
            'course_id' => $courseid,
        ]);

        if (!$restriction) {
            return false;
        }

        $newstatus = $restriction->is_enabled ? 0 : 1;

        $DB->update_record(
            'local_savian_chat_restrictions',
            (object) [
                'id' => $restrictionid,
                'is_enabled' => $newstatus,
                'timemodified' => time(),
                'usermodified' => $userid,
            ]
        );

        return $newstatus;
    }

    /**
     * Get a single restriction by ID.
     *
     * @param int $restrictionid Restriction ID
     * @param int $courseid Course ID (for validation)
     * @return object|null Restriction object or null
     */
    public function get_restriction(int $restrictionid, int $courseid): ?object {
        global $DB;

        $restriction = $DB->get_record('local_savian_chat_restrictions', [
            'id' => $restrictionid,
            'course_id' => $courseid,
        ]);

        if (!$restriction) {
            return null;
        }

        // Get assigned groups.
        $restriction->group_ids = $DB->get_fieldset_select(
            'local_savian_chat_restriction_groups',
            'group_id',
            'restriction_id = ?',
            [$restriction->id]
        );

        return $restriction;
    }

    /**
     * Format time remaining for display.
     *
     * @param int $timestamp Future timestamp
     * @return string Formatted time string
     */
    public static function format_time_remaining(int $timestamp): string {
        $now = time();
        $remaining = $timestamp - $now;

        if ($remaining <= 0) {
            return get_string('now', 'local_savian_ai');
        }

        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);

        if ($hours > 24) {
            $days = floor($hours / 24);
            return get_string('days_remaining', 'local_savian_ai', $days);
        } else if ($hours > 0) {
            return get_string(
                'hours_minutes_remaining',
                'local_savian_ai',
                (object) ['hours' => $hours, 'minutes' => $minutes]
            );
        } else {
            return get_string('minutes_remaining', 'local_savian_ai', $minutes);
        }
    }
}
