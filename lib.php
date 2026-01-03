<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED: Inject Savian branding CSS and third-party libraries into pages
 *
 * This function is deprecated and kept for backward compatibility.
 * The new hook system is used in classes/hook_callbacks/before_standard_head_html.php
 *
 * @deprecated since Moodle 4.5
 */
function local_savian_ai_before_standard_html_head() {
    // This function is deprecated - functionality moved to hook callbacks
    // See: classes/hook_callbacks/before_standard_head_html.php
    // Keeping empty function to avoid errors during transition
}

/**
 * Render consistent Savian AI page header
 *
 * @param string $title Page title
 * @param string $subtitle Optional subtitle
 * @return string HTML
 */
function local_savian_ai_render_header($title, $subtitle = '') {
    $html = '';

    // Header bar with logo and title
    $html .= html_writer::start_div('card mb-4');
    $html .= html_writer::start_div('card-body p-3');
    $html .= html_writer::start_div('d-flex justify-content-between align-items-center');

    // Title on left
    $html .= html_writer::start_div('');
    $html .= html_writer::tag('h2', $title, ['class' => 'mb-0 h4']);
    if ($subtitle) {
        $html .= html_writer::tag('small', $subtitle, ['class' => 'text-muted']);
    }
    $html .= html_writer::end_div();

    // Savian logo on right
    $html .= html_writer::tag('div', 'SAVIAN AI', ['class' => 'savian-text-primary font-weight-bold']);

    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Render consistent Savian AI footer
 *
 * @return string HTML
 */
function local_savian_ai_render_footer() {
    return html_writer::div(
        html_writer::tag('small',
            'Powered by ' . html_writer::link('https://savian.ai.vn/', 'Savian AI', [
                'target' => '_blank',
                'class' => 'savian-text-primary'
            ]),
            ['class' => 'text-muted']
        ),
        'text-center mt-4 mb-3'
    );
}

/**
 * Add navigation nodes
 *
 * @param global_navigation $navigation Navigation object
 */
function local_savian_ai_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    // Only add to navigation if user has permission
    if (has_capability('local/savian_ai:use', context_system::instance())) {
        $node = $navigation->add(
            get_string('pluginname', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_savian_ai',
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;

        // Add tutorials link
        $node->add(
            get_string('tutorials', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/tutorials.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'savian_tutorials',
            new pix_icon('t/help', '')
        );
    }
}

/**
 * Extend course navigation
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to add nodes for
 * @param context $context The course context
 */
function local_savian_ai_extend_navigation_course($navigation, $course, $context) {
    // Check if user has capability
    if (has_capability('local/savian_ai:use', $context)) {
        // Add single link to course landing page
        $navigation->add(
            get_string('pluginname', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/course.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'local_savian_ai',
            new pix_icon('i/report', '')
        );

        // Add chat history link for teachers
        if (has_capability('local/savian_ai:generate', $context)) {
            $navigation->add(
                get_string('chat_history', 'local_savian_ai'),
                new moodle_url('/local/savian_ai/chat_history.php', ['courseid' => $course->id]),
                navigation_node::TYPE_SETTING,
                null,
                'local_savian_ai_chat_history',
                new pix_icon('i/report', '')
            );
        }
    }
}

/**
 * DEPRECATED: Add chat widget to course pages
 *
 * This function is deprecated and kept for backward compatibility.
 * The new hook system is used in classes/hook_callbacks/before_footer_html.php
 *
 * @deprecated since Moodle 4.5
 */
function local_savian_ai_before_footer() {
    // This function is deprecated - functionality moved to hook callbacks
    // See: classes/hook_callbacks/before_footer_html.php
    // Keeping empty function to avoid errors during transition
}
