<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\content;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');

/**
 * Course Builder - Creates Moodle course content from AI-generated structures
 *
 * Converts external service course structures into native Moodle content including
 * sections, pages, activities, discussions, quizzes, assignments, and formative assessments.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_builder {

    /** @var qbank_creator Question bank creator */
    private $qbank_creator;

    public function __construct() {
        $this->qbank_creator = new qbank_creator();
    }

    /**
     * Add AI-generated content to existing course
     *
     * @param int $course_id Course ID
     * @param object $course_structure Course structure from API
     * @return array Results with created items
     */
    public function add_content_to_course($course_id, $course_structure) {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
        $results = [
            'sections_created' => 0,
            'pages_created' => 0,
            'activities_created' => 0,
            'discussions_created' => 0,
            'formative_created' => 0,  // ADDIE v2.0
            'quizzes_created' => 0,
            'assignments_created' => 0,
            'glossary_created' => false,
            'errors' => []
        ];

        // Get next section number
        $next_section_num = $this->get_next_section_number($course_id);

        // Create sections and content
        if (isset($course_structure->sections)) {
            foreach ($course_structure->sections as $section_data) {
                try {
                    $section_num = $this->create_section($course_id, $section_data, $next_section_num);
                    $results['sections_created']++;
                    $next_section_num++;

                    // Add content to this section
                    if (isset($section_data->content)) {
                        foreach ($section_data->content as $content_item) {
                            try {
                                switch ($content_item->type) {
                                    case 'page':
                                        $this->create_page($course_id, $section_num, $content_item);
                                        $results['pages_created']++;
                                        break;
                                    case 'activity':
                                        $this->create_activity($course_id, $section_num, $content_item);
                                        $results['activities_created']++;
                                        break;
                                    case 'discussion':
                                        $this->create_discussion($course_id, $section_num, $content_item);
                                        $results['discussions_created']++;
                                        break;
                                    case 'formative':  // ADDIE v2.0
                                        $this->create_formative_assessment($course_id, $section_num, $content_item);
                                        $results['formative_created']++;
                                        break;
                                    case 'quiz':
                                        $this->create_quiz($course_id, $section_num, $content_item);
                                        $results['quizzes_created']++;
                                        break;
                                    case 'assignment':
                                        $this->create_assignment($course_id, $section_num, $content_item);
                                        $results['assignments_created']++;
                                        break;
                                }
                            } catch (\Exception $e) {
                                $results['errors'][] = "Content item error: " . $e->getMessage();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Section error: " . $e->getMessage();
                }
            }
        }

        // Create glossary if terms provided
        if (isset($course_structure->glossary_terms) && !empty($course_structure->glossary_terms)) {
            try {
                $this->create_glossary($course_id, $course_structure->glossary_terms);
                $results['glossary_created'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = "Glossary error: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get next available section number in course
     */
    protected function get_next_section_number($course_id) {
        global $DB;
        $max_section = $DB->get_field_sql(
            'SELECT MAX(section) FROM {course_sections} WHERE course = ?',
            [$course_id]
        );
        return ($max_section ?? 0) + 1;
    }

    /**
     * Create a course section
     */
    protected function create_section($course_id, $section_data, $section_num) {
        global $DB;

        $section = new \stdClass();
        $section->course = $course_id;
        $section->section = $section_num;
        $section->name = $this->extract_string($section_data->title ?? "Section {$section_num}");
        $section->summary = $this->extract_string($section_data->summary ?? '');
        $section->summaryformat = FORMAT_HTML;
        $section->visible = 1;

        $section_id = $DB->insert_record('course_sections', $section);

        // Rebuild course cache
        rebuild_course_cache($course_id, true);

        return $section_num;
    }

    /**
     * Create a page resource
     */
    protected function create_page($course_id, $section_num, $page_data) {
        global $DB, $USER, $CFG;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module first.
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'page']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create page instance.
        $page = new \stdClass();
        $page->course = $course_id;
        $page->coursemodule = $cm->id;
        $page->name = $this->extract_string($page_data->title ?? 'Page');
        $page->intro = '';
        $page->introformat = FORMAT_HTML;
        $page->content = $this->extract_string($page_data->content ?? '');
        $page->contentformat = FORMAT_HTML;
        $page->display = 5;
        $page->displayoptions = '';
        $page->revision = 1;
        $page->timemodified = time();

        $page->id = $DB->insert_record('page', $page);

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $page->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $page->id;
    }

    /**
     * Create a quiz with questions
     */
    protected function create_quiz($course_id, $section_num, $quiz_data) {
        global $DB, $USER, $CFG;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module first
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create quiz instance
        $quiz = new \stdClass();
        $quiz->course = $course_id;
        $quiz->coursemodule = $cm->id;
        $quiz->name = $this->extract_string($quiz_data->title ?? 'Quiz');
        $quiz->intro = $this->extract_string($quiz_data->description ?? '');
        $quiz->introformat = FORMAT_HTML;
        $quiz->timeopen = 0;
        $quiz->timeclose = 0;
        $quiz->timelimit = 0;
        $quiz->overduehandling = 'autosubmit';
        $quiz->graceperiod = 0;
        $quiz->preferredbehaviour = 'deferredfeedback';
        $quiz->attempts = 0; // Unlimited
        $quiz->attemptonlast = 0;
        $quiz->grademethod = 1; // Highest grade
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = -1;
        $quiz->reviewattempt = 69904;
        $quiz->reviewcorrectness = 69904;
        $quiz->reviewmarks = 69904;
        $quiz->reviewspecificfeedback = 69904;
        $quiz->reviewgeneralfeedback = 69904;
        $quiz->reviewrightanswer = 69904;
        $quiz->reviewoverallfeedback = 69904;
        $quiz->questionsperpage = 1;
        $quiz->navmethod = 'free';
        $quiz->shuffleanswers = 1;
        $quiz->sumgrades = 0;
        $quiz->grade = 10;
        $quiz->timecreated = time();
        $quiz->timemodified = time();
        $quiz->password = '';
        $quiz->subnet = '';
        $quiz->browsersecurity = '-';
        $quiz->delay1 = 0;
        $quiz->delay2 = 0;
        $quiz->showuserpicture = 0;
        $quiz->showblocks = 0;
        $quiz->completionattemptsexhausted = 0;
        $quiz->completionpass = 0;
        $quiz->allowofflineattempts = 0;

        $quiz->id = $DB->insert_record('quiz', $quiz);

        // Update course_module with instance ID
        $DB->set_field('course_modules', 'instance', $quiz->id, ['id' => $cm->id]);

        // Add to section sequence
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Add questions if provided
        if (isset($quiz_data->questions) && !empty($quiz_data->questions)) {
            $this->qbank_creator->add_to_question_bank(
                (array)$quiz_data->questions,
                $course_id
            );
        }

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $quiz->id;
    }

    /**
     * Create an assignment
     */
    protected function create_assignment($course_id, $section_num, $assignment_data) {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module first
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'assign']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create assignment instance
        $assignment = new \stdClass();
        $assignment->course = $course_id;
        $assignment->coursemodule = $cm->id;
        $assignment->name = $this->extract_string($assignment_data->title ?? 'Assignment');
        $assignment->intro = $this->extract_string($assignment_data->description ?? '');
        $assignment->introformat = FORMAT_HTML;
        $assignment->alwaysshowdescription = 1;
        $assignment->submissiondrafts = 0;
        $assignment->sendnotifications = 0;
        $assignment->sendlatenotifications = 0;
        $assignment->duedate = 0;
        $assignment->cutoffdate = 0;
        $assignment->gradingduedate = 0;
        $assignment->allowsubmissionsfromdate = 0;
        $assignment->grade = 100;
        $assignment->timemodified = time();
        $assignment->requiresubmissionstatement = 0;
        $assignment->completionsubmit = 0;
        $assignment->teamsubmission = 0;
        $assignment->requireallteammemberssubmit = 0;
        $assignment->teamsubmissiongroupingid = 0;
        $assignment->blindmarking = 0;
        $assignment->attemptreopenmethod = 'none';
        $assignment->maxattempts = -1;
        $assignment->markingworkflow = 0;
        $assignment->markingallocation = 0;

        $assignment->id = $DB->insert_record('assign', $assignment);

        // Update course_module with instance ID
        $DB->set_field('course_modules', 'instance', $assignment->id, ['id' => $cm->id]);

        // Add to section sequence
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $assignment->id;
    }

    /**
     * Create an activity (hands-on exercise using label module)
     */
    protected function create_activity($course_id, $section_num, $activity_data) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module first (use label module for activities)
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'label']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create label instance with activity instructions
        $label = new \stdClass();
        $label->course = $course_id;
        $label->coursemodule = $cm->id;
        $label->name = $this->extract_string($activity_data->title ?? 'Activity');
        $label->intro = '<div class="alert alert-info">' .
                        '<h4>🎯 ' . $this->extract_string($activity_data->title ?? 'Activity') . '</h4>' .
                        $this->extract_string($activity_data->instructions ?? $activity_data->content ?? '') .
                        '</div>';
        $label->introformat = FORMAT_HTML;
        $label->timemodified = time();

        $label->id = $DB->insert_record('label', $label);

        // Update course_module with instance ID
        $DB->set_field('course_modules', 'instance', $label->id, ['id' => $cm->id]);

        // Add to section sequence
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $label->id;
    }

    /**
     * Create a discussion forum
     */
    protected function create_discussion($course_id, $section_num, $discussion_data) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module first
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'forum']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create forum instance
        $forum = new \stdClass();
        $forum->course = $course_id;
        $forum->coursemodule = $cm->id;
        $forum->name = $this->extract_string($discussion_data->title ?? 'Discussion');
        $forum->intro = $this->extract_string($discussion_data->prompt ?? $discussion_data->content ?? '');
        $forum->introformat = FORMAT_HTML;
        $forum->type = 'general';
        $forum->assessed = 0;
        $forum->assesstimestart = 0;
        $forum->assesstimefinish = 0;
        $forum->scale = 1;
        $forum->maxbytes = 0;
        $forum->maxattachments = 1;
        $forum->forcesubscribe = 0;
        $forum->trackingtype = 1;
        $forum->rsstype = 0;
        $forum->rssarticles = 0;
        $forum->timemodified = time();
        $forum->warnafter = 0;
        $forum->blockafter = 0;
        $forum->blockperiod = 0;
        $forum->completiondiscussions = 0;
        $forum->completionreplies = 0;
        $forum->completionposts = 0;
        $forum->displaywordcount = 0;
        $forum->lockdiscussionafter = 0;

        $forum->id = $DB->insert_record('forum', $forum);

        // Update course_module with instance ID
        $DB->set_field('course_modules', 'instance', $forum->id, ['id' => $cm->id]);

        // Add to section sequence
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $forum->id;
    }

    /**
     * Create formative assessment (self-check questions) - ADDIE v2.0
     */
    protected function create_formative_assessment($course_id, $section_num, $formative_data) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);

        // Create course module (use label)
        $cm = new \stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'label']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $course_id, 'section' => $section_num]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Build HTML content with collapsible answers
        $content = '<div class="formative-assessment alert alert-info">';
        $content .= '<h4>✓ Knowledge Check (Ungraded)</h4>';
        $content .= '<p>Test your understanding before moving forward:</p>';

        if (isset($formative_data->questions)) {
            foreach ($formative_data->questions as $idx => $q) {
                $content .= '<div class="mb-3 p-2 bg-light rounded">';
                $content .= '<p><strong>Q' . ($idx + 1) . ':</strong> ' .
                            $this->extract_string($q->question) . '</p>';
                $content .= '<details><summary class="btn btn-sm btn-outline-primary">Show Answer</summary>';
                $content .= '<div class="alert alert-success mt-2 mb-0">' .
                            $this->extract_string($q->answer) . '</div>';
                $content .= '</details></div>';
            }
        }

        $content .= '</div>';

        // Create label with formative content
        $label = new \stdClass();
        $label->course = $course_id;
        $label->coursemodule = $cm->id;
        $label->name = 'Self-Check Questions';
        $label->intro = $content;
        $label->introformat = FORMAT_HTML;
        $label->timemodified = time();

        $label->id = $DB->insert_record('label', $label);

        // Update course_module with instance ID
        $DB->set_field('course_modules', 'instance', $label->id, ['id' => $cm->id]);

        // Add to section sequence
        $section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($course_id, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $course_id);

        return $label->id;
    }

    /**
     * Create or update glossary with terms
     */
    protected function create_glossary($course_id, $terms) {
        global $DB;

        // Check if glossary already exists
        $glossary = $DB->get_record('glossary', ['course' => $course_id, 'name' => 'Course Glossary']);

        if (!$glossary) {
            // Create new glossary
            $glossary_data = new \stdClass();
            $glossary_data->course = $course_id;
            $glossary_data->name = 'Course Glossary';
            $glossary_data->intro = 'AI-generated glossary terms';
            $glossary_data->introformat = FORMAT_HTML;
            $glossary_data->allowduplicatedentries = 0;
            $glossary_data->displayformat = 'dictionary';
            $glossary_data->mainglossary = 0;
            $glossary_data->showspecial = 1;
            $glossary_data->showalphabet = 1;
            $glossary_data->showall = 1;
            $glossary_data->allowcomments = 0;
            $glossary_data->allowprintview = 1;
            $glossary_data->usedynalink = 0;
            $glossary_data->defaultapproval = 1;
            $glossary_data->globalglossary = 0;
            $glossary_data->entbypage = 10;
            $glossary_data->editalways = 0;
            $glossary_data->rsstype = 0;
            $glossary_data->rssarticles = 0;
            $glossary_data->assessed = 0;
            $glossary_data->assesstimestart = 0;
            $glossary_data->assesstimefinish = 0;
            $glossary_data->scale = 0;
            $glossary_data->timecreated = time();
            $glossary_data->timemodified = time();

            $glossary_id = glossary_add_instance($glossary_data);
            $glossary = $DB->get_record('glossary', ['id' => $glossary_id]);

            // Add to section 0 (general)
            course_add_cm_to_section($course_id, $glossary_id, 0);
        }

        // Add terms to glossary
        foreach ($terms as $term_data) {
            $entry = new \stdClass();
            $entry->glossaryid = $glossary->id;
            $entry->userid = $USER->id ?? 2;
            $entry->concept = $this->extract_string($term_data->term ?? '');
            $entry->definition = $this->extract_string($term_data->definition ?? '');
            $entry->definitionformat = FORMAT_HTML;
            $entry->definitiontrust = 0;
            $entry->attachment = '';
            $entry->timecreated = time();
            $entry->timemodified = time();
            $entry->teacherentry = 1;
            $entry->sourceglossaryid = 0;
            $entry->usedynalink = 0;
            $entry->casesensitive = 0;
            $entry->fullmatch = 1;
            $entry->approved = 1;

            $DB->insert_record('glossary_entries', $entry);
        }

        return $glossary->id;
    }

    /**
     * Trigger course_module_created event for a newly created course module.
     *
     * @param int $cmid Course module ID
     * @param int $courseid Course ID
     */
    protected function trigger_cm_created_event($cmid, $courseid) {
        try {
            $cminfo = get_coursemodule_from_id('', $cmid, $courseid);
            if ($cminfo) {
                $event = \core\event\course_module_created::create_from_cm($cminfo);
                $event->trigger();
            }
        } catch (\Exception $e) {
            debugging('Failed to trigger course_module_created event: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Safely extract string from data (handles nested objects)
     */
    protected function extract_string($value, $default = '') {
        if (is_string($value)) {
            return $value;
        }
        if (is_object($value) || is_array($value)) {
            if (is_object($value) && isset($value->en)) {
                return (string)$value->en;
            }
            if (is_array($value) && isset($value['en'])) {
                return (string)$value['en'];
            }
            return json_encode($value);
        }
        return (string)$default;
    }
}
