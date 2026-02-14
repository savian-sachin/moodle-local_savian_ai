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
 * Course content generation page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Dashboard is for teachers/managers only (not students).
require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Consistent header.
echo local_savian_ai_render_header(get_string('dashboard', 'local_savian_ai'), $course->fullname);

// Statistics.
$doccount = $DB->count_records('local_savian_ai_documents', ['course_id' => $courseid, 'is_active' => 1]);
$questionscount = $DB->get_field_sql(
    'SELECT COALESCE(SUM(questions_count), 0)
       FROM {local_savian_ai_generations}
      WHERE course_id = ? AND generation_type IN (?, ?)',
    [$courseid, 'questions', 'questions_from_documents']
);

// Get course content generation stats.
$coursegenerations = $DB->get_records('local_savian_ai_generations', [
    'course_id' => $courseid,
    'generation_type' => 'course_content',
]);

$sectionscreated = 0;
$pagescreated = 0;
$quizzescreated = 0;
$assignmentscreated = 0;

foreach ($coursegenerations as $gen) {
    if (!empty($gen->response_data)) {
        $response = json_decode($gen->response_data);
        if (isset($response->sections_created)) {
            $sectionscreated += $response->sections_created;
            $pagescreated += $response->pages_created ?? 0;
            $quizzescreated += $response->quizzes_created ?? 0;
            $assignmentscreated += $response->assignments_created ?? 0;
        }
    }
}

// Get last activity.
$lastactivity = $DB->get_field_sql(
    'SELECT MAX(timecreated) FROM {local_savian_ai_generations} WHERE course_id = ?',
    [$courseid]
);

echo html_writer::start_div('card mb-4 savian-accent-card');
echo html_writer::div(get_string('course_statistics', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_div('row');

// Documents stat.
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $doccount, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', get_string('documents', 'local_savian_ai'), ['class' => 'text-muted']);
echo html_writer::end_div();

// Questions stat.
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $questionscount, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', get_string('questions_header', 'local_savian_ai'), ['class' => 'text-muted']);
echo html_writer::end_div();

// Sections stat.
echo html_writer::start_div('col-md-3 col-6 mb-2');
echo html_writer::tag('div', $sectionscreated, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', get_string('sections_header', 'local_savian_ai'), ['class' => 'text-muted']);
echo html_writer::end_div();

// Pages stat.
echo html_writer::start_div('col-md-3 col-6 mb-2');
$totalactivities = $pagescreated + $quizzescreated + $assignmentscreated;
echo html_writer::tag('div', $totalactivities, ['class' => 'h3 mb-0 savian-text-primary']);
echo html_writer::tag('small', get_string('summary_activities', 'local_savian_ai'), ['class' => 'text-muted']);
echo html_writer::end_div();

echo html_writer::end_div();

// Last activity.
if ($lastactivity) {
    $lastactivitytext = html_writer::tag(
        'small',
        get_string('last_activity_date', 'local_savian_ai', userdate($lastactivity, '%d %B %Y, %H:%M')),
        ['class' => 'text-muted']
    );
    echo html_writer::div($lastactivitytext, 'mt-2 border-top pt-2');
}

echo html_writer::end_div();
echo html_writer::end_div();

// Feature cards.
echo html_writer::start_div('row');

// Documents card.
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::div(get_string('documents', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'p',
    get_string('documents_card_desc', 'local_savian_ai'),
    ['class' => 'card-text']
);
echo html_writer::link(
    new moodle_url('/local/savian_ai/documents.php', ['courseid' => $courseid]),
    get_string('manage_documents', 'local_savian_ai'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Chat card.
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::div(get_string('chat', 'local_savian_ai'), 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'p',
    get_string('chat_card_desc', 'local_savian_ai'),
    ['class' => 'card-text']
);
$chatbuttons = html_writer::link(
    new moodle_url('/local/savian_ai/chat.php', ['courseid' => $courseid]),
    get_string('open_chat', 'local_savian_ai'),
    ['class' => 'btn btn-savian mr-2']
);
if (has_capability('local/savian_ai:generate', $context)) {
    $chatbuttons .= html_writer::link(
        new moodle_url('/local/savian_ai/chat_course_settings.php', ['courseid' => $courseid]),
        get_string('settings_label', 'local_savian_ai'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );
}
echo html_writer::div($chatbuttons, '');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Generate Questions card.
if (has_capability('local/savian_ai:generate', $context)) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100');
    echo html_writer::div(get_string('generate_questions', 'local_savian_ai'), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag(
        'p',
        get_string('generate_questions_card_desc', 'local_savian_ai'),
        ['class' => 'card-text']
    );
    echo html_writer::link(
        new moodle_url('/local/savian_ai/generate.php', ['courseid' => $courseid, 'mode' => 'documents']),
        get_string('generate_questions', 'local_savian_ai'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Course Content Generation card.
    echo html_writer::start_div('col-md-12 mb-3');
    echo html_writer::start_div('card');
    echo html_writer::div(get_string('generate_course_content', 'local_savian_ai'), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag(
        'p',
        get_string('generate_course_content_desc', 'local_savian_ai'),
        ['class' => 'card-text']
    );
    echo html_writer::link(
        new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
        get_string('generate_course_content', 'local_savian_ai'),
        ['class' => 'btn btn-savian']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Learning Analytics card.
    echo html_writer::start_div('col-md-12 mb-3');
    echo html_writer::start_div('card savian-accent-card');
    echo html_writer::div(get_string('learning_analytics', 'local_savian_ai'), 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::tag(
        'p',
        get_string('analytics_description', 'local_savian_ai'),
        ['class' => 'card-text']
    );
    echo html_writer::link(
        new moodle_url('/local/savian_ai/analytics_reports.php', ['courseid' => $courseid]),
        get_string('learning_analytics', 'local_savian_ai'),
        ['class' => 'btn btn-savian']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // End row.

// Back to course.
echo html_writer::div(
    html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('backtocourse', 'local_savian_ai'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

// Help and tutorials link.
echo html_writer::start_div('text-center mt-5 mb-4');
echo html_writer::link(
    new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'teacher']),
    '<i class="fa fa-question-circle mr-2"></i>'
        . get_string('tutorials', 'local_savian_ai'),
    ['class' => 'btn btn-info btn-lg']
);
echo html_writer::tag(
    'p',
    get_string('tutorials_help_text_teacher', 'local_savian_ai'),
    ['class' => 'text-muted mt-2']
);
echo html_writer::end_div();

// Coming Soon Features (Collapsible).
echo html_writer::start_div('mt-5 mb-4');
echo html_writer::start_tag('details', ['class' => 'border rounded p-3 bg-light']);
echo html_writer::tag(
    'summary',
    get_string('coming_soon_features', 'local_savian_ai'),
    ['class' => 'font-weight-bold text-success', 'style' => 'cursor: pointer;']
);
echo html_writer::start_div('mt-3');

echo html_writer::tag('h6', 'Personalized Learning Paths', ['class' => 'text-success']);
echo html_writer::tag(
    'p',
    'AI will automatically generate personalized content for struggling students:',
    ['class' => 'small']
);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag(
    'li',
    'Auto-generate review materials for struggling topics identified in analytics'
);
echo html_writer::tag('li', 'Automatically assign personalized content to at-risk students');
echo html_writer::tag('li', 'Track improvement after interventions');
echo html_writer::tag('li', 'Adaptive difficulty based on student performance');
echo html_writer::end_tag('ul');

echo html_writer::tag('h6', 'AI-Powered Assessment Grading', ['class' => 'text-success mt-3']);
echo html_writer::tag(
    'p',
    'Automatic grading and feedback for open-ended responses:',
    ['class' => 'small']
);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag('li', 'Auto-grade short answer and essay questions');
echo html_writer::tag('li', 'Rubric-based assessment with AI suggestions');
echo html_writer::tag('li', 'Detailed personalized feedback for each student');
echo html_writer::tag('li', 'Save 70% of grading time');
echo html_writer::end_tag('ul');

echo html_writer::tag(
    'p',
    '<strong>Example workflow:</strong> Analytics identifies 15 students struggling with'
        . ' "Neural Networks" &rarr; AI generates review quiz &rarr; Auto-assigns to those'
        . ' 15 students &rarr; Tracks their improvement',
    ['class' => 'small text-muted']
);

echo html_writer::end_div();
echo html_writer::end_tag('details');
echo html_writer::end_div();

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
