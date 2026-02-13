// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Savian AI Tutorial Search Filter
 *
 * @module     local_savian_ai/tutorial_search
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    return {
        init: function() {
            $('#tutorial-search').on('input', function() {
                var query = $(this).val().toLowerCase();
                $('.tutorial-card').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(query) > -1);
                });
            });
        }
    };
});
