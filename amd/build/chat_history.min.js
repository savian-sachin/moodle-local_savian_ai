// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Savian AI Chat History Viewer (Teacher/Admin)
 *
 * @module     local_savian_ai/chat_history
 * @copyright  2025 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function($, Ajax, Notification, Templates) {

    var ChatHistory = function() {
        this.courseId = null;
    };

    ChatHistory.prototype.init = function(params) {
        this.courseId = params.courseid;

        this.render();
        this.loadConversations();
        this.loadAnalytics();
    };

    ChatHistory.prototype.render = function() {
        var html = `
            <div class="savian-chat-history-viewer">
                <!-- Analytics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h2 mb-0 savian-text-primary" id="stat-conversations">-</div>
                                <div class="text-muted small">Conversations</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h2 mb-0 savian-text-primary" id="stat-messages">-</div>
                                <div class="text-muted small">Messages</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h2 mb-0 savian-text-primary" id="stat-users">-</div>
                                <div class="text-muted small">Active Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h2 mb-0 savian-text-primary" id="stat-feedback">-</div>
                                <div class="text-muted small">Positive Feedback</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversations Table -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Student Conversations</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="conversations-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Messages</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner-border spinner-border-sm"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#chat-history-main').html(html);
    };

    ChatHistory.prototype.loadConversations = function() {
        var self = this;

        Ajax.call([{
            methodname: 'local_savian_ai_list_conversations',
            args: {courseid: this.courseId}
        }])[0].done(function(response) {
            if (response.success && response.data) {
                var tbody = '';
                response.data.forEach(function(conv) {
                    var lastActive = new Date(conv.last_message_at * 1000).toLocaleString();
                    tbody += `
                        <tr>
                            <td>User ${conv.user_id}</td>
                            <td>${conv.message_count}</td>
                            <td>${lastActive}</td>
                            <td>
                                <button class="btn btn-sm btn-primary view-conversation" data-conversation-id="${conv.id}">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                });

                if (tbody === '') {
                    tbody = '<tr><td colspan="4" class="text-center text-muted">No conversations yet</td></tr>';
                }

                $('#conversations-table tbody').html(tbody);
            }
        }).fail(function(error) {
            console.error('Failed to load conversations:', error);
        });
    };

    ChatHistory.prototype.loadAnalytics = function() {
        // Load analytics stats
        // This would call a backend method to get course chat statistics
        // For now, showing placeholder values
        $('#stat-conversations').text('0');
        $('#stat-messages').text('0');
        $('#stat-users').text('0');
        $('#stat-feedback').text('0%');
    };

    return {
        init: function(params) {
            var history = new ChatHistory();
            history.init(params);
        }
    };
});
