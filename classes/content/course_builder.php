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
 * Course builder.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\content;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');

/**
 * Course Builder - Creates Moodle course content from AI-generated structures.
 *
 * Converts external service course structures into native Moodle content including
 * sections, pages, activities, discussions, quizzes, assignments, and formative assessments.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_builder {
    /** @var qbank_creator Question bank creator. */
    private $qbankcreator;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->qbankcreator = new qbank_creator();
    }

    /**
     * Add AI-generated content to existing course.
     *
     * @param int $courseid Course ID
     * @param object $coursestructure Course structure from API
     * @return array Results with created items
     */
    public function add_content_to_course($courseid, $coursestructure) {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $results = [
            'sections_created' => 0,
            'pages_created' => 0,
            'activities_created' => 0,
            'discussions_created' => 0,
            'formative_created' => 0,
            'quizzes_created' => 0,
            'assignments_created' => 0,
            'glossary_created' => false,
            'errors' => [],
        ];

        // Get next section number.
        $nextsectionnum = $this->get_next_section_number($courseid);

        // Create sections and content.
        if (isset($coursestructure->sections)) {
            foreach ($coursestructure->sections as $sectiondata) {
                try {
                    $sectionnum = $this->create_section($courseid, $sectiondata, $nextsectionnum);
                    $results['sections_created']++;
                    $nextsectionnum++;

                    // Add content to this section.
                    if (isset($sectiondata->content)) {
                        foreach ($sectiondata->content as $contentitem) {
                            try {
                                switch ($contentitem->type) {
                                    case 'page':
                                        $this->create_page($courseid, $sectionnum, $contentitem);
                                        $results['pages_created']++;
                                        break;
                                    case 'activity':
                                        $this->create_activity($courseid, $sectionnum, $contentitem);
                                        $results['activities_created']++;
                                        break;
                                    case 'discussion':
                                        $this->create_discussion($courseid, $sectionnum, $contentitem);
                                        $results['discussions_created']++;
                                        break;
                                    case 'formative':
                                        $this->create_formative_assessment($courseid, $sectionnum, $contentitem);
                                        $results['formative_created']++;
                                        break;
                                    case 'quiz':
                                        $this->create_quiz($courseid, $sectionnum, $contentitem);
                                        $results['quizzes_created']++;
                                        break;
                                    case 'assignment':
                                        $this->create_assignment($courseid, $sectionnum, $contentitem);
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

        // Create glossary if terms provided.
        if (isset($coursestructure->glossary_terms) && !empty($coursestructure->glossary_terms)) {
            try {
                $this->create_glossary($courseid, $coursestructure->glossary_terms);
                $results['glossary_created'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = "Glossary error: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get next available section number in course.
     *
     * @param int $courseid Course ID
     * @return int Next section number
     */
    protected function get_next_section_number($courseid) {
        global $DB;
        $maxsection = $DB->get_field_sql(
            'SELECT MAX(section) FROM {course_sections} WHERE course = ?',
            [$courseid]
        );
        return ($maxsection ?? 0) + 1;
    }

    /**
     * Create a course section.
     *
     * @param int $courseid Course ID
     * @param object $sectiondata Section data from API
     * @param int $sectionnum Section number
     * @return int Section number
     */
    protected function create_section($courseid, $sectiondata, $sectionnum) {
        global $DB;

        $section = new \stdClass();
        $section->course = $courseid;
        $section->section = $sectionnum;
        $section->name = $this->extract_string($sectiondata->title ?? "Section {$sectionnum}");
        $section->summary = $this->extract_string($sectiondata->summary ?? '');
        $section->summaryformat = FORMAT_HTML;
        $section->visible = 1;

        $sectionid = $DB->insert_record('course_sections', $section);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        return $sectionnum;
    }

    /**
     * Create a page resource.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $pagedata Page data from API
     * @return int Page ID
     */
    protected function create_page($courseid, $sectionnum, $pagedata) {
        global $DB, $USER, $CFG;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module first.
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'page']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create page instance.
        $page = new \stdClass();
        $page->course = $courseid;
        $page->coursemodule = $cm->id;
        $page->name = $this->extract_string($pagedata->title ?? 'Page');
        $page->intro = '';
        $page->introformat = FORMAT_HTML;
        $page->content = $this->extract_string($pagedata->content ?? '');
        $page->contentformat = FORMAT_HTML;
        $page->display = 5;
        $page->displayoptions = '';
        $page->revision = 1;
        $page->timemodified = time();

        $page->id = $DB->insert_record('page', $page);

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $page->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $page->id;
    }

    /**
     * Create a quiz with questions.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $quizdata Quiz data from API
     * @return int Quiz ID
     */
    protected function create_quiz($courseid, $sectionnum, $quizdata) {
        global $DB, $USER, $CFG;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module first.
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create quiz instance.
        $quiz = new \stdClass();
        $quiz->course = $courseid;
        $quiz->coursemodule = $cm->id;
        $quiz->name = $this->extract_string($quizdata->title ?? 'Quiz');
        $quiz->intro = $this->extract_string($quizdata->description ?? '');
        $quiz->introformat = FORMAT_HTML;
        $quiz->timeopen = 0;
        $quiz->timeclose = 0;
        $quiz->timelimit = 0;
        $quiz->overduehandling = 'autosubmit';
        $quiz->graceperiod = 0;
        $quiz->preferredbehaviour = 'deferredfeedback';
        $quiz->attempts = 0;
        $quiz->attemptonlast = 0;
        $quiz->grademethod = 1;
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

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $quiz->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Add questions if provided.
        if (isset($quizdata->questions) && !empty($quizdata->questions)) {
            $this->qbankcreator->add_to_question_bank(
                (array)$quizdata->questions,
                $courseid
            );
        }

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $quiz->id;
    }

    /**
     * Create an assignment.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $assignmentdata Assignment data from API
     * @return int Assignment ID
     */
    protected function create_assignment($courseid, $sectionnum, $assignmentdata) {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module first.
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'assign']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create assignment instance.
        $assignment = new \stdClass();
        $assignment->course = $courseid;
        $assignment->coursemodule = $cm->id;
        $assignment->name = $this->extract_string($assignmentdata->title ?? 'Assignment');
        $assignment->intro = $this->extract_string($assignmentdata->description ?? '');
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

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $assignment->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $assignment->id;
    }

    /**
     * Create an activity (hands-on exercise using label module).
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $activitydata Activity data from API
     * @return int Label ID
     */
    protected function create_activity($courseid, $sectionnum, $activitydata) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module first (use label module for activities).
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'label']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create label instance with activity instructions.
        $label = new \stdClass();
        $label->course = $courseid;
        $label->coursemodule = $cm->id;
        $label->name = $this->extract_string($activitydata->title ?? 'Activity');
        $label->intro = '<div class="alert alert-info">'
            . '<h4>' . $this->extract_string($activitydata->title ?? 'Activity') . '</h4>'
            . $this->extract_string($activitydata->instructions ?? $activitydata->content ?? '')
            . '</div>';
        $label->introformat = FORMAT_HTML;
        $label->timemodified = time();

        $label->id = $DB->insert_record('label', $label);

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $label->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $label->id;
    }

    /**
     * Create a discussion forum.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $discussiondata Discussion data from API
     * @return int Forum ID
     */
    protected function create_discussion($courseid, $sectionnum, $discussiondata) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module first.
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'forum']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Create forum instance.
        $forum = new \stdClass();
        $forum->course = $courseid;
        $forum->coursemodule = $cm->id;
        $forum->name = $this->extract_string($discussiondata->title ?? 'Discussion');
        $forum->intro = $this->extract_string($discussiondata->prompt ?? $discussiondata->content ?? '');
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

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $forum->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $forum->id;
    }

    /**
     * Create formative assessment (self-check questions).
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param object $formativedata Formative assessment data from API
     * @return int Label ID
     */
    protected function create_formative_assessment($courseid, $sectionnum, $formativedata) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create course module (use label).
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'label']);
        $cm->instance = 0;
        $cm->section = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => $sectionnum]);
        $cm->idnumber = '';
        $cm->added = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        // Build HTML content with collapsible answers.
        $content = '<div class="formative-assessment alert alert-info">';
        $content .= '<h4>✓ Knowledge Check (Ungraded)</h4>';
        $content .= '<p>Test your understanding before moving forward:</p>';

        if (isset($formativedata->questions)) {
            foreach ($formativedata->questions as $idx => $q) {
                $content .= '<div class="mb-3 p-2 bg-light rounded">';
                $content .= '<p><strong>Q' . ($idx + 1) . ':</strong> '
                    . $this->extract_string($q->question) . '</p>';
                $content .= '<details><summary class="btn btn-sm btn-outline-primary">Show Answer</summary>';
                $content .= '<div class="alert alert-success mt-2 mb-0">'
                    . $this->extract_string($q->answer) . '</div>';
                $content .= '</details></div>';
            }
        }

        $content .= '</div>';

        // Create label with formative content.
        $label = new \stdClass();
        $label->course = $courseid;
        $label->coursemodule = $cm->id;
        $label->name = 'Self-Check Questions';
        $label->intro = $content;
        $label->introformat = FORMAT_HTML;
        $label->timemodified = time();

        $label->id = $DB->insert_record('label', $label);

        // Update course_module with instance ID.
        $DB->set_field('course_modules', 'instance', $label->id, ['id' => $cm->id]);

        // Add to section sequence.
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        $sequence = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $sequence[] = $cm->id;
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), ['id' => $section->id]);

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);

        // Trigger course module created event.
        $this->trigger_cm_created_event($cm->id, $courseid);

        return $label->id;
    }

    /**
     * Create or update glossary with terms.
     *
     * @param int $courseid Course ID
     * @param array $terms Array of term data
     * @return int Glossary ID
     */
    protected function create_glossary($courseid, $terms) {
        global $DB;

        // Check if glossary already exists.
        $glossary = $DB->get_record('glossary', ['course' => $courseid, 'name' => 'Course Glossary']);

        if (!$glossary) {
            // Create new glossary.
            $glossarydata = new \stdClass();
            $glossarydata->course = $courseid;
            $glossarydata->name = 'Course Glossary';
            $glossarydata->intro = 'AI-generated glossary terms';
            $glossarydata->introformat = FORMAT_HTML;
            $glossarydata->allowduplicatedentries = 0;
            $glossarydata->displayformat = 'dictionary';
            $glossarydata->mainglossary = 0;
            $glossarydata->showspecial = 1;
            $glossarydata->showalphabet = 1;
            $glossarydata->showall = 1;
            $glossarydata->allowcomments = 0;
            $glossarydata->allowprintview = 1;
            $glossarydata->usedynalink = 0;
            $glossarydata->defaultapproval = 1;
            $glossarydata->globalglossary = 0;
            $glossarydata->entbypage = 10;
            $glossarydata->editalways = 0;
            $glossarydata->rsstype = 0;
            $glossarydata->rssarticles = 0;
            $glossarydata->assessed = 0;
            $glossarydata->assesstimestart = 0;
            $glossarydata->assesstimefinish = 0;
            $glossarydata->scale = 0;
            $glossarydata->timecreated = time();
            $glossarydata->timemodified = time();

            $glossaryid = glossary_add_instance($glossarydata);
            $glossary = $DB->get_record('glossary', ['id' => $glossaryid]);

            // Add to section 0 (general).
            course_add_cm_to_section($courseid, $glossaryid, 0);
        }

        // Add terms to glossary.
        foreach ($terms as $termdata) {
            $entry = new \stdClass();
            $entry->glossaryid = $glossary->id;
            $entry->userid = $USER->id ?? 2;
            $entry->concept = $this->extract_string($termdata->term ?? '');
            $entry->definition = $this->extract_string($termdata->definition ?? '');
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
     * Safely extract string from data (handles nested objects).
     *
     * @param mixed $value Value to extract string from
     * @param string $default Default value if extraction fails
     * @return string Extracted string
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
