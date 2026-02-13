// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course content editor - View and Edit functionality for generated content
 *
 * @module     local_savian_ai/course_content_editor
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal', 'core/modal_save_cancel', 'core/modal_events', 'core/ajax', 'core/notification'],
function($, Modal, ModalSaveCancel, ModalEvents, Ajax, Notification) {

    var courseStructure = null;

    return {
        /**
         * Initialize the editor
         * Reads course structure from data attribute
         */
        init: function() {
            // Read structure from data attribute
            var $dataEl = $('#course-structure-data');
            if ($dataEl.length === 0) {
                return; // No structure data available
            }

            try {
                // Use attr() to get raw JSON string, not data() which auto-parses
                var structureData = $dataEl.attr('data-structure');
                if (!structureData) {
                    // eslint-disable-next-line no-console
                    console.error('No course structure data found');
                    return;
                }

                // Check if already parsed (jQuery might auto-parse)
                if (typeof structureData === 'string') {
                    courseStructure = JSON.parse(structureData);
                } else {
                    // Already an object
                    courseStructure = structureData;
                }
            } catch (e) {
                // eslint-disable-next-line no-console
                console.error('Failed to parse course structure:', e);
                return;
            }

            // View button click handler
            $(document).on('click', '[data-action="view-item"]', function() {
                var sectionIdx = $(this).data('section');
                var itemIdx = $(this).data('item');
                showViewModal(sectionIdx, itemIdx);
            });

            // Edit button click handler
            $(document).on('click', '[data-action="edit-item"]', function() {
                var sectionIdx = $(this).data('section');
                var itemIdx = $(this).data('item');
                showEditModal(sectionIdx, itemIdx);
            });

            // Expand/collapse all
            $('#expand-all').on('click', function() {
                $('.collapse').collapse('show');
                $(this).blur();
            });

            $('#collapse-all').on('click', function() {
                $('.collapse').collapse('hide');
                $(this).blur();
            });
        }
    };

    /**
     * Show view modal (read-only)
     *
     * @param {number} sectionIdx The section index.
     * @param {number} itemIdx The item index.
     */
    function showViewModal(sectionIdx, itemIdx) {
        var item = getItem(sectionIdx, itemIdx);
        if (!item) {
            return;
        }

        var content = getItemContent(item);
        var icon = getContentIcon(item.type);

        // Default title if item doesn't have one
        var defaultTitles = {
            'formative': 'Self-Check Questions',
            'page': 'Content Page',
            'activity': 'Learning Activity',
            'discussion': 'Discussion Topic',
            'quiz': 'Section Quiz',
            'assignment': 'Assignment'
        };
        var displayTitle = item.title || defaultTitles[item.type] || 'Content';

        return Modal.create({
            title: icon + ' ' + displayTitle,
            body: '<div class="savian-content-preview p-3">' + content + '</div>',
            large: true
        }).then(function(modal) {
            modal.show();
            return modal;
        }).catch(Notification.exception);
    }

    /**
     * Show edit modal
     *
     * @param {number} sectionIdx The section index.
     * @param {number} itemIdx The item index.
     */
    function showEditModal(sectionIdx, itemIdx) {
        var item = getItem(sectionIdx, itemIdx);
        if (!item) {
            return;
        }

        var content = getItemContent(item);

        // Default title if item doesn't have one
        var defaultTitles = {
            'formative': 'Self-Check Questions',
            'page': 'Content Page',
            'activity': 'Learning Activity',
            'discussion': 'Discussion Topic',
            'quiz': 'Section Quiz',
            'assignment': 'Assignment'
        };
        var displayTitle = item.title || defaultTitles[item.type] || 'Content';

        var bodyHtml = `
            <div class="form-group">
                <label for="edit-title" class="font-weight-bold">Title:</label>
                <input type="text" class="form-control" id="edit-title" value="${escapeHtml(displayTitle)}">
            </div>
            <div class="form-group">
                <label for="edit-content" class="font-weight-bold">Content:</label>
                <textarea class="form-control" id="edit-content" rows="15">${escapeHtml(content)}</textarea>
                <small class="form-text text-muted">You can edit the content before adding to the course.</small>
            </div>
        `;

        return ModalSaveCancel.create({
            title: '<i class="fa fa-edit"></i> Edit: ' + displayTitle,
            body: bodyHtml,
            large: true
        }).then(function(modal) {
            modal.show();

            // Handle save
            modal.getRoot().on(ModalEvents.save, function() {
                saveItemEdits(sectionIdx, itemIdx, modal);
            });

            return modal;
        }).catch(Notification.exception);
    }

    /**
     * Save edited item to session
     *
     * @param {number} sectionIdx The section index.
     * @param {number} itemIdx The item index.
     * @param {Object} modal The modal instance.
     */
    function saveItemEdits(sectionIdx, itemIdx, modal) {
        var newTitle = $('#edit-title').val();
        var newContent = $('#edit-content').val();

        // Update local structure
        var item = getItem(sectionIdx, itemIdx);
        if (item) {
            item.title = newTitle;

            // Update content based on item type
            if (item.type === 'page') {
                item.content = newContent;
            } else if (item.type === 'activity') {
                item.instructions = newContent;
            } else if (item.type === 'discussion') {
                item.prompt = newContent;
            } else if (item.type === 'formative') {
                // For formative, store as plain text (questions are in structured format)
                item.content = newContent;
            } else if (item.type === 'quiz') {
                item.description = newContent;
            } else if (item.type === 'assignment') {
                item.instructions = newContent;
            }

            // Save to session via AJAX
            Ajax.call([{
                methodname: 'local_savian_ai_save_course_structure',
                args: {
                    structure: JSON.stringify(courseStructure)
                }
            }])[0].done(function() {
                // Update preview display
                updatePreviewItem(sectionIdx, itemIdx, newTitle);
                Notification.addNotification({
                    message: 'Changes saved successfully',
                    type: 'success'
                });
                modal.hide();
            }).fail(function(error) {
                Notification.exception(error);
            });
        }
    }

    /**
     * Update preview display after edit
     *
     * @param {number} sectionIdx The section index.
     * @param {number} itemIdx The item index.
     * @param {string} newTitle The new title to display.
     */
    function updatePreviewItem(sectionIdx, itemIdx, newTitle) {
        var selector = '[data-action="view-item"][data-section="' + sectionIdx + '"][data-item="' + itemIdx + '"]';
        var $viewBtn = $(selector);

        // Find the title span (sibling of the view button)
        var $titleSpan = $viewBtn.closest('.list-group-item').find('span').first();
        $titleSpan.text(newTitle);
    }

    /**
     * Get item from structure
     *
     * @param {number} sectionIdx The section index.
     * @param {number} itemIdx The item index.
     * @returns {Object|null} The item or null.
     */
    function getItem(sectionIdx, itemIdx) {
        if (courseStructure && courseStructure.sections && courseStructure.sections[sectionIdx]) {
            var section = courseStructure.sections[sectionIdx];
            if (section.content && section.content[itemIdx]) {
                return section.content[itemIdx];
            }
        }
        return null;
    }

    /**
     * Get item content based on type
     *
     * @param {Object} item The content item.
     * @returns {string} The formatted content.
     */
    function getItemContent(item) {
        switch (item.type) {
            case 'page':
                return item.content || '';
            case 'activity':
                return item.instructions || item.content || '';
            case 'discussion':
                return item.prompt || item.content || '';
            case 'formative': // ADDIE v2.0
                return formatFormativeContent(item);
            case 'quiz':
                return formatQuizContent(item);
            case 'assignment':
                return formatAssignmentContent(item);
            default:
                return item.content || '';
        }
    }

    /**
     * Format formative assessment content (ADDIE v2.0)
     *
     * @param {Object} formative The formative assessment item.
     * @returns {string} The formatted HTML.
     */
    function formatFormativeContent(formative) {
        var html = '<div class="alert alert-info">';
        html += '<h5>✓ Knowledge Check (Ungraded)</h5>';

        // Show content if available (for edited items)
        if (formative.content) {
            html += '<div>' + formative.content + '</div>';
        }

        // Show structured questions
        if (formative.questions && formative.questions.length > 0) {
            html += '<p><strong>Self-assessment questions:</strong></p>';
            formative.questions.forEach(function(q, idx) {
                html += '<div class="mb-3 p-2 bg-light rounded">';
                html += '<p><strong>Q' + (idx + 1) + ':</strong> ' + escapeHtml(q.question) + '</p>';
                html += '<details><summary class="btn btn-sm btn-outline-primary">Show Answer</summary>';
                html += '<div class="alert alert-success mt-2 mb-0">' + escapeHtml(q.answer) + '</div>';
                html += '</details></div>';
            });
        }

        html += '</div>';
        return html;
    }

    /**
     * Format quiz content for display
     *
     * @param {Object} quiz The quiz item.
     * @returns {string} The formatted HTML.
     */
    function formatQuizContent(quiz) {
        var html = '<p>' + (quiz.description || '') + '</p>';

        if (quiz.questions && quiz.questions.length > 0) {
            html += '<h5>Questions (' + quiz.questions.length + '):</h5>';
            html += '<ol>';
            quiz.questions.forEach(function(q, _idx) {
                html += '<li><strong>' + escapeHtml(q.question_text || q.text) + '</strong>';
                if (q.answers && q.answers.length > 0) {
                    html += '<ul class="mt-2">';
                    q.answers.forEach(function(a) {
                        var correct = a.correct || a.fraction === 1 ? ' ✓ <em>(Correct)</em>' : '';
                        html += '<li>' + escapeHtml(a.text) + correct + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</li>';
            });
            html += '</ol>';
        }

        return html;
    }

    /**
     * Format assignment content for display
     *
     * @param {Object} assignment The assignment item.
     * @returns {string} The formatted HTML.
     */
    function formatAssignmentContent(assignment) {
        var html = '<div>';
        html += '<h5>Instructions:</h5>';
        html += '<p>' + (assignment.instructions || assignment.content || '') + '</p>';

        if (assignment.rubric && assignment.rubric.criteria) {
            html += '<h5 class="mt-3">📋 Grading Rubric:</h5>';
            html += '<table class="table table-bordered table-sm">';
            html += '<thead class="thead-light"><tr><th>Criterion</th>' +
                    '<th width="80">Points</th><th>Performance Levels</th></tr></thead>';
            html += '<tbody>';
            assignment.rubric.criteria.forEach(function(criterion) {
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(criterion.name) + '</strong></td>';
                html += '<td class="text-center">' + (criterion.points || '-') + '</td>';
                html += '<td>';

                // ADDIE v2.0: Show levels if available
                if (criterion.levels && criterion.levels.length > 0) {
                    html += '<ul class="list-unstyled mb-0">';
                    criterion.levels.forEach(function(level) {
                        html += '<li class="mb-2">';
                        html += '<span class="badge badge-secondary">' + level.score + ' pts</span> ';
                        html += escapeHtml(level.description);
                        html += '</li>';
                    });
                    html += '</ul>';
                } else {
                    // Fallback to simple description
                    html += escapeHtml(criterion.description || '');
                }

                html += '</td></tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr><td colspan="2"><strong>Total Points</strong></td><td class="text-center"><strong>' +
                    (assignment.rubric.total_points || 100) + '</strong></td></tr></tfoot>';
            html += '</table>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Get content icon
     *
     * @param {string} type The content type.
     * @returns {string} The icon string.
     */
    function getContentIcon(type) {
        var icons = {
            'page': '📄',
            'activity': '🎯',
            'discussion': '💬',
            'quiz': '❓',
            'assignment': '📝'
        };
        return icons[type] || '📌';
    }

    /**
     * Escape HTML
     *
     * @param {string} text The text to escape.
     * @returns {string} The escaped HTML string.
     */
    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
