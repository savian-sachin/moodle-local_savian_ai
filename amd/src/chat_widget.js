// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Savian AI Floating Chat Widget
 *
 * @module     local_savian_ai/chat_widget
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* globals MathJax */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    var ChatWidget = function() {
        this.isMinimized = true;
        this.isMaximized = false;
        this.position = 'bottom-right';
        this.conversationId = null;
        this.courseId = null;
        this.config = {};
        this.strings = {};
    };

    ChatWidget.prototype.init = function(config) {
        this.config = config || {};

        // Extract course ID from body class
        var bodyClass = $('body').attr('class');
        var courseMatch = bodyClass.match(/course-(\d+)/);
        if (courseMatch) {
            this.courseId = parseInt(courseMatch[1]);
        }

        // Load strings
        var self = this;
        Str.get_strings([
            {key: 'openchat', component: 'local_savian_ai'},
            {key: 'minimize', component: 'local_savian_ai'},
            {key: 'maximize', component: 'local_savian_ai'},
            {key: 'newconversation', component: 'local_savian_ai'},
            {key: 'history', component: 'local_savian_ai'},
            {key: 'typemessage', component: 'local_savian_ai'},
            {key: 'send', component: 'local_savian_ai'},
            {key: 'helpful', component: 'local_savian_ai'},
            {key: 'nothelpful', component: 'local_savian_ai'}
        ]).done(function(strings) {
            self.strings = {
                openchat: strings[0],
                minimize: strings[1],
                maximize: strings[2],
                newconversation: strings[3],
                history: strings[4],
                typemessage: strings[5],
                send: strings[6],
                helpful: strings[7],
                nothelpful: strings[8]
            };

            self.loadUserSettings();

            // Check if chat is restricted
            if (self.config.restriction && self.config.restriction.isRestricted) {
                self.renderRestrictedState();
            } else {
                self.render();
                self.attachEvents();

                // Auto-open if URL param present
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('openchat') === '1') {
                    self.maximize();
                }
            }
        });
    };

    ChatWidget.prototype.loadUserSettings = function() {
        // Load from localStorage
        var settings = localStorage.getItem('savian_chat_widget_settings');
        if (settings) {
            settings = JSON.parse(settings);
            this.position = settings.position || this.config.defaultPosition || 'bottom-right';
            this.isMinimized = settings.minimized !== undefined ? settings.minimized : true;
        } else {
            this.position = this.config.userPosition || this.config.defaultPosition || 'bottom-right';
            this.isMinimized = this.config.userMinimized !== undefined ? this.config.userMinimized : true;
        }
    };

    ChatWidget.prototype.render = function() {
        var positionClass = 'savian-widget-' + this.position;
        var minimizedClass = this.isMinimized ? 'minimized' : 'maximized';
        var welcomeMsg = this.config.welcomeMessage || 'Hi! I\'m your AI assistant. Ask me anything about your course materials.';

        var html = `
            <div id="savian-chat-widget" class="savian-chat-widget ${positionClass} ${minimizedClass}">
                <!-- Minimized bubble -->
                <div class="savian-chat-bubble" role="button" tabindex="0" aria-label="${this.strings.openchat}">
                    <i class="fa fa-comments"></i>
                    <span class="notification-badge hidden">0</span>
                </div>

                <!-- Maximized chat window -->
                <div class="savian-chat-window" role="dialog" aria-label="Savian AI Chat">
                    <div class="savian-chat-header">
                        <div class="savian-chat-title">
                            <i class="fa fa-graduation-cap"></i>
                            <span>Savian AI Tutor</span>
                        </div>
                        <div class="savian-chat-actions">
                            <button class="savian-btn-icon" id="savian-chat-new" title="${this.strings.newconversation}">
                                <i class="fa fa-plus"></i>
                            </button>
                            ${this.config.canViewHistory ?
                            '<button class="savian-btn-icon" id="savian-chat-history" title="' +
                            this.strings.history + '"><i class="fa fa-history"></i></button>' : ''}
                            <button class="savian-btn-icon" id="savian-chat-fullscreen" title="${this.strings.maximize}">
                                <i class="fa fa-expand"></i>
                            </button>
                            <button class="savian-btn-icon" id="savian-chat-minimize" title="${this.strings.minimize}">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="savian-chat-body">
                        <div class="savian-chat-messages" id="savian-chat-messages">
                            <!-- Welcome message -->
                            <div class="savian-chat-message assistant">
                                <div class="savian-message-content">${welcomeMsg}</div>
                            </div>
                        </div>

                        <!-- Document selector - HIDDEN (auto-uses course documents) -->
                    </div>

                    <div class="savian-chat-footer">
                        <div class="savian-chat-input-wrapper">
                            <textarea
                                id="savian-chat-input"
                                class="savian-chat-input"
                                placeholder="${this.strings.typemessage}"
                                rows="1"
                                aria-label="Chat message input"
                            ></textarea>
                            <button id="savian-chat-send" class="savian-btn-send" aria-label="${this.strings.send}">
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="savian-chat-status" id="savian-chat-status"></div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(html);

        // Load course documents if teacher
        if (this.config.canManageDocuments && this.courseId) {
            this.loadCourseDocuments();
        }
    };

    /**
     * Render the restricted state widget with countdown
     */
    ChatWidget.prototype.renderRestrictedState = function() {
        var restriction = this.config.restriction;
        var resumesAt = restriction.resumesAt ? restriction.resumesAt * 1000 : 0; // Convert to milliseconds
        var positionClass = 'savian-widget-' + this.position;

        var countdownHtml = '';
        if (resumesAt > 0) {
            countdownHtml = `
                <div class="restriction-countdown">
                    <p class="countdown-label">Resumes in:</p>
                    <div class="countdown-timer">
                        <div class="countdown-unit">
                            <span class="countdown-value" id="countdown-hours">--</span>
                            <span class="countdown-text">hrs</span>
                        </div>
                        <div class="countdown-unit">
                            <span class="countdown-value" id="countdown-minutes">--</span>
                            <span class="countdown-text">min</span>
                        </div>
                        <div class="countdown-unit">
                            <span class="countdown-value" id="countdown-seconds">--</span>
                            <span class="countdown-text">sec</span>
                        </div>
                    </div>
                </div>
            `;
        } else {
            countdownHtml = '<p class="restriction-no-end">Check back later</p>';
        }

        var html = `
            <div id="savian-chat-widget" class="savian-chat-widget ${positionClass} restricted">
                <!-- Restricted bubble with overlay -->
                <div class="savian-chat-bubble restricted" role="button" tabindex="0" aria-label="Chat unavailable">
                    <i class="fa fa-comments"></i>
                    <span class="restriction-icon-overlay"><i class="fa fa-ban"></i></span>
                </div>

                <!-- Restricted message window -->
                <div class="savian-chat-window restricted-window" role="dialog" aria-label="Chat Restricted">
                    <div class="savian-chat-header restricted-header">
                        <div class="savian-chat-title">
                            <i class="fa fa-clock-o"></i>
                            <span>Chat Temporarily Unavailable</span>
                        </div>
                        <div class="savian-chat-actions">
                            <button class="savian-btn-icon" id="savian-chat-minimize" title="Minimize">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="savian-chat-body restriction-body">
                        <div class="restriction-icon-large">
                            <i class="fa fa-graduation-cap fa-4x"></i>
                        </div>
                        <p class="restriction-message">${restriction.message}</p>
                        ${countdownHtml}
                    </div>
                </div>
            </div>
        `;

        $('body').append(html);

        // Attach events for restricted state
        this.attachRestrictedEvents();

        // Start countdown if there's an end time
        if (resumesAt > 0) {
            this.startCountdown(resumesAt);
        }
    };

    /**
     * Attach events for restricted state widget
     */
    ChatWidget.prototype.attachRestrictedEvents = function() {
        // Toggle on bubble click
        $(document).on('click', '#savian-chat-widget.restricted .savian-chat-bubble', function() {
            $('#savian-chat-widget').toggleClass('minimized').toggleClass('maximized');
        });

        // Minimize button
        $(document).on('click', '#savian-chat-widget.restricted #savian-chat-minimize', function(e) {
            e.stopPropagation();
            $('#savian-chat-widget').addClass('minimized').removeClass('maximized');
        });
    };

    /**
     * Start countdown timer
     * @param {number} targetTime - The target timestamp in milliseconds
     */
    ChatWidget.prototype.startCountdown = function(targetTime) {
        var updateCountdown = function() {
            var now = Date.now();
            var remaining = targetTime - now;

            if (remaining <= 0) {
                // Restriction ended - reload page to get fresh state
                window.location.reload();
                return;
            }

            var hours = Math.floor(remaining / (1000 * 60 * 60));
            var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((remaining % (1000 * 60)) / 1000);

            $('#countdown-hours').text(hours.toString().padStart(2, '0'));
            $('#countdown-minutes').text(minutes.toString().padStart(2, '0'));
            $('#countdown-seconds').text(seconds.toString().padStart(2, '0'));
        };

        // Initial update
        updateCountdown();

        // Update every second
        setInterval(updateCountdown, 1000);
    };

    ChatWidget.prototype.renderDocumentSelector = function() {
        return `
            <div class="savian-document-selector" id="savian-document-selector">
                <label>
                    <i class="fa fa-file-text"></i>
                    Select documents for context
                </label>
                <select multiple class="form-control form-control-sm" id="savian-doc-select" size="3">
                    <option value="">Loading...</option>
                </select>
            </div>
        `;
    };

    ChatWidget.prototype.loadCourseDocuments = function() {
        Ajax.call([{
            methodname: 'local_savian_ai_get_course_documents',
            args: {courseid: this.courseId}
        }])[0].done(function(response) {
            if (response.success && response.documents && response.documents.length > 0) {
                var html = '<option value="">Select documents (optional)</option>';
                response.documents.forEach(function(doc) {
                    html += '<option value="' + doc.id + '">' + doc.title + '</option>';
                });
                $('#savian-doc-select').html(html);
            } else {
                $('#savian-doc-select').html('<option value="">No documents available</option>');
            }
        }).fail(function(error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load documents:', error);
            $('#savian-doc-select').html('<option value="">Failed to load documents</option>');
        });
    };

    ChatWidget.prototype.attachEvents = function() {
        var self = this;

        // Toggle minimize/maximize on bubble click
        $(document).on('click', '#savian-chat-widget .savian-chat-bubble', function() {
            self.maximize();
        });

        // Also handle keyboard activation
        $(document).on('keydown', '#savian-chat-widget .savian-chat-bubble', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.maximize();
            }
        });

        // Minimize button
        $(document).on('click', '#savian-chat-minimize', function() {
            self.minimize();
        });

        // Fullscreen/maximize button
        $(document).on('click', '#savian-chat-fullscreen', function() {
            self.toggleFullscreen();
        });

        // ESC key to exit fullscreen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && self.isMaximized) {
                self.toggleFullscreen();
            }
        });

        // Send message button
        $(document).on('click', '#savian-chat-send', function() {
            self.sendMessage();
        });

        // Enter to send (Shift+Enter for new line)
        $(document).on('keydown', '#savian-chat-input', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                self.sendMessage();
            }
        });

        // Auto-resize textarea
        $(document).on('input', '#savian-chat-input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // New conversation
        $(document).on('click', '#savian-chat-new', function() {
            self.startNewConversation();
        });

        // View history
        $(document).on('click', '#savian-chat-history', function() {
            self.showHistory();
        });

        // Feedback buttons (delegated event for dynamic content)
        $(document).on('click', '.savian-btn-feedback', function() {
            var $btn = $(this);
            var messageid = $btn.closest('.savian-message-feedback').data('message-id');
            var feedback = parseInt($btn.data('feedback'));
            self.submitFeedback(messageid, feedback, $btn);
        });
    };

    ChatWidget.prototype.maximize = function() {
        this.isMinimized = false;
        $('#savian-chat-widget').removeClass('minimized').addClass('maximized');
        $('#savian-chat-input').focus();
        this.saveWidgetState();

        // Load conversation if exists
        if (this.conversationId) {
            this.loadConversation(this.conversationId);
        }
    };

    ChatWidget.prototype.minimize = function() {
        this.isMinimized = true;
        this.isMaximized = false;
        $('#savian-chat-widget').removeClass('maximized fullscreen').addClass('minimized');
        $('#savian-chat-backdrop').remove();
        this.updateFullscreenButton();
        this.saveWidgetState();
    };

    ChatWidget.prototype.toggleFullscreen = function() {
        this.isMaximized = !this.isMaximized;

        if (this.isMaximized) {
            // Enter fullscreen mode
            $('#savian-chat-widget').addClass('fullscreen');
            $('#savian-chat-fullscreen i').removeClass('fa-expand').addClass('fa-compress');
            $('#savian-chat-fullscreen').attr('title', 'Exit fullscreen');

            // Add backdrop
            if ($('#savian-chat-backdrop').length === 0) {
                $('body').append('<div id="savian-chat-backdrop" class="savian-chat-backdrop"></div>');
            }

            // Focus input
            $('#savian-chat-input').focus();
        } else {
            // Exit fullscreen mode
            $('#savian-chat-widget').removeClass('fullscreen');
            $('#savian-chat-fullscreen i').removeClass('fa-compress').addClass('fa-expand');
            $('#savian-chat-fullscreen').attr('title', this.strings.maximize);
            $('#savian-chat-backdrop').remove();
        }

        this.scrollToBottom();
    };

    ChatWidget.prototype.updateFullscreenButton = function() {
        if (this.isMaximized) {
            $('#savian-chat-fullscreen i').removeClass('fa-expand').addClass('fa-compress');
        } else {
            $('#savian-chat-fullscreen i').removeClass('fa-compress').addClass('fa-expand');
        }
    };

    ChatWidget.prototype.sendMessage = function() {
        var message = $('#savian-chat-input').val().trim();
        if (!message) {
            return;
        }

        // Disable input
        $('#savian-chat-input, #savian-chat-send').prop('disabled', true);

        // Add user message to UI
        this.addMessageToUI('user', message);

        // Clear input
        $('#savian-chat-input').val('').css('height', 'auto');

        // Show typing indicator
        this.showTypingIndicator();

        // Auto-include course documents (no manual selection)
        var documentIds = [];

        // Send to backend
        var self = this;
        Ajax.call([{
            methodname: 'local_savian_ai_send_chat_message',
            args: {
                message: message,
                conversationid: this.conversationId || 0,
                courseid: this.courseId || 0,
                documentids: documentIds
            }
        }])[0].done(function(response) {
            self.hideTypingIndicator();

            if (response.success) {
                // Update conversation ID
                self.conversationId = response.data.conversation_id;

                // Add assistant message
                self.addMessageToUI('assistant', response.data.assistant_message.formatted_content, {
                    sources: response.data.assistant_message.sources,
                    messageId: response.data.assistant_message.id
                });
            } else {
                Notification.addNotification({
                    message: response.error || 'Failed to send message',
                    type: 'error'
                });
            }

            // Re-enable input
            $('#savian-chat-input, #savian-chat-send').prop('disabled', false);
            $('#savian-chat-input').focus();

        }).fail(function(error) {
            self.hideTypingIndicator();
            Notification.exception(error);
            $('#savian-chat-input, #savian-chat-send').prop('disabled', false);
        });
    };

    ChatWidget.prototype.addMessageToUI = function(role, content, options) {
        options = options || {};

        // Parse sources if it's a JSON string
        var sources = options.sources;
        if (typeof sources === 'string') {
            try {
                sources = JSON.parse(sources);
            } catch (e) {
                sources = [];
            }
        }

        var messageHtml = `
            <div class="savian-chat-message ${role}" data-message-id="${options.messageId || ''}">
                <div class="savian-message-content">
                    ${content}
                </div>
                ${sources && sources.length > 0 ? this.renderSources(sources) : ''}
                ${role === 'assistant' && options.messageId && this.config.enableFeedback ?
                    this.renderFeedback(options.messageId) : ''}
                <div class="savian-message-time">${this.formatTime(new Date())}</div>
            </div>
        `;

        $('#savian-chat-messages').append(messageHtml);

        // Apply syntax highlighting and LaTeX rendering
        this.enhanceMessage($('#savian-chat-messages .savian-chat-message').last());

        // Scroll to bottom
        this.scrollToBottom();
    };

    ChatWidget.prototype.renderSources = function(sources) {
        if (!sources || !Array.isArray(sources) || sources.length === 0) {
            return '';
        }

        var sourcesHtml = '<div class="savian-message-sources"><i class="fa fa-book"></i> Sources: ';
        sources.forEach(function(source, idx) {
            var title = '';
            if (typeof source === 'object' && source !== null) {
                title = source.title || source.document_title || 'Document ' + (idx + 1);
            } else if (typeof source === 'string') {
                title = source;
            } else {
                title = 'Document ' + (idx + 1);
            }
            sourcesHtml += `<span class="savian-source-badge">${title}</span>`;
        });
        sourcesHtml += '</div>';

        return sourcesHtml;
    };

    ChatWidget.prototype.renderFeedback = function(messageId) {
        return `
            <div class="savian-message-feedback" data-message-id="${messageId}">
                <button class="savian-btn-feedback" data-feedback="1" aria-label="${this.strings.helpful}">
                    <i class="fa fa-thumbs-up"></i>
                </button>
                <button class="savian-btn-feedback" data-feedback="-1" aria-label="${this.strings.nothelpful}">
                    <i class="fa fa-thumbs-down"></i>
                </button>
            </div>
        `;
    };

    ChatWidget.prototype.enhanceMessage = function($messageEl) {
        // Apply code syntax highlighting if highlight.js is available
        $messageEl.find('pre code').each(function() {
            if (window.hljs) {
                window.hljs.highlightElement(this);
            }
        });

        // Render LaTeX with MathJax if available
        if (window.MathJax && window.MathJax.typesetPromise) {
            MathJax.typesetPromise([$messageEl[0]]).catch(function(err) {
                // eslint-disable-next-line no-console
                console.error('MathJax rendering error:', err);
            });
        }
    };

    ChatWidget.prototype.showTypingIndicator = function() {
        var typingHtml = `
            <div class="savian-chat-message assistant savian-typing-indicator" id="savian-typing-indicator">
                <div class="savian-message-content">
                    <span class="savian-typing-dot"></span>
                    <span class="savian-typing-dot"></span>
                    <span class="savian-typing-dot"></span>
                </div>
            </div>
        `;
        $('#savian-chat-messages').append(typingHtml);
        this.scrollToBottom();
    };

    ChatWidget.prototype.hideTypingIndicator = function() {
        $('#savian-typing-indicator').remove();
    };

    ChatWidget.prototype.scrollToBottom = function() {
        var $messages = $('#savian-chat-messages');
        $messages.scrollTop($messages[0].scrollHeight);
    };

    ChatWidget.prototype.loadConversation = function(conversationId) {
        var self = this;

        Ajax.call([{
            methodname: 'local_savian_ai_get_conversation',
            args: {conversationid: conversationId}
        }])[0].done(function(response) {
            if (response.success && response.data.messages) {
                // Clear messages
                $('#savian-chat-messages').empty();

                // Add messages
                response.data.messages.forEach(function(msg) {
                    self.addMessageToUI(msg.role, msg.formatted_content, {
                        sources: msg.sources,
                        messageId: msg.id
                    });
                });
            }
        }).fail(function(error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load conversation:', error);
        });
    };

    ChatWidget.prototype.startNewConversation = function() {
        this.conversationId = null;
        $('#savian-chat-messages').empty();

        var welcomeMsg = this.config.welcomeMessage || 'Hi! I\'m your AI assistant. Ask me anything about your course materials.';
        this.addMessageToUI('assistant', welcomeMsg);
    };

    ChatWidget.prototype.showHistory = function() {
        // Open history in new window
        if (this.config.canViewHistory && this.courseId) {
            var url = M.cfg.wwwroot + '/local/savian_ai/chat_history.php?courseid=' + this.courseId;
            window.open(url, '_blank');
        }
    };

    ChatWidget.prototype.submitFeedback = function(messageId, feedback, $btn) {
        Ajax.call([{
            methodname: 'local_savian_ai_submit_feedback',
            args: {
                messageid: messageId,
                feedback: feedback,
                comment: ''
            }
        }])[0].done(function(response) {
            if (response.success) {
                // Mark button as active
                $btn.addClass('active').siblings().removeClass('active');
            }
        }).fail(function(error) {
            // eslint-disable-next-line no-console
            console.error('Failed to submit feedback:', error);
        });
    };

    ChatWidget.prototype.saveWidgetState = function() {
        var settings = {
            position: this.position,
            minimized: this.isMinimized
        };

        localStorage.setItem('savian_chat_widget_settings', JSON.stringify(settings));

        // Sync to DB
        Ajax.call([{
            methodname: 'local_savian_ai_save_widget_state',
            args: {
                position: this.position,
                minimized: this.isMinimized ? 1 : 0
            }
        }])[0].fail(function(error) {
            // eslint-disable-next-line no-console
            console.error('Failed to save widget state:', error);
        });
    };

    ChatWidget.prototype.formatTime = function(date) {
        return date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    };

    return {
        init: function(config) {
            var widget = new ChatWidget();
            widget.init(config);
        }
    };
});
