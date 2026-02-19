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
 * Course creation page.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('local/savian_ai:generate', $context);

$PAGE->set_url(new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('generate_course_content', 'local_savian_ai'));
$PAGE->set_heading($course->fullname);

$client = new \local_savian_ai\api\client();
$coursebuilder = new \local_savian_ai\content\course_builder();
$saviancache = cache::make('local_savian_ai', 'session_data');

// Handle create action.
if ($action === 'create' && confirm_sesskey() && !empty($saviancache->get('course_structure'))) {
    $coursestructure = json_decode($saviancache->get('course_structure'));

    $results = $coursebuilder->add_content_to_course($courseid, $coursestructure);

    // Log the course content generation.
    $log = new stdClass();
    $pendingreq = $saviancache->get('pending_request');
    $log->request_id = $pendingreq ?: ('local_' . time());
    $log->generation_type = 'course_content';
    $log->course_id = $courseid;
    $log->user_id = $USER->id;
    $log->questions_count = 0;
    $log->status = 'completed';
    $log->response_data = json_encode($results);
    $log->timecreated = time();
    $log->timemodified = time();
    $DB->insert_record('local_savian_ai_generations', $log);

    // Store generation data for knowledge feedback loop (v2.2).
    $kbsavedata = [
        'course_structure' => $saviancache->get('course_structure'),
        'course_title' => $course->fullname,
        'course_id' => $courseid,
        'request_id' => $pendingreq ?: null,
        'results' => $results,
    ];
    $saviancache->set('kb_save_data', $kbsavedata);

    $saviancache->delete('course_structure');
    $saviancache->delete('pending_request');
    $saviancache->delete('sources');

    // Redirect to success page with save option.
    $successurl = new moodle_url('/local/savian_ai/create_course.php', [
        'courseid' => $courseid,
        'action' => 'success',
    ]);
    redirect($successurl);
}

// Handle polling.
if ($action === 'poll' && !empty($saviancache->get('pending_request'))) {
    $requestid = $saviancache->get('pending_request');
    $statusresponse = $client->get_generation_status($requestid);

    if ($statusresponse->http_code === 200) {
        if (isset($statusresponse->status) && $statusresponse->status === 'completed') {
            if (isset($statusresponse->course_structure)) {
                $saviancache->set('course_structure', json_encode($statusresponse->course_structure));
                $saviancache->set('sources', isset($statusresponse->sources) ? json_encode($statusresponse->sources) : null);
                $saviancache->delete('pending_request');

                $previewurl = new moodle_url('/local/savian_ai/create_course.php', [
                    'courseid' => $courseid,
                    'action' => 'preview',
                ]);
                redirect($previewurl, get_string('course_structure_generated', 'local_savian_ai'), null, 'success');
            }
        } else if (isset($statusresponse->status) && $statusresponse->status === 'failed') {
            $error = $statusresponse->error ?? get_string('generation_failed', 'local_savian_ai', '');
            $saviancache->delete('pending_request');
            redirect(
                new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
                get_string('generation_failed', 'local_savian_ai', $error),
                null,
                'error'
            );
        }
    }
}

// Handle form submission.
if (data_submitted() && confirm_sesskey()) {
    $docids = optional_param_array('document_ids', [], PARAM_INT);
    $title = $course->fullname;  // Use existing course name.
    $description = optional_param('description', '', PARAM_TEXT);
    $targetaudience = optional_param('target_audience', '', PARAM_TEXT);
    $duration = optional_param('duration_weeks', 4, PARAM_INT);
    $contenttypes = optional_param_array('content_types', ['sections', 'pages', 'quiz_questions'], PARAM_TEXT);

    // ADDIE v2.0 parameters.
    $agegroup = optional_param('age_group', 'undergrad', PARAM_ALPHA);
    $industry = optional_param('industry', 'general', PARAM_ALPHA);
    $priorknowledge = optional_param('prior_knowledge_level', 'beginner', PARAM_ALPHA);

    // Validate required fields.
    if (empty($docids)) {
        redirect(
            new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
            get_string('no_documents_selected', 'local_savian_ai'),
            null,
            'error'
        );
    }

    if (!empty($docids)) {
        // RAG-based generation with ADDIE v2.0.
        $options = [
            'course_id' => $courseid,
            'description' => $description,
            'target_audience' => $targetaudience,
            'duration_weeks' => $duration,
            'age_group' => $agegroup,
            'industry' => $industry,
            'prior_knowledge_level' => $priorknowledge,
            'content_types' => $contenttypes,
            'language' => 'en',
        ];
        $response = $client->generate_course_from_documents($docids, $title, $options);

        if ($response->http_code === 200 && isset($response->success) && $response->success) {
            if (isset($response->request_id) && isset($response->status) && $response->status === 'pending') {
                $saviancache->set('pending_request', $response->request_id);
                $saviancache->set('duration', $duration);
                $pollurl = new moodle_url('/local/savian_ai/create_course.php', [
                    'courseid' => $courseid,
                    'action' => 'poll',
                ]);
                redirect($pollurl, get_string('generating_course_content_msg', 'local_savian_ai'), null, 'info');
            }
        } else {
            // Error or synchronous response.
            $error = $response->error ?? $response->message ?? 'HTTP: ' . $response->http_code;
            redirect(
                new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]),
                get_string('generation_failed', 'local_savian_ai', $error),
                null,
                'error'
            );
        }
    }
}

echo $OUTPUT->header();

// Consistent header.
echo local_savian_ai_render_header(
    get_string('generate_course_content', 'local_savian_ai'),
    get_string('generate_course_content_subtitle', 'local_savian_ai')
);

if ($action === 'poll' && !empty($saviancache->get('pending_request'))) {
    // Show progress bar with real-time updates.
    echo $OUTPUT->heading(get_string('generating_course_content', 'local_savian_ai'), 3);

    // Progress card.
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body');

    // Progress title (dynamic).
    echo html_writer::tag('h5', get_string('progress_unknown', 'local_savian_ai'), ['id' => 'progress-title', 'class' => 'mb-3']);

    // Progress bar (30px height).
    echo html_writer::start_div('progress mb-3', ['style' => 'height: 30px;']);
    $progressattrs = [
        'id' => 'progress-bar',
        'role' => 'progressbar',
        'style' => 'width: 0%',
        'aria-valuenow' => '0',
        'aria-valuemin' => '0',
        'aria-valuemax' => '100',
    ];
    echo html_writer::div(
        '0%',
        'progress-bar bg-primary progress-bar-striped progress-bar-animated',
        $progressattrs
    );
    echo html_writer::end_div();

    // Progress details (current section).
    echo html_writer::tag('p', '', ['id' => 'progress-details', 'class' => 'text-muted mb-2']);

    // Estimated time.
    $duration = $saviancache->get('duration') ?: 4;
    if ($duration <= 4) {
        $esttime = get_string('estimated_time_4weeks', 'local_savian_ai');
    } else if ($duration <= 8) {
        $esttime = get_string('estimated_time_8weeks', 'local_savian_ai');
    } else {
        $esttime = get_string('estimated_time_12weeks', 'local_savian_ai');
    }
    echo html_writer::tag('p', $esttime, ['class' => 'small text-muted']);

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Cancel button.
    $cancelurl = new moodle_url('/local/savian_ai/create_course.php', [
        'courseid' => $courseid,
        'action' => 'cancel',
    ]);
    echo html_writer::div(
        html_writer::link(
            $cancelurl,
            get_string('cancel_generation', 'local_savian_ai'),
            ['class' => 'btn btn-outline-danger']
        ),
        'text-center'
    );

    // AJAX polling with progress updates.
    $requestid = $saviancache->get('pending_request');
    $PAGE->requires->js_amd_inline("
require(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    var requestId = '{$requestid}';
    var pollInterval = 2500; // 2.5 seconds.
    var courseid = {$courseid};

    function updateProgress() {
        Ajax.call([{
            methodname: 'local_savian_ai_get_generation_status',
            args: {requestid: requestId}
        }])[0].done(function(response) {
            if (response.success) {
                // Update progress bar.
                $('#progress-bar')
                    .css('width', response.progress + '%')
                    .attr('aria-valuenow', response.progress)
                    .text(response.progress + '%');

                // Update stage text.
                var stageText = getStageText(response.details.stage, response.progress);
                $('#progress-title').text(stageText);

                // Update details (current section if available).
                if (response.details.current_section) {
                    $('#progress-details').text(response.details.current_section);
                } else {
                    $('#progress-details').text('');
                }

                // Check if completed.
                if (response.status === 'completed') {
                    window.location.href = M.cfg.wwwroot +
                        '/local/savian_ai/create_course.php?courseid=' +
                        courseid + '&action=preview';
                } else if (response.status === 'failed') {
                    Notification.alert(
                        '" . get_string('generation_failed_title', 'local_savian_ai') . "',
                        response.error || '" . get_string('error', 'local_savian_ai') . "',
                        'OK');
                    window.location.href = M.cfg.wwwroot +
                        '/local/savian_ai/create_course.php?courseid=' + courseid;
                } else {
                    // Continue polling.
                    setTimeout(updateProgress, pollInterval);
                }
            } else {
                // Error in response.
                Notification.alert('" . get_string('error', 'local_savian_ai') . "', response.error || '" . get_string('generation_status_error', 'local_savian_ai') . "', 'OK');
                setTimeout(updateProgress, pollInterval * 2); // Retry with longer interval.
            }
        }).fail(function(error) {
            console.error('AJAX error:', error);
            setTimeout(updateProgress, pollInterval * 2); // Retry.
        });
    }

    function getStageText(stage, progress) {
        var stages = {
            'pending': '" . get_string('queued_processing', 'local_savian_ai') . "',
            // ADDIE v2.0 stages.
            'addie_analysis': '" . get_string('progress_addie_analysis', 'local_savian_ai') . "',
            'addie_design_outline': '" . get_string('progress_addie_design_outline', 'local_savian_ai') . "',
            'addie_design_completed': '" . get_string('progress_addie_design_completed', 'local_savian_ai') . "',
            'addie_development_completed': '" . get_string('progress_addie_development_completed', 'local_savian_ai') . "',
            'addie_implementation': '" . get_string('progress_addie_implementation', 'local_savian_ai') . "',
            'addie_evaluation': '" . get_string('progress_addie_evaluation', 'local_savian_ai') . "',
            'addie_completed': '" . get_string('progress_addie_completed', 'local_savian_ai') . "',
            'completed': '" . get_string('progress_complete', 'local_savian_ai') . "'
        };

        // Handle addie_dev_section_N pattern (ADDIE v2.0).
        if (stage && stage.indexOf('addie_dev_section_') === 0) {
            var sectionNum = stage.replace('addie_dev_section_', '');
            return '" . get_string('creating_week_content', 'local_savian_ai', "' + sectionNum + '") . "';
        }

        // Handle old generating_section pattern (backward compatibility).
        if (stage && stage.indexOf('generating_section_') === 0) {
            var sectionNum = stage.replace('generating_section_', '');
            return '" . get_string('creating_section_content', 'local_savian_ai', "' + sectionNum + '") . "';
        }

        return stages[stage] || '" . get_string('progress_unknown', 'local_savian_ai') . " (' + progress + '%)';
    }

    // Start polling after 1 second.
    setTimeout(updateProgress, 1000);
});
");
} else if ($action === 'preview' && !empty($saviancache->get('course_structure'))) {
    // Show enhanced preview.
    $structure = json_decode($saviancache->get('course_structure'));

    $previewtitle = get_string('preview_course_structure', 'local_savian_ai')
        . ' (' . count($structure->sections) . ' sections)';
    echo html_writer::tag('h3', $previewtitle, ['class' => 'mt-4']);

    // ADDIE v2.0: AI Transparency Notice.
    if (isset($structure->ai_transparency_notice)) {
        echo $structure->ai_transparency_notice;
    }

    // ADDIE v2.0: Quality Matters Alignment.
    if (isset($structure->quality_markers->qm_alignment)) {
        $qm = $structure->quality_markers->qm_alignment;
        $scoreclass = $qm->qm_certified_ready ? 'success' : 'warning';

        echo html_writer::start_div('card mb-3 border-' . $scoreclass);
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📊 ' . get_string('qm_alignment', 'local_savian_ai'), ['class' => 'card-title']);
        $qmscorecontent = get_string('qm_score', 'local_savian_ai') . ': '
            . html_writer::tag('strong', $qm->total_score . '%')
            . ' (' . $qm->standards_met . '/' . $qm->standards_total . ' standards met)';
        echo html_writer::tag('p', $qmscorecontent);

        if ($qm->qm_certified_ready) {
            echo html_writer::div('✅ ' . get_string('qm_certified_ready', 'local_savian_ai'), 'alert alert-success mb-2');
        } else {
            echo html_writer::div('⚠️ ' . get_string('qm_below_threshold', 'local_savian_ai'), 'alert alert-warning mb-2');
        }

        if (isset($qm->recommendations) && !empty($qm->recommendations)) {
            $qmreclabel = get_string('qm_recommendations', 'local_savian_ai') . ':';
            echo html_writer::tag('strong', $qmreclabel, ['class' => 'd-block mt-2 mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-0']);
            foreach ($qm->recommendations as $rec) {
                echo html_writer::tag('li', s($rec));
            }
            echo html_writer::end_tag('ul');
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // ADDIE v2.1: Quality Report (NEW).
    if (isset($structure->quality_report)) {
        $report = $structure->quality_report;

        echo html_writer::start_div('card mb-3 border-success');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📊 ' . get_string('quality_report', 'local_savian_ai'), ['class' => 'card-title text-success']);

        // Scores in row.
        echo html_writer::start_div('row text-center mb-3');

        // Overall Score.
        $overallscore = $report->overall_score ?? 0;
        $scorecolor = $overallscore >= 80 ? 'success' : ($overallscore >= 60 ? 'warning' : 'danger');
        echo html_writer::div(
            html_writer::tag('div', $overallscore . '/100', ['class' => "h2 mb-0 text-{$scorecolor}"]) .
            html_writer::tag('div', get_string('overall_score', 'local_savian_ai'), ['class' => 'small text-muted']),
            'col-md-3'
        );

        // Source Coverage.
        if (isset($report->source_coverage_average)) {
            $coveragepct = round($report->source_coverage_average * 100);
            $coveragecolor = $coveragepct >= 80 ? 'success' : ($coveragepct >= 60 ? 'info' : 'warning');
            echo html_writer::div(
                html_writer::tag('div', $coveragepct . '%', ['class' => "h2 mb-0 text-{$coveragecolor}"]) .
                html_writer::tag('div', get_string('source_coverage', 'local_savian_ai'), ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        // Learning Depth.
        if (isset($report->learning_depth_average)) {
            $depthscore = $report->learning_depth_average;
            $depthcolor = $depthscore >= 75 ? 'success' : ($depthscore >= 50 ? 'primary' : 'secondary');
            echo html_writer::div(
                html_writer::tag('div', $depthscore . '/100', ['class' => "h2 mb-0 text-{$depthcolor}"]) .
                html_writer::tag('div', get_string('learning_depth', 'local_savian_ai'), ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        // Hallucination Risk (v2.1).
        if (isset($report->hallucination_risk)) {
            $risk = strtolower($report->hallucination_risk);
            $riskcolor = $risk === 'low' ? 'success' : ($risk === 'medium' ? 'warning' : 'danger');
            $riskicon = $risk === 'low' ? '✓' : ($risk === 'medium' ? '⚠️' : '❗');
            echo html_writer::div(
                html_writer::tag('div', $riskicon, ['class' => "h2 mb-0 text-{$riskcolor}"]) .
                html_writer::tag('div', get_string('risk_label_text', 'local_savian_ai', ucfirst($risk)), ['class' => 'small text-muted']),
                'col-md-3'
            );
        }

        echo html_writer::end_div(); // End row.

        // Strengths (v2.1).
        if (isset($report->instructor_summary->strengths) && !empty($report->instructor_summary->strengths)) {
            echo html_writer::start_div('alert alert-success mb-2');
            echo html_writer::tag('strong', '✅ ' . get_string('strengths_label', 'local_savian_ai'), ['class' => 'd-block mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'mb-0 small']);
            foreach ($report->instructor_summary->strengths as $strength) {
                echo html_writer::tag('li', s($strength));
            }
            echo html_writer::end_tag('ul');
            echo html_writer::end_div();
        }

        // Priority Reviews.
        if (isset($report->instructor_summary->priority_reviews) && !empty($report->instructor_summary->priority_reviews)) {
            echo html_writer::tag('strong', '📝 ' . get_string('priority_reviews', 'local_savian_ai') . ':', ['class' => 'd-block mb-2']);
            echo html_writer::start_tag('ul', ['class' => 'mb-2']);
            foreach ($report->instructor_summary->priority_reviews as $item) {
                echo html_writer::tag('li', s($item));
            }
            echo html_writer::end_tag('ul');
        }

        // Recommended review time.
        if (isset($report->instructor_summary->recommended_review_time)) {
            echo html_writer::div(
                '⏱️ <em>' . get_string('estimated_review_time', 'local_savian_ai', s($report->instructor_summary->recommended_review_time)) . '</em>',
                'text-muted small'
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // ADDIE v2.0: Pedagogical Metadata (Updated field names).
    if (isset($structure->pedagogical_metadata)) {
        $meta = $structure->pedagogical_metadata;

        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', '📚 ' . get_string('pedagogical_metadata', 'local_savian_ai'), ['class' => 'card-title']);

        echo html_writer::start_tag('dl', ['class' => 'row mb-0 small']);

        // Designed For (replaces age_group_name).
        if (isset($meta->designed_for)) {
            echo html_writer::tag('dt', get_string('designed_for_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->designed_for), ['class' => 'col-sm-7']);
        }

        // Subject Area (replaces industry_name).
        if (isset($meta->subject_area)) {
            echo html_writer::tag('dt', get_string('subject_area_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->subject_area), ['class' => 'col-sm-7']);
        }

        // Content Level (replaces reading_level).
        if (isset($meta->content_level)) {
            echo html_writer::tag('dt', get_string('content_level_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->content_level), ['class' => 'col-sm-7']);
        }

        // Instructional Approach (replaces pedagogy_approach).
        if (isset($meta->instructional_approach)) {
            echo html_writer::tag('dt', get_string('instructional_approach_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->instructional_approach), ['class' => 'col-sm-7']);
        }

        // Thinking Skills (NEW field).
        if (isset($meta->thinking_skills)) {
            echo html_writer::tag('dt', get_string('thinking_skills_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->thinking_skills), ['class' => 'col-sm-7']);
        }

        // Generation Method (still available).
        if (isset($meta->generation_method)) {
            echo html_writer::tag('dt', get_string('generation_method_label', 'local_savian_ai'), ['class' => 'col-sm-5']);
            echo html_writer::tag('dd', s($meta->generation_method), ['class' => 'col-sm-7']);
        }

        echo html_writer::end_tag('dl');

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Show sources if available.
    if (!empty($saviancache->get('sources'))) {
        $sources = json_decode($saviancache->get('sources'));
        echo html_writer::start_div('card mb-3 savian-accent-card');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('strong', '📚 ' . get_string('based_on_documents', 'local_savian_ai') . ': ');
        $sourcenames = array_map(function ($s) {
            $title = $s->title ?? get_string('csv_unknown', 'local_savian_ai');
            $chunks = $s->chunks_used ?? 0;
            return "{$title} (" . get_string('chunks_used', 'local_savian_ai', $chunks) . ")";
        }, $sources);
        echo html_writer::tag('div', implode(', ', $sourcenames), ['class' => 'text-muted mt-1']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Enhanced summary statistics card.
    $totalpages = 0;
    $totalactivities = 0;
    $totaldiscussions = 0;
    $totalquizzes = 0;
    $totalassignments = 0;

    foreach ($structure->sections as $section) {
        if (isset($section->content)) {
            foreach ($section->content as $item) {
                switch ($item->type) {
                    case 'page':
                        $totalpages++;
                        break;
                    case 'activity':
                        $totalactivities++;
                        break;
                    case 'discussion':
                        $totaldiscussions++;
                        break;
                    case 'quiz':
                        $totalquizzes++;
                        break;
                    case 'assignment':
                        $totalassignments++;
                        break;
                }
            }
        }
    }

    echo html_writer::start_div('card mb-4 savian-accent-card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('content_summary', 'local_savian_ai'), ['class' => 'card-title mb-3']);
    echo html_writer::start_div('row text-center');

    // Sections.
    echo html_writer::div(
        html_writer::tag('div', count($structure->sections), ['class' => 'h3 mb-0 savian-text-primary']) .
        html_writer::tag('div', get_string('summary_sections', 'local_savian_ai'), ['class' => 'text-muted small']),
        'col-md-2'
    );

    // Pages.
    echo html_writer::div(
        html_writer::tag('div', $totalpages, ['class' => 'h3 mb-0 savian-text-primary']) .
        html_writer::tag('div', get_string('summary_pages', 'local_savian_ai'), ['class' => 'text-muted small']),
        'col-md-2'
    );

    // Activities.
    if ($totalactivities > 0) {
        echo html_writer::div(
            html_writer::tag('div', $totalactivities, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_activities', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Discussions.
    if ($totaldiscussions > 0) {
        echo html_writer::div(
            html_writer::tag('div', $totaldiscussions, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_discussions', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Quizzes.
    if ($totalquizzes > 0) {
        echo html_writer::div(
            html_writer::tag('div', $totalquizzes, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_quizzes', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    // Assignments.
    if ($totalassignments > 0) {
        echo html_writer::div(
            html_writer::tag('div', $totalassignments, ['class' => 'h3 mb-0 savian-text-primary']) .
            html_writer::tag('div', get_string('summary_assignments', 'local_savian_ai'), ['class' => 'text-muted small']),
            'col-md-2'
        );
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Expand/Collapse controls.
    echo html_writer::start_div('mb-3 text-right');
    $expandattrs = ['id' => 'expand-all', 'class' => 'btn btn-sm btn-outline-secondary mr-2'];
    echo html_writer::tag('button', get_string('expand_all', 'local_savian_ai'), $expandattrs);
    $collapseattrs = ['id' => 'collapse-all', 'class' => 'btn btn-sm btn-outline-secondary'];
    echo html_writer::tag('button', get_string('collapse_all', 'local_savian_ai'), $collapseattrs);
    echo html_writer::end_div();

    // Helper function for content icons.
    $geticon = function ($type) {
        $icons = [
            'page' => '📄',
            'activity' => '🎯',
            'discussion' => '💬',
            'formative' => '✓',
            'quiz' => '❓',
            'assignment' => '📝',
        ];
        return $icons[$type] ?? '📌';
    };

    // Show structure with enhanced features.
    foreach ($structure->sections as $idx => $section) {
        $sectionid = 'section_' . $idx;
        echo html_writer::start_div('card mb-3');

        // Card header with checkbox and toggle.
        echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');

        echo html_writer::start_div('d-flex align-items-center flex-wrap');
        $sectioncheckattrs = [
            'type' => 'checkbox',
            'name' => 'include_section_' . $idx,
            'checked' => 'checked',
            'class' => 'mr-2',
            'title' => get_string('include_item', 'local_savian_ai'),
        ];
        echo html_writer::empty_tag('input', $sectioncheckattrs);
        echo html_writer::tag('strong', '📖 ' . get_string('section_n_label', 'local_savian_ai', ($idx + 1)) . ' ' . s($section->title ?? ''));

        // ADDIE v2.1: Section coverage badge.
        if (isset($section->coverage_info)) {
            $coverage = $section->coverage_info;
            $scorepct = round(($coverage->coverage_score ?? 0) * 100);
            $status = $coverage->status ?? 'unknown';

            if ($status === 'excellent' || $scorepct >= 80) {
                $badgeclass = 'success';
                $badgetext = "✅ {$scorepct}%";
            } else if ($status === 'good' || $scorepct >= 60) {
                $badgeclass = 'info';
                $badgetext = "📊 {$scorepct}%";
            } else {
                $badgeclass = 'warning';
                $badgetext = "⚠️ {$scorepct}%";
            }

            $coverageattrs = [
                'class' => "badge badge-{$badgeclass} ml-2",
                'title' => get_string('source_coverage_title', 'local_savian_ai'),
            ];
            echo html_writer::tag('span', $badgetext, $coverageattrs);
        }

        // ADDIE v2.1: Learning depth badge.
        if (isset($section->learning_depth)) {
            $depth = $section->learning_depth;
            $depthlevel = $depth->depth_level ?? 'moderate';
            $depthscore = $depth->depth_score ?? 0;

            $depthicon = $depthlevel === 'deep' ? '🎯' : ($depthlevel === 'moderate' ? '📚' : '📖');
            $depthattrs = [
                'class' => 'badge badge-primary ml-1',
                'title' => get_string('learning_depth_title', 'local_savian_ai', $depthlevel),
            ];
            echo html_writer::tag('span', "{$depthicon} {$depthscore}", $depthattrs);
        }

        echo html_writer::end_div();

        // Toggle button.
        $toggleattrs = [
            'class' => 'btn btn-sm btn-link text-dark',
            'data-toggle' => 'collapse',
            'data-target' => '#' . $sectionid,
            'aria-expanded' => 'true',
            'aria-controls' => $sectionid,
        ];
        echo html_writer::tag('button', '<i class="fa fa-chevron-down"></i>', $toggleattrs);

        echo html_writer::end_div();

        // Collapsible body.
        echo html_writer::start_div('collapse show', ['id' => $sectionid]);
        echo html_writer::start_div('card-body');

        // Section summary.
        if (isset($section->summary)) {
            echo html_writer::tag('div', format_text($section->summary, FORMAT_HTML), ['class' => 'text-muted mb-3 small']);
        }

        // ADDIE v2.0: Prerequisites.
        if (isset($section->prerequisites) && !empty($section->prerequisites)) {
            $prereqlabel = get_string('prerequisites', 'local_savian_ai') . ':';
            echo html_writer::tag('strong', $prereqlabel, ['class' => 'd-block mb-1']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-2']);
            foreach ($section->prerequisites as $prereq) {
                echo html_writer::tag('li', s($prereq));
            }
            echo html_writer::end_tag('ul');
        }

        // ADDIE v2.0: Estimated hours.
        if (isset($section->estimated_hours)) {
            echo html_writer::tag(
                'p',
                get_string('estimated_hours', 'local_savian_ai', $section->estimated_hours),
                ['class' => 'small text-muted mb-2']
            );
        }

        // ADDIE v2.0: QM alignment notes.
        if (isset($section->qm_alignment_notes)) {
            echo html_writer::div(
                '<small><strong>QM:</strong> ' . s($section->qm_alignment_notes) . '</small>',
                'text-muted mb-2'
            );
        }

        // ADDIE v2.0: Source documents used.
        if (isset($section->source_documents_used) && !empty($section->source_documents_used)) {
            echo html_writer::div(
                '<small><strong>📚 Sources:</strong> ' . implode(', ', array_map('s', $section->source_documents_used)) . '</small>',
                'text-muted mb-2'
            );
        }

        // Learning objectives.
        if (isset($section->learning_objectives) && !empty($section->learning_objectives)) {
            $objlabel = get_string('learning_objectives', 'local_savian_ai') . ':';
            echo html_writer::tag('strong', $objlabel, ['class' => 'd-block mb-2']);
            echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
            foreach ($section->learning_objectives as $obj) {
                echo html_writer::tag('li', s($obj));
            }
            echo html_writer::end_tag('ul');
        }

        // Content items with checkboxes and edit buttons.
        if (isset($section->content) && !empty($section->content)) {
            echo html_writer::div(get_string('section_content', 'local_savian_ai') . ':', 'font-weight-bold mb-2 mt-3');
            echo html_writer::start_div('list-group list-group-flush');

            foreach ($section->content as $itemidx => $item) {
                $icon = $geticon($item->type);
                $typelabel = ucfirst(str_replace('_', ' ', $item->type));
                $typebadge = html_writer::tag('span', $typelabel, ['class' => 'badge badge-info badge-sm']);

                // Default titles for items without explicit titles.
                $defaulttitles = [
                    'formative' => get_string('default_formative_title', 'local_savian_ai'),
                    'page' => get_string('content_page_default', 'local_savian_ai'),
                    'activity' => get_string('default_activity_title', 'local_savian_ai'),
                    'discussion' => get_string('default_discussion_title', 'local_savian_ai'),
                    'quiz' => get_string('default_quiz_title', 'local_savian_ai'),
                    'assignment' => get_string('default_assignment_title', 'local_savian_ai'),
                ];
                $displaytitle = $item->title ?? ($defaulttitles[$item->type] ?? 'Untitled');

                echo html_writer::start_div('list-group-item d-flex justify-content-between align-items-center');

                // Checkbox + icon + title + badge + quality tag.
                echo html_writer::start_div('d-flex align-items-center flex-wrap');
                $itemcheckattrs = [
                    'type' => 'checkbox',
                    'name' => "include_item_{$idx}_{$itemidx}",
                    'checked' => 'checked',
                    'class' => 'mr-2',
                    'title' => get_string('include_item', 'local_savian_ai'),
                ];
                echo html_writer::empty_tag('input', $itemcheckattrs);
                echo "{$icon} " . html_writer::tag('span', s($displaytitle), ['class' => 'mr-2']);
                echo $typebadge;

                // ADDIE v2.1: Quality tags for pages.
                if ($item->type === 'page' && isset($item->quality_tags)) {
                    $tags = $item->quality_tags;
                    $confidence = $tags->source_confidence ?? 'medium';

                    if ($confidence === 'high') {
                        $confnote = $tags->instructor_note ?? get_string('high_confidence', 'local_savian_ai');
                        $confattrs = ['class' => 'badge badge-success ml-2', 'title' => $confnote];
                        echo html_writer::tag('span', get_string('quality_tag_verified', 'local_savian_ai'), $confattrs);
                    } else if ($confidence === 'medium') {
                        $confnote = $tags->instructor_note ?? get_string('medium_confidence', 'local_savian_ai');
                        $confattrs = ['class' => 'badge badge-warning ml-2', 'title' => $confnote];
                        echo html_writer::tag('span', get_string('quality_tag_review', 'local_savian_ai'), $confattrs);
                    } else {
                        $confnote = $tags->instructor_note ?? get_string('low_confidence', 'local_savian_ai');
                        $confattrs = ['class' => 'badge badge-danger ml-2', 'title' => $confnote];
                        echo html_writer::tag('span', get_string('quality_tag_priority', 'local_savian_ai'), $confattrs);
                    }

                    // Supplemented content indicator.
                    if (isset($tags->supplemented_content) && $tags->supplemented_content) {
                        $suppattrs = [
                            'class' => 'badge badge-info ml-1',
                            'title' => get_string('supplemented_note', 'local_savian_ai'),
                        ];
                        echo html_writer::tag('span', get_string('quality_tag_supplemented', 'local_savian_ai'), $suppattrs);
                    }
                }

                echo html_writer::end_div();

                // View and Edit buttons.
                echo html_writer::start_div('btn-group btn-group-sm');
                $viewattrs = [
                    'class' => 'btn btn-outline-info',
                    'data-action' => 'view-item',
                    'data-section' => $idx,
                    'data-item' => $itemidx,
                    'title' => get_string('view_full_content', 'local_savian_ai'),
                ];
                echo html_writer::tag('button', '<i class="fa fa-eye"></i> ' . get_string('view_conversation', 'local_savian_ai'), $viewattrs);
                $editattrs = [
                    'class' => 'btn btn-outline-primary',
                    'data-action' => 'edit-item',
                    'data-section' => $idx,
                    'data-item' => $itemidx,
                    'title' => get_string('view_edit_before_adding', 'local_savian_ai'),
                ];
                echo html_writer::tag('button', '<i class="fa fa-edit"></i> ' . get_string('edit_item', 'local_savian_ai'), $editattrs);
                echo html_writer::end_div();

                echo html_writer::end_div();
            }

            echo html_writer::end_div();
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    // Action buttons.
    echo html_writer::start_div('text-center mt-4');

    $createurl = new moodle_url('/local/savian_ai/create_course.php', [
        'courseid' => $courseid,
        'action' => 'create',
        'sesskey' => sesskey(),
    ]);
    echo html_writer::link(
        $createurl,
        get_string('add_to_this_course', 'local_savian_ai'),
        ['class' => 'btn btn-savian btn-lg mr-2']
    );

    $regenurl = new moodle_url('/local/savian_ai/create_course.php', ['courseid' => $courseid]);
    echo html_writer::link(
        $regenurl,
        get_string('regenerate', 'local_savian_ai'),
        ['class' => 'btn btn-outline-secondary']
    );

    echo html_writer::end_div();

    // Store course structure in data attribute for JavaScript access.
    $structureattrs = [
        'id' => 'course-structure-data',
        'data-structure' => json_encode($structure),
        'style' => 'display:none;',
    ];
    echo html_writer::div('', '', $structureattrs);

    // Initialize view/edit functionality.
    $PAGE->requires->js_call_amd('local_savian_ai/course_content_editor', 'init');
} else if ($action === 'success' && !empty($saviancache->get('kb_save_data'))) {
    // Success page with Knowledge Feedback Loop option (v2.2).
    $savedata = $saviancache->get('kb_save_data');
    $results = $savedata['results'];

    // Build success message.
    $parts = [];
    if ($results['sections_created'] > 0) {
        $parts[] = $results['sections_created'] . ' ' . get_string('summary_sections', 'local_savian_ai');
    }
    if ($results['pages_created'] > 0) {
        $parts[] = $results['pages_created'] . ' ' . get_string('summary_pages', 'local_savian_ai');
    }
    if (isset($results['activities_created']) && $results['activities_created'] > 0) {
        $parts[] = $results['activities_created'] . ' ' . get_string('summary_activities', 'local_savian_ai');
    }
    if (isset($results['discussions_created']) && $results['discussions_created'] > 0) {
        $parts[] = $results['discussions_created'] . ' ' . get_string('summary_discussions', 'local_savian_ai');
    }
    if (isset($results['formative_created']) && $results['formative_created'] > 0) {
        $parts[] = $results['formative_created'] . ' ' . get_string('content_type_formative', 'local_savian_ai');
    }
    if ($results['quizzes_created'] > 0) {
        $parts[] = $results['quizzes_created'] . ' ' . get_string('summary_quizzes', 'local_savian_ai');
    }
    if ($results['assignments_created'] > 0) {
        $parts[] = $results['assignments_created'] . ' ' . get_string('summary_assignments', 'local_savian_ai');
    }

    echo html_writer::start_div('alert alert-success', ['style' => 'border-left: 4px solid #28a745;']);
    echo html_writer::tag('h4', '✅ ' . get_string('course_created_success', 'local_savian_ai'));
    echo html_writer::tag('p', get_string('course_imported_with', 'local_savian_ai', implode(', ', $parts)));
    echo html_writer::end_div();

    // Knowledge Feedback Loop Prompt.
    echo html_writer::start_div('card border-primary mt-4 mb-4');
    echo html_writer::start_div('card-body p-4');

    echo html_writer::tag('h5', '💡 ' . get_string('save_to_knowledge_base', 'local_savian_ai'), ['class' => 'card-title text-primary']);
    echo html_writer::tag('p', '<strong>' . get_string('build_knowledge_base', 'local_savian_ai') . '</strong>', ['class' => 'mb-3']);

    echo html_writer::tag('p', get_string('kb_benefits_intro', 'local_savian_ai'));
    echo html_writer::start_tag('ul', ['class' => 'mb-3']);
    echo html_writer::tag('li', '✓ ' . get_string('benefit_future_courses', 'local_savian_ai'));
    echo html_writer::tag('li', '✓ ' . get_string('benefit_student_chat', 'local_savian_ai'));
    echo html_writer::tag('li', '✓ ' . get_string('benefit_reduce_review', 'local_savian_ai'));
    echo html_writer::tag('li', '✓ ' . get_string('benefit_preserve_expertise', 'local_savian_ai'));
    echo html_writer::end_tag('ul');

    $kbnotice = '<small class="text-muted">'
        . get_string('kb_processing_note', 'local_savian_ai')
        . '</small>';
    echo html_writer::tag('p', $kbnotice);

    // Buttons.
    echo html_writer::start_div('mt-4');
    $kburl = new moodle_url('/local/savian_ai/save_to_knowledge_base.php', [
        'courseid' => $courseid,
        'sesskey' => sesskey(),
    ]);
    echo html_writer::link(
        $kburl,
        '<i class="fa fa-save mr-2"></i>' . get_string('save_to_knowledge_base', 'local_savian_ai'),
        ['class' => 'btn btn-primary btn-lg mr-2']
    );
    echo html_writer::link(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('skip_and_continue', 'local_savian_ai'),
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    // Show enhanced form with Moodle standards.
    $generatorintro = '<strong>' . get_string('ai_course_generator', 'local_savian_ai') . '</strong>'
        . ' - ' . get_string('ai_course_generator_desc', 'local_savian_ai');
    echo html_writer::div($generatorintro, 'alert alert-info mb-4');

    // Show only current course completed documents.
    $documents = $DB->get_records_menu(
        'local_savian_ai_documents',
        ['is_active' => 1, 'status' => 'completed', 'course_id' => $courseid],
        'title ASC',
        'savian_doc_id, title'
    );

    if (empty($documents)) {
        echo $OUTPUT->notification(get_string('no_documents_available', 'local_savian_ai'), 'warning');
    } else {
        echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        // FIELDSET 1: Basic Information.
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '📚 ' . get_string('basic_information', 'local_savian_ai'), ['class' => 'font-weight-bold text-primary']);

        // Display course name (not editable - use existing course).
        echo html_writer::start_div('alert alert-light border mb-3');
        echo html_writer::tag('strong', get_string('target_course', 'local_savian_ai') . ' ', ['class' => 'd-block mb-1']);
        echo html_writer::tag('div', '📖 ' . s($course->fullname), ['class' => 'h5 mb-0 text-primary']);
        echo html_writer::end_div();

        $desclabel = get_string('description_optional', 'local_savian_ai')
            . ' <span class="text-muted">(' . get_string('optional', 'local_savian_ai') . ')</span>';
        $desctextareaattrs = [
            'id' => 'course-desc',
            'name' => 'description',
            'class' => 'form-control',
            'rows' => 2,
            'placeholder' => get_string('description_placeholder', 'local_savian_ai'),
        ];
        $descfield = html_writer::tag('label', $desclabel, ['class' => 'font-weight-bold', 'for' => 'course-desc'])
            . html_writer::tag('textarea', '', $desctextareaattrs);
        echo html_writer::div($descfield, 'form-group');

        $contextlabel = get_string('additional_context', 'local_savian_ai')
            . ' <span class="text-muted">(' . get_string('optional', 'local_savian_ai') . ')</span>';
        $contexthelp = get_string('additional_context_help', 'local_savian_ai');
        $contextinputattrs = [
            'type' => 'text',
            'id' => 'target-aud',
            'name' => 'target_audience',
            'class' => 'form-control',
            'placeholder' => get_string('additional_context_placeholder', 'local_savian_ai'),
        ];
        $contextfield = html_writer::tag('label', $contextlabel, ['class' => 'font-weight-bold', 'for' => 'target-aud'])
            . html_writer::empty_tag('input', $contextinputattrs)
            . html_writer::tag('small', $contexthelp, ['class' => 'form-text text-muted']);
        echo html_writer::div($contextfield, 'form-group');

        echo html_writer::end_tag('fieldset');

        // FIELDSET 2: Learner Profile (ADDIE v2.0) - Improved Alignment.
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '👥 ' . get_string('learner_profile', 'local_savian_ai'), ['class' => 'font-weight-bold text-primary']);
        $profilehelp = '<small class="text-muted">'
            . get_string('learner_profile_help', 'local_savian_ai')
            . '</small>';
        echo html_writer::div($profilehelp, 'mb-3');

        echo html_writer::start_div('row');

        // Age Group.
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        $agegrouplabel = get_string('age_group', 'local_savian_ai') . ': <span class="text-danger">*</span>';
        echo html_writer::tag('label', $agegrouplabel, ['class' => 'font-weight-bold d-block', 'for' => 'age-group']);
        $ageoptions = [
            'k5' => get_string('age_group_elementary', 'local_savian_ai'),
            'middle' => get_string('age_group_middle', 'local_savian_ai'),
            'high' => get_string('age_group_high', 'local_savian_ai'),
            'undergrad' => get_string('age_group_undergrad', 'local_savian_ai'),
            'graduate' => get_string('age_group_graduate', 'local_savian_ai'),
            'professional' => get_string('age_group_professional', 'local_savian_ai'),
        ];
        echo html_writer::select(
            $ageoptions,
            'age_group',
            'undergrad',
            false,
            ['class' => 'form-control', 'id' => 'age-group', 'required' => true]
        );
        echo html_writer::tag(
            'small',
            get_string('age_group_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Industry.
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        $industrylabel = get_string('industry', 'local_savian_ai') . ': <span class="text-danger">*</span>';
        echo html_writer::tag('label', $industrylabel, ['class' => 'font-weight-bold d-block', 'for' => 'industry']);
        $industryoptions = [
            'general' => get_string('industry_general', 'local_savian_ai'),
            'k12' => get_string('industry_k12', 'local_savian_ai'),
            'higher_ed' => get_string('industry_higher_ed', 'local_savian_ai'),
            'healthcare' => get_string('industry_healthcare', 'local_savian_ai'),
            'technology' => get_string('industry_technology', 'local_savian_ai'),
            'business' => get_string('industry_business', 'local_savian_ai'),
            'corporate' => get_string('industry_corporate', 'local_savian_ai'),
        ];
        echo html_writer::select(
            $industryoptions,
            'industry',
            'general',
            false,
            ['class' => 'form-control', 'id' => 'industry', 'required' => true]
        );
        echo html_writer::tag(
            'small',
            get_string('industry_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Prior Knowledge.
        echo html_writer::start_div('col-md-4');
        echo html_writer::start_div('form-group');
        $priorlabel = get_string('prior_knowledge', 'local_savian_ai') . ':';
        echo html_writer::tag('label', $priorlabel, ['class' => 'font-weight-bold d-block', 'for' => 'prior-knowledge']);
        $prioroptions = [
            'beginner' => get_string('prior_knowledge_beginner', 'local_savian_ai'),
            'intermediate' => get_string('prior_knowledge_intermediate', 'local_savian_ai'),
            'advanced' => get_string('prior_knowledge_advanced', 'local_savian_ai'),
        ];
        echo html_writer::select(
            $prioroptions,
            'prior_knowledge_level',
            'beginner',
            false,
            ['class' => 'form-control', 'id' => 'prior-knowledge']
        );
        echo html_writer::tag(
            'small',
            get_string('prior_knowledge_help', 'local_savian_ai'),
            ['class' => 'form-text text-muted d-block mt-2']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::end_div(); // End row.
        echo html_writer::end_tag('fieldset');

        // FIELDSET 3: Source Documents and Duration.
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '📄 ' . get_string('source_docs_settings', 'local_savian_ai'), ['class' => 'font-weight-bold text-primary']);

        echo html_writer::start_div('row');

        // Documents (left side - 8 columns).
        echo html_writer::start_div('col-md-8');
        // Document selection with visual cards.
        echo html_writer::start_div('form-group');
        $doclabel = get_string('select_documents_required', 'local_savian_ai') . ' <span class="text-danger">*</span>';
        echo html_writer::tag('label', $doclabel, ['class' => 'font-weight-bold d-block mb-2']);
        $doccount = get_string('docs_available_count', 'local_savian_ai', count($documents));
        echo html_writer::tag('small', $doccount, ['class' => 'text-muted d-block mb-3']);

        if (count($documents) > 0) {
            // Add "Select All" / "Deselect All" buttons.
            echo html_writer::start_div('mb-2');
            $selectallattrs = [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-primary mr-2',
                'onclick' => 'document.querySelectorAll(".doc-checkbox").forEach(el => el.checked = true);',
            ];
            echo html_writer::tag('button', get_string('select_all', 'local_savian_ai'), $selectallattrs);
            $deselectallattrs = [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary',
                'onclick' => 'document.querySelectorAll(".doc-checkbox").forEach(el => el.checked = false);',
            ];
            echo html_writer::tag('button', get_string('deselect_all', 'local_savian_ai'), $deselectallattrs);
            echo html_writer::end_div();

            // Document cards in grid.
            echo html_writer::start_div('row');
            $count = 0;
            foreach ($documents as $docid => $doctitle) {
                $count++;
                echo html_writer::start_div('col-md-6 col-lg-4 mb-2');
                echo html_writer::start_div('card h-100');
                echo html_writer::start_div('card-body p-2');
                echo html_writer::start_div('custom-control custom-checkbox');
                $doccheckattrs = [
                    'type' => 'checkbox',
                    'name' => 'document_ids[]',
                    'value' => $docid,
                    'id' => 'doc_' . $docid,
                    'class' => 'custom-control-input doc-checkbox',
                    'checked' => $count <= 3 ? 'checked' : null,
                ];
                echo html_writer::empty_tag('input', $doccheckattrs);
                $doclabelattrs = [
                    'for' => 'doc_' . $docid,
                    'class' => 'custom-control-label font-weight-normal',
                ];
                echo html_writer::tag('label', s($doctitle), $doclabelattrs);
                echo html_writer::end_div();
                echo html_writer::end_div();
                echo html_writer::end_div();
                echo html_writer::end_div();
            }
            echo html_writer::end_div();
        } else {
            echo html_writer::div(get_string('no_documents_upload_first', 'local_savian_ai'), 'alert alert-warning');
        }

        echo html_writer::end_div();
        echo html_writer::end_div();

        // Duration (right side - 4 columns).
        echo html_writer::start_div('col-md-4');
        $durationlabel = html_writer::tag(
            'label',
            get_string('course_duration', 'local_savian_ai') . ': <span class="text-danger">*</span>',
            ['class' => 'font-weight-bold', 'for' => 'duration']
        );
        $durationoptions = array_combine(range(1, 12), range(1, 12));
        $durationselect = html_writer::select(
            $durationoptions,
            'duration_weeks',
            4,
            false,
            ['class' => 'form-control', 'id' => 'duration', 'required' => true]
        );
        $weeksappend = html_writer::div(
            html_writer::tag('span', get_string('weeks', 'local_savian_ai'), ['class' => 'input-group-text']),
            'input-group-append'
        );
        $durationinput = html_writer::start_div('input-group')
            . $durationselect . $weeksappend
            . html_writer::end_div();
        $durationhelp = html_writer::tag('small', get_string('course_duration_recommended', 'local_savian_ai'), ['class' => 'form-text text-muted']);
        echo html_writer::div($durationlabel . $durationinput . $durationhelp, 'form-group');
        echo html_writer::end_div();

        echo html_writer::end_div(); // End row.

        echo html_writer::end_tag('fieldset');

        // FIELDSET 4: Content Types.
        echo html_writer::start_tag('fieldset', ['class' => 'mb-4']);
        echo html_writer::tag('legend', '🎨 ' . get_string('content_types', 'local_savian_ai'), ['class' => 'font-weight-bold text-primary']);
        echo html_writer::div(
            '<small class="text-muted">' . get_string('content_types_note', 'local_savian_ai') . '</small>',
            'mb-3'
        );

        $contenttypesdef = [
            'sections' => [
                'label' => get_string('content_type_sections', 'local_savian_ai'),
                'desc' => get_string('content_type_sections_desc', 'local_savian_ai'),
                'default' => true,
                'required' => true,
            ],
            'pages' => [
                'label' => get_string('content_type_pages', 'local_savian_ai'),
                'desc' => get_string('content_type_pages_desc', 'local_savian_ai'),
                'default' => true,
                'required' => true,
            ],
            'activities' => [
                'label' => get_string('content_type_activities', 'local_savian_ai'),
                'desc' => get_string('content_type_activities_desc', 'local_savian_ai'),
                'default' => false,
            ],
            'discussions' => [
                'label' => get_string('content_type_discussions', 'local_savian_ai'),
                'desc' => get_string('content_type_discussions_desc', 'local_savian_ai'),
                'default' => false,
            ],
            'quiz_questions' => [
                'label' => get_string('content_type_quizzes', 'local_savian_ai'),
                'desc' => get_string('content_type_quizzes_desc', 'local_savian_ai'),
                'default' => true,
            ],
            'assignments' => [
                'label' => get_string('content_type_assignments', 'local_savian_ai'),
                'desc' => get_string('content_type_assignments_desc', 'local_savian_ai'),
                'default' => false,
            ],
        ];

        echo html_writer::start_div('row');
        foreach ($contenttypesdef as $type => $info) {
            $isrequired = $info['required'] ?? false;
            echo html_writer::start_div('col-md-6 mb-2');
            echo html_writer::start_div('card h-100' . ($isrequired ? ' border-primary' : ''));
            echo html_writer::start_div('card-body p-3');
            echo html_writer::start_div('custom-control custom-checkbox');
            $typecheckattrs = [
                'type' => 'checkbox',
                'name' => 'content_types[]',
                'value' => $type,
                'id' => 'content_' . $type,
                'class' => 'custom-control-input',
                'checked' => ($info['default'] ?? false) ? 'checked' : null,
                'disabled' => $isrequired ? 'disabled' : null,
            ];
            echo html_writer::empty_tag('input', $typecheckattrs);
            if ($isrequired) {
                $hiddenattrs = [
                    'type' => 'hidden',
                    'name' => 'content_types[]',
                    'value' => $type,
                ];
                echo html_writer::empty_tag('input', $hiddenattrs);
            }
            $typelabelattrs = [
                'for' => 'content_' . $type,
                'class' => 'custom-control-label font-weight-bold',
            ];
            echo html_writer::tag('label', $info['label'], $typelabelattrs);
            echo html_writer::tag('small', $info['desc'], ['class' => 'd-block text-muted mt-1']);
            echo html_writer::end_div(); // End custom-control.
            echo html_writer::end_div(); // End card-body.
            echo html_writer::end_div(); // End card.
            echo html_writer::end_div(); // End col.
        }
        echo html_writer::end_div(); // End row.

        echo html_writer::end_tag('fieldset');

        // Enhanced Submit Section.
        echo html_writer::start_div('card border-primary mt-5 mb-4');
        echo html_writer::start_div('card-body text-center p-4');

        // Title.
        echo html_writer::tag('h5', '🚀 ' . get_string('ready_to_generate', 'local_savian_ai'), ['class' => 'card-title mb-3']);

        // Description.
        echo html_writer::div(
            get_string('generate_submit_desc', 'local_savian_ai'),
            'text-muted mb-3'
        );

        // Estimated time with icon.
        echo html_writer::start_div('alert alert-info d-inline-block mb-3');
        echo '<i class="fa fa-clock-o"></i> <strong>' . get_string('estimated_time_generate', 'local_savian_ai')
            . '</strong> ' . get_string('estimated_time_generate_desc', 'local_savian_ai');
        echo html_writer::end_div();

        // Main generate button with gradient effect.
        $submitbutton = html_writer::tag(
            'button',
            '<i class="fa fa-magic mr-2"></i> ' . get_string('generate_button', 'local_savian_ai'),
            [
                'type' => 'submit',
                'class' => 'btn btn-savian btn-lg px-5 py-3',
                'style' => 'font-size: 1.2em; box-shadow: 0 4px 6px rgba(108, 59, 170, 0.3);',
            ]
        );
        echo html_writer::div($submitbutton, 'd-block mb-2');

        // Subtitle.
        echo html_writer::tag(
            'small',
            get_string('generate_submit_note', 'local_savian_ai'),
            ['class' => 'text-muted']
        );

        echo html_writer::end_div(); // End card-body.
        echo html_writer::end_div(); // End card.

        echo html_writer::end_tag('form');
    }
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/savian_ai/course.php', ['courseid' => $courseid]),
        '← ' . get_string('back_to_dashboard', 'local_savian_ai'),
        ['class' => 'btn btn-secondary mt-3']
    ),
    ''
);

// Coming Soon Features (Collapsible).
echo html_writer::start_div('mt-5 mb-4');
echo html_writer::start_tag('details', ['class' => 'border rounded p-3 bg-light']);
echo html_writer::tag(
    'summary',
    'Coming Soon: Exciting New Features',
    ['class' => 'font-weight-bold text-primary', 'style' => 'cursor: pointer;']
);
echo html_writer::start_div('mt-3');

echo html_writer::tag('h6', '📌 Insert Content Between Existing Topics', ['class' => 'text-primary']);
echo html_writer::tag('p', 'Generate content to insert between existing course sections:', ['class' => 'small']);
echo html_writer::start_tag('ul', ['class' => 'small mb-3']);
echo html_writer::tag('li', 'Already have Week 1? Add Week 2, 3, 4 incrementally');
echo html_writer::tag('li', 'Expand courses without recreating from scratch');
echo html_writer::tag('li', 'Perfect for building courses over time');
echo html_writer::end_tag('ul');

echo html_writer::end_div();
echo html_writer::end_tag('details');
echo html_writer::end_div();

// Footer.
echo local_savian_ai_render_footer();

echo $OUTPUT->footer();
