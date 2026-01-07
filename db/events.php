<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for Savian AI plugin
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = [
    // Quiz attempt submitted - triggers analytics update
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_savian_ai\observer\analytics_observer::quiz_submitted',
        'internal' => true,
        'priority' => 100,
    ],

    // Assignment submitted - triggers analytics update
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => '\local_savian_ai\observer\analytics_observer::assignment_submitted',
        'internal' => true,
        'priority' => 100,
    ],

    // Activity completion updated - triggers analytics update
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\local_savian_ai\observer\analytics_observer::completion_updated',
        'internal' => true,
        'priority' => 100,
    ],

    // Course completed - triggers comprehensive end-of-course report
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\local_savian_ai\observer\analytics_observer::course_completed',
        'internal' => true,
        'priority' => 200, // Higher priority for end-of-course
    ],
];
