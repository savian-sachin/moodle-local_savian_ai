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
 * AMD module for Writing Practice: polling and grammar highlighting.
 *
 * @module     local_savian_ai/writing_practice
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * @constructor
     */
    var WritingPractice = function() {
        /** @type {object} Module configuration. */
        this.config = {};
        /** @type {number|null} Polling timer handle. */
        this.pollTimer = null;
        /** @type {boolean} Whether polling is active. */
        this.polling = false;
        /** @type {number} Timestamp (ms) when polling started. */
        this.startTime = 0;
        /** @type {number} Maximum polling duration in ms (5 minutes). */
        this.maxElapsed = 300000;
        /** @type {number[]} Backoff intervals in ms — ramps up to 10 s then holds. */
        this.intervals = [3000, 5000, 5000, 8000, 10000, 10000, 10000];
        /** @type {number} Current interval index. */
        this.intervalIndex = 0;
        /** @type {number} Consecutive AJAX error count. */
        this.errorCount = 0;
        /** @type {number} Max consecutive errors before giving up. */
        this.maxErrors = 5;
    };

    /**
     * Initialise the module.
     *
     * @param {object} cfg Configuration: {mode, submissionuuid, courseid, feedbackurl}.
     * @return {void}
     */
    WritingPractice.prototype.init = function(cfg) {
        this.config = cfg;
        if (cfg.mode === 'poll') {
            this.startTime = Date.now();
            this.polling = true;
            this.scheduleNext();
        } else if (cfg.mode === 'feedback') {
            this.initGrammarHighlighting();
        }
    };

    /**
     * Schedule the next poll, respecting the timeout.
     *
     * @return {void}
     */
    WritingPractice.prototype.scheduleNext = function() {
        if (!this.polling) {
            return;
        }

        var elapsed = Date.now() - this.startTime;
        if (elapsed >= this.maxElapsed) {
            this.stopPolling();
            var timeoutMsg = $('#savian-wp-timeout-msg').text();
            Notification.addNotification({message: timeoutMsg || 'Assessment timed out.', type: 'error'});
            return;
        }

        var idx = Math.min(this.intervalIndex, this.intervals.length - 1);
        var delay = this.intervals[idx];
        this.intervalIndex++;

        var self = this;
        this.pollTimer = setTimeout(function() {
            self.doPoll();
        }, delay);
    };

    /**
     * Perform a single AJAX poll.
     *
     * On transient error: increments errorCount and retries up to maxErrors times.
     * On terminal status (completed/failed): stops polling.
     *
     * @return {void}
     */
    WritingPractice.prototype.doPoll = function() {
        var self = this;

        Ajax.call([{
            methodname: 'local_savian_ai_get_submission_status',
            args: {
                submissionuuid: self.config.submissionuuid,
                courseid: self.config.courseid
            }
        }])[0].then(function(result) {
            // Reset error counter on success.
            self.errorCount = 0;

            self.updateProgress(result.progress, result.stage);

            if (result.status === 'completed') {
                self.stopPolling();
                window.location.href = self.config.feedbackurl;
                return result;
            }

            if (result.status === 'failed') {
                self.stopPolling();
                Notification.addNotification({
                    message: result.stage || 'Assessment failed. Please try again.',
                    type: 'error'
                });
                return result;
            }

            // Still pending/processing — schedule next poll.
            self.scheduleNext();
            return result;
        }).catch(function() {
            self.errorCount++;
            if (self.errorCount >= self.maxErrors) {
                self.stopPolling();
                Notification.addNotification({
                    message: 'Lost connection to server. Please refresh the page.',
                    type: 'error'
                });
                return;
            }
            // Transient error — keep polling, show a subtle status update.
            $('#savian-wp-stage-label').text('Checking... (' + self.errorCount + ' retries)');
            self.scheduleNext();
        });
    };

    /**
     * Update the progress bar and stage label.
     *
     * @param {number} progress Progress value 0-100.
     * @param {string} stage Human-readable stage description.
     * @return {void}
     */
    WritingPractice.prototype.updateProgress = function(progress, stage) {
        var bar = $('#savian-wp-progress-bar');
        bar.css('width', progress + '%');
        bar.attr('aria-valuenow', progress);
        bar.text(progress + '%');

        if (stage) {
            $('#savian-wp-stage-label').text(stage);
        }
    };

    /**
     * Stop polling and clear the timer.
     *
     * @return {void}
     */
    WritingPractice.prototype.stopPolling = function() {
        this.polling = false;
        if (this.pollTimer !== null) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    };

    /**
     * Annotate submission text with grammar error highlights using safe DOM methods.
     *
     * Reads errors JSON from #savian-wp-errors data attribute, builds a
     * DocumentFragment with <mark> spans at the correct offsets, and replaces
     * the text node — no innerHTML is used.
     *
     * @return {void}
     */
    WritingPractice.prototype.initGrammarHighlighting = function() {
        var errorsEl = document.getElementById('savian-wp-errors');
        var textEl = document.getElementById('savian-wp-submission-text');
        if (!errorsEl || !textEl) {
            return;
        }

        var errors;
        try {
            errors = JSON.parse(errorsEl.getAttribute('data-errors') || '[]');
        } catch (e) {
            return;
        }

        if (!errors.length) {
            return;
        }

        var text = textEl.textContent || '';

        // Sort errors by offset ascending.
        errors = errors.slice().sort(function(a, b) {
            return (a.offset || 0) - (b.offset || 0);
        });

        var fragment = document.createDocumentFragment();
        var cursor = 0;

        errors.forEach(function(err) {
            var offset = parseInt(err.offset, 10) || 0;
            var length = parseInt(err.length, 10) || 1;

            // Text before this error.
            if (offset > cursor) {
                fragment.appendChild(document.createTextNode(text.slice(cursor, offset)));
            }

            // Build the <mark> using DOM to avoid XSS.
            var mark = document.createElement('mark');
            var cls = 'savian-wp-error-mark';
            if ((err.type || '').toLowerCase() === 'spelling') {
                cls += ' spelling';
            }
            mark.className = cls;
            var replacements = (err.replacements || []).join(', ');
            var tipText = (err.message || '') + (replacements ? ' \u2192 ' + replacements : '');
            mark.setAttribute('title', tipText);
            mark.textContent = text.slice(offset, offset + length);
            fragment.appendChild(mark);

            cursor = offset + length;
        });

        // Any remaining text after the last error.
        if (cursor < text.length) {
            fragment.appendChild(document.createTextNode(text.slice(cursor)));
        }

        // Replace children with the annotated fragment.
        while (textEl.firstChild) {
            textEl.removeChild(textEl.firstChild);
        }
        textEl.appendChild(fragment);
    };

    return {
        /**
         * Module entry point called by PHP.
         *
         * @param {object} cfg Configuration object.
         * @return {void}
         */
        init: function(cfg) {
            var widget = new WritingPractice();
            widget.init(cfg);
        }
    };
});
