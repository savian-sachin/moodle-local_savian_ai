<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_savian_ai', get_string('pluginname', 'local_savian_ai'));

    // API URL
    $settings->add(new admin_setting_configtext(
        'local_savian_ai/api_url',
        get_string('api_url', 'local_savian_ai'),
        get_string('api_url_desc', 'local_savian_ai'),
        '',  // No default - must be configured
        PARAM_URL
    ));

    // Organization Code
    $settings->add(new admin_setting_configtext(
        'local_savian_ai/org_code',
        get_string('org_code', 'local_savian_ai'),
        get_string('org_code_desc', 'local_savian_ai'),
        't001',
        PARAM_ALPHANUMEXT
    ));

    // API Key
    $settings->add(new admin_setting_configpasswordunmask(
        'local_savian_ai/api_key',
        get_string('api_key', 'local_savian_ai'),
        get_string('api_key_desc', 'local_savian_ai'),
        ''
    ));

    // === CHAT WIDGET SETTINGS ===
    $settings->add(new admin_setting_heading(
        'local_savian_ai/chat_heading',
        get_string('chat_settings_heading', 'local_savian_ai'),
        get_string('chat_settings_desc', 'local_savian_ai')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_savian_ai/enable_chat_widget',
        get_string('enable_chat_widget', 'local_savian_ai'),
        get_string('enable_chat_widget_desc', 'local_savian_ai'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_savian_ai/chat_course_pages_only',
        get_string('chat_course_pages_only', 'local_savian_ai'),
        get_string('chat_course_pages_only_desc', 'local_savian_ai'),
        1
    ));

    $settings->add(new admin_setting_confightmleditor(
        'local_savian_ai/chat_welcome_message',
        get_string('chat_welcome_message', 'local_savian_ai'),
        get_string('chat_welcome_message_desc', 'local_savian_ai'),
        get_string('default_welcome_message', 'local_savian_ai')
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_savian_ai/chat_primary_color',
        get_string('chat_primary_color', 'local_savian_ai'),
        get_string('chat_primary_color_desc', 'local_savian_ai'),
        '#6C3BAA'
    ));

    $settings->add(new admin_setting_configselect(
        'local_savian_ai/chat_default_position',
        get_string('chat_default_position', 'local_savian_ai'),
        get_string('chat_default_position_desc', 'local_savian_ai'),
        'bottom-right',
        [
            'bottom-right' => get_string('position_bottom_right', 'local_savian_ai'),
            'bottom-left' => get_string('position_bottom_left', 'local_savian_ai')
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_savian_ai/chat_widget_size',
        get_string('chat_widget_size', 'local_savian_ai'),
        get_string('chat_widget_size_desc', 'local_savian_ai'),
        'medium',
        [
            'small' => get_string('size_small', 'local_savian_ai'),
            'medium' => get_string('size_medium', 'local_savian_ai'),
            'large' => get_string('size_large', 'local_savian_ai')
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_savian_ai/enable_conversation_history',
        get_string('enable_conversation_history', 'local_savian_ai'),
        get_string('enable_conversation_history_desc', 'local_savian_ai'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_savian_ai/enable_chat_feedback',
        get_string('enable_chat_feedback', 'local_savian_ai'),
        get_string('enable_chat_feedback_desc', 'local_savian_ai'),
        1
    ));

    $ADMIN->add('localplugins', $settings);

    // Add link to plugin management page
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_savian_ai_dashboard',
            get_string('dashboard', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/index.php'),
            'local/savian_ai:use'
        )
    );

    // Add chat monitoring dashboard link (admins only)
    if (has_capability('local/savian_ai:manage', context_system::instance())) {
        $ADMIN->add(
            'localplugins',
            new admin_externalpage(
                'local_savian_ai_chat_monitor',
                get_string('chat_monitoring', 'local_savian_ai'),
                new moodle_url('/local/savian_ai/chat_monitor.php'),
                'local/savian_ai:manage'
            )
        );
    }
}
