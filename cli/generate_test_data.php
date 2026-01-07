<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

/**
 * Generate realistic test data for analytics testing
 *
 * Creates test users, enrollments, activity logs, quiz attempts, assignments,
 * forum posts, and completion data with realistic patterns.
 *
 * Usage:
 *   php generate_test_data.php --course=123 --students=100 --weeks=12
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'course' => null,
        'students' => 50,
        'weeks' => 12,
        'at-risk-percentage' => 20,
        'seed' => null,
    ],
    [
        'h' => 'help',
        'c' => 'course',
        's' => 'students',
        'w' => 'weeks',
    ]
);

if ($options['help'] || !$options['course']) {
    $help = "
Generate realistic test data for learning analytics testing.

Creates test users with varied engagement patterns, quiz attempts, assignments,
forum posts, and activity logs. Generates three student profiles:
  - High performers (excellent grades, high engagement)
  - Average students (moderate grades and engagement)
  - At-risk students (low grades, minimal engagement)

Usage:
  php generate_test_data.php --course=<course_id> [options]

Options:
  --course=<id>              Course ID (required)
  --students=<number>        Number of test students (default: 50)
  --weeks=<number>           Weeks of simulated activity (default: 12)
  --at-risk-percentage=<pct> Percentage of at-risk students (default: 20)
  --seed=<number>            Random seed for reproducibility (optional)
  -h, --help                 Print this help

Examples:
  php generate_test_data.php --course=123 --students=100 --weeks=12
  php generate_test_data.php -c 123 -s 50 -w 8 --at-risk-percentage=25

Student Profiles Generated:
  - High Performers: ~20% (grades 80-100%, high engagement)
  - Average Students: ~60% (grades 60-79%, moderate engagement)
  - At-Risk Students: ~20% (grades <60%, low engagement)

Generated Data:
  - User accounts (username: teststudent_XXX)
  - Course enrollments
  - Activity logs (page views, actions)
  - Quiz attempts (if quizzes exist in course)
  - Assignment submissions (if assignments exist)
  - Forum posts (if forums exist)
  - Activity completion tracking

";
    echo $help;
    exit(0);
}

// Validate course
$course_id = (int)$options['course'];
$course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

$student_count = (int)$options['students'];
$weeks = (int)$options['weeks'];
$at_risk_pct = (int)$options['at-risk-percentage'];

// Set random seed if provided
if ($options['seed']) {
    mt_srand((int)$options['seed']);
}

echo "\n";
echo "========================================\n";
echo " Test Data Generator for Analytics\n";
echo "========================================\n\n";
echo "Course: {$course->fullname} (ID: {$course_id})\n";
echo "Students to create: {$student_count}\n";
echo "Activity period: {$weeks} weeks\n";
echo "At-risk percentage: {$at_risk_pct}%\n\n";

// Calculate student profile distribution
$high_performers = round($student_count * 0.20);
$average_students = $student_count - $high_performers - round($student_count * ($at_risk_pct / 100));
$at_risk_students = $student_count - $high_performers - $average_students;

echo "Student profiles:\n";
echo "  - High performers: {$high_performers}\n";
echo "  - Average students: {$average_students}\n";
echo "  - At-risk students: {$at_risk_students}\n\n";

// Get course modules
$course_modules = get_fast_modinfo($course_id)->get_cms();
$quizzes = [];
$assignments = [];
$forums = [];

foreach ($course_modules as $cm) {
    if ($cm->modname == 'quiz') {
        $quizzes[] = $cm;
    } else if ($cm->modname == 'assign') {
        $assignments[] = $cm;
    } else if ($cm->modname == 'forum') {
        $forums[] = $cm;
    }
}

echo "Found in course:\n";
echo "  - Quizzes: " . count($quizzes) . "\n";
echo "  - Assignments: " . count($assignments) . "\n";
echo "  - Forums: " . count($forums) . "\n\n";

if (count($quizzes) == 0 && count($assignments) == 0) {
    echo "WARNING: No quizzes or assignments found. Grade data will be limited.\n\n";
}

echo "Starting data generation...\n\n";

// Get student role
$student_role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

// Get manual enrollment plugin
$enrol_plugin = enrol_get_plugin('manual');
$enrol_instance = $DB->get_record('enrol', [
    'courseid' => $course_id,
    'enrol' => 'manual'
], '*', MUST_EXIST);

// Time calculations
$now = time();
$course_start = $course->startdate > 0 ? $course->startdate : $now - ($weeks * 7 * 86400);
$course_end = $now;

$created_users = [];
$profile_index = 0;

// Determine profile for each student
$profiles = array_merge(
    array_fill(0, $high_performers, 'high'),
    array_fill(0, $average_students, 'average'),
    array_fill(0, $at_risk_students, 'at_risk')
);
shuffle($profiles);

echo "Creating test users and enrolling...\n";
$progress = new progress_bar('create_users', 500, true);

foreach ($profiles as $i => $profile) {
    $progress->update($i + 1, $student_count, "Creating student " . ($i + 1) . "/{$student_count}");

    // Create user
    $username = 'teststudent_' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
    $user = new stdClass();
    $user->username = $username;
    $user->password = hash_internal_user_password('Test123!');
    $user->firstname = 'Test';
    $user->lastname = 'Student ' . ($i + 1);
    $user->email = $username . '@example.com';
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = $now;
    $user->timemodified = $now;

    $user->id = user_create_user($user, false, false);

    // Enroll user
    $enrollment_date = $course_start + mt_rand(0, 7 * 86400); // Enroll within first week
    $enrol_plugin->enrol_user($enrol_instance, $user->id, $student_role->id, $enrollment_date);

    $created_users[] = [
        'user' => $user,
        'profile' => $profile,
        'enrollment_date' => $enrollment_date
    ];
}

echo "\n\nGenerating activity logs...\n";
$progress = new progress_bar('activity_logs', 500, true);

$log_count = 0;
foreach ($created_users as $idx => $data) {
    $progress->update($idx + 1, count($created_users), "Processing student " . ($idx + 1));

    $user = $data['user'];
    $profile = $data['profile'];
    $enrollment_date = $data['enrollment_date'];

    // Generate activity based on profile
    switch ($profile) {
        case 'high':
            $login_frequency = mt_rand(4, 6); // 4-6 logins per week
            $actions_per_login = mt_rand(20, 40);
            $activity_consistency = 0.9; // 90% chance of activity each week
            break;
        case 'average':
            $login_frequency = mt_rand(2, 4); // 2-4 logins per week
            $actions_per_login = mt_rand(10, 25);
            $activity_consistency = 0.7; // 70% chance
            break;
        case 'at_risk':
            $login_frequency = mt_rand(0, 2); // 0-2 logins per week
            $actions_per_login = mt_rand(3, 10);
            $activity_consistency = 0.3; // 30% chance
            // Stop activity in last 2-4 weeks for at-risk students
            $weeks_inactive = mt_rand(2, 4);
            break;
    }

    // Generate weekly activity
    for ($week = 0; $week < $weeks; $week++) {
        $week_start = $enrollment_date + ($week * 7 * 86400);

        // At-risk students: stop activity in recent weeks
        if ($profile == 'at_risk' && $week >= ($weeks - $weeks_inactive)) {
            continue; // No activity in recent weeks
        }

        // Check consistency
        if (mt_rand(1, 100) / 100 > $activity_consistency) {
            continue; // Skip this week
        }

        // Generate logins for this week
        for ($login = 0; $login < $login_frequency; $login++) {
            $day_offset = mt_rand(0, 6);
            $hour = mt_rand(8, 22);
            $minute = mt_rand(0, 59);
            $login_time = $week_start + ($day_offset * 86400) + ($hour * 3600) + ($minute * 60);

            if ($login_time > $now) {
                break; // Don't create future logs
            }

            // Insert login log
            $log = new stdClass();
            $log->eventname = '\core\event\user_loggedin';
            $log->component = 'core';
            $log->action = 'loggedin';
            $log->target = 'user';
            $log->crud = 'r';
            $log->edulevel = 0;
            $log->userid = $user->id;
            $log->courseid = $course_id;
            $log->timecreated = $login_time;
            $log->origin = 'web';
            $log->ip = '192.168.1.' . mt_rand(1, 254);
            $log->other = 'N;';

            $DB->insert_record('logstore_standard_log', $log);
            $log_count++;

            // Generate actions during this session
            $num_actions = $actions_per_login;
            for ($action = 0; $action < $num_actions; $action++) {
                $action_time = $login_time + mt_rand(30, 1800); // 30s to 30min after login

                $log = new stdClass();
                $log->eventname = '\mod_page\event\course_module_viewed';
                $log->component = 'mod_page';
                $log->action = 'viewed';
                $log->target = 'course_module';
                $log->crud = 'r';
                $log->edulevel = 0;
                $log->userid = $user->id;
                $log->courseid = $course_id;
                $log->timecreated = $action_time;
                $log->origin = 'web';
                $log->ip = '192.168.1.' . mt_rand(1, 254);
                $log->other = 'N;';

                $DB->insert_record('logstore_standard_log', $log);
                $log_count++;
            }
        }
    }
}

echo "\n\nGenerated {$log_count} activity log entries\n\n";

// Generate quiz attempts
if (!empty($quizzes)) {
    echo "Generating quiz attempts...\n";
    $progress = new progress_bar('quiz_attempts', 500, true);

    $quiz_attempt_count = 0;
    foreach ($created_users as $idx => $data) {
        $progress->update($idx + 1, count($created_users), "Processing student " . ($idx + 1));

        $user = $data['user'];
        $profile = $data['profile'];

        foreach ($quizzes as $quiz_cm) {
            $quiz = $DB->get_record('quiz', ['id' => $quiz_cm->instance]);
            if (!$quiz) continue;

            // Determine if student attempts based on profile
            $attempt_probability = $profile == 'high' ? 1.0 : ($profile == 'average' ? 0.85 : 0.5);
            if (mt_rand(1, 100) / 100 > $attempt_probability) {
                continue; // Skip this quiz
            }

            // Determine grade based on profile
            switch ($profile) {
                case 'high':
                    $grade_pct = mt_rand(80, 100) / 100;
                    break;
                case 'average':
                    $grade_pct = mt_rand(60, 79) / 100;
                    break;
                case 'at_risk':
                    $grade_pct = mt_rand(20, 59) / 100;
                    break;
            }

            $grade = $quiz->sumgrades * $grade_pct;

            // Create quiz attempt
            $attempt = new stdClass();
            $attempt->quiz = $quiz->id;
            $attempt->userid = $user->id;
            $attempt->attempt = 1;
            $attempt->uniqueid = mt_rand(100000, 999999);
            $attempt->state = 'finished';
            $attempt->timestart = $data['enrollment_date'] + mt_rand(7 * 86400, $weeks * 7 * 86400);
            $attempt->timefinish = $attempt->timestart + mt_rand(600, 3600); // 10-60 minutes
            $attempt->timemodified = $attempt->timefinish;
            $attempt->sumgrades = $grade;
            $attempt->preview = 0;

            $DB->insert_record('quiz_attempts', $attempt);

            // Update quiz_grades
            $quiz_grade = new stdClass();
            $quiz_grade->quiz = $quiz->id;
            $quiz_grade->userid = $user->id;
            $quiz_grade->grade = $grade;
            $quiz_grade->timemodified = $attempt->timefinish;

            $DB->insert_record('quiz_grades', $quiz_grade);

            $quiz_attempt_count++;
        }
    }

    echo "\n\nGenerated {$quiz_attempt_count} quiz attempts\n\n";
}

// Generate assignment submissions
if (!empty($assignments)) {
    echo "Generating assignment submissions...\n";
    $progress = new progress_bar('assignments', 500, true);

    $submission_count = 0;
    foreach ($created_users as $idx => $data) {
        $progress->update($idx + 1, count($created_users), "Processing student " . ($idx + 1));

        $user = $data['user'];
        $profile = $data['profile'];

        foreach ($assignments as $assign_cm) {
            $assign = $DB->get_record('assign', ['id' => $assign_cm->instance]);
            if (!$assign) continue;

            // Determine if student submits based on profile
            $submit_probability = $profile == 'high' ? 1.0 : ($profile == 'average' ? 0.9 : 0.6);
            if (mt_rand(1, 100) / 100 > $submit_probability) {
                continue; // Skip this assignment
            }

            // Determine if late
            $is_late = $profile == 'at_risk' ? (mt_rand(1, 100) <= 50) : (mt_rand(1, 100) <= 10);
            $submission_time = $assign->duedate > 0 ?
                ($is_late ? $assign->duedate + mt_rand(86400, 7 * 86400) : $assign->duedate - mt_rand(3600, 7 * 86400)) :
                $data['enrollment_date'] + mt_rand(7 * 86400, $weeks * 7 * 86400);

            // Create submission
            $submission = new stdClass();
            $submission->assignment = $assign->id;
            $submission->userid = $user->id;
            $submission->timecreated = $submission_time;
            $submission->timemodified = $submission_time;
            $submission->status = 'submitted';
            $submission->groupid = 0;
            $submission->attemptnumber = 0;
            $submission->latest = 1;

            $DB->insert_record('assign_submission', $submission);

            // Create grade
            switch ($profile) {
                case 'high':
                    $grade_pct = mt_rand(85, 100) / 100;
                    break;
                case 'average':
                    $grade_pct = mt_rand(65, 84) / 100;
                    break;
                case 'at_risk':
                    $grade_pct = mt_rand(30, 64) / 100;
                    break;
            }

            $grade_obj = new stdClass();
            $grade_obj->assignment = $assign->id;
            $grade_obj->userid = $user->id;
            $grade_obj->timecreated = $submission_time + 86400; // Graded next day
            $grade_obj->timemodified = $grade_obj->timecreated;
            $grade_obj->grader = 2; // Admin
            $grade_obj->grade = $assign->grade * $grade_pct;
            $grade_obj->attemptnumber = 0;

            $DB->insert_record('assign_grades', $grade_obj);

            $submission_count++;
        }
    }

    echo "\n\nGenerated {$submission_count} assignment submissions\n\n";
}

// Generate forum posts
if (!empty($forums)) {
    echo "Generating forum posts...\n";
    $progress = new progress_bar('forum_posts', 500, true);

    $post_count = 0;
    foreach ($created_users as $idx => $data) {
        $progress->update($idx + 1, count($created_users), "Processing student " . ($idx + 1));

        $user = $data['user'];
        $profile = $data['profile'];

        // Determine post count based on profile
        $post_count_range = $profile == 'high' ? [5, 15] : ($profile == 'average' ? [2, 8] : [0, 3]);
        $num_posts = mt_rand($post_count_range[0], $post_count_range[1]);

        for ($p = 0; $p < $num_posts; $p++) {
            $forum = $forums[array_rand($forums)];
            $forum_record = $DB->get_record('forum', ['id' => $forum->instance]);
            if (!$forum_record) continue;

            // Get or create discussion
            $discussions = $DB->get_records('forum_discussions', ['forum' => $forum_record->id], '', 'id', 0, 5);
            if (empty($discussions)) {
                // Create discussion
                $discussion = new stdClass();
                $discussion->course = $course_id;
                $discussion->forum = $forum_record->id;
                $discussion->name = 'Test Discussion ' . mt_rand(1, 100);
                $discussion->firstpost = 0;
                $discussion->userid = $user->id;
                $discussion->groupid = 0;
                $discussion->timemodified = $data['enrollment_date'] + mt_rand(0, $weeks * 7 * 86400);
                $discussion->usermodified = $user->id;
                $discussion->timestart = 0;
                $discussion->timeend = 0;

                $discussion->id = $DB->insert_record('forum_discussions', $discussion);
            } else {
                $discussion = reset($discussions);
            }

            // Create post
            $post = new stdClass();
            $post->discussion = $discussion->id;
            $post->parent = 0; // Top-level post
            $post->userid = $user->id;
            $post->created = $data['enrollment_date'] + mt_rand(0, $weeks * 7 * 86400);
            $post->modified = $post->created;
            $post->subject = 'Test post ' . mt_rand(1, 1000);
            $post->message = 'This is a test forum post generated for analytics testing.';
            $post->messageformat = FORMAT_HTML;

            $DB->insert_record('forum_posts', $post);
            $post_count++;
        }
    }

    echo "\n\nGenerated {$post_count} forum posts\n\n";
}

// Generate completion data
echo "Generating completion tracking...\n";
$progress = new progress_bar('completion', 500, true);

$completion_count = 0;
foreach ($created_users as $idx => $data) {
    $progress->update($idx + 1, count($created_users), "Processing student " . ($idx + 1));

    $user = $data['user'];
    $profile = $data['profile'];

    // Completion rate based on profile
    $completion_rate = $profile == 'high' ? 0.95 : ($profile == 'average' ? 0.7 : 0.3);

    foreach ($course_modules as $cm) {
        if ($cm->completion == COMPLETION_TRACKING_NONE) {
            continue;
        }

        // Decide if completed
        if (mt_rand(1, 100) / 100 > $completion_rate) {
            continue;
        }

        $completion = new stdClass();
        $completion->coursemoduleid = $cm->id;
        $completion->userid = $user->id;
        $completion->completionstate = COMPLETION_COMPLETE;
        $completion->viewed = 1;
        $completion->timemodified = $data['enrollment_date'] + mt_rand(0, $weeks * 7 * 86400);

        $DB->insert_record('course_modules_completion', $completion);
        $completion_count++;
    }
}

echo "\n\nGenerated {$completion_count} completion records\n\n";

// Summary
echo "========================================\n";
echo " Test Data Generation Complete!\n";
echo "========================================\n\n";
echo "Summary:\n";
echo "  - Users created: " . count($created_users) . "\n";
echo "  - Activity logs: {$log_count}\n";
echo "  - Quiz attempts: " . ($quiz_attempt_count ?? 0) . "\n";
echo "  - Assignment submissions: " . ($submission_count ?? 0) . "\n";
echo "  - Forum posts: " . ($post_count ?? 0) . "\n";
echo "  - Completion records: {$completion_count}\n\n";

echo "Student profiles:\n";
echo "  - High performers: {$high_performers} students\n";
echo "  - Average: {$average_students} students\n";
echo "  - At-risk: {$at_risk_students} students\n\n";

echo "Test users login:\n";
echo "  Username: teststudent_XXXX (e.g., teststudent_0001)\n";
echo "  Password: Test123!\n\n";

echo "Next steps:\n";
echo "  1. Test analytics: php cli/send_analytics_manual.php --course={$course_id}\n";
echo "  2. View course: http://localhost:8002/course/view.php?id={$course_id}\n";
echo "  3. Check analytics dashboard\n\n";

exit(0);
