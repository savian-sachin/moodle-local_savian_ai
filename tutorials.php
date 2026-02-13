<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Savian AI - Help & Tutorials
 *
 * Comprehensive tutorials for administrators, teachers, and students.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$role = optional_param('role', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/savian_ai/tutorials.php', ['role' => $role]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('tutorials', 'local_savian_ai'));
$PAGE->set_heading(get_string('tutorials', 'local_savian_ai'));

echo $OUTPUT->header();

// Header.
echo local_savian_ai_render_header(
    get_string('tutorials', 'local_savian_ai'),
    get_string('select_your_role', 'local_savian_ai')
);

// Role Selector.
echo html_writer::start_div('text-center mb-4');
echo html_writer::tag('p', get_string('select_your_role', 'local_savian_ai') . ':', ['class' => 'lead']);
echo html_writer::start_div('btn-group btn-group-lg', ['role' => 'group']);

$roles = ['admin', 'teacher', 'student'];
$role_labels = [
    'admin' => get_string('for_administrators', 'local_savian_ai'),
    'teacher' => get_string('for_teachers', 'local_savian_ai'),
    'student' => get_string('for_students', 'local_savian_ai'),
];

foreach ($roles as $r) {
    $active = ($role === $r) ? ' active' : '';
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => $r]),
        $role_labels[$r],
        ['class' => 'btn btn-primary' . $active]
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

// Search Box.
echo html_writer::start_div('mb-4');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'tutorial-search',
    'class' => 'form-control',
    'placeholder' => get_string('tutorial_search', 'local_savian_ai'),
]);
echo html_writer::end_div();

// Content based on role.
echo html_writer::start_div('tutorial-content');

switch ($role) {
    case 'admin':
        local_savian_ai_show_admin_tutorials();
        break;
    case 'teacher':
        local_savian_ai_show_teacher_tutorials();
        break;
    case 'student':
        local_savian_ai_show_student_tutorials();
        break;
    default:
        local_savian_ai_show_overview();
}

echo html_writer::end_div();

// Search functionality via AMD module.
$PAGE->requires->js_call_amd('local_savian_ai/tutorial_search', 'init');

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();

/**
 * Show overview with all tutorials
 */
function local_savian_ai_show_overview() {
    $s = 'local_savian_ai';
    echo html_writer::tag('h2', get_string('tutorial_welcome', $s));
    echo html_writer::tag('p', get_string('tutorial_overview_intro', $s), ['class' => 'lead']);

    echo html_writer::start_div('row mt-4');

    // Admin card.
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h4', get_string('tutorial_administrators', $s), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('tutorial_admin_desc', $s), ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'admin']),
        get_string('tutorial_view_admin', $s),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Teacher card.
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h4', get_string('tutorial_teachers', $s), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('tutorial_teacher_desc', $s), ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'teacher']),
        get_string('tutorial_view_teacher', $s),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Student card.
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h4', get_string('tutorial_students', $s), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('tutorial_student_desc', $s), ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'student']),
        get_string('tutorial_view_student', $s),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();
}

/**
 * Administrator tutorials
 */
function local_savian_ai_show_admin_tutorials() {
    $s = 'local_savian_ai';
    echo html_writer::tag('h2', get_string('tutorial_admin_title', $s));

    // Quick Start.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::start_div('card-header bg-primary text-white');
    echo html_writer::tag('h4', get_string('tutorial_quickstart', $s), ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('tutorial_quickstart_intro', $s), ['class' => 'lead']);
    echo html_writer::start_tag('ol', ['class' => 'lead']);
    echo html_writer::tag('li', get_string('tutorial_quickstart_step1', $s));
    echo html_writer::tag('li', get_string('tutorial_quickstart_step2', $s));
    echo html_writer::tag('li', get_string('tutorial_quickstart_step3', $s));
    echo html_writer::tag('li', get_string('tutorial_quickstart_step4', $s));
    echo html_writer::tag('li', get_string('tutorial_quickstart_step5', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Configuration Guide.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_config_guide', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_step1_access', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_step1_nav', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::tag('h5', get_string('tutorial_step2_credentials', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_api_url_label', $s));
    echo html_writer::tag('li', get_string('tutorial_api_key_label', $s));
    echo html_writer::tag('li', get_string('tutorial_org_code_label', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_step3_validate', $s), ['class' => 'mt-3']);
    echo html_writer::tag('p', get_string('tutorial_validate_desc', $s));
    echo html_writer::div(get_string('tutorial_validate_success', $s), 'alert alert-success');
    echo html_writer::tag('h5', get_string('tutorial_step4_chat', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_chat_enable', $s));
    echo html_writer::tag('li', get_string('tutorial_chat_position', $s));
    echo html_writer::tag('li', get_string('tutorial_chat_welcome', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Assign Capabilities.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_capabilities', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('tutorial_cap_intro', $s));
    echo html_writer::tag('h6', get_string('tutorial_cap_teachers', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_cap_teacher_step1', $s));
    echo html_writer::tag('li', get_string('tutorial_cap_teacher_step2', $s));
    echo html_writer::tag('li', get_string('tutorial_cap_teacher_step3', $s));
    echo html_writer::tag('li', get_string('tutorial_cap_teacher_step4', $s));
    echo html_writer::tag('li', get_string('tutorial_cap_teacher_step5', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::tag('h6', get_string('tutorial_cap_students', $s), ['class' => 'mt-3']);
    echo html_writer::tag('p', get_string('tutorial_cap_student_desc', $s));
    echo html_writer::div(get_string('tutorial_cap_tip', $s), 'alert alert-info');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Monitoring.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_monitoring', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('tutorial_monitoring_intro', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_monitoring_chat', $s));
    echo html_writer::tag('li', get_string('tutorial_monitoring_view', $s));
    echo html_writer::tag('li', get_string('tutorial_monitoring_filter', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Troubleshooting.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_troubleshooting', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', get_string('tutorial_trouble_widget', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_trouble_widget1', $s));
    echo html_writer::tag('li', get_string('tutorial_trouble_widget2', $s));
    echo html_writer::tag('li', get_string('tutorial_trouble_widget3', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h6', get_string('tutorial_trouble_connection', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_trouble_conn1', $s));
    echo html_writer::tag('li', get_string('tutorial_trouble_conn2', $s));
    echo html_writer::tag('li', get_string('tutorial_trouble_conn3', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Teacher tutorials
 */
function local_savian_ai_show_teacher_tutorials() {
    $s = 'local_savian_ai';
    echo html_writer::tag('h2', get_string('tutorial_teacher_title', $s));

    // Tutorial 1: Upload Documents.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_upload_title', $s), 'card-header bg-info text-white');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_upload_why', $s));
    echo html_writer::tag('p', get_string('tutorial_upload_why_desc', $s));
    echo html_writer::tag('h5', get_string('tutorial_upload_steps', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_upload_step1', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step2', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step3', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step4', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step5', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step6', $s));
    echo html_writer::tag('li', get_string('tutorial_upload_step7', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::div(get_string('tutorial_upload_tip', $s), 'alert alert-success mt-3');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 2: Generate Course.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_generate_title', $s), 'card-header bg-success text-white');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('tutorial_generate_intro', $s), ['class' => 'lead']);
    echo html_writer::tag('h5', get_string('tutorial_gen_step1', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_gen_step1_desc', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::tag('h5', get_string('tutorial_gen_step2', $s), ['class' => 'mt-3']);
    echo html_writer::tag('h6', get_string('tutorial_gen_basic', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_gen_target_course', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_description', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_context', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h6', get_string('tutorial_gen_learner', $s), ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_gen_age', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_industry', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_prior', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::div(get_string('tutorial_gen_age_tip', $s), 'alert alert-info');
    echo html_writer::tag('h6', get_string('tutorial_gen_source', $s), ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_gen_select_docs', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_duration', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h6', get_string('tutorial_gen_content_types', $s), ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_gen_ct_sections', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_ct_pages', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_ct_activities', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_ct_discussions', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_ct_quizzes', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_ct_assignments', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_gen_step3', $s), ['class' => 'mt-4']);
    echo html_writer::tag('p', get_string('tutorial_gen_progress_desc', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_gen_progress_2', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_progress_10', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_progress_45', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_progress_85', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_progress_100', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_gen_step4', $s), ['class' => 'mt-4']);
    echo html_writer::tag('p', get_string('tutorial_gen_step4_desc', $s));
    echo html_writer::tag('h5', get_string('tutorial_gen_step5', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_gen_step5_1', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_step5_2', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_step5_3', $s));
    echo html_writer::tag('li', get_string('tutorial_gen_step5_4', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 3: Quality Scores (abbreviated for brevity - uses lang strings).
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_quality_title', $s), 'card-header bg-warning');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('tutorial_quality_intro', $s), ['class' => 'lead']);
    echo html_writer::tag('h5', get_string('tutorial_quality_overall', $s));
    echo html_writer::tag('h5', get_string('tutorial_quality_source', $s), ['class' => 'mt-4']);
    echo html_writer::tag('p', get_string('tutorial_quality_source_desc', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_quality_source_80', $s));
    echo html_writer::tag('li', get_string('tutorial_quality_source_60', $s));
    echo html_writer::tag('li', get_string('tutorial_quality_source_below', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::div(get_string('tutorial_quality_source_tip', $s), 'alert alert-info');
    echo html_writer::tag('h5', get_string('tutorial_quality_depth', $s), ['class' => 'mt-4']);
    echo html_writer::tag('p', get_string('tutorial_quality_depth_desc', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_quality_depth_75', $s));
    echo html_writer::tag('li', get_string('tutorial_quality_depth_50', $s));
    echo html_writer::tag('li', get_string('tutorial_quality_depth_below', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_quality_tags', $s), ['class' => 'mt-4']);
    echo html_writer::div(get_string('tutorial_quality_tip', $s), 'alert alert-success mt-3');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 4: Knowledge Base.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_kb_title', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_kb_what', $s));
    echo html_writer::tag('p', get_string('tutorial_kb_what_desc', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_kb_step1', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_step2', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_step3', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_step4', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_step5', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::tag('h5', get_string('tutorial_kb_benefits', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_kb_benefit1', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_benefit2', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_benefit3', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_benefit4', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_kb_how', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_kb_how1', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_how2', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_how3', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_how4', $s));
    echo html_writer::tag('li', get_string('tutorial_kb_how5', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::div(get_string('tutorial_kb_grows', $s), 'alert alert-success');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 5: View/Edit Content.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_edit_title', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_edit_view', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_edit_view1', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_view2', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_view3', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_edit_heading', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_edit_step1', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_step2', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_step3', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_step4', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_step5', $s));
    echo html_writer::tag('li', get_string('tutorial_edit_step6', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::div(get_string('tutorial_edit_tip', $s), 'alert alert-info mt-3');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 6: Learning Analytics.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_analytics_title', $s), 'card-header bg-danger text-white');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_analytics_what', $s));
    echo html_writer::tag('p', get_string('tutorial_analytics_what_desc', $s), ['class' => 'lead']);
    echo html_writer::tag('h5', get_string('tutorial_analytics_features', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_analytics_feat1', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_feat2', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_feat3', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_feat4', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_feat5', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_analytics_access', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_analytics_access1', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_access2', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_access3', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_access4', $s));
    echo html_writer::tag('li', get_string('tutorial_analytics_access5', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::div(get_string('tutorial_analytics_privacy', $s), 'alert alert-info mt-3');
    echo html_writer::div(get_string('tutorial_analytics_tip', $s), 'alert alert-success');
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 7: Chat History.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_chathistory_title', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('tutorial_chathistory_why', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_chathistory_why1', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_why2', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_why3', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_why4', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::tag('h5', get_string('tutorial_chathistory_access', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_chathistory_access1', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_access2', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_access3', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_access4', $s));
    echo html_writer::end_tag('ol');
    echo html_writer::tag('h5', get_string('tutorial_chathistory_see', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_chathistory_see1', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_see2', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_see3', $s));
    echo html_writer::tag('li', get_string('tutorial_chathistory_see4', $s));
    echo html_writer::end_tag('ul');
    echo html_writer::div(get_string('tutorial_chathistory_tip', $s), 'alert alert-info mt-3');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Student tutorials
 */
function local_savian_ai_show_student_tutorials() {
    $s = 'local_savian_ai';
    echo html_writer::tag('h2', get_string('tutorial_student_title', $s));

    // Tutorial: Using Chat.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_student_guide', $s), 'card-header bg-primary text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', get_string('tutorial_student_find', $s));
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', get_string('tutorial_student_find1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_find2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_find3', $s));
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h5', get_string('tutorial_student_asking', $s), ['class' => 'mt-4']);
    echo html_writer::tag('p', get_string('tutorial_student_good', $s));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_good1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_good2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_good3', $s));
    echo html_writer::tag('li', get_string('tutorial_student_good4', $s));
    echo html_writer::tag('li', get_string('tutorial_student_good5', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::tag('p', get_string('tutorial_student_notfor', $s), ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_notfor1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_notfor2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_notfor3', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', get_string('tutorial_student_responses', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_resp1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_resp2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_resp3', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', get_string('tutorial_student_feedback', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_fb1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_fb2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_fb3', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', get_string('tutorial_student_privacy', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_priv1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_priv2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_priv3', $s));
    echo html_writer::tag('li', get_string('tutorial_student_priv4', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', get_string('tutorial_student_best', $s), ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('tutorial_student_best1', $s));
    echo html_writer::tag('li', get_string('tutorial_student_best2', $s));
    echo html_writer::tag('li', get_string('tutorial_student_best3', $s));
    echo html_writer::tag('li', get_string('tutorial_student_best4', $s));
    echo html_writer::tag('li', get_string('tutorial_student_best5', $s));
    echo html_writer::end_tag('ul');

    echo html_writer::div(get_string('tutorial_student_wise', $s), 'alert alert-success mt-3');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // FAQs.
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div(get_string('tutorial_faq', $s), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', get_string('tutorial_faq_homework_q', $s));
    echo html_writer::tag('p', get_string('tutorial_faq_homework_a', $s));
    echo html_writer::tag('h6', get_string('tutorial_faq_private_q', $s), ['class' => 'mt-3']);
    echo html_writer::tag('p', get_string('tutorial_faq_private_a', $s));
    echo html_writer::tag('h6', get_string('tutorial_faq_source_q', $s), ['class' => 'mt-3']);
    echo html_writer::tag('p', get_string('tutorial_faq_source_a', $s));
    echo html_writer::tag('h6', get_string('tutorial_faq_wrong_q', $s), ['class' => 'mt-3']);
    echo html_writer::tag('p', get_string('tutorial_faq_wrong_a', $s));
    echo html_writer::end_div();
    echo html_writer::end_div();
}
