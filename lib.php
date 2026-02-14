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
 * Library functions for Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * DEPRECATED: Inject Savian branding CSS and third-party libraries into pages.
 *
 * This function is deprecated and kept for backward compatibility.
 * The new hook system is used in classes/hook_callbacks/before_standard_head_html.php
 *
 * @deprecated since Moodle 4.5
 */
function local_savian_ai_before_standard_html_head() {
    // This function is deprecated - functionality moved to hook callbacks.
    // See: classes/hook_callbacks/before_standard_head_html.php.
    // Keeping empty function to avoid errors during transition.
}

/**
 * Render consistent Savian AI page header.
 *
 * @param string $title Page title.
 * @param string $subtitle Optional subtitle.
 * @return string HTML.
 */
function local_savian_ai_render_header($title, $subtitle = '') {
    $html = '';

    // Header bar with logo and title.
    $html .= html_writer::start_div('card mb-4');
    $html .= html_writer::start_div('card-body p-3');
    $html .= html_writer::start_div('d-flex justify-content-between align-items-center');

    // Title on left.
    $html .= html_writer::start_div('');
    $html .= html_writer::tag('h2', $title, ['class' => 'mb-0 h4']);
    if ($subtitle) {
        $html .= html_writer::tag('small', $subtitle, ['class' => 'text-muted']);
    }
    $html .= html_writer::end_div();

    // Savian logo on right.
    $html .= html_writer::tag(
        'div',
        get_string('header_brand', 'local_savian_ai'),
        ['class' => 'savian-text-primary font-weight-bold']
    );

    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Render consistent Savian AI footer.
 *
 * @return string HTML.
 */
function local_savian_ai_render_footer() {
    $link = html_writer::link(
        'https://savian.ai.vn/',
        get_string('pluginname', 'local_savian_ai'),
        [
            'target' => '_blank',
            'class' => 'savian-text-primary',
        ]
    );
    return html_writer::div(
        html_writer::tag(
            'small',
            get_string('powered_by', 'local_savian_ai', $link),
            ['class' => 'text-muted']
        ),
        'text-center mt-4 mb-3'
    );
}

/**
 * Add navigation nodes.
 *
 * @param global_navigation $navigation Navigation object.
 */
function local_savian_ai_extend_navigation(global_navigation $navigation) {
    // Only add to navigation if user has permission.
    if (has_capability('local/savian_ai:use', context_system::instance())) {
        $node = $navigation->add(
            get_string('pluginname', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_savian_ai',
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;

        // Add tutorials link.
        $node->add(
            get_string('tutorials', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/tutorials.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'savian_tutorials',
            new pix_icon('t/help', '')
        );
    }
}

/**
 * Extend course navigation.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass $course The course to add nodes for.
 * @param context $context The course context.
 */
function local_savian_ai_extend_navigation_course($navigation, $course, $context) {
    // Dashboard and features are for teachers only (require 'generate' capability).
    if (has_capability('local/savian_ai:generate', $context)) {
        // Add dashboard link.
        $navigation->add(
            get_string('pluginname', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/course.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'local_savian_ai',
            new pix_icon('i/report', '')
        );

        // Add chat history link.
        $navigation->add(
            get_string('chat_history', 'local_savian_ai'),
            new moodle_url('/local/savian_ai/chat_history.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'local_savian_ai_chat_history',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * DEPRECATED: Add chat widget to course pages.
 *
 * This function is deprecated and kept for backward compatibility.
 * The new hook system is used in classes/hook_callbacks/before_footer_html.php
 *
 * @deprecated since Moodle 4.5
 */
function local_savian_ai_before_footer() {
    // This function is deprecated - functionality moved to hook callbacks.
    // See: classes/hook_callbacks/before_footer_html.php.
    // Keeping empty function to avoid errors during transition.
}

/**
 * Callback function when organization code setting is updated.
 *
 * This is called by Moodle's admin settings system after the org_code is saved.
 * It clears all documents since they belong to the previous organization.
 */
function local_savian_ai_org_code_updated() {
    global $DB;

    // Get the new org code (already saved).
    $neworgcode = get_config('local_savian_ai', 'org_code');

    // Get the previous org code from cache (stored when settings page loaded).
    $cache = \cache::make('local_savian_ai', 'session_data');
    $previousorgcode = $cache->get('previous_org_code');

    // If no cache value, try the stored config value.
    if (empty($previousorgcode)) {
        $previousorgcode = get_config('local_savian_ai', 'previous_org_code');
    }

    // If org code has changed and there was a previous value.
    if (!empty($previousorgcode) && $previousorgcode !== $neworgcode) {
        // Count and delete all documents.
        $count = $DB->count_records('local_savian_ai_documents');

        if ($count > 0) {
            // Delete all documents.
            $DB->delete_records('local_savian_ai_documents');

            // Show admin notification.
            \core\notification::warning(
                get_string('org_code_changed_documents_cleared', 'local_savian_ai', $count)
            );
        }

        // Sync new documents from API immediately.
        local_savian_ai_sync_documents();
    }

    // Store the current org code for future comparison.
    set_config('previous_org_code', $neworgcode, 'local_savian_ai');

    // Clear cache.
    $cache->delete('previous_org_code');
}

/**
 * Sync documents from API to local database.
 *
 * @return int Number of documents synced.
 */
function local_savian_ai_sync_documents() {
    global $DB;

    $client = new \local_savian_ai\api\client();
    $syncresponse = $client->get_documents(['per_page' => 100]);
    $synced = 0;

    if ($syncresponse->http_code === 200 && isset($syncresponse->documents)) {
        foreach ($syncresponse->documents as $doc) {
            $existing = $DB->get_record('local_savian_ai_documents', ['savian_doc_id' => $doc->id]);

            $record = new stdClass();
            $record->savian_doc_id = $doc->id;
            $record->title = $doc->title;
            $record->description = $doc->description ?? '';
            $record->subject_area = $doc->subject_area ?? '';
            $record->status = $doc->processing_status;
            $record->progress = $doc->processing_progress ?? 0;
            $record->chunk_count = $doc->chunk_count ?? 0;
            $record->qna_count = $doc->qna_count ?? 0;
            $record->file_size = $doc->file_size ?? 0;
            $record->file_type = $doc->source_file_type ?? '';
            $record->tags = json_encode($doc->tags ?? []);
            $record->is_active = $doc->is_active ? 1 : 0;
            $record->last_synced = time();
            $record->timemodified = time();

            $apicourseid = $doc->moodle_course_id ?? $doc->course_id ?? null;

            if ($existing) {
                $record->id = $existing->id;
                $record->course_id = $existing->course_id ?: $apicourseid;
                $record->timecreated = $existing->timecreated;
                $record->usermodified = $existing->usermodified;
                $DB->update_record('local_savian_ai_documents', $record);
            } else {
                $record->course_id = $apicourseid;
                $record->timecreated = time();
                $record->usermodified = 0;
                $DB->insert_record('local_savian_ai_documents', $record);
            }
            $synced++;
        }
    }

    return $synced;
}
