<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled tasks for Savian AI plugin
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$tasks = [
    // Daily analytics task - runs at 2:00 AM daily
    [
        'classname' => 'local_savian_ai\task\send_analytics_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],

    // Weekly analytics task - runs Sunday at 3:00 AM
    [
        'classname' => 'local_savian_ai\task\send_analytics_weekly',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '0', // 0 = Sunday
        'month' => '*'
    ],

    // Cleanup old analytics data - runs daily at 4:00 AM
    [
        'classname' => 'local_savian_ai\task\cleanup_old_analytics',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
];
