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
 * Upgrade steps for Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Savian AI plugin upgrade script.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Success.
 */
function xmldb_local_savian_ai_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026010100) {
        // Create chat conversations table.
        $table = new xmldb_table('local_savian_chat_conversations');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('conversation_uuid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('context_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'course');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('document_ids', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('message_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('last_message_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('is_archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id']);
        $table->add_key('conversation_uuid', XMLDB_KEY_UNIQUE, ['conversation_uuid']);

        $table->add_index('course_id', XMLDB_INDEX_NOTUNIQUE, ['course_id']);
        $table->add_index('user_course', XMLDB_INDEX_NOTUNIQUE, ['user_id', 'course_id']);
        $table->add_index('last_message_at', XMLDB_INDEX_NOTUNIQUE, ['last_message_at']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create chat messages table.
        $table = new xmldb_table('local_savian_chat_messages');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('conversation_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message_uuid', XMLDB_TYPE_CHAR, '36', null, null, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('formatted_content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sources', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('feedback', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('feedback_comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('token_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('response_time_ms', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('conversation_id', XMLDB_KEY_FOREIGN, ['conversation_id'], 'local_savian_chat_conversations', ['id']);

        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        $table->add_index('role', XMLDB_INDEX_NOTUNIQUE, ['role']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create chat settings table.
        $table = new xmldb_table('local_savian_chat_settings');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('widget_position', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'bottom-right');
        $table->add_field('widget_minimized', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sound_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('theme_preference', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'auto');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN_UNIQUE, ['user_id'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026010100, 'local', 'savian_ai');
    }

    if ($oldversion < 2026010101) {
        // Create per-course chat configuration table.
        $table = new xmldb_table('local_savian_chat_course_config');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('chat_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('students_can_chat', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('welcome_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('auto_include_docs', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_id', XMLDB_KEY_FOREIGN_UNIQUE, ['course_id'], 'course', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026010101, 'local', 'savian_ai');
    }

    if ($oldversion < 2026010302) {
        // Create chat restrictions table for time-based chat disabling.
        $table = new xmldb_table('local_savian_chat_restrictions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('restriction_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('quiz_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('restriction_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('is_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_id_fk', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);
        $table->add_key('quiz_id_fk', XMLDB_KEY_FOREIGN, ['quiz_id'], 'quiz', ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        $table->add_index('course_enabled', XMLDB_INDEX_NOTUNIQUE, ['course_id', 'is_enabled']);
        $table->add_index('quiz_unique', XMLDB_INDEX_UNIQUE, ['course_id', 'quiz_id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create chat restriction groups mapping table.
        $table = new xmldb_table('local_savian_chat_restriction_groups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('restriction_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('group_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('restriction_id_fk', XMLDB_KEY_FOREIGN, ['restriction_id'], 'local_savian_chat_restrictions', ['id']);
        $table->add_key('group_id_fk', XMLDB_KEY_FOREIGN, ['group_id'], 'groups', ['id']);

        $table->add_index('restriction_group_unique', XMLDB_INDEX_UNIQUE, ['restriction_id', 'group_id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026010302, 'local', 'savian_ai');
    }

    if ($oldversion < 2026010700) {
        // Create analytics reports table for tracking sent analytics reports.
        $table = new xmldb_table('local_savian_analytics_reports');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('report_type', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('trigger_type', XMLDB_TYPE_CHAR, '30', null, null, null, null);
        $table->add_field('date_from', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('date_to', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('student_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('activity_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('api_response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('retry_count', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_id', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id']);

        $table->add_index('report_type', XMLDB_INDEX_NOTUNIQUE, ['report_type']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create analytics cache table for caching extracted metrics.
        $table = new xmldb_table('local_savian_analytics_cache');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('anon_user_id', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('metrics_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('last_activity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('course_user', XMLDB_INDEX_UNIQUE, ['course_id', 'anon_user_id']);
        $table->add_index('last_activity', XMLDB_INDEX_NOTUNIQUE, ['last_activity']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create analytics events table for tracking real-time events.
        $table = new xmldb_table('local_savian_analytics_events');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event_context', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('processed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_id', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id']);

        $table->add_index('processed', XMLDB_INDEX_NOTUNIQUE, ['processed']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026010700, 'local', 'savian_ai');
    }

    return true;
}
