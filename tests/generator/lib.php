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
 * Data generator for local_savian_ai tests.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data generator for local_savian_ai plugin tests.
 *
 * Provides helper methods to create test fixtures for conversations,
 * messages, restrictions, and generations.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_savian_ai_generator extends component_generator_base {
    /**
     * @var int Conversation counter for unique UUIDs.
     */
    protected $conversationcount = 0;

    /**
     * @var int Generation counter for unique request IDs.
     */
    protected $generationcount = 0;

    /**
     * @var int Writing task counter for unique API task IDs.
     */
    protected $writingtaskcount = 0;

    /**
     * Reset the generators internal state.
     */
    public function reset() {
        $this->conversationcount = 0;
        $this->generationcount   = 0;
        $this->writingtaskcount  = 0;
        parent::reset();
    }

    /**
     * Create a chat conversation record.
     *
     * @param array|stdClass $record Data for the conversation.
     * @return stdClass The created conversation record.
     */
    public function create_conversation($record = []) {
        global $DB;

        $this->conversationcount++;
        $record = (array) $record;

        $defaults = [
            'conversation_uuid' => 'test-uuid-' . $this->conversationcount . '-' . uniqid(),
            'user_id' => 2,
            'course_id' => null,
            'context_type' => 'course',
            'title' => 'Test conversation ' . $this->conversationcount,
            'document_ids' => null,
            'message_count' => 0,
            'last_message_at' => time(),
            'is_archived' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $record = array_merge($defaults, $record);
        $record['id'] = $DB->insert_record('local_savian_ai_chat_conversations', (object) $record);

        return (object) $record;
    }

    /**
     * Create a chat message record.
     *
     * @param array|stdClass $record Data for the message.
     * @return stdClass The created message record.
     */
    public function create_message($record = []) {
        global $DB;

        $record = (array) $record;

        $defaults = [
            'conversation_id' => 0,
            'message_uuid' => null,
            'role' => 'user',
            'content' => 'Test message',
            'formatted_content' => null,
            'sources' => null,
            'feedback' => null,
            'feedback_comment' => null,
            'token_count' => 0,
            'response_time_ms' => null,
            'timecreated' => time(),
        ];

        $record = array_merge($defaults, $record);
        $record['id'] = $DB->insert_record('local_savian_ai_chat_messages', (object) $record);

        return (object) $record;
    }

    /**
     * Create a chat restriction record.
     *
     * @param array|stdClass $record Data for the restriction.
     * @return stdClass The created restriction record.
     */
    public function create_restriction($record = []) {
        global $DB;

        $record = (array) $record;

        $defaults = [
            'course_id' => 0,
            'restriction_type' => 'manual',
            'name' => 'Test restriction',
            'quiz_id' => null,
            'timestart' => null,
            'timeend' => null,
            'restriction_message' => 'Chat is restricted during this period.',
            'is_enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => 2,
        ];

        $record = array_merge($defaults, $record);
        $record['id'] = $DB->insert_record('local_savian_ai_chat_restrictions', (object) $record);

        return (object) $record;
    }

    /**
     * Create a writing task record.
     *
     * @param array $data Override defaults for the writing task.
     * @return stdClass The created writing task record.
     */
    public function create_writing_task(array $data = []): \stdClass {
        global $DB;

        $this->writingtaskcount++;

        $defaults = [
            'api_task_id'              => $this->writingtaskcount,
            'course_id'                => 0,
            'teacher_user_id'          => 0,
            'title'                    => 'Test writing task ' . $this->writingtaskcount,
            'prompt'                   => 'Write about the topic.',
            'task_type'                => 'essay',
            'exam_type'                => 'general',
            'target_cefr_level'        => 'B2',
            'word_count_min'           => 150,
            'word_count_max'           => 300,
            'time_limit_minutes'       => null,
            'include_improved_writing' => 1,
            'language'                 => 'en',
            'is_active'                => 1,
            'timecreated'              => time(),
            'timemodified'             => time(),
        ];

        $record     = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_savian_ai_writing_tasks', $record);
        return $record;
    }

    /**
     * Create a writing submission record.
     *
     * @param array $data Override defaults for the submission.
     * @return stdClass The created writing submission record.
     */
    public function create_writing_submission(array $data = []): \stdClass {
        global $DB;

        $defaults = [
            'submission_uuid'  => \core\uuid::generate(),
            'writing_task_id'  => 0,
            'api_task_id'      => 0,
            'moodle_user_id'   => 0,
            'status'           => 'pending',
            'progress'         => 0,
            'stage'            => '',
            'word_count'       => 200,
            'time_spent_seconds' => null,
            'feedback_json'    => null,
            'error_message'    => null,
            'timecreated'      => time(),
            'timemodified'     => time(),
        ];

        $record     = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_savian_ai_writing_submissions', $record);
        return $record;
    }

    /**
     * Create a generation record.
     *
     * @param array|stdClass $record Data for the generation.
     * @return stdClass The created generation record.
     */
    public function create_generation($record = []) {
        global $DB;

        $this->generationcount++;
        $record = (array) $record;

        $defaults = [
            'request_id' => 'test-req-' . $this->generationcount . '-' . uniqid(),
            'generation_type' => 'questions',
            'course_id' => null,
            'user_id' => 2,
            'topic' => 'Test topic',
            'document_ids' => null,
            'questions_count' => 5,
            'qbank_category_id' => null,
            'status' => 'completed',
            'request_data' => null,
            'response_data' => null,
            'error_message' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $record = array_merge($defaults, $record);
        $record['id'] = $DB->insert_record('local_savian_ai_generations', (object) $record);

        return (object) $record;
    }
}
