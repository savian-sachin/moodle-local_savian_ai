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
 * Before standard head HTML hook callback.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_savian_ai\hook_callbacks;

/**
 * Hook callback for injecting CSS into page head.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_standard_head_html {
    /**
     * Callback to inject Savian AI CSS and JavaScript libraries.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function callback(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $CFG, $PAGE;

        // Inject CSS link tags.
        $csslink1 = '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/savian_ai/styles/savian.css" />';
        $csslink2 = '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/savian_ai/styles/chat_widget.css" />';
        $highlightcssurl = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css';
        $csslink3 = '<link rel="stylesheet" href="' . $highlightcssurl . '" />';

        $hook->add_html($csslink1 . "\n" . $csslink2 . "\n" . $csslink3);

        // Load JavaScript libraries via Moodle's JS loading mechanism.
        $PAGE->requires->js(new \moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js'), true);
        $PAGE->requires->js(new \moodle_url('https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js'), true);
    }
}
