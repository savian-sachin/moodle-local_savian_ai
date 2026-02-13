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
 * Upload document form definition.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for uploading documents to Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_document_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        // Title.
        $mform->addElement('text', 'title', get_string('document_title', 'local_savian_ai'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        // File picker.
        $mform->addElement(
            'filepicker',
            'document',
            get_string('upload_document', 'local_savian_ai'),
            null,
            [
                'accepted_types' => ['.pdf', '.docx', '.txt'],
                'maxbytes' => 50 * 1024 * 1024,
            ]
        );
        $mform->addRule('document', get_string('required'), 'required', null, 'client');

        // Description.
        $mform->addElement(
            'textarea',
            'description',
            get_string('document_description', 'local_savian_ai'),
            [
                'rows' => 4,
                'cols' => 60,
            ]
        );
        $mform->setType('description', PARAM_TEXT);

        // Subject area.
        $mform->addElement('text', 'subject_area', get_string('document_subject', 'local_savian_ai'), ['size' => 40]);
        $mform->setType('subject_area', PARAM_TEXT);

        // Tags.
        $mform->addElement('text', 'tags', get_string('document_tags', 'local_savian_ai'), ['size' => 60]);
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'document_tags', 'local_savian_ai');

        // Course ID (hidden) - preserve context after upload.
        $courseid = $this->_customdata['courseid'] ?? 0;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Action buttons.
        $this->add_action_buttons(true, get_string('upload_document', 'local_savian_ai'));
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

        // Validate file was uploaded.
        if (empty($data['document'])) {
            $errors['document'] = get_string('required');
        }

        return $errors;
    }
}
