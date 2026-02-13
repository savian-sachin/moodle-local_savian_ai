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
 * Privacy API provider.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for Savian AI plugin.
 *
 * Implements GDPR compliance by declaring what user data is stored,
 * providing export functionality, and enabling data deletion.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Declare what user data this plugin stores.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {

        // Chat conversations.
        $collection->add_database_table('local_savian_chat_conversations', [
            'user_id' => 'privacy:metadata:conversations:user_id',
            'course_id' => 'privacy:metadata:conversations:course_id',
            'title' => 'privacy:metadata:conversations:title',
            'timecreated' => 'privacy:metadata:conversations:timecreated',
        ], 'privacy:metadata:conversations');

        // Chat messages.
        $collection->add_database_table('local_savian_chat_messages', [
            'conversation_id' => 'privacy:metadata:messages:conversation_id',
            'role' => 'privacy:metadata:messages:role',
            'content' => 'privacy:metadata:messages:content',
            'feedback' => 'privacy:metadata:messages:feedback',
            'feedback_comment' => 'privacy:metadata:messages:feedback_comment',
            'timecreated' => 'privacy:metadata:messages:timecreated',
        ], 'privacy:metadata:messages');

        // Chat settings.
        $collection->add_database_table('local_savian_chat_settings', [
            'user_id' => 'privacy:metadata:settings:user_id',
            'widget_position' => 'privacy:metadata:settings:widget_position',
            'widget_minimized' => 'privacy:metadata:settings:widget_minimized',
        ], 'privacy:metadata:settings');

        // Generation history.
        $collection->add_database_table('local_savian_generations', [
            'user_id' => 'privacy:metadata:generations:user_id',
            'course_id' => 'privacy:metadata:generations:course_id',
            'generation_type' => 'privacy:metadata:generations:generation_type',
            'status' => 'privacy:metadata:generations:status',
            'timecreated' => 'privacy:metadata:generations:timecreated',
        ], 'privacy:metadata:generations');

        // Analytics reports (who triggered manual reports).
        $collection->add_database_table('local_savian_analytics_reports', [
            'course_id' => 'privacy:metadata:analytics_reports:course_id',
            'user_id' => 'privacy:metadata:analytics_reports:user_id',
            'report_type' => 'privacy:metadata:analytics_reports:report_type',
            'timecreated' => 'privacy:metadata:analytics_reports:timecreated',
        ], 'privacy:metadata:analytics_reports');

        // Analytics events (real-time tracking).
        $collection->add_database_table('local_savian_analytics_events', [
            'course_id' => 'privacy:metadata:analytics_events:course_id',
            'user_id' => 'privacy:metadata:analytics_events:user_id',
            'event_name' => 'privacy:metadata:analytics_events:event_name',
            'timecreated' => 'privacy:metadata:analytics_events:timecreated',
        ], 'privacy:metadata:analytics_events');

        // External service data.
        $collection->add_external_location_link('savian_api', [
            'user_id' => 'privacy:metadata:external:user_id',
            'user_email' => 'privacy:metadata:external:user_email',
            'course_id' => 'privacy:metadata:external:course_id',
            'chat_message' => 'privacy:metadata:external:chat_message',
            'document_content' => 'privacy:metadata:external:document_content',
            'anonymized_analytics' => 'privacy:metadata:external:anonymized_analytics',
        ], 'privacy:metadata:external');

        return $collection;
    }

    /**
     * Get contexts that contain user data.
     *
     * @param int $userid The user ID.
     * @return contextlist The contextlist containing the contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Add system context for global chat and document usage.
        $contextlist->add_system_context();

        // Add course contexts where user has chat conversations.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_chat_conversations} c ON c.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND c.user_id = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            // Export chat conversations.
            $conversations = $DB->get_records('local_savian_chat_conversations', [
                'user_id' => $userid,
            ]);

            foreach ($conversations as $conversation) {
                // Get messages for this conversation.
                $messages = $DB->get_records('local_savian_chat_messages', [
                    'conversation_id' => $conversation->id,
                ], 'timecreated ASC');

                $conversationdata = (object) [
                    'title' => $conversation->title,
                    'course_id' => $conversation->course_id,
                    'created' => \core_privacy\local\request\transform::datetime($conversation->timecreated),
                    'messages' => array_map(function($msg) {
                        return [
                            'role' => $msg->role,
                            'content' => $msg->content,
                            'feedback' => $msg->feedback,
                            'feedback_comment' => $msg->feedback_comment,
                            'created' => \core_privacy\local\request\transform::datetime($msg->timecreated),
                        ];
                    }, array_values($messages)),
                ];

                writer::with_context($context)->export_data(
                    [get_string('privacy:chatdata', 'local_savian_ai'), $conversation->id],
                    $conversationdata
                );
            }

            // Export chat settings.
            $settings = $DB->get_record('local_savian_chat_settings', ['user_id' => $userid]);
            if ($settings) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:chatsettings', 'local_savian_ai')],
                    (object) [
                        'widget_position' => $settings->widget_position,
                        'widget_minimized' => $settings->widget_minimized,
                    ]
                );
            }

            // Export generation history.
            $generations = $DB->get_records('local_savian_generations', ['user_id' => $userid]);
            foreach ($generations as $generation) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:generationdata', 'local_savian_ai'), $generation->id],
                    (object) [
                        'type' => $generation->generation_type,
                        'course_id' => $generation->course_id,
                        'status' => $generation->status,
                        'created' => \core_privacy\local\request\transform::datetime($generation->timecreated),
                    ]
                );
            }

            // Export analytics reports (manually triggered by this user).
            $analyticsreports = $DB->get_records('local_savian_analytics_reports', ['user_id' => $userid]);
            foreach ($analyticsreports as $report) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:analyticsreports', 'local_savian_ai'), $report->id],
                    (object) [
                        'course_id' => $report->course_id,
                        'report_type' => $report->report_type,
                        'trigger_type' => $report->trigger_type,
                        'student_count' => $report->student_count,
                        'status' => $report->status,
                        'created' => \core_privacy\local\request\transform::datetime($report->timecreated),
                    ]
                );
            }

            // Export analytics events (real-time tracking).
            $analyticsevents = $DB->get_records('local_savian_analytics_events', ['user_id' => $userid]);
            if (!empty($analyticsevents)) {
                $eventsdata = array_map(function($event) {
                    return [
                        'course_id' => $event->course_id,
                        'event_name' => $event->event_name,
                        'processed' => $event->processed ? 'Yes' : 'No',
                        'created' => \core_privacy\local\request\transform::datetime($event->timecreated),
                    ];
                }, array_values($analyticsevents));

                writer::with_context($context)->export_data(
                    [get_string('privacy:analyticsevents', 'local_savian_ai')],
                    (object) ['events' => $eventsdata]
                );
            }
        }
    }

    /**
     * Delete all user data for the specified users in the specified context.
     *
     * @param userlist $userlist The approved context and user information to delete data for.
     */
    public static function delete_data_for_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Delete for all users in the userlist.
            $userids = $userlist->get_userids();
            foreach ($userids as $userid) {
                self::delete_user_data($userid);
            }
        }
    }

    /**
     * Delete all data for a specific user.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        self::delete_user_data($userid);
    }

    /**
     * Delete all user data (helper method).
     *
     * @param int $userid User ID.
     */
    protected static function delete_user_data(int $userid) {
        global $DB;

        // Get all conversations for this user.
        $conversations = $DB->get_records('local_savian_chat_conversations', ['user_id' => $userid]);

        foreach ($conversations as $conversation) {
            // Delete all messages in this conversation.
            $DB->delete_records('local_savian_chat_messages', ['conversation_id' => $conversation->id]);
        }

        // Delete conversations.
        $DB->delete_records('local_savian_chat_conversations', ['user_id' => $userid]);

        // Delete chat settings.
        $DB->delete_records('local_savian_chat_settings', ['user_id' => $userid]);

        // Delete generation history.
        $DB->delete_records('local_savian_generations', ['user_id' => $userid]);

        // Delete analytics reports triggered by this user.
        $DB->delete_records('local_savian_analytics_reports', ['user_id' => $userid]);

        // Delete analytics events for this user.
        $DB->delete_records('local_savian_analytics_events', ['user_id' => $userid]);

        // Delete analytics cache (anonymized data).
        // Note: We delete by finding the anonymized ID first.
        $anonymizer = new \local_savian_ai\analytics\anonymizer();
        $anonid = $anonymizer->anonymize_user_id($userid);
        $DB->delete_records('local_savian_analytics_cache', ['anon_user_id' => $anonid]);

        // Note: Documents are not user-specific (shared resources), so not deleted.
    }

    /**
     * Get list of users with data in specified context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Add users who have chat data.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_chat_conversations}";
            $userlist->add_from_sql('user_id', $sql, []);

            // Add users who have generation history.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_generations}";
            $userlist->add_from_sql('user_id', $sql, []);

            // Add users who triggered analytics reports.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_analytics_reports} WHERE user_id IS NOT NULL";
            $userlist->add_from_sql('user_id', $sql, []);

            // Add users in analytics events.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_analytics_events}";
            $userlist->add_from_sql('user_id', $sql, []);
        }
    }
}
