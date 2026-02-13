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
 * Chat external service functions.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

/**
 * External chat API - web services for AJAX communication.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat extends external_api {
    // Send message methods.

    /**
     * Parameters for send_message.
     */
    public static function send_message_parameters() {
        return new external_function_parameters(
            [
                'message' => new external_value(PARAM_RAW, 'Message content'),
                'conversationid' => new external_value(PARAM_INT, 'Conversation ID', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
                'documentids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Document ID'),
                    'Document IDs for context',
                    VALUE_DEFAULT,
                    []
                ),
            ]
        );
    }

    /**
     * Send chat message.
     *
     * @param string $message The message text.
     * @param string $conversationid Conversation ID.
     * @param int $courseid Course ID.
     * @param array $documentids Document IDs for context.
     * @return array Response data.
     */
    public static function send_message($message, $conversationid, $courseid, $documentids) {
        global $USER;

        $params = self::validate_parameters(
            self::send_message_parameters(),
            [
                'message' => $message,
                'conversationid' => $conversationid,
                'courseid' => $courseid,
                'documentids' => $documentids,
            ]
        );

        // Verify capability.
        $context = $params['courseid']
            ? \context_course::instance($params['courseid']) : \context_system::instance();
        self::validate_context($context);
        require_capability('local/savian_ai:use', $context);

        // Check for chat restrictions (students only, teachers bypass).
        if ($params['courseid'] > 0) {
            $isstudent = !has_capability('local/savian_ai:generate', $context);
            if ($isstudent) {
                $restrictionmanager = new \local_savian_ai\chat\restriction_manager();
                $restriction = $restrictionmanager->get_active_restriction($params['courseid'], $USER->id);
                if ($restriction) {
                    throw new \moodle_exception(
                        'chat_restricted',
                        'local_savian_ai',
                        '',
                        $restriction->message
                    );
                }
            }
        }

        $manager = new \local_savian_ai\chat\manager();
        $result = $manager->send_message(
            $params['message'],
            $params['conversationid'],
            $params['courseid'],
            $params['documentids']
        );

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Return structure for send_message.
     */
    public static function send_message_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'data' => new external_single_structure(
                    [
                        'conversation_id' => new external_value(PARAM_INT, 'Conversation ID'),
                        'user_message' => new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'Message ID'),
                                'role' => new external_value(PARAM_TEXT, 'Message role'),
                                'content' => new external_value(PARAM_RAW, 'Message content'),
                                'formatted_content' => new external_value(PARAM_RAW, 'Formatted content'),
                                'sources' => new external_value(PARAM_RAW, 'Sources JSON', VALUE_OPTIONAL),
                                'feedback' => new external_value(PARAM_INT, 'Feedback', VALUE_OPTIONAL),
                                'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
                                'formatted_time' => new external_value(PARAM_TEXT, 'Formatted time'),
                            ]
                        ),
                        'assistant_message' => new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'Message ID'),
                                'role' => new external_value(PARAM_TEXT, 'Message role'),
                                'content' => new external_value(PARAM_RAW, 'Message content'),
                                'formatted_content' => new external_value(PARAM_RAW, 'Formatted content'),
                                'sources' => new external_value(PARAM_RAW, 'Sources JSON', VALUE_OPTIONAL),
                                'feedback' => new external_value(PARAM_INT, 'Feedback', VALUE_OPTIONAL),
                                'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
                                'formatted_time' => new external_value(PARAM_TEXT, 'Formatted time'),
                            ]
                        ),
                    ]
                ),
            ]
        );
    }

    // Get conversation methods.

    /**
     * Parameters for get_conversation.
     */
    public static function get_conversation_parameters() {
        return new external_function_parameters(
            [
                'conversationid' => new external_value(PARAM_INT, 'Conversation ID'),
            ]
        );
    }

    /**
     * Get conversation with messages.
     *
     * @param int $conversationid Conversation ID.
     * @return array Response data.
     */
    public static function get_conversation($conversationid) {
        $params = self::validate_parameters(
            self::get_conversation_parameters(),
            [
                'conversationid' => $conversationid,
            ]
        );

        $manager = new \local_savian_ai\chat\manager();
        $result = $manager->get_conversation($params['conversationid']);

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Return structure for get_conversation.
     */
    public static function get_conversation_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'data' => new external_single_structure(
                    [
                        'conversation' => new external_value(PARAM_RAW, 'Conversation data'),
                        'messages' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_INT, 'Message ID'),
                                    'role' => new external_value(PARAM_TEXT, 'Role'),
                                    'content' => new external_value(PARAM_RAW, 'Content'),
                                    'formatted_content' => new external_value(PARAM_RAW, 'Formatted content'),
                                    'sources' => new external_value(PARAM_RAW, 'Sources', VALUE_OPTIONAL),
                                    'feedback' => new external_value(PARAM_INT, 'Feedback', VALUE_OPTIONAL),
                                    'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
                                    'formatted_time' => new external_value(PARAM_TEXT, 'Formatted time'),
                                ]
                            ),
                            'Messages'
                        ),
                    ]
                ),
            ]
        );
    }

    // List conversations methods.

    /**
     * Parameters for list_conversations.
     */
    public static function list_conversations_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * List user conversations.
     *
     * @param int $courseid Course ID.
     * @return array Response data.
     */
    public static function list_conversations($courseid) {
        global $USER;

        $params = self::validate_parameters(
            self::list_conversations_parameters(),
            [
                'courseid' => $courseid,
            ]
        );

        // Verify capability.
        if ($params['courseid']) {
            $context = \context_course::instance($params['courseid']);
            self::validate_context($context);
            require_capability('local/savian_ai:use', $context);
        } else {
            $context = \context_system::instance();
            self::validate_context($context);
        }

        $manager = new \local_savian_ai\chat\manager();
        $conversations = $manager->list_user_conversations($params['courseid']);

        // Ensure we return an array, even if empty.
        return [
            'success' => true,
            'data' => is_array($conversations) ? $conversations : [],
        ];
    }

    /**
     * Return structure for list_conversations.
     */
    public static function list_conversations_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Conversation ID'),
                            'conversation_uuid' => new external_value(PARAM_TEXT, 'Conversation UUID'),
                            'user_id' => new external_value(PARAM_INT, 'User ID'),
                            'course_id' => new external_value(PARAM_INT, 'Course ID', VALUE_OPTIONAL),
                            'context_type' => new external_value(PARAM_TEXT, 'Context type'),
                            'title' => new external_value(PARAM_TEXT, 'Title', VALUE_OPTIONAL),
                            'document_ids' => new external_value(PARAM_RAW, 'Document IDs JSON', VALUE_OPTIONAL),
                            'message_count' => new external_value(PARAM_INT, 'Message count'),
                            'last_message_at' => new external_value(PARAM_INT, 'Last message timestamp'),
                            'is_archived' => new external_value(PARAM_INT, 'Is archived'),
                            'timecreated' => new external_value(PARAM_INT, 'Created timestamp'),
                            'timemodified' => new external_value(PARAM_INT, 'Modified timestamp'),
                        ]
                    ),
                    'List of conversations'
                ),
            ]
        );
    }

    // Submit feedback methods.

    /**
     * Parameters for submit_feedback.
     */
    public static function submit_feedback_parameters() {
        return new external_function_parameters(
            [
                'messageid' => new external_value(PARAM_INT, 'Message ID'),
                'feedback' => new external_value(PARAM_INT, 'Feedback (1 or -1)'),
                'comment' => new external_value(PARAM_TEXT, 'Comment', VALUE_DEFAULT, ''),
            ]
        );
    }

    /**
     * Submit feedback.
     *
     * @param int $messageid Message ID.
     * @param string $feedback Feedback type (thumbsup/thumbsdown).
     * @param string $comment Optional feedback comment.
     * @return array Response data.
     */
    public static function submit_feedback($messageid, $feedback, $comment) {
        $params = self::validate_parameters(
            self::submit_feedback_parameters(),
            [
                'messageid' => $messageid,
                'feedback' => $feedback,
                'comment' => $comment,
            ]
        );

        $manager = new \local_savian_ai\chat\manager();
        $result = $manager->submit_feedback(
            $params['messageid'],
            $params['feedback'],
            $params['comment']
        );

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Return structure for submit_feedback.
     */
    public static function submit_feedback_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'data' => new external_single_structure(
                    [
                        'success' => new external_value(PARAM_BOOL, 'Operation success'),
                    ]
                ),
            ]
        );
    }

    // Save widget state methods.

    /**
     * Parameters for save_widget_state.
     */
    public static function save_widget_state_parameters() {
        return new external_function_parameters(
            [
                'position' => new external_value(PARAM_TEXT, 'Widget position (bottom-right, bottom-left)'),
                'minimized' => new external_value(PARAM_INT, 'Minimized state (0 or 1)'),
            ]
        );
    }

    /**
     * Save widget state.
     *
     * @param string $position Widget position.
     * @param bool $minimized Whether widget is minimized.
     * @return array Response data.
     */
    public static function save_widget_state($position, $minimized) {
        $params = self::validate_parameters(
            self::save_widget_state_parameters(),
            [
                'position' => $position,
                'minimized' => $minimized,
            ]
        );

        $manager = new \local_savian_ai\chat\manager();
        $result = $manager->save_widget_preferences(
            $params['position'],
            $params['minimized']
        );

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Return structure for save_widget_state.
     */
    public static function save_widget_state_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'data' => new external_single_structure(
                    [
                        'success' => new external_value(PARAM_BOOL, 'Operation success'),
                    ]
                ),
            ]
        );
    }

    // Get course documents methods.

    /**
     * Parameters for get_course_documents.
     */
    public static function get_course_documents_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Get course documents for selection in chat.
     *
     * @param int $courseid Course ID.
     * @return array Response data.
     */
    public static function get_course_documents($courseid) {
        global $DB;

        $params = self::validate_parameters(
            self::get_course_documents_parameters(),
            [
                'courseid' => $courseid,
            ]
        );

        // Verify capability.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/savian_ai:generate', $context);

        // Get completed documents for this course.
        $documents = $DB->get_records(
            'local_savian_ai_documents',
            [
                'course_id' => $params['courseid'],
                'is_active' => 1,
                'status' => 'completed',
            ],
            'title ASC',
            'savian_doc_id, title, subject_area, chunk_count'
        );

        $docsarray = [];
        foreach ($documents as $doc) {
            $docsarray[] = [
                'id' => $doc->savian_doc_id,
                'title' => $doc->title,
                'subject_area' => $doc->subject_area ?? '',
                'chunk_count' => $doc->chunk_count,
            ];
        }

        return [
            'success' => true,
            'documents' => $docsarray,
        ];
    }

    /**
     * Return structure for get_course_documents.
     */
    public static function get_course_documents_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'documents' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Document ID'),
                            'title' => new external_value(PARAM_TEXT, 'Title'),
                            'subject_area' => new external_value(PARAM_TEXT, 'Subject area'),
                            'chunk_count' => new external_value(PARAM_INT, 'Chunk count'),
                        ]
                    ),
                    'List of documents'
                ),
            ]
        );
    }
}
