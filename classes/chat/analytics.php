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
 * Chat analytics handler.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\chat;

defined('MOODLE_INTERNAL') || die();

/**
 * Chat analytics class - provides statistics for teachers and admins.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics {
    /**
     * Get conversation statistics for a course.
     *
     * @param int $courseid Course ID
     * @return object Statistics object
     */
    public function get_course_stats($courseid) {
        global $DB;

        $sql = "SELECT
                    COUNT(DISTINCT c.id) as total_conversations,
                    COUNT(DISTINCT c.user_id) as unique_users,
                    COUNT(m.id) as total_messages,
                    AVG(c.message_count) as avg_messages_per_conversation,
                    AVG(m.response_time_ms) as avg_response_time
                FROM {local_savian_chat_conversations} c
                LEFT JOIN {local_savian_chat_messages} m ON m.conversation_id = c.id
                WHERE c.course_id = ? AND c.is_archived = 0";

        $stats = $DB->get_record_sql($sql, [$courseid]);

        // Round averages.
        if ($stats) {
            $stats->avg_messages_per_conversation = round($stats->avg_messages_per_conversation, 1);
            $stats->avg_response_time = round($stats->avg_response_time);
        }

        return $stats;
    }

    /**
     * Get user engagement metrics.
     *
     * @param int $courseid Course ID
     * @param int|null $startdate Start date filter (timestamp)
     * @param int|null $enddate End date filter (timestamp)
     * @return array Array of user engagement data
     */
    public function get_user_engagement($courseid, $startdate = null, $enddate = null) {
        global $DB;

        $params = ['courseid' => $courseid];
        $datefilter = '';

        if ($startdate) {
            $datefilter .= " AND c.timecreated >= :startdate";
            $params['startdate'] = $startdate;
        }
        if ($enddate) {
            $datefilter .= " AND c.timecreated <= :enddate";
            $params['enddate'] = $enddate;
        }

        $sql = "SELECT
                    u.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    COUNT(DISTINCT c.id) as conversation_count,
                    COUNT(m.id) as message_count,
                    MAX(c.last_message_at) as last_active
                FROM {user} u
                JOIN {local_savian_chat_conversations} c ON c.user_id = u.id
                LEFT JOIN {local_savian_chat_messages} m ON m.conversation_id = c.id
                WHERE c.course_id = :courseid $datefilter
                GROUP BY u.id, u.firstname, u.lastname, u.email
                ORDER BY conversation_count DESC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get feedback statistics.
     *
     * @param int $courseid Course ID
     * @return object Feedback statistics
     */
    public function get_feedback_stats($courseid) {
        global $DB;

        $sql = "SELECT
                    COUNT(CASE WHEN m.feedback = 1 THEN 1 END) as positive_feedback,
                    COUNT(CASE WHEN m.feedback = -1 THEN 1 END) as negative_feedback,
                    COUNT(CASE WHEN m.feedback IS NOT NULL THEN 1 END) as total_feedback,
                    COUNT(m.id) as total_assistant_messages
                FROM {local_savian_chat_messages} m
                JOIN {local_savian_chat_conversations} c ON c.id = m.conversation_id
                WHERE c.course_id = ? AND m.role = 'assistant'";

        $stats = $DB->get_record_sql($sql, [$courseid]);

        // Calculate feedback rate.
        if ($stats && $stats->total_assistant_messages > 0) {
            $feedbackpct = ($stats->total_feedback / $stats->total_assistant_messages) * 100;
            $stats->feedback_rate = round($feedbackpct, 1);
        } else {
            $stats->feedback_rate = 0;
        }

        return $stats;
    }

    /**
     * Get system-wide statistics (for admins).
     *
     * @return object System-wide statistics
     */
    public function get_system_stats() {
        global $DB;

        $stats = new \stdClass();
        $stats->total_conversations = $DB->count_records('local_savian_chat_conversations', ['is_archived' => 0]);
        $stats->total_messages = $DB->count_records('local_savian_chat_messages');
        $stats->unique_users = $DB->count_records_sql(
            'SELECT COUNT(DISTINCT user_id) FROM {local_savian_chat_conversations}'
        );

        // Average response time.
        $avgresponse = $DB->get_field_sql(
            'SELECT AVG(response_time_ms) FROM {local_savian_chat_messages} WHERE response_time_ms IS NOT NULL'
        );
        $stats->avg_response_time = round($avgresponse);

        // Total tokens used.
        $stats->total_tokens = $DB->get_field_sql(
            'SELECT SUM(token_count) FROM {local_savian_chat_messages}'
        );

        return $stats;
    }

    /**
     * Get top courses by chat activity.
     *
     * @param int $limit Number of courses to return
     * @return array Array of course data
     */
    public function get_top_courses($limit = 10) {
        global $DB;

        $sql = "SELECT
                    c.course_id,
                    co.fullname,
                    COUNT(DISTINCT c.id) as conversation_count,
                    COUNT(DISTINCT c.user_id) as user_count,
                    COUNT(m.id) as message_count
                FROM {local_savian_chat_conversations} c
                JOIN {course} co ON co.id = c.course_id
                LEFT JOIN {local_savian_chat_messages} m ON m.conversation_id = c.id
                WHERE c.course_id IS NOT NULL
                GROUP BY c.course_id, co.fullname
                ORDER BY conversation_count DESC";

        return array_values($DB->get_records_sql($sql, null, 0, $limit));
    }

    /**
     * Get recent conversations (for monitoring dashboard).
     *
     * @param int $limit Number of conversations to return
     * @return array Array of recent conversations
     */
    public function get_recent_conversations($limit = 20) {
        global $DB;

        $sql = "SELECT
                    c.*,
                    u.firstname,
                    u.lastname,
                    co.fullname as course_name
                FROM {local_savian_chat_conversations} c
                JOIN {user} u ON u.id = c.user_id
                LEFT JOIN {course} co ON co.id = c.course_id
                ORDER BY c.last_message_at DESC";

        return array_values($DB->get_records_sql($sql, null, 0, $limit));
    }

    /**
     * Get usage over time (for charts).
     *
     * @param int $days Number of days to look back
     * @return array Array of daily usage data
     */
    public function get_usage_over_time($days = 30) {
        global $DB;

        $cutoff = time() - ($days * 24 * 60 * 60);

        $sql = "SELECT
                    DATE(FROM_UNIXTIME(timecreated)) as date,
                    COUNT(DISTINCT conversation_id) as conversations,
                    COUNT(id) as messages
                FROM {local_savian_chat_messages}
                WHERE timecreated >= ?
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY date ASC";

        return array_values($DB->get_records_sql($sql, [$cutoff]));
    }
}
