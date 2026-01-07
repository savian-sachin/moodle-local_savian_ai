<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * DEPRECATED: Redirects to analytics_reports.php
 *
 * This page has been merged into analytics_reports.php.
 * Keeping this redirect for backward compatibility.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Redirect to combined analytics dashboard
$url_params = ['courseid' => $courseid];
if (!empty($action)) {
    $url_params['action'] = $action;
}

redirect(new moodle_url('/local/savian_ai/analytics_reports.php', $url_params));
