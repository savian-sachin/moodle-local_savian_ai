<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\hook_callbacks;

/**
 * Hook callback for loading chat widget before footer
 *
 * @package    local_savian_ai
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer_html {

    /**
     * Callback to load chat widget on course pages
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $USER, $COURSE, $DB;

        // Check if chat widget is enabled
        $widget_enabled = get_config('local_savian_ai', 'enable_chat_widget');
        if (!$widget_enabled || !isloggedin() || isguestuser()) {
            return;
        }

        // Only show on course pages (not admin, settings, preferences, etc.)
        $context = $COURSE->id != SITEID ? \context_course::instance($COURSE->id) : \context_system::instance();

        // Check if this is a course context
        if ($COURSE->id == SITEID) {
            return; // Never show on site-level pages
        }

        // Get current page type
        $pagetype = $PAGE->pagetype;

        // List of allowed page types (course-related pages students access)
        $allowed_pagetypes = [
            'course-view-',           // Course homepage
            'mod-',                   // All activity modules (forum, quiz, assign, page, etc.)
            'blocks-',                // Block pages within course
            'grade-report-',          // Grade reports
            'local-savian_ai-',       // Our own plugin pages
        ];

        // Check if current page is in allowed list
        $is_allowed_page = false;
        foreach ($allowed_pagetypes as $allowed) {
            if (strpos($pagetype, $allowed) === 0) {
                $is_allowed_page = true;
                break;
            }
        }

        // Block on admin, settings, preferences, user profile edit, etc.
        $blocked_pagetypes = [
            'admin-',
            'my-index',               // Dashboard
            'user-edit',              // Edit profile
            'user-preferences-',      // User preferences
            'course-edit',            // Course settings
            'enrol-',                 // Enrollment pages
        ];

        foreach ($blocked_pagetypes as $blocked) {
            if (strpos($pagetype, $blocked) === 0) {
                return; // Don't show on blocked pages
            }
        }

        // Must be on an allowed page type
        if (!$is_allowed_page) {
            return;
        }

        if (!has_capability('local/savian_ai:use', $context)) {
            return;
        }

        // Check course-level chat settings
        if ($COURSE->id != SITEID) {
            $course_config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $COURSE->id]);

            // If course config exists, check if chat is enabled
            if ($course_config && !$course_config->chat_enabled) {
                return; // Chat disabled for this course
            }

            // Check if students can chat in this course
            $is_student = !has_capability('local/savian_ai:generate', $context);
            if ($course_config && $is_student && !$course_config->students_can_chat) {
                return; // Students can't chat in this course
            }
        }

        // Check for active chat restrictions (students only, teachers bypass)
        $restriction = null;
        if ($COURSE->id != SITEID) {
            $is_student = !has_capability('local/savian_ai:generate', $context);
            if ($is_student) {
                $restriction_manager = new \local_savian_ai\chat\restriction_manager();
                $restriction = $restriction_manager->get_active_restriction($COURSE->id, $USER->id);
            }
        }

        // Load user settings
        $user_settings = $DB->get_record('local_savian_chat_settings', ['user_id' => $USER->id]);

        // Get welcome message (course-specific overrides global)
        $welcome_message = get_config('local_savian_ai', 'chat_welcome_message');
        if ($COURSE->id != SITEID) {
            $course_config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $COURSE->id]);
            if ($course_config && !empty($course_config->welcome_message)) {
                $welcome_message = $course_config->welcome_message;
            }
        }

        // Prepare config
        $config = [
            'welcomeMessage' => $welcome_message,
            'primaryColor' => get_config('local_savian_ai', 'chat_primary_color') ?: '#6C3BAA',
            'defaultPosition' => get_config('local_savian_ai', 'chat_default_position') ?: 'bottom-right',
            'widgetSize' => get_config('local_savian_ai', 'chat_widget_size') ?: 'medium',
            'canManageDocuments' => has_capability('local/savian_ai:generate', $context),
            'canViewHistory' => has_capability('local/savian_ai:generate', $context),
            'enableFeedback' => get_config('local_savian_ai', 'enable_chat_feedback'),
            'userPosition' => $user_settings ? $user_settings->widget_position : null,
            'userMinimized' => $user_settings ? $user_settings->widget_minimized : 1,
            'restriction' => $restriction ? [
                'isRestricted' => true,
                'message' => $restriction->message,
                'resumesAt' => $restriction->resumes_at,
                'restrictionType' => $restriction->restriction_type,
                'restrictionName' => $restriction->restriction_name ?? ''
            ] : null
        ];

        // Load AMD module
        $PAGE->requires->js_call_amd('local_savian_ai/chat_widget', 'init', [$config]);
    }
}
