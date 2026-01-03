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

// Header
echo local_savian_ai_render_header(
    get_string('tutorials', 'local_savian_ai'),
    get_string('select_your_role', 'local_savian_ai')
);

// Role Selector
echo html_writer::start_div('text-center mb-4');
echo html_writer::tag('p', get_string('select_your_role', 'local_savian_ai') . ':', ['class' => 'lead']);
echo html_writer::start_div('btn-group btn-group-lg', ['role' => 'group']);

$roles = ['admin', 'teacher', 'student'];
$role_icons = ['admin' => '👨‍💼', 'teacher' => '🎓', 'student' => '📚'];
$role_labels = [
    'admin' => get_string('for_administrators', 'local_savian_ai'),
    'teacher' => get_string('for_teachers', 'local_savian_ai'),
    'student' => get_string('for_students', 'local_savian_ai')
];

foreach ($roles as $r) {
    $active = ($role === $r) ? ' active' : '';
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => $r]),
        $role_icons[$r] . ' ' . $role_labels[$r],
        ['class' => 'btn btn-primary' . $active]
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

// Search Box
echo html_writer::start_div('mb-4');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'tutorial-search',
    'class' => 'form-control',
    'placeholder' => get_string('tutorial_search', 'local_savian_ai')
]);
echo html_writer::end_div();

// Content based on role
echo html_writer::start_div('tutorial-content');

switch ($role) {
    case 'admin':
        show_admin_tutorials();
        break;
    case 'teacher':
        show_teacher_tutorials();
        break;
    case 'student':
        show_student_tutorials();
        break;
    default:
        show_overview();
}

echo html_writer::end_div();

// Search functionality
$PAGE->requires->js_amd_inline("
require(['jquery'], function($) {
    $('#tutorial-search').on('input', function() {
        var query = $(this).val().toLowerCase();
        $('.tutorial-card').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    });
});
");

// Footer
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();

/**
 * Show overview with all tutorials
 */
function show_overview() {
    echo html_writer::tag('h2', '📚 Welcome to Savian AI Tutorials');
    echo html_writer::tag('p', 'Select your role above to see relevant tutorials, or browse all tutorials below.', ['class' => 'lead']);

    echo html_writer::start_div('row mt-4');

    // Admin card
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('div', '👨‍💼', ['class' => 'display-3']);
    echo html_writer::tag('h4', 'Administrators', ['class' => 'card-title']);
    echo html_writer::tag('p', 'Setup, configuration, and monitoring', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'admin']),
        'View Admin Tutorials',
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Teacher card
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('div', '🎓', ['class' => 'display-3']);
    echo html_writer::tag('h4', 'Teachers', ['class' => 'card-title']);
    echo html_writer::tag('p', 'Course generation, quality scores, and best practices', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'teacher']),
        'View Teacher Tutorials',
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Student card
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100 tutorial-card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('div', '📚', ['class' => 'display-3']);
    echo html_writer::tag('h4', 'Students', ['class' => 'card-title']);
    echo html_writer::tag('p', 'Using the AI chat tutor effectively', ['class' => 'card-text']);
    echo html_writer::link(
        new moodle_url('/local/savian_ai/tutorials.php', ['role' => 'student']),
        'View Student Tutorials',
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
function show_admin_tutorials() {
    echo html_writer::tag('h2', '👨‍💼 Administrator Tutorials');

    // Quick Start
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::start_div('card-header bg-primary text-white');
    echo html_writer::tag('h4', '🚀 Quick Start (5 minutes)', ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p', 'Get Savian AI running in 5 minutes:', ['class' => 'lead']);
    echo html_writer::start_tag('ol', ['class' => 'lead']);
    echo html_writer::tag('li', 'Configure API credentials');
    echo html_writer::tag('li', 'Validate connection');
    echo html_writer::tag('li', 'Assign capabilities to roles');
    echo html_writer::tag('li', 'Enable chat widget');
    echo html_writer::tag('li', 'Test with a course');
    echo html_writer::end_tag('ol');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Configuration Guide
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('⚙️ Configuration Guide', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', 'Step 1: Access Settings');
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Navigate to: <strong>Site Administration → Plugins → Local plugins → Savian AI</strong>');
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h5', 'Step 2: Enter API Credentials', ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>API Base URL</strong>: Your Savian AI endpoint (e.g., https://api.savian.ai/moodle/v1/)');
    echo html_writer::tag('li', '<strong>API Key</strong>: Provided by Savian AI');
    echo html_writer::tag('li', '<strong>Organization Code</strong>: Your org identifier');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', 'Step 3: Validate Connection', ['class' => 'mt-3']);
    echo html_writer::tag('p', 'Click <strong>"Validate Connection"</strong> button to test credentials.');
    echo html_writer::div('✅ Success message = Ready to use!', 'alert alert-success');

    echo html_writer::tag('h5', 'Step 4: Configure Chat (Optional)', ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Enable chat widget: ✓');
    echo html_writer::tag('li', 'Default position: Bottom-right');
    echo html_writer::tag('li', 'Welcome message: Customize or use default');
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Assign Capabilities
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('🔐 Assign Capabilities to Roles', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p', 'Give users access to features:');

    echo html_writer::tag('h6', 'For Teachers:');
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Site Admin → Users → Permissions → Define roles');
    echo html_writer::tag('li', 'Click "Teacher" role (or "Editing teacher")');
    echo html_writer::tag('li', 'Search for: "Savian"');
    echo html_writer::tag('li', 'Check: <code>local/savian_ai:generate</code>');
    echo html_writer::tag('li', 'Save changes');
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h6', 'For Students:', ['class' => 'mt-3']);
    echo html_writer::tag('p', 'Already have <code>local/savian_ai:use</code> by default (chat access)');

    echo html_writer::div('💡 <strong>Tip:</strong> Test with a teacher account before rolling out to all faculty', 'alert alert-info');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Monitoring
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('📊 Monitoring Usage', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p', 'Track system-wide activity:');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>Chat Monitor</strong>: Site Admin → Local plugins → Savian AI → Chat Monitoring');
    echo html_writer::tag('li', 'View: Total conversations, active users, feedback statistics');
    echo html_writer::tag('li', 'Filter by: Course, date range, user');
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Troubleshooting
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('🔧 Troubleshooting', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h6', 'Chat widget not appearing:');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Check: Chat widget enabled in settings');
    echo html_writer::tag('li', 'Verify: User has <code>local/savian_ai:use</code> capability');
    echo html_writer::tag('li', 'Purge caches: Site Admin → Development → Purge all caches');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h6', 'Connection validation fails:', ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Verify API URL is correct and accessible');
    echo html_writer::tag('li', 'Check API key is valid');
    echo html_writer::tag('li', 'Ensure organization code matches');
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Teacher tutorials
 */
function show_teacher_tutorials() {
    echo html_writer::tag('h2', '🎓 Teacher Tutorials');

    // Tutorial 1: Upload Documents
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('📄 Tutorial 1: Uploading Documents (2 minutes)', 'card-header bg-info text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', 'Why Upload Documents?');
    echo html_writer::tag('p', 'Your documents become the foundation for AI-generated courses and chat responses.');

    echo html_writer::tag('h5', 'Step-by-Step:');
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Navigate to your course');
    echo html_writer::tag('li', 'Click <strong>"Savian AI"</strong> in course navigation');
    echo html_writer::tag('li', 'Click <strong>"Documents"</strong>');
    echo html_writer::tag('li', 'Click <strong>"+ Upload Document"</strong> button');
    echo html_writer::tag('li', 'Fill the form:
        <ul>
            <li><strong>Title</strong>: Descriptive name</li>
            <li><strong>File</strong>: Choose PDF/DOCX (max 50MB)</li>
            <li><strong>Description</strong>: Optional summary</li>
            <li><strong>Subject Area</strong>: e.g., "Healthcare Ethics"</li>
            <li><strong>Upload to</strong>: Select course or Global Library</li>
        </ul>');
    echo html_writer::tag('li', 'Click <strong>"Upload"</strong>');
    echo html_writer::tag('li', 'Wait for status to change: Uploading → Processing → <strong>Ready</strong> (30-60 seconds)');
    echo html_writer::end_tag('ol');

    echo html_writer::div('💡 <strong>Best Practice:</strong> Upload 2-3 related documents for comprehensive course generation', 'alert alert-success mt-3');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 2: Generate Course
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('🎨 Tutorial 2: Generate Your First Course (10 minutes)', 'card-header bg-success text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p', 'Create a complete course structure with AI in 3-8 minutes:', ['class' => 'lead']);

    echo html_writer::tag('h5', 'Step 1: Start Generation');
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'From course → <strong>Savian AI → Generate Course Content</strong>');
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h5', 'Step 2: Fill the Form', ['class' => 'mt-3']);
    
    echo html_writer::tag('h6', '📚 Basic Information:');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>Target Course</strong>: Auto-filled (your course name)');
    echo html_writer::tag('li', '<strong>Description</strong>: Optional - add specific goals');
    echo html_writer::tag('li', '<strong>Additional Context</strong>: e.g., "First-year medical students" (optional)');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h6', '👥 Learner Profile:', ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>Age Group</strong>: K-5, Middle, High School, Undergrad, Graduate, Professional');
    echo html_writer::tag('li', '<strong>Industry</strong>: Healthcare, Technology, Business, K-12, etc.');
    echo html_writer::tag('li', '<strong>Prior Knowledge</strong>: Beginner, Intermediate, Advanced');
    echo html_writer::end_tag('ul');

    echo html_writer::div('💡 Age group adapts vocabulary and reading level. Industry customizes terminology and examples.', 'alert alert-info');

    echo html_writer::tag('h6', '📄 Source Documents:', ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Select 1-3 documents (Ctrl+Click for multiple)');
    echo html_writer::tag('li', 'Duration: 4-8 weeks recommended');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h6', '🎨 Content Types:', ['class' => 'mt-2']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '✓ Sections (required) - Weekly/topical organization');
    echo html_writer::tag('li', '✓ Pages (required) - 400-800 words, age-adapted');
    echo html_writer::tag('li', 'Activities - Hands-on exercises');
    echo html_writer::tag('li', 'Discussions - Forum prompts');
    echo html_writer::tag('li', '✓ Quizzes (recommended) - Section assessments');
    echo html_writer::tag('li', 'Assignments - Projects with rubrics');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', 'Step 3: Watch Progress (3-8 min)', ['class' => 'mt-4']);
    echo html_writer::tag('p', 'Real-time progress bar shows ADDIE stages:');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '2% - Analyzing learner profile');
    echo html_writer::tag('li', '10% - Course outline ready ✓');
    echo html_writer::tag('li', '45% - Creating Week 3 content');
    echo html_writer::tag('li', '85% - Adding quality markers');
    echo html_writer::tag('li', '100% - Course ready! Auto-redirects to preview');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', 'Step 4: Review Quality (2 min)', ['class' => 'mt-4']);
    echo html_writer::tag('p', 'Preview shows comprehensive quality information. See "Understanding Quality Scores" tutorial for details.');

    echo html_writer::tag('h5', 'Step 5: Add to Course (1 min)', ['class' => 'mt-4']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Optionally uncheck items you don\'t want');
    echo html_writer::tag('li', 'Click <strong>"Add to THIS Course"</strong>');
    echo html_writer::tag('li', 'Wait 10-30 seconds for creation');
    echo html_writer::tag('li', 'Success! View your course to see new sections');
    echo html_writer::end_tag('ol');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 3: Quality Scores
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('📊 Tutorial 3: Understanding Quality Scores (5 minutes)', 'card-header bg-warning');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('p', 'Quality scores help you understand content reliability and guide your review efforts.', ['class' => 'lead']);

    echo html_writer::tag('h5', '📈 Overall Score (0-100)');
    echo html_writer::start_div('row text-center mb-3');
    echo html_writer::div('<div class="h2 text-success">80-100</div><div>Excellent</div><small>Minimal review needed</small>', 'col-md-3');
    echo html_writer::div('<div class="h2 text-warning">60-79</div><div>Good</div><small>Review supplemented parts</small>', 'col-md-3');
    echo html_writer::div('<div class="h2 text-orange">40-59</div><div>Fair</div><small>Significant review needed</small>', 'col-md-3');
    echo html_writer::div('<div class="h2 text-danger">0-39</div><div>Poor</div><small>Upload more documents</small>', 'col-md-3');
    echo html_writer::end_div();

    echo html_writer::tag('h5', '📚 Source Coverage (%)', ['class' => 'mt-4']);
    echo html_writer::tag('p', '<strong>What it measures:</strong> Percentage of content directly from your uploaded documents');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>80%+</strong> = Excellent grounding, minimal AI supplementation');
    echo html_writer::tag('li', '<strong>60-79%</strong> = Good coverage, some gaps filled');
    echo html_writer::tag('li', '<strong><60%</strong> = Moderate supplementation, careful review needed');
    echo html_writer::end_tag('ul');

    echo html_writer::div('💡 Higher coverage = More trustworthy content from YOUR materials', 'alert alert-info');

    echo html_writer::tag('h5', '🎯 Learning Depth (0-100)', ['class' => 'mt-4']);
    echo html_writer::tag('p', '<strong>What it measures:</strong> Bloom\'s taxonomy level - higher-order thinking');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>75+</strong> = Deep learning (analysis, evaluation, creation)');
    echo html_writer::tag('li', '<strong>50-74</strong> = Moderate (mix of levels)');
    echo html_writer::tag('li', '<strong><50</strong> = Surface (memorization focus)');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', '🏷️ Page-Level Tags', ['class' => 'mt-4']);
    echo html_writer::start_div('row');
    echo html_writer::div('<span class="badge badge-success badge-lg">✓ Verified</span><p class="mt-2">85%+ from sources<br>Trust with light review</p>', 'col-md-3 text-center');
    echo html_writer::div('<span class="badge badge-warning badge-lg">⚠️ Review</span><p class="mt-2">70-84% from sources<br>Verify accuracy</p>', 'col-md-3 text-center');
    echo html_writer::div('<span class="badge badge-danger badge-lg">❗ Priority</span><p class="mt-2"><70% from sources<br>Thorough review</p>', 'col-md-3 text-center');
    echo html_writer::div('<span class="badge badge-info badge-lg">ℹ️ Supplemented</span><p class="mt-2">AI-added context<br>Verify specifics</p>', 'col-md-3 text-center');
    echo html_writer::end_div();

    echo html_writer::div('✅ <strong>Best Practice:</strong> Focus your review time on yellow/red items. Green items need minimal review.', 'alert alert-success mt-3');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 4: Knowledge Base
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('💾 Tutorial 4: Saving to Knowledge Base (3 minutes)', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', 'What is the Knowledge Feedback Loop?');
    echo html_writer::tag('p', 'After adding generated content to your course, you can save it back to the knowledge base. This creates a virtuous cycle:');

    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Generate course from documents');
    echo html_writer::tag('li', 'Review and approve content');
    echo html_writer::tag('li', 'Add to your Moodle course');
    echo html_writer::tag('li', '<strong>Save approved course to knowledge base</strong>');
    echo html_writer::tag('li', 'Future courses can now use this approved content as a source!');
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h5', 'Benefits:', ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '✓ Future courses build on vetted content');
    echo html_writer::tag('li', '✓ Students can chat with approved course materials');
    echo html_writer::tag('li', '✓ Reduced review time (60 min → 40 min for similar courses)');
    echo html_writer::tag('li', '✓ Quality improves over time (60% → 85%+ QM scores)');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', 'How to Save:', ['class' => 'mt-3']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'After clicking "Add to THIS Course"');
    echo html_writer::tag('li', 'Success page shows "💡 Save to Knowledge Base" prompt');
    echo html_writer::tag('li', 'Click <strong>"Save to Knowledge Base"</strong>');
    echo html_writer::tag('li', 'Processing takes 2-3 minutes');
    echo html_writer::tag('li', 'Approved course appears in documents as "[Title] (Instructor Approved)"');
    echo html_writer::end_tag('ol');

    echo html_writer::div('🔄 Your knowledge base grows with each approved course!', 'alert alert-success');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Tutorial 5: View Edit Content
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('✏️ Tutorial 5: Reviewing and Editing Content', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', 'View Content (Read-Only)');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'In preview, click <strong>"👁 View"</strong> button on any item');
    echo html_writer::tag('li', 'Modal opens showing full content:
        <ul>
            <li>Pages: Complete 400-800 word content</li>
            <li>Activities: Detailed instructions</li>
            <li>Quizzes: All questions with answers marked</li>
            <li>Assignments: Instructions + rubric table</li>
        </ul>');
    echo html_writer::tag('li', 'Click "Close" when done');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', 'Edit Content', ['class' => 'mt-3']);
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Click <strong>"✏️ Edit"</strong> button');
    echo html_writer::tag('li', 'Modal opens with editable fields:
        <ul>
            <li>Title: Modify if needed</li>
            <li>Content: Full textarea (15 rows)</li>
        </ul>');
    echo html_writer::tag('li', 'Make your changes');
    echo html_writer::tag('li', 'Click <strong>"Save"</strong>');
    echo html_writer::tag('li', 'Changes persist - title updates in preview');
    echo html_writer::tag('li', 'When you add to course, edited version is used');
    echo html_writer::end_tag('ol');

    echo html_writer::div('💡 <strong>Tip:</strong> Focus edits on items with yellow/red quality tags', 'alert alert-info mt-3');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Student tutorials
 */
function show_student_tutorials() {
    echo html_writer::tag('h2', '📚 Student Tutorials');

    // Tutorial: Using Chat
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('💬 Using Your AI Tutor - Complete Guide', 'card-header bg-primary text-white');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h5', '🔍 Finding the Chat');
    echo html_writer::start_tag('ol');
    echo html_writer::tag('li', 'Go to any course');
    echo html_writer::tag('li', 'Look for purple chat bubble in <strong>bottom-right corner</strong>');
    echo html_writer::tag('li', 'Click to open');
    echo html_writer::end_tag('ol');

    echo html_writer::tag('h5', '💬 Asking Questions', ['class' => 'mt-4']);
    echo html_writer::tag('p', '<strong>Good questions to ask:</strong>');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '"What is [concept]?"');
    echo html_writer::tag('li', '"Explain the difference between X and Y"');
    echo html_writer::tag('li', '"How do I apply this concept?"');
    echo html_writer::tag('li', '"Summarize Week 2 content"');
    echo html_writer::tag('li', '"What are the steps to implement Z?"');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('p', '<strong>NOT for:</strong>', ['class' => 'mt-3']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '❌ Homework answers (use for understanding, not solutions)');
    echo html_writer::tag('li', '❌ Quiz/test answers (learning tool only)');
    echo html_writer::tag('li', '❌ Personal information');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', '📚 Understanding Responses', ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>Sources shown</strong>: Every answer includes which documents/pages were used');
    echo html_writer::tag('li', '<strong>Click sources</strong>: See exactly where information came from');
    echo html_writer::tag('li', '<strong>Verify</strong>: Cross-check important information');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', '👍 Providing Feedback', ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', '<strong>Thumbs up</strong>: Answer was helpful');
    echo html_writer::tag('li', '<strong>Thumbs down</strong>: Not helpful or inaccurate');
    echo html_writer::tag('li', 'Your feedback improves the AI over time');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', '🔒 Your Privacy', ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Your conversations are private');
    echo html_writer::tag('li', 'Teachers can view for learning support');
    echo html_writer::tag('li', 'Not shared with other students');
    echo html_writer::tag('li', 'You can request data export or deletion');
    echo html_writer::end_tag('ul');

    echo html_writer::tag('h5', '✨ Best Practices', ['class' => 'mt-4']);
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Be specific in your questions');
    echo html_writer::tag('li', 'Ask one question at a time');
    echo html_writer::tag('li', 'Provide context if needed');
    echo html_writer::tag('li', 'Check the sources provided');
    echo html_writer::tag('li', 'Use for concept clarification and learning');
    echo html_writer::end_tag('ul');

    echo html_writer::div('💡 The AI tutor is here to help you learn, not to do your work for you. Use it wisely!', 'alert alert-success mt-3');

    echo html_writer::end_div();
    echo html_writer::end_div();

    // FAQs
    echo html_writer::start_div('card mb-4 tutorial-card');
    echo html_writer::div('❓ Frequently Asked Questions', 'card-header');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h6', 'Q: Can the AI do my homework?');
    echo html_writer::tag('p', 'A: No. The AI is a learning tool to help you understand concepts, not to provide answers to assignments. Use it to clarify understanding, then do the work yourself.');

    echo html_writer::tag('h6', 'Q: Are my chats private?', ['class' => 'mt-3']);
    echo html_writer::tag('p', 'A: Yes. Your conversations are private to you. Teachers can view them for learning support, but they\'re not shared with other students.');

    echo html_writer::tag('h6', 'Q: Where does the AI get its answers?', ['class' => 'mt-3']);
    echo html_writer::tag('p', 'A: From your course materials - uploaded documents, course pages, and approved content. Sources are shown with each answer.');

    echo html_writer::tag('h6', 'Q: What if the AI gives a wrong answer?', ['class' => 'mt-3']);
    echo html_writer::tag('p', 'A: Use the thumbs down button and let your teacher know. Always verify important information against course materials.');

    echo html_writer::end_div();
    echo html_writer::end_div();
}
