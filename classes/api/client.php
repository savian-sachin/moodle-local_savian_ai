<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Savian AI API Client
 *
 * Handles all communication with the Savian AI external service.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /** @var string Base URL for API */
    private $base_url;

    /** @var string API key for authentication */
    private $api_key;

    /** @var string Organization code */
    private $org_code;

    /**
     * Constructor
     */
    public function __construct() {
        $this->base_url = get_config('local_savian_ai', 'api_url') ?: '';
        $this->api_key = get_config('local_savian_ai', 'api_key');
        $this->org_code = get_config('local_savian_ai', 'org_code') ?: 't001';

        // Ensure base URL ends with /
        if (substr($this->base_url, -1) !== '/') {
            $this->base_url .= '/';
        }
    }

    /**
     * Validate API credentials
     *
     * @return object Response object
     */
    public function validate() {
        return $this->request('POST', 'auth/validate/', [
            'org_code' => $this->org_code
        ]);
    }

    /**
     * Upload document for processing
     *
     * @param string $filepath Path to file
     * @param string $title Document title
     * @param array $metadata Optional metadata (description, subject_area, tags)
     * @return object Response object
     */
    public function upload_document($filepath, $title, $metadata = []) {
        $ch = curl_init();

        $post_fields = [
            'document' => new \CURLFile($filepath),
            'title' => $title,
            'description' => $metadata['description'] ?? '',
            'subject_area' => $metadata['subject_area'] ?? '',
            'tags' => json_encode($metadata['tags'] ?? []),
            'course_id' => $metadata['course_id'] ?? '',
            'course_name' => $metadata['course_name'] ?? ''
        ];

        // Upload document with metadata

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->base_url . 'documents/upload/',
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->api_key,
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300, // 5 minutes for file upload
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return $this->error_response($curl_error, 0);
        }

        $result = json_decode($response);
        if (!$result) {
            return $this->error_response('Invalid JSON response', $http_code);
        }

        $result->http_code = $http_code;
        return $result;
    }

    /**
     * Get list of documents
     *
     * @param array $params Query parameters (page, per_page, status, etc.)
     * @return object Response object
     */
    public function get_documents($params = []) {
        $query = http_build_query($params);
        $endpoint = 'documents/' . ($query ? '?' . $query : '');
        return $this->request('GET', $endpoint);
    }

    /**
     * Get document details
     *
     * @param int $doc_id Document ID
     * @return object Response object
     */
    public function get_document($doc_id) {
        return $this->request('GET', "documents/{$doc_id}/");
    }

    /**
     * Get document processing status
     *
     * @param int $doc_id Document ID
     * @return object Response object
     */
    public function get_document_status($doc_id) {
        return $this->request('GET', "documents/{$doc_id}/status/");
    }

    /**
     * Delete document
     *
     * @param int $doc_id Document ID
     * @return object Response object
     */
    public function delete_document($doc_id) {
        return $this->request('DELETE', "documents/{$doc_id}/delete/");
    }

    /**
     * Generate questions from topic (no documents)
     *
     * @param string $topic Topic name
     * @param array $options Generation options
     * @return object Response object
     */
    public function generate_questions($topic, $options = []) {
        $data = [
            'topic' => $topic,
            'learning_objectives' => $options['learning_objectives'] ?? [],
            'question_types' => $options['question_types'] ?? ['multichoice'],
            'count' => $options['count'] ?? 5,
            'difficulty' => $options['difficulty'] ?? 'medium',
            'bloom_level' => $options['bloom_level'] ?? 'understand',
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/questions/', $data);
    }

    /**
     * Generate questions from documents using RAG
     *
     * @param array $document_ids Array of document IDs
     * @param string $topic Topic name
     * @param array $options Generation options
     * @return object Response object
     */
    public function generate_questions_from_docs($document_ids, $topic, $options = []) {
        $data = [
            'document_ids' => $document_ids,
            'topic' => $topic,
            'learning_objectives' => $options['learning_objectives'] ?? [],
            'question_types' => $options['question_types'] ?? ['multichoice'],
            'count' => $options['count'] ?? 5,
            'difficulty' => $options['difficulty'] ?? 'medium',
            'bloom_level' => $options['bloom_level'] ?? 'understand',
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/questions-from-documents/', $data);
    }

    /**
     * Get generation status (for async requests)
     *
     * @param string $request_id Request ID from generation
     * @return object Response object
     */
    public function get_generation_status($request_id) {
        return $this->request('GET', "generate/status/{$request_id}/");
    }

    /**
     * Get usage statistics
     *
     * @return object Response object
     */
    public function get_usage() {
        return $this->request('GET', 'usage/');
    }

    /**
     * Generate course content from topic
     *
     * @param string $course_title Course title
     * @param array $options Generation options
     * @return object Response object
     */
    public function generate_course_content($course_title, $options = []) {
        $data = [
            'course_title' => $course_title,
            'description' => $options['description'] ?? '',
            'target_audience' => $options['target_audience'] ?? '',
            'duration_weeks' => $options['duration_weeks'] ?? 4,
            'content_types' => $options['content_types'] ?? ['sections', 'pages'],
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/course-content/', $data);
    }

    /**
     * Generate course content from documents using RAG
     *
     * @param array $document_ids Array of document IDs
     * @param string $course_title Course title
     * @param array $options Generation options
     * @return object Response object
     */
    public function generate_course_from_documents($document_ids, $course_title, $options = []) {
        $data = [
            'document_ids' => $document_ids,
            'course_title' => $course_title,
            'course_id' => $options['course_id'] ?? null,
            'description' => $options['description'] ?? '',
            'target_audience' => $options['target_audience'] ?? '',
            'duration_weeks' => $options['duration_weeks'] ?? 4,

            // ADDIE v2.0 parameters
            'age_group' => $options['age_group'] ?? 'undergrad',
            'industry' => $options['industry'] ?? 'general',
            'prior_knowledge_level' => $options['prior_knowledge_level'] ?? 'beginner',

            'content_types' => $options['content_types'] ?? [
                'sections', 'pages', 'activities', 'discussions',
                'quiz_questions', 'assignments'
            ],
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/course-from-documents/', $data);
    }

    /**
     * Chat with documents
     *
     * @param string $message User message
     * @param array $document_ids Document IDs for context
     * @param array $options Additional options
     * @return object Response object
     */
    public function chat($message, $document_ids, $options = []) {
        $data = [
            'message' => $message,
            'document_ids' => $document_ids,
            'language' => $options['language'] ?? 'en',
            'conversation_id' => $options['conversation_id'] ?? null,
        ];

        return $this->request('POST', 'chat/', $data);
    }

    /**
     * Internal request method
     *
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return object Response object
     */
    private function request($method, $endpoint, $data = []) {
        if (empty($this->api_key)) {
            return $this->error_response('API key not configured', 0);
        }

        $ch = curl_init();

        $options = [
            CURLOPT_URL => $this->base_url . $endpoint,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->api_key,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } else if ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if (!empty($data)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return $this->error_response($curl_error, 0);
        }

        $result = json_decode($response);
        if (!$result) {
            return $this->error_response('Invalid JSON response: ' . substr($response, 0, 200), $http_code);
        }

        $result->http_code = $http_code;
        return $result;
    }

    /**
     * Send chat message
     *
     * @param string $message Message content
     * @param string|null $conversation_uuid Conversation UUID
     * @param array $options Additional options (user_id, user_email, user_role, course_id, course_name, document_ids, language)
     * @return object Response with message and conversation_id
     */
    public function chat_send($message, $conversation_uuid = null, $options = []) {
        global $USER, $COURSE;

        $data = [
            'message' => $message,
            'conversation_id' => $conversation_uuid,
            'user_id' => (string)($options['user_id'] ?? $USER->id),
            'user_email' => $options['user_email'] ?? $USER->email,
            'user_role' => $options['user_role'] ?? 'teacher',
            'course_id' => $options['course_id'] ? (string)$options['course_id'] : null,
            'course_name' => $options['course_name'] ?? ($COURSE->id != SITEID ? $COURSE->fullname : null),
            'document_ids' => $options['document_ids'] ?? [],
            'language' => $options['language'] ?? 'en'
        ];

        return $this->request('POST', 'chat/send/', $data);
    }

    /**
     * Get conversation list
     *
     * @param array $params Query parameters (user_id, course_id)
     * @return object Response with conversations array
     */
    public function chat_conversations($params = []) {
        $query = http_build_query($params);
        $endpoint = 'chat/conversations/' . ($query ? '?' . $query : '');
        return $this->request('GET', $endpoint);
    }

    /**
     * Get conversation messages
     *
     * @param string $conversation_uuid Conversation UUID
     * @return object Response with messages array
     */
    public function chat_conversation_messages($conversation_uuid) {
        return $this->request('GET', "chat/conversations/{$conversation_uuid}/messages/");
    }

    /**
     * Submit feedback for a message
     *
     * @param string $message_uuid Message UUID
     * @param int $feedback Feedback value (1 or -1)
     * @param string $comment Optional comment
     * @return object Response
     */
    public function chat_feedback($message_uuid, $feedback, $comment = '') {
        $data = [
            'message_id' => $message_uuid,
            'feedback' => $feedback === 1 ? 'helpful' : 'not_helpful',
            'comment' => $comment
        ];

        return $this->request('POST', 'chat/feedback/', $data);
    }

    /**
     * Save approved course content to knowledge base (v2.2 - Knowledge Feedback Loop)
     *
     * @param object $course_structure Course structure from generation
     * @param string $course_title Course title
     * @param int $course_id Moodle course ID
     * @param string $approved_by Instructor name
     * @param string $request_id Original generation request ID
     * @return object Response object
     */
    public function save_approved_course_to_knowledge_base($course_structure, $course_title, $course_id, $approved_by, $request_id = null) {
        global $COURSE;

        $data = [
            'course_title' => $course_title,
            'course_id' => (string) $course_id,
            'course_name' => $COURSE->fullname ?? '',
            'course_structure' => $course_structure,
            'generation_request_id' => $request_id,
            'approved_by' => $approved_by,
            'approval_date' => date('Y-m-d'),
            'instructor_notes' => 'Reviewed and approved for institutional use'
        ];

        return $this->request('POST', 'courses/save-approved/', $data);
    }

    /**
     * Create error response object
     *
     * @param string $message Error message
     * @param int $http_code HTTP status code
     * @return object Error response object
     */
    private function error_response($message, $http_code) {
        return (object) [
            'success' => false,
            'error' => $message,
            'http_code' => $http_code,
        ];
    }
}
