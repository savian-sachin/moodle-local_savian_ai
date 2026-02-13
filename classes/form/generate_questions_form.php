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
 * Generate questions form definition.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for generating questions with Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_questions_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $courseid = $customdata['courseid'] ?? 0;
        $mode = $customdata['mode'] ?? 'topic'; // Possible values: 'topic' or 'documents'.

        // Course ID (hidden) - must be first to ensure it is included.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);

        // Generation mode (hidden).
        $mform->addElement('hidden', 'mode', $mode);
        $mform->setType('mode', PARAM_ALPHA);
        $mform->setDefault('mode', $mode);

        // Topic.
        $mform->addElement('text', 'topic', get_string('topic', 'local_savian_ai'), ['size' => 60]);
        $mform->setType('topic', PARAM_TEXT);
        $mform->addRule('topic', get_string('required'), 'required', null, 'client');

        // Document selection (only for RAG mode) - simple dropdown.
        if ($mode === 'documents') {
            // Get only current course documents.
            $documents = $DB->get_records_menu('local_savian_documents',
                ['is_active' => 1, 'status' => 'completed', 'course_id' => $courseid],
                'title ASC',
                'savian_doc_id, title');

            if (empty($documents)) {
                $mform->addElement('static', 'no_documents', '',
                    \html_writer::div(get_string('no_documents', 'local_savian_ai'), 'alert alert-warning'));
            } else {
                $options = [
                    'multiple' => true,
                    'size' => min(8, count($documents)),
                ];
                $select = $mform->addElement('select', 'document_ids',
                    get_string('select_documents', 'local_savian_ai'),
                    $documents, $options);
                $mform->addRule('document_ids', get_string('required'), 'required', null, 'client');
            }
        }

        // Learning objectives.
        $mform->addElement('textarea', 'learning_objectives',
            get_string('learning_objectives', 'local_savian_ai'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('learning_objectives', PARAM_TEXT);
        $mform->addHelpButton('learning_objectives', 'learning_objectives', 'local_savian_ai');

        // Question types.
        $questiontypes = [
            'multichoice' => get_string('qtype_multichoice', 'local_savian_ai'),
            'truefalse' => get_string('qtype_truefalse', 'local_savian_ai'),
            'shortanswer' => get_string('qtype_shortanswer', 'local_savian_ai'),
            'essay' => get_string('qtype_essay', 'local_savian_ai'),
            'matching' => get_string('qtype_matching', 'local_savian_ai'),
        ];
        $select = $mform->addElement('select', 'question_types',
            get_string('question_types', 'local_savian_ai'),
            $questiontypes);
        $select->setMultiple(true);
        $select->setSelected(['multichoice', 'truefalse']);
        $mform->addRule('question_types', get_string('required'), 'required', null, 'client');

        // Question count.
        $mform->addElement('select', 'count',
            get_string('question_count', 'local_savian_ai'),
            [5 => 5, 10 => 10, 15 => 15, 20 => 20, 25 => 25]);
        $mform->setDefault('count', 5);

        // Difficulty.
        $difficulties = [
            'easy' => get_string('difficulty_easy', 'local_savian_ai'),
            'medium' => get_string('difficulty_medium', 'local_savian_ai'),
            'hard' => get_string('difficulty_hard', 'local_savian_ai'),
        ];
        $mform->addElement('select', 'difficulty',
            get_string('difficulty', 'local_savian_ai'),
            $difficulties);
        $mform->setDefault('difficulty', 'medium');

        // Bloom's level.
        $bloomlevels = [
            'remember' => get_string('bloom_remember', 'local_savian_ai'),
            'understand' => get_string('bloom_understand', 'local_savian_ai'),
            'apply' => get_string('bloom_apply', 'local_savian_ai'),
            'analyze' => get_string('bloom_analyze', 'local_savian_ai'),
            'evaluate' => get_string('bloom_evaluate', 'local_savian_ai'),
            'create' => get_string('bloom_create', 'local_savian_ai'),
        ];
        $mform->addElement('select', 'bloom_level',
            get_string('bloom_level', 'local_savian_ai'),
            $bloomlevels);
        $mform->setDefault('bloom_level', 'understand');

        // Language.
        $languages = [
            'en' => 'English',
            'vi' => 'Vietnamese',
        ];
        $mform->addElement('select', 'language',
            get_string('language', 'local_savian_ai'),
            $languages);
        $mform->setDefault('language', 'en');

        // Action buttons.
        $this->add_action_buttons(true, get_string('generate_questions', 'local_savian_ai'));
    }

    /**
     * Validation.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['mode'] === 'documents' && empty($data['document_ids'])) {
            $errors['document_ids'] = get_string('required');
        }

        if (empty($data['question_types'])) {
            $errors['question_types'] = get_string('required');
        }

        return $errors;
    }
}
