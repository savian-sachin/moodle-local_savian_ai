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
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

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
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Declare what user data this plugin stores.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // Chat conversations.
        $collection->add_database_table(
            'local_savian_ai_chat_conversations',
            [
                'user_id' => 'privacy:metadata:conversations:user_id',
                'course_id' => 'privacy:metadata:conversations:course_id',
                'title' => 'privacy:metadata:conversations:title',
                'timecreated' => 'privacy:metadata:conversations:timecreated',
            ],
            'privacy:metadata:conversations'
        );

        // Chat messages.
        $collection->add_database_table(
            'local_savian_ai_chat_messages',
            [
                'conversation_id' => 'privacy:metadata:messages:conversation_id',
                'role' => 'privacy:metadata:messages:role',
                'content' => 'privacy:metadata:messages:content',
                'feedback' => 'privacy:metadata:messages:feedback',
                'feedback_comment' => 'privacy:metadata:messages:feedback_comment',
                'timecreated' => 'privacy:metadata:messages:timecreated',
            ],
            'privacy:metadata:messages'
        );

        // Chat settings.
        $collection->add_database_table(
            'local_savian_ai_chat_settings',
            [
                'user_id' => 'privacy:metadata:settings:user_id',
                'widget_position' => 'privacy:metadata:settings:widget_position',
                'widget_minimized' => 'privacy:metadata:settings:widget_minimized',
            ],
            'privacy:metadata:settings'
        );

        // Generation history.
        $collection->add_database_table(
            'local_savian_ai_generations',
            [
                'user_id' => 'privacy:metadata:generations:user_id',
                'course_id' => 'privacy:metadata:generations:course_id',
                'generation_type' => 'privacy:metadata:generations:generation_type',
                'status' => 'privacy:metadata:generations:status',
                'timecreated' => 'privacy:metadata:generations:timecreated',
            ],
            'privacy:metadata:generations'
        );

        // Documents (tracks who uploaded/modified).
        $collection->add_database_table(
            'local_savian_ai_documents',
            [
                'usermodified' => 'privacy:metadata:documents:usermodified',
                'course_id' => 'privacy:metadata:documents:course_id',
                'title' => 'privacy:metadata:documents:title',
                'timecreated' => 'privacy:metadata:documents:timecreated',
            ],
            'privacy:metadata:documents'
        );

        // Per-course chat configuration (tracks who modified).
        $collection->add_database_table(
            'local_savian_ai_chat_course_config',
            [
                'usermodified' => 'privacy:metadata:chat_course_config:usermodified',
                'course_id' => 'privacy:metadata:chat_course_config:course_id',
                'timemodified' => 'privacy:metadata:chat_course_config:timemodified',
            ],
            'privacy:metadata:chat_course_config'
        );

        // Chat restrictions (tracks who created).
        $collection->add_database_table(
            'local_savian_ai_chat_restrictions',
            [
                'usermodified' => 'privacy:metadata:chat_restrictions:usermodified',
                'course_id' => 'privacy:metadata:chat_restrictions:course_id',
                'timecreated' => 'privacy:metadata:chat_restrictions:timecreated',
            ],
            'privacy:metadata:chat_restrictions'
        );

        // Analytics reports (who triggered manual reports).
        $collection->add_database_table(
            'local_savian_ai_analytics_reports',
            [
                'course_id' => 'privacy:metadata:analytics_reports:course_id',
                'user_id' => 'privacy:metadata:analytics_reports:user_id',
                'report_type' => 'privacy:metadata:analytics_reports:report_type',
                'timecreated' => 'privacy:metadata:analytics_reports:timecreated',
            ],
            'privacy:metadata:analytics_reports'
        );

        // Analytics events (real-time tracking).
        $collection->add_database_table(
            'local_savian_ai_analytics_events',
            [
                'course_id' => 'privacy:metadata:analytics_events:course_id',
                'user_id' => 'privacy:metadata:analytics_events:user_id',
                'event_name' => 'privacy:metadata:analytics_events:event_name',
                'timecreated' => 'privacy:metadata:analytics_events:timecreated',
            ],
            'privacy:metadata:analytics_events'
        );

        // Analytics cache (anonymized user data).
        $collection->add_database_table(
            'local_savian_ai_analytics_cache',
            [
                'anon_user_id' => 'privacy:metadata:analytics_cache:anon_user_id',
                'course_id' => 'privacy:metadata:analytics_cache:course_id',
                'timecreated' => 'privacy:metadata:analytics_cache:timecreated',
            ],
            'privacy:metadata:analytics_cache'
        );

        // Writing tasks (created by teachers).
        $collection->add_database_table(
            'local_savian_ai_writing_tasks',
            [
                'teacher_user_id' => 'privacy:metadata:writing_tasks:teacher_user_id',
                'course_id'       => 'privacy:metadata:writing_tasks:course_id',
                'title'           => 'privacy:metadata:writing_tasks:title',
                'timecreated'     => 'privacy:metadata:writing_tasks:timecreated',
            ],
            'privacy:metadata:writing_tasks'
        );

        // Writing submissions (student submissions and AI feedback).
        $collection->add_database_table(
            'local_savian_ai_writing_submissions',
            [
                'moodle_user_id' => 'privacy:metadata:writing_submissions:moodle_user_id',
                'feedback_json'  => 'privacy:metadata:writing_submissions:feedback_json',
                'word_count'     => 'privacy:metadata:writing_submissions:word_count',
                'timecreated'    => 'privacy:metadata:writing_submissions:timecreated',
            ],
            'privacy:metadata:writing_submissions'
        );

        // External service data.
        $collection->add_external_location_link(
            'savian_api',
            [
                'user_id' => 'privacy:metadata:external:user_id',
                'user_email' => 'privacy:metadata:external:user_email',
                'course_id' => 'privacy:metadata:external:course_id',
                'chat_message' => 'privacy:metadata:external:chat_message',
                'document_content' => 'privacy:metadata:external:document_content',
                'anonymized_analytics' => 'privacy:metadata:external:anonymized_analytics',
            ],
            'privacy:metadata:external'
        );

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

        // System context for global chat settings.
        $contextlist->add_system_context();

        // Course contexts where user has chat conversations.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_chat_conversations} c ON c.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND c.user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        // Course contexts where user has generation history.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_generations} g ON g.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND g.user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        // Course contexts where user has analytics reports.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_analytics_reports} r ON r.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND r.user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        // Course contexts where user has analytics events.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_analytics_events} e ON e.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND e.user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        // Course contexts where user has writing submissions.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_writing_tasks} wt ON wt.course_id = ctx.instanceid
                  JOIN {local_savian_ai_writing_submissions} ws ON ws.writing_task_id = wt.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND ws.moodle_user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        // Course contexts where user created writing tasks (teachers).
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_savian_ai_writing_tasks} wt ON wt.course_id = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND wt.teacher_user_id = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

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
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                // Export chat settings (system-level user preference).
                $settings = $DB->get_record('local_savian_ai_chat_settings', ['user_id' => $userid]);
                if ($settings) {
                    writer::with_context($context)->export_data(
                        [get_string('privacy:chatsettings', 'local_savian_ai')],
                        (object) [
                            'widget_position' => $settings->widget_position,
                            'widget_minimized' => $settings->widget_minimized,
                        ]
                    );
                }
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                $courseid = $context->instanceid;

                // Export chat conversations for this course.
                $conversations = $DB->get_records(
                    'local_savian_ai_chat_conversations',
                    ['user_id' => $userid, 'course_id' => $courseid]
                );

                foreach ($conversations as $conversation) {
                    $messages = $DB->get_records(
                        'local_savian_ai_chat_messages',
                        ['conversation_id' => $conversation->id],
                        'timecreated ASC'
                    );

                    $conversationdata = (object) [
                        'title' => $conversation->title,
                        'course_id' => $conversation->course_id,
                        'created' => \core_privacy\local\request\transform::datetime($conversation->timecreated),
                        'messages' => array_map(
                            function ($msg) {
                                return [
                                    'role' => $msg->role,
                                    'content' => $msg->content,
                                    'feedback' => $msg->feedback,
                                    'feedback_comment' => $msg->feedback_comment,
                                    'created' => \core_privacy\local\request\transform::datetime($msg->timecreated),
                                ];
                            },
                            array_values($messages)
                        ),
                    ];

                    writer::with_context($context)->export_data(
                        [get_string('privacy:chatdata', 'local_savian_ai'), $conversation->id],
                        $conversationdata
                    );
                }

                // Export generation history for this course.
                $generations = $DB->get_records(
                    'local_savian_ai_generations',
                    ['user_id' => $userid, 'course_id' => $courseid]
                );
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

                // Export analytics reports for this course.
                $reports = $DB->get_records(
                    'local_savian_ai_analytics_reports',
                    ['user_id' => $userid, 'course_id' => $courseid]
                );
                foreach ($reports as $report) {
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

                // Export analytics events for this course.
                $events = $DB->get_records(
                    'local_savian_ai_analytics_events',
                    ['user_id' => $userid, 'course_id' => $courseid]
                );
                if (!empty($events)) {
                    $eventsdata = array_map(
                        function ($event) {
                            return [
                                'course_id' => $event->course_id,
                                'event_name' => $event->event_name,
                                'processed' => \core_privacy\local\request\transform::yesno($event->processed),
                                'created' => \core_privacy\local\request\transform::datetime($event->timecreated),
                            ];
                        },
                        array_values($events)
                    );

                    writer::with_context($context)->export_data(
                        [get_string('privacy:analyticsevents', 'local_savian_ai')],
                        (object) ['events' => $eventsdata]
                    );
                }

                // Export writing submissions for this course.
                $sql = "SELECT ws.*
                          FROM {local_savian_ai_writing_submissions} ws
                          JOIN {local_savian_ai_writing_tasks} wt ON wt.id = ws.writing_task_id
                         WHERE ws.moodle_user_id = :userid
                           AND wt.course_id = :courseid";
                $submissions = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
                foreach ($submissions as $submission) {
                    writer::with_context($context)->export_data(
                        [get_string('writing_practice', 'local_savian_ai'), $submission->id],
                        (object) [
                            'submission_uuid' => $submission->submission_uuid,
                            'status'          => $submission->status,
                            'word_count'      => $submission->word_count,
                            'created' => \core_privacy\local\request\transform::datetime($submission->timecreated),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Delete all chat settings.
            $DB->delete_records('local_savian_ai_chat_settings');
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;

            // Delete messages for conversations in this course.
            $conversations = $DB->get_records('local_savian_ai_chat_conversations', ['course_id' => $courseid]);
            foreach ($conversations as $conversation) {
                $DB->delete_records('local_savian_ai_chat_messages', ['conversation_id' => $conversation->id]);
            }

            // Delete conversations for this course.
            $DB->delete_records('local_savian_ai_chat_conversations', ['course_id' => $courseid]);

            // Delete generations for this course.
            $DB->delete_records('local_savian_ai_generations', ['course_id' => $courseid]);

            // Delete analytics reports for this course.
            $DB->delete_records('local_savian_ai_analytics_reports', ['course_id' => $courseid]);

            // Delete analytics events for this course.
            $DB->delete_records('local_savian_ai_analytics_events', ['course_id' => $courseid]);

            // Delete analytics cache for this course.
            $DB->delete_records('local_savian_ai_analytics_cache', ['course_id' => $courseid]);

            // Delete writing submissions for this course.
            $taskids = $DB->get_fieldset_select(
                'local_savian_ai_writing_tasks',
                'id',
                'course_id = :courseid',
                ['courseid' => $courseid]
            );
            if (!empty($taskids)) {
                list($insql, $inparams) = $DB->get_in_or_equal($taskids, SQL_PARAMS_NAMED);
                $DB->delete_records_select(
                    'local_savian_ai_writing_submissions',
                    'writing_task_id ' . $insql,
                    $inparams
                );
            }
        }
    }

    /**
     * Delete all data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                // Delete chat settings.
                $DB->delete_records('local_savian_ai_chat_settings', ['user_id' => $userid]);
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                $courseid = $context->instanceid;
                self::delete_user_data_in_course($userid, $courseid);
            }
        }
    }

    /**
     * Delete all data for the specified users in a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete data for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            foreach ($userids as $userid) {
                $DB->delete_records('local_savian_ai_chat_settings', ['user_id' => $userid]);
            }
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;
            foreach ($userids as $userid) {
                self::delete_user_data_in_course($userid, $courseid);
            }
        }
    }

    /**
     * Delete user data within a specific course.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     */
    protected static function delete_user_data_in_course(int $userid, int $courseid) {
        global $DB;

        // Delete messages for this user's conversations in this course.
        $conversations = $DB->get_records(
            'local_savian_ai_chat_conversations',
            ['user_id' => $userid, 'course_id' => $courseid]
        );
        foreach ($conversations as $conversation) {
            $DB->delete_records('local_savian_ai_chat_messages', ['conversation_id' => $conversation->id]);
        }

        // Delete conversations.
        $DB->delete_records('local_savian_ai_chat_conversations', ['user_id' => $userid, 'course_id' => $courseid]);

        // Delete generation history.
        $DB->delete_records('local_savian_ai_generations', ['user_id' => $userid, 'course_id' => $courseid]);

        // Delete analytics reports triggered by this user.
        $DB->delete_records('local_savian_ai_analytics_reports', ['user_id' => $userid, 'course_id' => $courseid]);

        // Delete analytics events.
        $DB->delete_records('local_savian_ai_analytics_events', ['user_id' => $userid, 'course_id' => $courseid]);

        // Delete analytics cache (anonymized data).
        $anonymizer = new \local_savian_ai\analytics\anonymizer();
        $anonid = $anonymizer->anonymize_user_id($userid);
        $DB->delete_records('local_savian_ai_analytics_cache', ['anon_user_id' => $anonid, 'course_id' => $courseid]);

        // Delete writing submissions for this user in this course.
        $DB->delete_records('local_savian_ai_writing_submissions', ['moodle_user_id' => $userid]);
    }

    /**
     * Get list of users with data in specified context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Users who have chat settings.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_ai_chat_settings}";
            $userlist->add_from_sql('user_id', $sql, []);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;

            // Users who have conversations in this course.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_ai_chat_conversations} WHERE course_id = :courseid";
            $userlist->add_from_sql('user_id', $sql, ['courseid' => $courseid]);

            // Users who have generation history in this course.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_ai_generations} WHERE course_id = :courseid";
            $userlist->add_from_sql('user_id', $sql, ['courseid' => $courseid]);

            // Users who triggered analytics reports in this course.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_ai_analytics_reports}
                     WHERE course_id = :courseid AND user_id IS NOT NULL";
            $userlist->add_from_sql('user_id', $sql, ['courseid' => $courseid]);

            // Users in analytics events for this course.
            $sql = "SELECT DISTINCT user_id FROM {local_savian_ai_analytics_events} WHERE course_id = :courseid";
            $userlist->add_from_sql('user_id', $sql, ['courseid' => $courseid]);

            // Users who have writing submissions in this course.
            $sql = "SELECT DISTINCT ws.moodle_user_id
                      FROM {local_savian_ai_writing_submissions} ws
                      JOIN {local_savian_ai_writing_tasks} wt ON wt.id = ws.writing_task_id
                     WHERE wt.course_id = :courseid";
            $userlist->add_from_sql('moodle_user_id', $sql, ['courseid' => $courseid]);
        }
    }
}
