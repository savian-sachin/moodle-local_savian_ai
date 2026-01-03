<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Savian AI plugin upgrade script
 *
 * @param int $oldversion The version we are upgrading from
 * @return bool Success
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

    return true;
}
