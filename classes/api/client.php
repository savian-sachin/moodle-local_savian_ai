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
 * Savian AI API client.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Savian AI API Client.
 *
 * Handles all communication with the Savian AI external service.
 * Uses Moodle's \curl class (lib/filelib.php) for proxy and SSL compatibility.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /** @var string Base URL for API. */
    private $baseurl;

    /** @var string API key for authentication. */
    private $apikey;

    /** @var string Organization code. */
    private $orgcode;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->baseurl = get_config('local_savian_ai', 'api_url') ?: 'https://app.savian.ai.vn/api/moodle/v1/';
        $this->apikey = get_config('local_savian_ai', 'api_key');
        $this->orgcode = get_config('local_savian_ai', 'org_code') ?: 't001';

        // Ensure base URL ends with /.
        if (substr($this->baseurl, -1) !== '/') {
            $this->baseurl .= '/';
        }
    }

    /**
     * Validate API credentials.
     *
     * @return object Response object.
     */
    public function validate() {
        return $this->request(
            'POST',
            'auth/validate/',
            ['org_code' => $this->orgcode]
        );
    }

    /**
     * Upload document for processing.
     *
     * @param string $filepath Path to file.
     * @param string $title Document title.
     * @param array $metadata Optional metadata (description, subject_area, tags).
     * @return object Response object.
     */
    public function upload_document($filepath, $title, $metadata = []) {
        if (empty($this->apikey)) {
            return $this->error_response('API key not configured', 0);
        }

        $curl = new \curl();
        $curl->setHeader('X-API-Key: ' . $this->apikey);

        $params = [
            'document' => new \CURLFile($filepath),
            'title' => $title,
            'description' => $metadata['description'] ?? '',
            'subject_area' => $metadata['subject_area'] ?? '',
            'tags' => json_encode($metadata['tags'] ?? []),
            'course_id' => $metadata['course_id'] ?? '',
            'course_name' => $metadata['course_name'] ?? '',
        ];

        $curl->setopt(['CURLOPT_TIMEOUT' => 300]);

        $response = $curl->post($this->baseurl . 'documents/upload/', $params);
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;
        $curlerror = $curl->get_errno() ? $curl->error : '';

        if ($curlerror) {
            return $this->error_response($curlerror, 0);
        }

        $result = json_decode($response);
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->error_response('Invalid JSON response', $httpcode);
        }

        if (!is_object($result)) {
            $wrapped = new \stdClass();
            $wrapped->success = ($httpcode >= 200 && $httpcode < 300);
            $wrapped->data = $result;
            $wrapped->http_code = $httpcode;
            return $wrapped;
        }

        $result->http_code = $httpcode;
        return $result;
    }

    /**
     * Get list of documents.
     *
     * @param array $params Query parameters (page, per_page, status, etc.).
     * @return object Response object.
     */
    public function get_documents($params = []) {
        $query = http_build_query($params);
        $endpoint = 'documents/' . ($query ? '?' . $query : '');
        return $this->request('GET', $endpoint);
    }

    /**
     * Get document details.
     *
     * @param int $docid Document ID.
     * @return object Response object.
     */
    public function get_document($docid) {
        return $this->request('GET', "documents/{$docid}/");
    }

    /**
     * Get document processing status.
     *
     * @param int $docid Document ID.
     * @return object Response object.
     */
    public function get_document_status($docid) {
        return $this->request('GET', "documents/{$docid}/status/");
    }

    /**
     * Delete document.
     *
     * @param int $docid Document ID.
     * @return object Response object.
     */
    public function delete_document($docid) {
        return $this->request('DELETE', "documents/{$docid}/delete/");
    }

    /**
     * Generate questions from topic (no documents).
     *
     * @param string $topic Topic name.
     * @param array $options Generation options.
     * @return object Response object.
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
     * Generate questions from documents using RAG.
     *
     * @param array $documentids Array of document IDs.
     * @param string $topic Topic name.
     * @param array $options Generation options.
     * @return object Response object.
     */
    public function generate_questions_from_docs($documentids, $topic, $options = []) {
        $data = [
            'document_ids' => $documentids,
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
     * Get generation status (for async requests).
     *
     * @param string $requestid Request ID from generation.
     * @return object Response object.
     */
    public function get_generation_status($requestid) {
        return $this->request('GET', "generate/status/{$requestid}/");
    }

    /**
     * Get usage statistics.
     *
     * @return object Response object.
     */
    public function get_usage() {
        return $this->request('GET', 'usage/');
    }

    /**
     * Generate course content from topic.
     *
     * @param string $coursetitle Course title.
     * @param array $options Generation options.
     * @return object Response object.
     */
    public function generate_course_content($coursetitle, $options = []) {
        $data = [
            'course_title' => $coursetitle,
            'description' => $options['description'] ?? '',
            'target_audience' => $options['target_audience'] ?? '',
            'duration_weeks' => $options['duration_weeks'] ?? 4,
            'content_types' => $options['content_types'] ?? ['sections', 'pages'],
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/course-content/', $data);
    }

    /**
     * Generate course content from documents using RAG.
     *
     * @param array $documentids Array of document IDs.
     * @param string $coursetitle Course title.
     * @param array $options Generation options.
     * @return object Response object.
     */
    public function generate_course_from_documents($documentids, $coursetitle, $options = []) {
        $data = [
            'document_ids' => $documentids,
            'course_title' => $coursetitle,
            'course_id' => $options['course_id'] ?? null,
            'description' => $options['description'] ?? '',
            'target_audience' => $options['target_audience'] ?? '',
            'duration_weeks' => $options['duration_weeks'] ?? 4,

            // ADDIE v2.0 parameters.
            'age_group' => $options['age_group'] ?? 'undergrad',
            'industry' => $options['industry'] ?? 'general',
            'prior_knowledge_level' => $options['prior_knowledge_level'] ?? 'beginner',

            'content_types' => $options['content_types'] ?? [
                'sections', 'pages', 'activities', 'discussions',
                'quiz_questions', 'assignments',
            ],
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'generate/course-from-documents/', $data);
    }

    /**
     * Chat with documents.
     *
     * @param string $message User message.
     * @param array $documentids Document IDs for context.
     * @param array $options Additional options.
     * @return object Response object.
     */
    public function chat($message, $documentids, $options = []) {
        $data = [
            'message' => $message,
            'document_ids' => $documentids,
            'language' => $options['language'] ?? 'en',
            'conversation_id' => $options['conversation_id'] ?? null,
        ];

        return $this->request('POST', 'chat/', $data);
    }

    /**
     * Internal request method using Moodle's \curl class.
     *
     * @param string $method HTTP method (GET, POST, DELETE).
     * @param string $endpoint API endpoint.
     * @param array $data Request data.
     * @return object Response object.
     */
    private function request($method, $endpoint, $data = []) {
        if (empty($this->apikey)) {
            return $this->error_response('API key not configured', 0);
        }

        $curl = new \curl();
        $curl->setHeader('X-API-Key: ' . $this->apikey);
        $curl->setHeader('Content-Type: application/json');
        $curl->setopt(['CURLOPT_TIMEOUT' => 60]);

        $url = $this->baseurl . $endpoint;

        $response = '';
        if ($method === 'POST') {
            $response = $curl->post($url, json_encode($data));
        } else if ($method === 'DELETE') {
            $response = $curl->delete($url);
        } else {
            $response = $curl->get($url);
        }

        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;
        $curlerror = $curl->get_errno() ? $curl->error : '';

        // Detect blocked URL (Moodle HTTP security).
        if ($httpcode == 0 && empty($curlerror) && $response === '') {
            return $this->error_response(
                get_string('error_url_blocked', 'local_savian_ai', $url),
                0
            );
        }

        if ($curlerror) {
            return $this->error_response($curlerror, 0);
        }

        // Handle empty responses (e.g. DELETE returning 200/204 with no body).
        if ($response === '' || $response === false) {
            $result = new \stdClass();
            $result->success = ($httpcode >= 200 && $httpcode < 300);
            $result->http_code = $httpcode;
            return $result;
        }

        $result = json_decode($response);
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->error_response('Invalid JSON response: ' . substr($response, 0, 200), $httpcode);
        }

        if (!is_object($result)) {
            $wrapped = new \stdClass();
            $wrapped->success = ($httpcode >= 200 && $httpcode < 300);
            $wrapped->data = $result;
            $wrapped->http_code = $httpcode;
            return $wrapped;
        }

        $result->http_code = $httpcode;
        return $result;
    }

    /**
     * Send chat message.
     *
     * @param string $message Message content.
     * @param string|null $conversationuuid Conversation UUID.
     * @param array $options Additional options.
     * @return object Response with message and conversation_id.
     */
    public function chat_send($message, $conversationuuid = null, $options = []) {
        global $USER, $COURSE;

        $data = [
            'message' => $message,
            'conversation_id' => $conversationuuid,
            'user_id' => (string)($options['user_id'] ?? $USER->id),
            'user_role' => $options['user_role'] ?? 'teacher',
            'course_id' => $options['course_id'] ? (string)$options['course_id'] : null,
            'course_name' => $options['course_name'] ?? ($COURSE->id != SITEID ? $COURSE->fullname : null),
            'document_ids' => $options['document_ids'] ?? [],
            'language' => $options['language'] ?? 'en',
        ];

        return $this->request('POST', 'chat/send/', $data);
    }

    /**
     * Get conversation list.
     *
     * @param array $params Query parameters (user_id, course_id).
     * @return object Response with conversations array.
     */
    public function chat_conversations($params = []) {
        $query = http_build_query($params);
        $endpoint = 'chat/conversations/' . ($query ? '?' . $query : '');
        return $this->request('GET', $endpoint);
    }

    /**
     * Get conversation messages.
     *
     * @param string $conversationuuid Conversation UUID.
     * @return object Response with messages array.
     */
    public function chat_conversation_messages($conversationuuid) {
        return $this->request('GET', "chat/conversations/{$conversationuuid}/messages/");
    }

    /**
     * Submit feedback for a message.
     *
     * @param string $messageuuid Message UUID.
     * @param int $feedback Feedback value (1 or -1).
     * @param string $comment Optional comment.
     * @return object Response.
     */
    public function chat_feedback($messageuuid, $feedback, $comment = '') {
        $data = [
            'message_id' => $messageuuid,
            'feedback' => $feedback === 1 ? 'helpful' : 'not_helpful',
            'comment' => $comment,
        ];

        return $this->request('POST', 'chat/feedback/', $data);
    }

    /**
     * Save approved course content to knowledge base (v2.2 - Knowledge Feedback Loop).
     *
     * @param object $coursestructure Course structure from generation.
     * @param string $coursetitle Course title.
     * @param int $courseid Moodle course ID.
     * @param string $approvedby Instructor name.
     * @param string $requestid Original generation request ID.
     * @return object Response object.
     */
    public function save_approved_course_to_knowledge_base(
        $coursestructure,
        $coursetitle,
        $courseid,
        $approvedby,
        $requestid = null
    ) {
        global $COURSE;

        $data = [
            'course_title' => $coursetitle,
            'course_id' => (string) $courseid,
            'course_name' => $COURSE->fullname ?? '',
            'course_structure' => $coursestructure,
            'generation_request_id' => $requestid,
            'approved_by' => $approvedby,
            'approval_date' => date('Y-m-d'),
            'instructor_notes' => 'Reviewed and approved for institutional use',
        ];

        return $this->request('POST', 'courses/save-approved/', $data);
    }

    /**
     * Send analytics data to API for learning insights.
     *
     * @param array $reportdata Complete analytics report data.
     * @return object Response object with insights or request_id for async processing.
     */
    public function send_analytics($reportdata) {
        return $this->request('POST', 'analytics/course-data/', $reportdata);
    }

    /**
     * Get analytics processing status (for async processing).
     *
     * @param string $reportid Report ID from analytics submission.
     * @return object Status response with insights when completed.
     */
    public function get_analytics_status($reportid) {
        return $this->request('GET', "analytics/status/{$reportid}/");
    }

    /**
     * Get latest analytics report for a course.
     *
     * @param int $courseid Course ID.
     * @return object Latest report with insights.
     */
    public function get_latest_analytics($courseid) {
        return $this->request('GET', "analytics/course/{$courseid}/latest/");
    }

    /**
     * Get analytics report history for a course.
     *
     * @param int $courseid Course ID.
     * @return object Report history.
     */
    public function get_analytics_history($courseid) {
        return $this->request('GET', "analytics/course/{$courseid}/history/");
    }

    /**
     * Create a writing task.
     *
     * @param array $data Task data (title, prompt, task_type, exam_type, etc.).
     * @return object Response object.
     */
    public function create_writing_task($data) {
        return $this->request('POST', 'writing/tasks/create/', $data);
    }

    /**
     * List writing tasks.
     *
     * @param int|null $courseid Optional course ID filter.
     * @return object Response object.
     */
    public function list_writing_tasks($courseid = null) {
        $q = $courseid ? '?course_id=' . urlencode($courseid) : '';
        return $this->request('GET', 'writing/tasks/' . $q);
    }

    /**
     * Get a single writing task.
     *
     * @param int $taskid Task ID.
     * @return object Response object.
     */
    public function get_writing_task($taskid) {
        return $this->request('GET', 'writing/tasks/' . (int)$taskid . '/');
    }

    /**
     * Delete a writing task.
     *
     * @param int $taskid Task ID.
     * @return object Response object.
     */
    public function delete_writing_task($taskid) {
        return $this->request('POST', 'writing/tasks/' . (int)$taskid . '/delete/', []);
    }

    /**
     * Submit writing for AI assessment.
     *
     * @param array $data Submission data (task_id, text, user_id, etc.).
     * @return object Response object.
     */
    public function submit_writing($data) {
        return $this->request('POST', 'writing/submit/', $data);
    }

    /**
     * Get writing submission status.
     *
     * @param string $submissionid Submission UUID.
     * @return object Response object.
     */
    public function get_writing_submission_status($submissionid) {
        return $this->request('GET', 'writing/submissions/' . $submissionid . '/status/');
    }

    /**
     * Get writing submission feedback.
     *
     * @param string $submissionid Submission UUID.
     * @return object Response object.
     */
    public function get_writing_submission_feedback($submissionid) {
        return $this->request('GET', 'writing/submissions/' . $submissionid . '/feedback/');
    }

    /**
     * Get class writing report.
     *
     * @param int $courseid Course ID.
     * @param array $filters Optional filters.
     * @return object Response object.
     */
    public function get_writing_class_report($courseid, $filters = []) {
        $q = $filters ? '?' . http_build_query($filters) : '';
        return $this->request('GET', 'writing/reports/class/' . urlencode($courseid) . '/' . $q);
    }

    /**
     * Get at-risk students for writing.
     *
     * @param int $courseid Course ID.
     * @return object Response object.
     */
    public function get_writing_at_risk_students($courseid) {
        return $this->request('GET', 'writing/reports/at-risk/' . urlencode($courseid) . '/');
    }

    /**
     * Create error response object.
     *
     * @param string $message Error message.
     * @param int $httpcode HTTP status code.
     * @return object Error response object.
     */
    private function error_response($message, $httpcode) {
        return (object) [
            'success' => false,
            'error' => $message,
            'http_code' => $httpcode,
        ];
    }
}
