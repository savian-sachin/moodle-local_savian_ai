<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_savian_ai_send_chat_message' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'send_message',
        'classpath' => '',
        'description' => 'Send a chat message to Savian AI',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_get_conversation' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'get_conversation',
        'classpath' => '',
        'description' => 'Get conversation with message history',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_list_conversations' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'list_conversations',
        'classpath' => '',
        'description' => 'List user conversations',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_submit_feedback' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'submit_feedback',
        'classpath' => '',
        'description' => 'Submit feedback for an AI response',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_save_widget_state' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'save_widget_state',
        'classpath' => '',
        'description' => 'Save chat widget preferences',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_get_course_documents' => [
        'classname' => 'local_savian_ai\external\chat',
        'methodname' => 'get_course_documents',
        'classpath' => '',
        'description' => 'Get course documents for chat context',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_get_generation_status' => [
        'classname' => 'local_savian_ai\external\generation',
        'methodname' => 'get_generation_status',
        'classpath' => '',
        'description' => 'Get course generation status for AJAX polling',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_savian_ai_save_course_structure' => [
        'classname' => 'local_savian_ai\external\generation',
        'methodname' => 'save_course_structure',
        'classpath' => '',
        'description' => 'Save edited course structure to session',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
