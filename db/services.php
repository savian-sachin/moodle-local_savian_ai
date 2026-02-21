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
 * External service definitions for Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    'local_savian_ai_get_submission_status' => [
        'classname' => 'local_savian_ai\external\writing',
        'methodname' => 'get_submission_status',
        'classpath' => '',
        'description' => 'Poll AI writing submission status',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
