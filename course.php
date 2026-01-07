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

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Dashboard is for teachers/managers only (not students)
require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Consistent header
echo local_savian_ai_render_header('Dashboard', $course->fullname);

// Statistics
$doc_count = $DB->count_records('local_savian_documents', ['course_id' => $courseid, 'is_active' => 1]);
$questions_count = $DB->get_field_sql(
    'SELECT COALESCE(SUM(questions_count), 0) FROM {local_savian_generations} WHERE course_id = ? AND generation_type IN (?, ?)',
    [$courseid, 'questions', 'questions_from_documents']
);

// Get course content generation stats
$course_generations = $DB->get_records('local_savian_generations', [
    'course_id' => $courseid,
    'generation_type' => 'course_content'
]);

$sections_created = 0;
$pages_created = 0;
$quizzes_created = 0;
$assignments_created = 0;

foreach ($course_generations as $gen) {
    if (!empty($gen->response_data)) {
        $response = json_decode($gen->response_data);
        if (isset($response->sections_created)) {
            $sections_created += $response->sections_created;
            $pages_created += $response->pages_created ?? 0;
            $quizzes_created += $response->quizzes_created ?? 0;
            $assignments_created += $response->assignments_created ?? 0;
        }
    }
}

// Get last activity
$last_activity = $DB->get_field_sql(
    'SELECT MAX(timecreated) FROM {local_savian_generations} WHERE course_id = ?',
    [$courseid]
);

echo html_writer::start_div('card mb-4 savian-accent-card');
echo html_writer::div('📊 Course Statistics', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_div('row');

// Documents stat
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $doc_count, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', 'Documents', ['class' => 'text-muted']);
echo html_writer::end_div();

// Questions stat
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $questions_count, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', 'Questions', ['class' => 'text-muted']);
echo html_writer::end_div();

// Sections stat
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $sections_created, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', 'Sections', ['class' => 'text-muted']);
echo html_writer::end_div();

// Pages stat
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $pages_created + $quizzes_created + $assignments_created, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', 'Activities', ['class' => 'text-muted']);
echo html_writer::end_div();

echo html_writer::end_div();

// Last activity
if ($last_activity) {
    echo html_writer::div(
        html_writer::tag('small', 'Last activity: ' . userdate($last_activity, '%d %B %Y, %H:%M'), ['class' => 'text-muted']),
        'mt-2 border-top pt-2'
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

// Feature cards
echo html_writer::start_div('row');

// Documents card
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::div('📄 Documents', 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag('p', 'Upload and manage documents for AI content generation.', ['class' => 'card-text']);
echo html_writer::link(
    new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]),
    'Manage Documents',
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Chat card
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::div('💬 AI Chat', 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag('p', 'Ask questions about course materials and get instant AI-powered answers.', ['class' => 'card-text']);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/chat.php', ['courseid' => $courseid]),
        'Open Chat',
        ['class' => 'btn btn-savian mr-2']
    ) .
    (has_capability('local/savian_ai:generate', $context) ?
        html_writer::link(
            new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]),
            '⚙ Settings',
            ['class' => 'btn btn-outline-secondary btn-sm']
        ) : ''),
    ''
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Generate Questions card
if (has_capability('local/savian_ai:generate', $context)) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100');
    echo html_writer::div('❓ Generate Questions', 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', 'Create quiz questions from your course documents.', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'mode' => 'documents']),
        'Generate Questions',
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Course Content Generation card
    echo html_writer::start_div('col-md-12 mb-3');
    echo html_writer::start_div('card');
    echo html_writer::div('📚 Generate Course Content', 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', 'Generate complete course sections with pages, quizzes, and assignments from your documents.', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
        'Generate Course Content',
        ['class' => 'btn btn-savian']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Learning Analytics card
    echo html_writer::start_div('col-md-12 mb-3');
    echo html_writer::start_div('card savian-accent-card');
    echo html_writer::div('📊 Learning Analytics', 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', 'Get AI-powered insights on student performance and engagement. Identify at-risk students who need intervention.', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
        '📊 Analytics Dashboard',
        ['class' => 'btn btn-savian']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // row

// Back to course
echo html_writer::div(
    html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        '← Back to course',
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

// Help & Tutorials Link
echo html_writer::start_div('text-center mt-5 mb-4');
echo html_writer::link(
    new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'teacher']),
    '<i class="fa fa-question-circle mr-2"></i>' . get_string('tutorials', 'local_savian_ai'),
    ['class' => 'btn btn-info btn-lg']
);
echo html_writer::tag('p', 'Need help? Check out our step-by-step tutorials!', ['class' => 'text-muted mt-2']);
echo html_writer::end_div();

// Coming Soon Features (Collapsible)
echo html_writer::start_div('mt-5 mb-4');
echo html_writer::start_tag('details', ['class' => 'border rounded p-3 bg-light']);
echo html_writer::tag('summary', '🔮 Coming Soon: Advanced Features', ['class' => 'font-weight-bold text-success', 'style' => 'cursor: pointer;']);
echo html_writer::start_div('mt-3');

echo html_writer::tag('h6', '✨ Personalized Learning Paths', ['class' => 'text-success']);
echo html_writer::tag('p', 'AI will automatically generate personalized content for struggling students:', ['class' => 'small']);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag('li', '🎯 Auto-generate review materials for struggling topics identified in analytics');
echo html_writer::tag('li', '📤 Automatically assign personalized content to at-risk students');
echo html_writer::tag('li', '📊 Track improvement after interventions');
echo html_writer::tag('li', '🤖 Adaptive difficulty based on student performance');
echo html_writer::end_tag('ul');

echo html_writer::tag('h6', '📝 AI-Powered Assessment Grading', ['class' => 'text-success mt-3']);
echo html_writer::tag('p', 'Automatic grading and feedback for open-ended responses:', ['class' => 'small']);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag('li', '✨ Auto-grade short answer and essay questions');
echo html_writer::tag('li', '📊 Rubric-based assessment with AI suggestions');
echo html_writer::tag('li', '💬 Detailed personalized feedback for each student');
echo html_writer::tag('li', '⏱️ Save 70% of grading time');
echo html_writer::end_tag('ul');

echo html_writer::tag('p', '<strong>Example workflow:</strong> Analytics identifies 15 students struggling with "Neural Networks" → AI generates review quiz → Auto-assigns to those 15 students → Tracks their improvement', ['class' => 'small text-muted']);

echo html_writer::end_div();
echo html_writer::end_tag('details');
echo html_writer::end_div();

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
