<?php
// Test page for debugging chat web services
require_once(__DIR__ . '/../../config.php');

require_login();
$courseid = optional_param('courseid', 2, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/savian_ai/test_chat_service.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Test Chat Services');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Chat Service Test Page');

// Test 1: List conversations
echo html_writer::tag('h3', 'Test 1: List Conversations');
try {
    $manager = new \local_savian_ai\chat\manager();
    $conversations = $manager->list_user_conversations($courseid);

    echo html_writer::tag('pre', 'Success! Found ' . count($conversations) . ' conversations:' . "\n" .
        print_r($conversations, true), ['class' => 'alert alert-success']);
} catch (Exception $e) {
    echo html_writer::tag('pre', 'Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
        ['class' => 'alert alert-danger']);
}

// Test 2: External function call simulation
echo html_writer::tag('h3', 'Test 2: External Function Call', ['class' => 'mt-4']);
try {
    $result = \local_savian_ai\external\chat::list_conversations($courseid);
    echo html_writer::tag('pre', 'Success! External function returned:' . "\n" .
        print_r($result, true), ['class' => 'alert alert-success']);
} catch (Exception $e) {
    echo html_writer::tag('pre', 'Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
        ['class' => 'alert alert-danger']);
}

// Test 3: Check capabilities
echo html_writer::tag('h3', 'Test 3: Capability Check', ['class' => 'mt-4']);
$context = context_course::instance($courseid);
$has_use = has_capability('local/savian_ai:use', $context);
$has_generate = has_capability('local/savian_ai:generate', $context);

echo html_writer::tag('p', "User ID: $USER->id", ['class' => 'mb-1']);
echo html_writer::tag('p', "Course ID: $courseid", ['class' => 'mb-1']);
echo html_writer::tag('p', "Has 'local/savian_ai:use': " . ($has_use ? 'YES' : 'NO'),
    ['class' => $has_use ? 'text-success' : 'text-danger']);
echo html_writer::tag('p', "Has 'local/savian_ai:generate': " . ($has_generate ? 'YES' : 'NO'),
    ['class' => $has_generate ? 'text-success' : 'text-danger']);

// Test 4: Check database
echo html_writer::tag('h3', 'Test 4: Database Check', ['class' => 'mt-4']);
$conv_count = $DB->count_records('local_savian_chat_conversations', ['user_id' => $USER->id]);
$msg_count = $DB->count_records('local_savian_chat_messages');
echo html_writer::tag('p', "Total conversations for your user: $conv_count");
echo html_writer::tag('p', "Total messages in system: $msg_count");

echo html_writer::div(
    html_writer::link(new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← Back', ['class' => 'btn btn-secondary']),
    'mt-4'
);

echo $OUTPUT->footer();
