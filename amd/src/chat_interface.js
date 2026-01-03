// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Savian AI Full-Page Chat Interface
 *
 * @module     local_savian_ai/chat_interface
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var ChatInterface = function() {
        this.conversationId = null;
        this.courseId = null;
    };

    ChatInterface.prototype.init = function(params) {
        this.courseId = params.courseid || null;
        this.conversationId = params.conversationid || null;
        this.fullpage = params.fullpage || false;

        this.render();
        this.attachEvents();

        // Load conversation if specified
        if (this.conversationId) {
            this.loadConversation(this.conversationId);
        }
    };

    ChatInterface.prototype.render = function() {
        var html = `
            <div class="savian-chat-fullpage">
                <div class="chat-sidebar">
                    <div class="sidebar-header">
                        <h4>Conversations</h4>
                        <button class="btn btn-sm btn-savian" id="new-conversation-btn">
                            <i class="fa fa-plus"></i> New
                        </button>
                    </div>
                    <div class="conversation-list" id="conversation-list">
                        <div class="text-center p-3">
                            <div class="spinner-border spinner-border-sm"></div>
                        </div>
                    </div>
                </div>

                <div class="chat-main">
                    <div class="chat-messages-container" id="chat-messages-full">
                        <div class="chat-message assistant">
                            <div class="message-content">
                                Hi! I'm your AI assistant. Select a conversation or start a new one.
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <textarea
                            id="chat-input-full"
                            class="form-control"
                            placeholder="Type your message..."
                            rows="3"
                        ></textarea>
                        <button id="send-message-btn" class="btn btn-savian mt-2">
                            <i class="fa fa-paper-plane"></i> Send
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#savian-chat-main').html(html);
        this.loadConversationList();
    };

    ChatInterface.prototype.attachEvents = function() {
        var self = this;

        $('#send-message-btn').on('click', function() {
            self.sendMessage();
        });

        $('#chat-input-full').on('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                self.sendMessage();
            }
        });

        $('#new-conversation-btn').on('click', function() {
            self.startNewConversation();
        });

        // Click on conversation in sidebar
        $(document).on('click', '.conversation-item', function() {
            var convId = parseInt($(this).data('conversation-id'));
            self.loadConversation(convId);
            $('.conversation-item').removeClass('active');
            $(this).addClass('active');
        });
    };

    ChatInterface.prototype.loadConversationList = function() {
        var self = this;

        Ajax.call([{
            methodname: 'local_savian_ai_list_conversations',
            args: {courseid: this.courseId || 0}
        }])[0].done(function(response) {
            console.log('Conversations response:', response);
            if (response.success && response.data) {
                var html = '';
                if (Array.isArray(response.data) && response.data.length > 0) {
                    response.data.forEach(function(conv) {
                        var title = conv.title || 'Conversation ' + conv.id;
                        var time = new Date(conv.last_message_at * 1000).toLocaleString();
                        html += `
                            <div class="conversation-item" data-conversation-id="${conv.id}">
                                <div class="conv-title">${title}</div>
                                <div class="conv-meta">
                                    <small>${conv.message_count} messages • ${time}</small>
                                </div>
                            </div>
                        `;
                    });
                }
                $('#conversation-list').html(html || '<p class="text-muted p-3">No conversations yet. Start chatting below!</p>');
            } else {
                console.error('Invalid response:', response);
                $('#conversation-list').html('<p class="text-muted p-3">No conversations yet</p>');
            }
        }).fail(function(error) {
            console.error('Failed to load conversations:', error);
            $('#conversation-list').html('<p class="text-danger p-3">Failed to load. Check console for details.</p>');
        });
    };

    ChatInterface.prototype.loadConversation = function(conversationId) {
        // Similar to chat_widget.js loadConversation
        this.conversationId = conversationId;
        // Implementation would load and display messages
    };

    ChatInterface.prototype.sendMessage = function() {
        // Similar to chat_widget.js sendMessage
        var message = $('#chat-input-full').val().trim();
        if (!message) {
            return;
        }
        // Implementation would send message and display response
    };

    ChatInterface.prototype.startNewConversation = function() {
        this.conversationId = null;
        $('#chat-messages-full').html('<div class="chat-message assistant"><div class="message-content">Hi! I\'m your AI tutor. How can I help you today?</div></div>');
        $('.conversation-item').removeClass('active');
    };

    return {
        init: function(params) {
            var interface = new ChatInterface();
            interface.init(params);
        }
    };
});
