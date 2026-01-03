<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

/**
 * External generation API - web services for course content generation status
 *
 * @package    local_savian_ai
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generation extends external_api {

    // ========================================
    // GET GENERATION STATUS
    // ========================================

    /**
     * Parameters for get_generation_status
     */
    public static function get_generation_status_parameters() {
        return new external_function_parameters([
            'requestid' => new external_value(PARAM_TEXT, 'Generation request ID')
        ]);
    }

    /**
     * Get course generation status for async polling
     *
     * @param string $requestid Request ID from generation API
     * @return array Status response
     */
    public static function get_generation_status($requestid) {
        global $USER, $SESSION;

        // Validate parameters
        $params = self::validate_parameters(self::get_generation_status_parameters(), [
            'requestid' => $requestid
        ]);

        // Validate context - require login
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/savian_ai:generate', $context);

        // Call API
        $client = new \local_savian_ai\api\client();
        $response = $client->get_generation_status($params['requestid']);

        // Process response
        if ($response->http_code === 200) {
            $status = $response->status ?? 'unknown';
            $progress = isset($response->progress) ? (int)$response->progress : 0;

            // CRITICAL: Save course_structure to session when completed
            if ($status === 'completed' && isset($response->course_structure)) {
                $SESSION->savian_ai_course_structure = json_encode($response->course_structure);
                $SESSION->savian_ai_sources = isset($response->sources) ? json_encode($response->sources) : null;
                unset($SESSION->savian_ai_pending_request);
            }

            return [
                'success' => true,
                'status' => $status,
                'progress' => $progress,
                'details' => [
                    'stage' => $response->details->stage ?? '',
                    'current_section' => $response->details->current_section ?? ''
                ],
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'status' => 'error',
                'progress' => 0,
                'details' => [
                    'stage' => '',
                    'current_section' => ''
                ],
                'error' => $response->error ?? 'Failed to get status (HTTP ' . $response->http_code . ')'
            ];
        }
    }

    /**
     * Returns description of get_generation_status return value
     */
    public static function get_generation_status_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'status' => new external_value(PARAM_TEXT, 'Generation status: pending, processing, completed, failed'),
            'progress' => new external_value(PARAM_INT, 'Progress percentage (0-100)'),
            'details' => new external_single_structure([
                'stage' => new external_value(PARAM_TEXT, 'Current stage'),
                'current_section' => new external_value(PARAM_TEXT, 'Current section being generated', VALUE_OPTIONAL)
            ]),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL)
        ]);
    }

    // ========================================
    // SAVE COURSE STRUCTURE (for edits)
    // ========================================

    /**
     * Parameters for save_course_structure
     */
    public static function save_course_structure_parameters() {
        return new external_function_parameters([
            'structure' => new external_value(PARAM_RAW, 'Course structure JSON')
        ]);
    }

    /**
     * Save edited course structure to session
     *
     * @param string $structure Course structure JSON
     * @return array Success response
     */
    public static function save_course_structure($structure) {
        global $SESSION;

        // Validate parameters
        $params = self::validate_parameters(self::save_course_structure_parameters(), [
            'structure' => $structure
        ]);

        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/savian_ai:generate', $context);

        // Validate JSON
        $decoded = json_decode($params['structure']);
        if ($decoded === null) {
            return [
                'success' => false,
                'error' => 'Invalid JSON structure'
            ];
        }

        // Save to session
        $SESSION->savian_ai_course_structure = $params['structure'];

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Returns description of save_course_structure return value
     */
    public static function save_course_structure_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL)
        ]);
    }
}
