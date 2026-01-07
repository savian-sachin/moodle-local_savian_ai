<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('chat_course_settings', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

// Restriction manager instance
$restriction_manager = new \local_savian_ai\chat\restriction_manager();

// Handle restriction actions
if (in_array($action, ['save_restriction', 'delete_restriction', 'toggle_restriction']) && confirm_sesskey()) {
    $redirect_url = new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]);

    if ($action === 'save_restriction') {
        $data = new stdClass();
        $data->id = optional_param('restriction_id', 0, PARAM_INT);
        $data->restriction_type = required_param('restriction_type', PARAM_ALPHA);
        $data->name = optional_param('restriction_name', '', PARAM_TEXT);
        $data->quiz_id = optional_param('quiz_id', null, PARAM_INT);
        $data->timestart = optional_param('timestart', 0, PARAM_INT);
        $data->timeend = optional_param('timeend', 0, PARAM_INT);
        $data->restriction_message = optional_param('restriction_message', '', PARAM_TEXT);
        $data->is_enabled = optional_param('is_enabled', 1, PARAM_INT);
        $data->group_ids = optional_param_array('group_ids', [], PARAM_INT);

        // Validation
        if ($data->restriction_type === 'manual') {
            if ($data->timeend > 0 && $data->timestart > 0 && $data->timeend <= $data->timestart) {
                redirect($redirect_url, get_string('error_invalid_time_range', 'local_savian_ai'), null, 'error');
            }
        }

        $restriction_manager->save_restriction($data, $courseid, $USER->id);
        redirect($redirect_url, get_string('restriction_saved', 'local_savian_ai'), null, 'success');
    }

    if ($action === 'delete_restriction') {
        $restrictionid = required_param('restriction_id', PARAM_INT);
        if ($restriction_manager->delete_restriction($restrictionid, $courseid)) {
            redirect($redirect_url, get_string('restriction_deleted', 'local_savian_ai'), null, 'success');
        } else {
            redirect($redirect_url, get_string('error_restriction_not_found', 'local_savian_ai'), null, 'error');
        }
    }

    if ($action === 'toggle_restriction') {
        $restrictionid = required_param('restriction_id', PARAM_INT);
        $result = $restriction_manager->toggle_restriction($restrictionid, $courseid, $USER->id);
        if ($result !== false) {
            redirect($redirect_url, get_string('restriction_toggled', 'local_savian_ai'), null, 'success');
        } else {
            redirect($redirect_url, get_string('error_restriction_not_found', 'local_savian_ai'), null, 'error');
        }
    }
}

// Handle form submission
if ($action === 'save' && confirm_sesskey()) {
    $chat_enabled = optional_param('chat_enabled', 0, PARAM_INT);
    $students_can_chat = optional_param('students_can_chat', 0, PARAM_INT);
    $welcome_message = optional_param('welcome_message', '', PARAM_TEXT);
    $auto_include_docs = optional_param('auto_include_docs', 0, PARAM_INT);

    $config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $courseid]);

    if ($config) {
        $config->chat_enabled = $chat_enabled;
        $config->students_can_chat = $students_can_chat;
        $config->welcome_message = $welcome_message;
        $config->auto_include_docs = $auto_include_docs;
        $config->timemodified = time();
        $config->usermodified = $USER->id;
        $DB->update_record('local_savian_chat_course_config', $config);
    } else {
        $config = new stdClass();
        $config->course_id = $courseid;
        $config->chat_enabled = $chat_enabled;
        $config->students_can_chat = $students_can_chat;
        $config->welcome_message = $welcome_message;
        $config->auto_include_docs = $auto_include_docs;
        $config->timemodified = time();
        $config->usermodified = $USER->id;
        $DB->insert_record('local_savian_chat_course_config', $config);
    }

    redirect(new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]),
             get_string('settings_saved', 'local_savian_ai'), null, 'success');
}

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header(
    get_string('chat_course_settings', 'local_savian_ai'),
    get_string('chat_course_settings_desc', 'local_savian_ai')
);

// Get current settings
$config = $DB->get_record('local_savian_chat_course_config', ['course_id' => $courseid]);
if (!$config) {
    $config = new stdClass();
    $config->chat_enabled = 1;
    $config->students_can_chat = 1;
    $config->welcome_message = '';
    $config->auto_include_docs = 1;
}

// Settings form
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid])
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('chat_settings', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');

// Enable chat checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'chat_enabled',
    'id' => 'chat_enabled',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->chat_enabled ? 'checked' : null
]);
echo html_writer::tag('label', get_string('enable_chat_for_course', 'local_savian_ai'), [
    'for' => 'chat_enabled',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('enable_chat_for_course_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Students can chat checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'students_can_chat',
    'id' => 'students_can_chat',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->students_can_chat ? 'checked' : null
]);
echo html_writer::tag('label', get_string('students_can_chat', 'local_savian_ai'), [
    'for' => 'students_can_chat',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('students_can_chat_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Auto-include documents checkbox
echo html_writer::start_div('form-group mb-3');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'auto_include_docs',
    'id' => 'auto_include_docs',
    'class' => 'form-check-input',
    'value' => 1,
    'checked' => $config->auto_include_docs ? 'checked' : null
]);
echo html_writer::tag('label', get_string('auto_include_docs', 'local_savian_ai'), [
    'for' => 'auto_include_docs',
    'class' => 'form-check-label font-weight-bold'
]);
echo html_writer::end_div();
echo html_writer::tag('small', get_string('auto_include_docs_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// Welcome message textarea
echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('course_welcome_message', 'local_savian_ai'), [
    'for' => 'welcome_message',
    'class' => 'font-weight-bold'
]);
echo html_writer::tag('textarea', s($config->welcome_message ?? ''), [
    'name' => 'welcome_message',
    'id' => 'welcome_message',
    'class' => 'form-control',
    'rows' => 3,
    'placeholder' => get_string('course_welcome_message_placeholder', 'local_savian_ai')
]);
echo html_writer::tag('small', get_string('course_welcome_message_desc', 'local_savian_ai'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

// Save button
echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('savechanges'),
        'class' => 'btn btn-primary'
    ]),
    'text-center mt-3'
);

echo html_writer::end_tag('form');

// ======== CHAT RESTRICTIONS SECTION ========
echo html_writer::start_div('card mb-4 mt-4');
echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
echo html_writer::tag('span', get_string('chat_restrictions', 'local_savian_ai'), ['class' => 'font-weight-bold']);
echo html_writer::start_div('');
echo html_writer::link('#addQuizRestrictionModal', get_string('add_quiz_restriction', 'local_savian_ai'), [
    'class' => 'btn btn-sm btn-outline-primary mr-2',
    'data-toggle' => 'modal'
]);
echo html_writer::link('#addManualRestrictionModal', get_string('add_manual_restriction', 'local_savian_ai'), [
    'class' => 'btn btn-sm btn-outline-secondary',
    'data-toggle' => 'modal'
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card-body');
echo html_writer::tag('p', get_string('chat_restrictions_desc', 'local_savian_ai'), ['class' => 'text-muted small mb-3']);

// Get existing restrictions
$restrictions = $restriction_manager->get_restrictions($courseid);

if (empty($restrictions)) {
    echo html_writer::div(get_string('no_restrictions', 'local_savian_ai'), 'alert alert-info');
} else {
    // Restrictions table
    $table = new html_table();
    $table->head = [
        get_string('restriction_type_quiz', 'local_savian_ai') . ' / ' . get_string('restriction_name', 'local_savian_ai'),
        get_string('restriction_start', 'local_savian_ai') . ' - ' . get_string('restriction_end', 'local_savian_ai'),
        get_string('select_groups', 'local_savian_ai'),
        get_string('document_status', 'local_savian_ai'),
        get_string('actions', 'moodle')
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($restrictions as $restriction) {
        $row = [];

        // Name/Type
        if ($restriction->restriction_type === 'quiz') {
            $name = html_writer::tag('i', '', ['class' => 'fa fa-question-circle mr-1']);
            $name .= html_writer::tag('span', s($restriction->quiz_name ?: get_string('quiz_deleted', 'local_savian_ai')), ['class' => 'font-weight-bold']);
            $name .= html_writer::tag('span', ' (' . get_string('restriction_type_quiz', 'local_savian_ai') . ')', ['class' => 'text-muted small']);
        } else {
            $name = html_writer::tag('i', '', ['class' => 'fa fa-clock-o mr-1']);
            $name .= html_writer::tag('span', s($restriction->name ?: get_string('unnamed_restriction', 'local_savian_ai')), ['class' => 'font-weight-bold']);
            $name .= html_writer::tag('span', ' (' . get_string('restriction_type_manual', 'local_savian_ai') . ')', ['class' => 'text-muted small']);
        }
        $row[] = $name;

        // Time range
        $start = $restriction->effective_timestart ? userdate($restriction->effective_timestart, get_string('strftimedatetime', 'langconfig')) : '-';
        $end = $restriction->effective_timeend ? userdate($restriction->effective_timeend, get_string('strftimedatetime', 'langconfig')) : get_string('check_back_later', 'local_savian_ai');
        $row[] = $start . html_writer::tag('br', '') . $end;

        // Groups
        if (empty($restriction->group_names)) {
            $row[] = html_writer::tag('span', get_string('all_students', 'local_savian_ai'), ['class' => 'badge badge-secondary']);
        } else {
            $group_badges = '';
            foreach ($restriction->group_names as $group_name) {
                $group_badges .= html_writer::tag('span', s($group_name), ['class' => 'badge badge-info mr-1']);
            }
            $row[] = $group_badges;
        }

        // Status
        $status_class = 'badge-secondary';
        $status_text = get_string('restriction_' . $restriction->status, 'local_savian_ai');
        switch ($restriction->status) {
            case 'active':
                $status_class = 'badge-danger';
                if ($restriction->effective_timeend > 0) {
                    $status_text .= ' - ' . get_string('ends_in', 'local_savian_ai',
                        \local_savian_ai\chat\restriction_manager::format_time_remaining($restriction->effective_timeend));
                }
                break;
            case 'scheduled':
                $status_class = 'badge-warning';
                if ($restriction->effective_timestart > 0) {
                    $status_text .= ' - ' . get_string('starts_in', 'local_savian_ai',
                        \local_savian_ai\chat\restriction_manager::format_time_remaining($restriction->effective_timestart));
                }
                break;
            case 'expired':
                $status_class = 'badge-secondary';
                break;
            case 'disabled':
                $status_class = 'badge-light text-muted';
                break;
        }
        $row[] = html_writer::tag('span', $status_text, ['class' => "badge {$status_class}"]);

        // Actions
        $actions = [];

        // Toggle enable/disable
        $toggle_url = new moodle_url('/local/savian_ai/chat_course_settings.php', [
            'courseid' => $courseid,
            'action' => 'toggle_restriction',
            'restriction_id' => $restriction->id,
            'sesskey' => sesskey()
        ]);
        $toggle_text = $restriction->is_enabled ? get_string('disable_restriction', 'local_savian_ai') : get_string('enable_restriction', 'local_savian_ai');
        $toggle_class = $restriction->is_enabled ? 'btn-outline-warning' : 'btn-outline-success';
        $actions[] = html_writer::link($toggle_url, $toggle_text, ['class' => "btn btn-sm {$toggle_class}"]);

        // Delete
        $delete_url = new moodle_url('/local/savian_ai/chat_course_settings.php', [
            'courseid' => $courseid,
            'action' => 'delete_restriction',
            'restriction_id' => $restriction->id,
            'sesskey' => sesskey()
        ]);
        $actions[] = html_writer::link($delete_url, get_string('delete'), [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("' . get_string('confirm_delete_restriction', 'local_savian_ai') . '");'
        ]);

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo html_writer::end_div();
echo html_writer::end_div();

// ======== ADD QUIZ RESTRICTION MODAL ========
$quizzes = $restriction_manager->get_course_quizzes($courseid);
$groups = $restriction_manager->get_course_groups($courseid);

echo html_writer::start_div('modal fade', ['id' => 'addQuizRestrictionModal', 'tabindex' => '-1', 'role' => 'dialog']);
echo html_writer::start_div('modal-dialog', ['role' => 'document']);
echo html_writer::start_div('modal-content');
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid])
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save_restriction']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'restriction_type', 'value' => 'quiz']);

echo html_writer::start_div('modal-header');
echo html_writer::tag('h5', get_string('add_quiz_restriction', 'local_savian_ai'), ['class' => 'modal-title']);
echo html_writer::tag('button', html_writer::tag('span', '&times;', ['aria-hidden' => 'true']), [
    'type' => 'button', 'class' => 'close', 'data-dismiss' => 'modal', 'aria-label' => 'Close'
]);
echo html_writer::end_div();

echo html_writer::start_div('modal-body');

if (empty($quizzes)) {
    echo html_writer::div(get_string('no_quizzes_available', 'local_savian_ai'), 'alert alert-warning');
} else {
    // Quiz select
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('select_quiz', 'local_savian_ai'), ['for' => 'quiz_id', 'class' => 'font-weight-bold']);
    echo html_writer::start_tag('select', ['name' => 'quiz_id', 'id' => 'quiz_id', 'class' => 'form-control', 'required' => 'required']);
    echo html_writer::tag('option', '-- ' . get_string('select_quiz', 'local_savian_ai') . ' --', ['value' => '']);
    foreach ($quizzes as $quiz) {
        $timing = '';
        if ($quiz->timeopen || $quiz->timeclose) {
            $open = $quiz->timeopen ? userdate($quiz->timeopen, get_string('strftimedatetime', 'langconfig')) : '-';
            $close = $quiz->timeclose ? userdate($quiz->timeclose, get_string('strftimedatetime', 'langconfig')) : '-';
            $timing = " ({$open} - {$close})";
        }
        echo html_writer::tag('option', s($quiz->name) . $timing, ['value' => $quiz->id]);
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_div();

    // Group select
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('select_groups', 'local_savian_ai'), ['class' => 'font-weight-bold']);
    echo html_writer::tag('small', ' (' . get_string('all_students', 'local_savian_ai') . ' if none selected)', ['class' => 'text-muted']);
    if (!empty($groups)) {
        foreach ($groups as $group) {
            echo html_writer::start_div('form-check');
            echo html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'group_ids[]', 'id' => 'quiz_group_' . $group->id,
                'value' => $group->id, 'class' => 'form-check-input'
            ]);
            echo html_writer::tag('label', s($group->name), ['for' => 'quiz_group_' . $group->id, 'class' => 'form-check-label']);
            echo html_writer::end_div();
        }
    } else {
        echo html_writer::div(get_string('all_students', 'local_savian_ai'), 'text-muted');
    }
    echo html_writer::end_div();

    // Custom message
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('restriction_message', 'local_savian_ai'), ['for' => 'quiz_restriction_message']);
    echo html_writer::tag('textarea', '', [
        'name' => 'restriction_message', 'id' => 'quiz_restriction_message', 'class' => 'form-control', 'rows' => 2,
        'placeholder' => get_string('chat_restricted_quiz_default', 'local_savian_ai')
    ]);
    echo html_writer::end_div();
}

echo html_writer::end_div();

echo html_writer::start_div('modal-footer');
echo html_writer::tag('button', get_string('cancel'), ['type' => 'button', 'class' => 'btn btn-secondary', 'data-dismiss' => 'modal']);
if (!empty($quizzes)) {
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']);
}
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// ======== ADD MANUAL RESTRICTION MODAL ========
echo html_writer::start_div('modal fade', ['id' => 'addManualRestrictionModal', 'tabindex' => '-1', 'role' => 'dialog']);
echo html_writer::start_div('modal-dialog', ['role' => 'document']);
echo html_writer::start_div('modal-content');
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid])
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save_restriction']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'restriction_type', 'value' => 'manual']);

echo html_writer::start_div('modal-header');
echo html_writer::tag('h5', get_string('add_manual_restriction', 'local_savian_ai'), ['class' => 'modal-title']);
echo html_writer::tag('button', html_writer::tag('span', '&times;', ['aria-hidden' => 'true']), [
    'type' => 'button', 'class' => 'close', 'data-dismiss' => 'modal', 'aria-label' => 'Close'
]);
echo html_writer::end_div();

echo html_writer::start_div('modal-body');

// Restriction name
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('restriction_name', 'local_savian_ai'), ['for' => 'restriction_name', 'class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'restriction_name', 'id' => 'restriction_name', 'class' => 'form-control',
    'placeholder' => get_string('restriction_name_placeholder', 'local_savian_ai'), 'required' => 'required'
]);
echo html_writer::end_div();

// Start time
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('restriction_start', 'local_savian_ai'), ['for' => 'timestart_input', 'class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'datetime-local', 'name' => 'timestart_input', 'id' => 'timestart_input', 'class' => 'form-control', 'required' => 'required'
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timestart', 'id' => 'timestart']);
echo html_writer::end_div();

// End time
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('restriction_end', 'local_savian_ai'), ['for' => 'timeend_input', 'class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'datetime-local', 'name' => 'timeend_input', 'id' => 'timeend_input', 'class' => 'form-control', 'required' => 'required'
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeend', 'id' => 'timeend']);
echo html_writer::end_div();

// Group select
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('select_groups', 'local_savian_ai'), ['class' => 'font-weight-bold']);
echo html_writer::tag('small', ' (' . get_string('all_students', 'local_savian_ai') . ' if none selected)', ['class' => 'text-muted']);
if (!empty($groups)) {
    foreach ($groups as $group) {
        echo html_writer::start_div('form-check');
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox', 'name' => 'group_ids[]', 'id' => 'manual_group_' . $group->id,
            'value' => $group->id, 'class' => 'form-check-input'
        ]);
        echo html_writer::tag('label', s($group->name), ['for' => 'manual_group_' . $group->id, 'class' => 'form-check-label']);
        echo html_writer::end_div();
    }
} else {
    echo html_writer::div(get_string('all_students', 'local_savian_ai'), 'text-muted');
}
echo html_writer::end_div();

// Custom message
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('restriction_message', 'local_savian_ai'), ['for' => 'manual_restriction_message']);
echo html_writer::tag('textarea', '', [
    'name' => 'restriction_message', 'id' => 'manual_restriction_message', 'class' => 'form-control', 'rows' => 2,
    'placeholder' => get_string('chat_restricted_manual_default', 'local_savian_ai')
]);
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('modal-footer');
echo html_writer::tag('button', get_string('cancel'), ['type' => 'button', 'class' => 'btn btn-secondary', 'data-dismiss' => 'modal']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// JavaScript for datetime conversion
$PAGE->requires->js_amd_inline("
    document.getElementById('timestart_input').addEventListener('change', function() {
        var date = new Date(this.value);
        document.getElementById('timestart').value = Math.floor(date.getTime() / 1000);
    });
    document.getElementById('timeend_input').addEventListener('change', function() {
        var date = new Date(this.value);
        document.getElementById('timeend').value = Math.floor(date.getTime() / 1000);
    });
");

// Back button
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← ' . get_string('back'),
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
