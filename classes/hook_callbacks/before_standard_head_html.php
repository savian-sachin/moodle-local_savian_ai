<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\hook_callbacks;

/**
 * Hook callback for injecting CSS and JS into page head
 *
 * @package    local_savian_ai
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_standard_head_html {

    /**
     * Callback to inject Savian AI CSS and JavaScript libraries
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function callback(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $CFG;

        // Inject CSS link tags
        $csslink1 = '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/savian_ai/styles/savian.css" />';
        $csslink2 = '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/savian_ai/styles/chat_widget.css" />';
        $csslink3 = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" />';

        // Add to hook
        $hook->add_html($csslink1 . "\n" . $csslink2 . "\n" . $csslink3);

        // Inject JavaScript libraries (these go in head for early loading)
        $jslink1 = '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>';
        $jslink2 = '<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>';

        $hook->add_html("\n" . $jslink1 . "\n" . $jslink2);
    }
}
