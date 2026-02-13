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
 * Chat manager.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\chat;

defined('MOODLE_INTERNAL') || die();

/**
 * Chat manager class - handles chat operations and business logic.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Send a chat message.
     *
     * @param string $message The message content
     * @param int $conversationid Existing conversation ID (0 for new)
     * @param int|null $courseid Course context
     * @param array $documentids Document IDs for context
     * @return array Result with conversation_id, user_message, assistant_message
     * @throws \moodle_exception
     */
    public function send_message($message, $conversationid, $courseid = null, $documentids = []) {
        global $DB, $USER;

        $starttime = microtime(true);

        // Determine context.
        $context = $courseid ? \context_course::instance($courseid) : \context_system::instance();

        // Get or create conversation.
        $isnewconversation = false;
        if ($conversationid) {
            $conversation = $DB->get_record(
                'local_savian_chat_conversations',
                ['id' => $conversationid, 'user_id' => $USER->id],
                '*',
                MUST_EXIST
            );
            $conversationuuid = $conversation->conversation_uuid;
        } else {
            // New conversation - create locally but send null to API (let API create it).
            $conversation = $this->create_conversation($courseid, $documentids);
            $conversationuuid = null;
            $isnewconversation = true;
        }

        // Role-based document filtering for students.
        if (!has_capability('local/savian_ai:generate', $context)) {
            // Students can only use course documents.
            if (!$courseid) {
                throw new \moodle_exception('error_students_course_only', 'local_savian_ai');
            }

            // Auto-include all active course documents for students.
            $documentids = $DB->get_fieldset_select(
                'local_savian_documents',
                'savian_doc_id',
                'course_id = ? AND is_active = 1',
                [$courseid]
            );
        }

        // Save user message to DB.
        $usermessage = $this->save_message($conversation->id, 'user', $message);

        // Get user role for API.
        $userrole = 'student';
        if (has_capability('local/savian_ai:manage', \context_system::instance())) {
            $userrole = 'admin';
        } else if (has_capability('local/savian_ai:generate', $context)) {
            $userrole = 'teacher';
        }

        // Call Savian AI API.
        $client = new \local_savian_ai\api\client();
        $coursename = $courseid ? $DB->get_field('course', 'fullname', ['id' => $courseid]) : null;
        $docids = $documentids ?: json_decode($conversation->document_ids, true);
        $response = $client->chat_send(
            $message,
            $conversationuuid,
            [
                'user_id' => $USER->id,
                'user_email' => $USER->email,
                'user_role' => $userrole,
                'course_id' => $courseid,
                'course_name' => $coursename,
                'document_ids' => $docids,
                'language' => current_language(),
            ]
        );

        if ($response->http_code !== 200 || (!isset($response->message) && !isset($response->response))) {
            // Get detailed error message.
            $errormsg = 'Unknown error';
            if (isset($response->error)) {
                $errormsg = $response->error;
            } else if (isset($response->detail)) {
                $errormsg = $response->detail;
            } else {
                $errormsg = 'HTTP ' . $response->http_code . ': ' . json_encode($response);
            }

            throw new \moodle_exception('error_chat_failed', 'local_savian_ai', '', $errormsg);
        }

        // Update conversation UUID if this was a new conversation.
        if ($isnewconversation && isset($response->conversation_id)) {
            $DB->set_field(
                'local_savian_chat_conversations',
                'conversation_uuid',
                $response->conversation_id,
                ['id' => $conversation->id]
            );
            $conversation->conversation_uuid = $response->conversation_id;
        }

        // Calculate response time (round to integer for database).
        $responsetime = round((microtime(true) - $starttime) * 1000);

        // API returns 'response' field, not 'message'.
        $airesponsetext = $response->response ?? $response->message ?? '';

        // Save assistant response to DB.
        $tokensused = isset($response->metadata->tokens_used) ? (int) $response->metadata->tokens_used : 0;
        $assistantmessage = $this->save_message(
            $conversation->id,
            'assistant',
            $airesponsetext,
            [
                'sources' => $response->sources ?? [],
                'response_time_ms' => $responsetime,
                'message_uuid' => $response->message_id ?? null,
                'token_count' => $tokensused,
            ]
        );

        // Update conversation metadata.
        $DB->set_field(
            'local_savian_chat_conversations',
            'message_count',
            $conversation->message_count + 2,
            ['id' => $conversation->id]
        );
        $DB->set_field(
            'local_savian_chat_conversations',
            'last_message_at',
            time(),
            ['id' => $conversation->id]
        );
        $DB->set_field(
            'local_savian_chat_conversations',
            'timemodified',
            time(),
            ['id' => $conversation->id]
        );

        // Format response for frontend.
        return [
            'conversation_id' => $conversation->id,
            'user_message' => $this->format_message($usermessage),
            'assistant_message' => $this->format_message($assistantmessage),
        ];
    }

    /**
     * Create new conversation.
     *
     * @param int|null $courseid Course ID
     * @param array $documentids Document IDs
     * @return object Conversation record
     */
    private function create_conversation($courseid, $documentids) {
        global $DB, $USER;

        $conversation = new \stdClass();
        $conversation->conversation_uuid = $this->generate_uuid();
        $conversation->user_id = $USER->id;
        $conversation->course_id = $courseid;
        $conversation->context_type = $courseid ? 'course' : 'global';
        $conversation->document_ids = json_encode($documentids);
        $conversation->message_count = 0;
        $conversation->timecreated = time();
        $conversation->timemodified = time();
        $conversation->last_message_at = time();

        $conversation->id = $DB->insert_record('local_savian_chat_conversations', $conversation);

        return $conversation;
    }

    /**
     * Save message to database.
     *
     * @param int $conversationid Conversation ID
     * @param string $role Message role (user/assistant/system)
     * @param string $content Message content
     * @param array $options Additional options (sources, response_time_ms, message_uuid, token_count)
     * @return object Message record
     */
    private function save_message($conversationid, $role, $content, $options = []) {
        global $DB;

        $message = new \stdClass();
        $message->conversation_id = $conversationid;
        $message->role = $role;
        $message->content = $content;
        $message->message_uuid = $options['message_uuid'] ?? null;
        $message->sources = isset($options['sources']) ? json_encode($options['sources']) : null;
        $message->response_time_ms = $options['response_time_ms'] ?? null;
        $message->token_count = $options['token_count'] ?? 0;
        $message->timecreated = time();

        // Format content (Markdown, LaTeX, code highlighting).
        $message->formatted_content = $this->format_content($content);

        $message->id = $DB->insert_record('local_savian_chat_messages', $message);

        return $message;
    }

    /**
     * Format message content with Markdown, LaTeX, code highlighting.
     *
     * @param string $content Raw message content
     * @return string Formatted HTML content
     */
    private function format_content($content) {
        // Use Moodle's format_text with Markdown filter.
        // This will be enhanced by JavaScript for code highlighting and LaTeX.
        return format_text($content, FORMAT_MARKDOWN, ['noclean' => true]);
    }

    /**
     * Get conversation with messages.
     *
     * @param int $conversationid Conversation ID
     * @return array Conversation data with messages
     * @throws \moodle_exception
     */
    public function get_conversation($conversationid) {
        global $DB, $USER;

        $conversation = $DB->get_record(
            'local_savian_chat_conversations',
            ['id' => $conversationid, 'user_id' => $USER->id],
            '*',
            MUST_EXIST
        );

        $messages = $DB->get_records(
            'local_savian_chat_messages',
            ['conversation_id' => $conversationid],
            'timecreated ASC'
        );

        return [
            'conversation' => $conversation,
            'messages' => array_values(array_map([$this, 'format_message'], $messages)),
        ];
    }

    /**
     * List user conversations.
     *
     * @param int|null $courseid Course filter (optional)
     * @return array List of conversations
     */
    public function list_user_conversations($courseid = null) {
        global $DB, $USER;

        $params = ['user_id' => $USER->id, 'is_archived' => 0];
        if ($courseid) {
            $params['course_id'] = $courseid;
        }

        $conversations = $DB->get_records(
            'local_savian_chat_conversations',
            $params,
            'last_message_at DESC',
            '*',
            0,
            50
        );

        return array_values($conversations);
    }

    /**
     * Submit feedback for a message.
     *
     * @param int $messageid Message ID
     * @param int $feedback Feedback value (1=helpful, -1=not helpful)
     * @param string $comment Optional comment
     * @return array Success response
     * @throws \moodle_exception
     */
    public function submit_feedback($messageid, $feedback, $comment = '') {
        global $DB, $USER;

        $message = $DB->get_record('local_savian_chat_messages', ['id' => $messageid], '*', MUST_EXIST);

        // Verify user owns this conversation.
        $conversation = $DB->get_record(
            'local_savian_chat_conversations',
            ['id' => $message->conversation_id, 'user_id' => $USER->id],
            '*',
            MUST_EXIST
        );

        // Update message.
        $DB->set_field('local_savian_chat_messages', 'feedback', $feedback, ['id' => $messageid]);
        if ($comment) {
            $DB->set_field('local_savian_chat_messages', 'feedback_comment', $comment, ['id' => $messageid]);
        }

        // Submit to API if message_uuid exists.
        if ($message->message_uuid) {
            $client = new \local_savian_ai\api\client();
            $client->chat_feedback($message->message_uuid, $feedback, $comment);
        }

        return ['success' => true];
    }

    /**
     * Archive conversation.
     *
     * @param int $conversationid Conversation ID
     * @return array Success response
     * @throws \moodle_exception
     */
    public function archive_conversation($conversationid) {
        global $DB, $USER;

        $conversation = $DB->get_record(
            'local_savian_chat_conversations',
            ['id' => $conversationid, 'user_id' => $USER->id],
            '*',
            MUST_EXIST
        );

        $DB->set_field('local_savian_chat_conversations', 'is_archived', 1, ['id' => $conversationid]);
        $DB->set_field('local_savian_chat_conversations', 'timemodified', time(), ['id' => $conversationid]);

        return ['success' => true];
    }

    /**
     * Save widget preferences.
     *
     * @param string $position Widget position
     * @param int $minimized Widget minimized state
     * @return array Success response
     */
    public function save_widget_preferences($position, $minimized) {
        global $DB, $USER;

        $settings = $DB->get_record('local_savian_chat_settings', ['user_id' => $USER->id]);

        if ($settings) {
            $settings->widget_position = $position;
            $settings->widget_minimized = $minimized;
            $settings->timemodified = time();
            $DB->update_record('local_savian_chat_settings', $settings);
        } else {
            $settings = new \stdClass();
            $settings->user_id = $USER->id;
            $settings->widget_position = $position;
            $settings->widget_minimized = $minimized;
            $settings->timemodified = time();
            $DB->insert_record('local_savian_chat_settings', $settings);
        }

        return ['success' => true];
    }

    /**
     * Format message for frontend.
     *
     * @param object $message Message record
     * @return array Formatted message
     */
    private function format_message($message) {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'formatted_content' => $message->formatted_content,
            'sources' => $message->sources ?? '[]',  // Keep as JSON string for external API.
            'feedback' => $message->feedback,
            'timestamp' => $message->timecreated,
            'formatted_time' => userdate($message->timecreated, '%H:%M'),
        ];
    }

    /**
     * Generate UUID.
     *
     * @return string UUID
     */
    private function generate_uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
