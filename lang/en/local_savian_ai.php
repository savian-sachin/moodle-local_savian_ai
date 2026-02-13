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
 * English language strings for Savian AI.
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Savian AI';

// Capabilities.
$string['savian_ai:use'] = 'Use Savian AI features';
$string['savian_ai:manage'] = 'Manage Savian AI settings';
$string['savian_ai:generate'] = 'Generate content with Savian AI';

// Settings.
$string['settings_heading'] = 'Savian AI Configuration';
$string['api_url'] = 'API Base URL';
$string['api_url_desc'] = 'Savian AI API endpoint (e.g., https://app.savian.ai.vn/api/moodle/v1/)';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'Your Savian AI API key';
$string['org_code'] = 'Organization Code';
$string['org_code_desc'] = 'Your organization code.';
$string['org_code_warning'] = 'Warning: Changing the organization code will delete all uploaded documents as they are tied to the organization. You can recover documents by sending an email to info@savian.ai.vn with your registered email ID and the organization code for which you want to recover.';
$string['org_code_changed_documents_cleared'] = 'Organization code changed. {$a} document(s) have been cleared as they are no longer valid for the new organization.';
$string['validate_connection'] = 'Validate Connection';
$string['connection_valid'] = 'Connection successful! Organization: {$a}';
$string['connection_invalid'] = 'Connection failed: {$a}';
$string['connection_status'] = 'Connection Status';
$string['connection_status_connected'] = 'Connected to {$a}';
$string['connection_status_failed'] = 'Connection failed: {$a}';
$string['connection_status_error'] = 'Error: {$a}';
$string['connection_status_not_configured'] = 'API credentials not configured. Please enter API URL, Organization Code, and API Key below.';

// Navigation.
$string['dashboard'] = 'Dashboard';
$string['documents'] = 'Documents';
$string['generate'] = 'Generate Questions';
$string['usage'] = 'Usage Statistics';
$string['chat'] = 'Document Q&A';

// Documents page.
$string['upload_document'] = 'Upload Document';
$string['document_title'] = 'Title';
$string['document_description'] = 'Description';
$string['document_subject'] = 'Subject Area';
$string['document_tags'] = 'Tags';
$string['document_tags_help'] = 'Comma-separated tags';
$string['document_uploaded'] = 'Document uploaded successfully';
$string['document_upload_failed'] = 'Document upload failed: {$a}';
$string['document_list'] = 'Document List';
$string['document_status'] = 'Status';
$string['document_chunks'] = 'Chunks';
$string['document_size'] = 'Size';
$string['document_delete'] = 'Delete';
$string['document_deleted'] = 'Document deleted successfully';
$string['document_delete_failed'] = 'Document deletion failed: {$a}';
$string['document_reprocess'] = 'Reprocess';
// Simplified status labels for end users.
$string['status_ready'] = 'Ready';
$string['status_uploading'] = 'Uploading...';
$string['status_processing_simple'] = 'Processing...';
$string['status_failed'] = 'Failed';

// Technical status labels (kept for backward compatibility).
$string['status_pending'] = 'Pending';
$string['status_processing'] = 'Processing';
$string['status_embedding'] = 'Generating Embeddings';
$string['status_generating_questions'] = 'Generating Questions';
$string['status_generating_qnas'] = 'Generating Q&As';
$string['status_completed'] = 'Completed';
$string['no_documents'] = 'No documents uploaded yet';
$string['auto_refresh_notice'] = 'This page will automatically refresh in 30 seconds to update processing status';

// Question generation.
$string['generate_from_topic'] = 'Generate from Topic';
$string['generate_from_documents'] = 'Generate from Documents';
$string['topic'] = 'Topic';
$string['learning_objectives'] = 'Learning Objectives';
$string['learning_objectives_help'] = 'One objective per line';
$string['question_types'] = 'Question Types';
$string['question_count'] = 'Number of Questions';
$string['difficulty'] = 'Difficulty';
$string['difficulty_easy'] = 'Easy';
$string['difficulty_medium'] = 'Medium';
$string['difficulty_hard'] = 'Hard';
$string['bloom_level'] = 'Bloom\'s Level';
$string['bloom_remember'] = 'Remember';
$string['bloom_understand'] = 'Understand';
$string['bloom_apply'] = 'Apply';
$string['bloom_analyze'] = 'Analyze';
$string['bloom_evaluate'] = 'Evaluate';
$string['bloom_create'] = 'Create';
$string['language'] = 'Language';
$string['select_documents'] = 'Select Documents';
$string['generate_questions'] = 'Generate Questions';
$string['questions_generated'] = '{$a} questions generated successfully';
$string['generation_failed'] = 'Generation failed: {$a}';
$string['generation_pending'] = 'Question generation started. Please wait...';
$string['preview_questions'] = 'Preview Questions';
$string['add_to_question_bank'] = 'Add to Question Bank';
$string['questions_added'] = '{$a} questions added to question bank';
$string['backtocourse'] = 'Back to course';

// Question types.
$string['qtype_multichoice'] = 'Multiple Choice';
$string['qtype_truefalse'] = 'True/False';
$string['qtype_shortanswer'] = 'Short Answer';
$string['qtype_essay'] = 'Essay';
$string['qtype_matching'] = 'Matching';

// Usage statistics.
$string['quota_heading'] = 'Monthly Quota Usage';
$string['quota_questions'] = 'Questions';
$string['quota_documents'] = 'Documents';
$string['quota_course_content'] = 'Course Content';
$string['quota_used'] = 'Used';
$string['quota_limit'] = 'Limit';
$string['quota_remaining'] = 'Remaining';
$string['billing_period'] = 'Billing Period';
$string['days_remaining'] = '{$a} days remaining';

// Errors.
$string['error_no_api_key'] = 'API key not configured. Please configure in site administration.';
$string['error_api_connection'] = 'Unable to connect to Savian AI API';
$string['error_invalid_document'] = 'Invalid document ID';
$string['error_no_permission'] = 'You do not have permission to perform this action';
$string['error_quota_exceeded'] = 'Monthly quota exceeded';
$string['error_chat_failed'] = 'Failed to send chat message: {$a}';
$string['error_conversation_not_found'] = 'Conversation not found';
$string['error_message_failed'] = 'Failed to send message';
$string['error_feedback_failed'] = 'Failed to submit feedback';
$string['error_students_course_only'] = 'Students can only use chat within course context';
$string['error_url_blocked'] = 'The API URL ({$a}) is blocked by Moodle HTTP security. An administrator must add this host to the allowed list at Site Administration → General → HTTP Security → cURL blocked hosts list.';

// Chat widget.
$string['chat'] = 'AI Chat';
$string['openchat'] = 'Open chat';
$string['minimize'] = 'Minimize';
$string['maximize'] = 'Maximize to fullscreen';
$string['newconversation'] = 'New conversation';
$string['history'] = 'History';
$string['typemessage'] = 'Type your message...';
$string['chatinput'] = 'Chat message input';
$string['send'] = 'Send';
$string['helpful'] = 'Helpful';
$string['nothelpful'] = 'Not helpful';
$string['chaterror'] = 'Failed to send message. Please try again.';
$string['selectdocuments'] = 'Select documents for context';
$string['coursechatcontext'] = 'I can answer questions about this course.';
$string['globalchat'] = 'Global Chat';

// Chat settings.
$string['chat_settings_heading'] = 'Chat Widget Settings';
$string['chat_settings_desc'] = 'Configure the floating chat widget appearance and behavior';
$string['enable_chat_widget'] = 'Enable Chat Widget';
$string['enable_chat_widget_desc'] = 'Show floating chat bubble on course pages';
$string['chat_course_pages_only'] = 'Course Pages Only';
$string['chat_course_pages_only_desc'] = 'Only show widget on course pages (not site-wide)';
$string['chat_welcome_message'] = 'Welcome Message';
$string['chat_welcome_message_desc'] = 'First message shown when user opens chat';
$string['default_welcome_message'] = 'Hi! I\'m your AI tutor. Ask me anything about your course materials.';
$string['chat_primary_color'] = 'Widget Color';
$string['chat_primary_color_desc'] = 'Primary color for chat widget (hex code)';
$string['chat_default_position'] = 'Default Position';
$string['chat_default_position_desc'] = 'Where the chat bubble appears';
$string['position_bottom_right'] = 'Bottom Right';
$string['position_bottom_left'] = 'Bottom Left';
$string['chat_widget_size'] = 'Widget Size';
$string['chat_widget_size_desc'] = 'Size of the chat window';
$string['size_small'] = 'Small (320px)';
$string['size_medium'] = 'Medium (380px)';
$string['size_large'] = 'Large (450px)';
$string['enable_conversation_history'] = 'Conversation History';
$string['enable_conversation_history_desc'] = 'Allow teachers to view student conversations';
$string['enable_chat_feedback'] = 'Enable Feedback';
$string['enable_chat_feedback_desc'] = 'Show thumbs up/down buttons on AI responses';

// Chat history and monitoring.
$string['chat_history'] = 'Conversation History';
$string['chat_history_desc'] = 'View and analyze student conversations';
$string['chat_monitoring'] = 'Chat Monitoring';
$string['chat_monitoring_desc'] = 'System-wide chat analytics and insights';
$string['conversation_count'] = 'Total Conversations';
$string['message_count'] = 'Total Messages';
$string['avg_messages_per_conv'] = 'Avg Messages/Conversation';
$string['unique_users'] = 'Unique Users';
$string['feedback_stats'] = 'Feedback Statistics';
$string['positive_feedback'] = 'Positive';
$string['negative_feedback'] = 'Negative';
$string['feedback_rate'] = 'Feedback Rate';
$string['user_engagement'] = 'User Engagement';
$string['last_active'] = 'Last Active';
$string['view_conversation'] = 'View';
$string['archive_conversation'] = 'Archive';
$string['export_data'] = 'Export Data';
$string['system_wide_stats'] = 'System-Wide Statistics';
$string['top_courses'] = 'Top Courses by Chat Usage';
$string['recent_conversations'] = 'Recent Conversations';
$string['response_time_avg'] = 'Average Response Time';
$string['token_usage'] = 'Token Usage';
$string['no_chat_activity'] = 'No chat activity yet';
$string['no_conversations'] = 'No conversations found';

// Course-level chat settings.
$string['chat_course_settings'] = 'Chat Settings';
$string['chat_course_settings_desc'] = 'Configure chat widget for this course';
$string['chat_settings'] = 'Chat Configuration';
$string['enable_chat_for_course'] = 'Enable chat widget for this course';
$string['enable_chat_for_course_desc'] = 'Show the floating chat bubble on this course\'s pages';
$string['students_can_chat'] = 'Allow students to use chat';
$string['students_can_chat_desc'] = 'If unchecked, only teachers can use the chat widget in this course';
$string['course_welcome_message'] = 'Course-specific welcome message';
$string['course_welcome_message_desc'] = 'Custom welcome message for this course (leave blank to use global default)';
$string['course_welcome_message_placeholder'] = 'Hi! Ask me anything about this course...';
$string['auto_include_docs'] = 'Auto-include course documents';
$string['auto_include_docs_desc'] = 'Automatically include all course documents in chat context for students';
$string['settings_saved'] = 'Settings saved successfully';

// Capabilities.
$string['savian_ai:use'] = 'Use Savian AI features';
$string['savian_ai:generate'] = 'Generate content with Savian AI';
$string['savian_ai:manage'] = 'Manage Savian AI settings';
$string['savian_ai:viewchathistory'] = 'View conversation history';
$string['savian_ai:managechatdocuments'] = 'Manage documents in chat';

// Enhanced course generation.
$string['target_audience'] = 'Target Audience';
$string['target_audience_help'] = 'Who is this course designed for? (e.g., Beginning developers, High school students)';
$string['target_audience_placeholder'] = 'e.g., Beginning developers, High school students';
$string['content_types_select'] = 'Select Content Types';
$string['content_types_help'] = 'Choose which types of content to generate';
$string['content_type_sections'] = 'Course Sections';
$string['content_type_pages'] = 'Teaching Pages (400-600 words)';
$string['content_type_activities'] = 'Hands-on Activities';
$string['content_type_discussions'] = 'Discussion Forums';
$string['content_type_quizzes'] = 'Section Quizzes';
$string['content_type_assignments'] = 'Assignments with Rubrics';

// Progress tracking.
$string['generating_course_content'] = 'Generating Course Content';
$string['progress_analyzing'] = 'Analyzing documents and creating outline...';
$string['progress_outline_complete'] = 'Outline complete, generating sections...';
$string['progress_generating_section'] = 'Creating section {$a} content...';
$string['progress_sections_complete'] = 'All sections generated, finalizing...';
$string['progress_finalizing'] = 'Adding glossary and final touches...';
$string['progress_complete'] = 'Course generation complete!';
$string['progress_unknown'] = 'Processing...';
$string['estimated_time'] = 'Estimated time: {$a} minutes';
$string['estimated_time_4weeks'] = 'Estimated time: 3-5 minutes';
$string['estimated_time_8weeks'] = 'Estimated time: 5-8 minutes';
$string['estimated_time_12weeks'] = 'Estimated time: 8-12 minutes';
$string['cancel_generation'] = 'Cancel Generation';

// Preview.
$string['preview_course_structure'] = 'Preview Course Structure';
$string['content_summary'] = 'Generated Content Summary';
$string['summary_sections'] = 'Sections';
$string['summary_pages'] = 'Pages';
$string['summary_activities'] = 'Activities';
$string['summary_discussions'] = 'Discussions';
$string['summary_quizzes'] = 'Quizzes';
$string['summary_assignments'] = 'Assignments';
$string['include_item'] = 'Include this item';
$string['edit_item'] = 'Edit';
$string['expand_all'] = 'Expand All';
$string['collapse_all'] = 'Collapse All';
$string['add_selected'] = 'Add Selected to Course';
$string['add_to_this_course'] = 'Add to THIS Course';
$string['regenerate'] = 'Regenerate';
$string['learning_objectives'] = 'Learning Objectives';
$string['section_content'] = 'Content';
$string['will_create'] = 'Will create';

// Content created.
$string['content_created_success'] = 'Course content created successfully';
$string['content_created_details'] = 'Created: {$a->sections} sections, {$a->pages} pages, {$a->activities} activities, {$a->discussions} discussions, {$a->quizzes} quizzes, {$a->assignments} assignments';
$string['content_created_simple'] = 'Course content created: {$a->sections} sections, {$a->pages} pages, {$a->quizzes} quizzes, {$a->assignments} assignments';
$string['content_created_with_errors'] = 'Course content created with {$a} errors';

// Generation errors.
$string['generation_failed'] = 'Generation failed: {$a}';
$string['generation_timeout'] = 'Generation timeout after {$a} seconds';
$string['no_documents_selected'] = 'No documents selected. Please select at least one document.';
$string['no_title_provided'] = 'No course title provided';

// Based on documents.
$string['based_on_documents'] = 'Based on documents';
$string['chunks_used'] = '{$a} chunks used';

// ADDIE v2.0 - Age/Industry Adaptation.
$string['age_group'] = 'Target Learner Age Group';
$string['age_group_help'] = 'Adapts vocabulary, reading level, and pedagogy to learner age';
$string['industry'] = 'Industry Context';
$string['industry_help'] = 'Customizes terminology, examples, and compliance requirements';
$string['prior_knowledge'] = 'Prior Knowledge Level';
$string['prior_knowledge_help'] = 'Adjusts content difficulty based on learner background';

// ADDIE progress stages.
$string['progress_addie_analysis'] = 'Analyzing learner profile and context...';
$string['progress_addie_design_outline'] = 'Designing course structure...';
$string['progress_addie_design_completed'] = 'Course outline ready ✓';
$string['progress_addie_development'] = 'Generating content...';
$string['progress_addie_development_completed'] = 'All sections generated ✓';
$string['progress_addie_implementation'] = 'Adding quality markers...';
$string['progress_addie_evaluation'] = 'Calculating quality scores...';
$string['progress_addie_completed'] = 'Course ready! ✓';

// Quality Matters.
$string['qm_alignment'] = 'Quality Matters Alignment';
$string['qm_score'] = 'QM Score';
$string['qm_certified_ready'] = 'QM Certification Ready';
$string['qm_below_threshold'] = 'Below QM certification threshold';
$string['qm_recommendations'] = 'QM Recommendations';
$string['qm_standards_met'] = '{$a->met} of {$a->total} standards met';
$string['verify_media_accessibility'] = 'Verify all media has accessibility features';

// Quality markers.
$string['pedagogical_metadata'] = 'Course Specifications';
$string['source_confidence'] = 'Source Confidence';
$string['ai_transparency'] = 'AI-Generated Content Notice';

// Pedagogical metadata fields (ADDIE v2.0 updated).
$string['designed_for'] = 'Designed For';
$string['subject_area'] = 'Subject Area';
$string['content_level'] = 'Content Level';
$string['instructional_approach'] = 'Instructional Approach';
$string['thinking_skills'] = 'Thinking Skills';
$string['generation_method'] = 'Generation Method';
$string['human_review_required'] = 'Human Review Required';

// Section metadata.
$string['prerequisites'] = 'Prerequisites';
$string['estimated_hours'] = '{$a} hours';
$string['qm_notes'] = 'QM Notes';
$string['source_documents_used'] = 'Source Documents';

// Content types.
$string['content_type_formative'] = 'Self-Check Questions (Formative)';

// Privacy API.
$string['privacy:metadata:conversations'] = 'Chat conversations with AI tutor';
$string['privacy:metadata:conversations:user_id'] = 'User who created the conversation';
$string['privacy:metadata:conversations:course_id'] = 'Course context for conversation';
$string['privacy:metadata:conversations:title'] = 'Conversation title';
$string['privacy:metadata:conversations:timecreated'] = 'When conversation was created';
$string['privacy:metadata:messages'] = 'Chat messages (user and AI responses)';
$string['privacy:metadata:messages:conversation_id'] = 'Parent conversation';
$string['privacy:metadata:messages:role'] = 'Message sender (user or assistant)';
$string['privacy:metadata:messages:content'] = 'Message content';
$string['privacy:metadata:messages:feedback'] = 'User feedback on AI response';
$string['privacy:metadata:messages:feedback_comment'] = 'Feedback comment';
$string['privacy:metadata:messages:timecreated'] = 'When message was sent';
$string['privacy:metadata:settings'] = 'Chat widget preferences';
$string['privacy:metadata:settings:user_id'] = 'User ID';
$string['privacy:metadata:settings:widget_position'] = 'Widget position preference';
$string['privacy:metadata:settings:widget_minimized'] = 'Widget minimized state';
$string['privacy:metadata:generations'] = 'AI generation request history';
$string['privacy:metadata:generations:user_id'] = 'User who requested generation';
$string['privacy:metadata:generations:course_id'] = 'Target course';
$string['privacy:metadata:generations:generation_type'] = 'Type of content generated';
$string['privacy:metadata:generations:status'] = 'Generation status';
$string['privacy:metadata:generations:timecreated'] = 'When generation was requested';
$string['privacy:metadata:external'] = 'Savian AI External Service';
$string['privacy:metadata:external:user_id'] = 'User ID sent to AI service';
$string['privacy:metadata:external:user_email'] = 'User email for context';
$string['privacy:metadata:external:course_id'] = 'Course ID for scoping';
$string['privacy:metadata:external:chat_message'] = 'Chat messages sent to AI';
$string['privacy:metadata:external:document_content'] = 'Document content for processing';
$string['privacy:chatdata'] = 'Chat Conversations';
$string['privacy:chatsettings'] = 'Chat Settings';
$string['privacy:generationdata'] = 'Generation Requests';

// Knowledge Feedback Loop (v2.2).
$string['save_to_knowledge_base'] = 'Save to Knowledge Base';
$string['knowledge_feedback_loop'] = 'Knowledge Feedback Loop';
$string['build_knowledge_base'] = 'Build your institutional knowledge base!';
$string['save_benefits'] = 'Benefits of saving this approved course:';
$string['benefit_future_courses'] = 'Future courses can build on this approved content';
$string['benefit_student_chat'] = 'Students can chat with this course material';
$string['benefit_reduce_review'] = 'Reduces review time for similar courses';
$string['benefit_preserve_expertise'] = 'Preserves your teaching expertise';
$string['processing_time_kb'] = 'Processing time: 2-3 minutes';
$string['skip_and_continue'] = 'Skip and Go to Course';
$string['course_saved_kb'] = 'Course Saved to Knowledge Base!';
$string['kb_save_success'] = 'Your approved course content has been saved and is being processed';
$string['kb_save_failed'] = 'Failed to save course to knowledge base';
$string['no_course_data'] = 'No course data found. Please generate a course first';
$string['what_happens_next'] = 'What happens next';
$string['kb_processing'] = 'Processing: 2-3 minutes (chunking and embedding)';
$string['kb_availability'] = 'Will appear in document list as approved course';
$string['kb_usage'] = 'Future course generations can use this content';
$string['kb_chat'] = 'Students can ask questions about this course';

// Tutorials.
$string['tutorials'] = 'Help & Tutorials';
$string['select_your_role'] = 'Select your role to see relevant tutorials';
$string['for_administrators'] = 'For Administrators';
$string['for_teachers'] = 'For Teachers';
$string['for_students'] = 'For Students';
$string['quick_start'] = 'Quick Start Guide';
$string['tutorial_search'] = 'Search tutorials...';
$string['tutorial_admin_setup'] = 'Administrator Setup Guide';
$string['tutorial_teacher_generate'] = 'Generate Your First Course';
$string['tutorial_quality_scores'] = 'Understanding Quality Scores';
$string['tutorial_student_chat'] = 'Using the AI Chat Tutor';
$string['tutorial_knowledge_base'] = 'Knowledge Feedback Loop';
$string['video_tutorials'] = 'Video Tutorials';
$string['faqs'] = 'Frequently Asked Questions';

// ADDIE v2.1: Quality Control.
$string['quality_report'] = 'Course Quality Report';
$string['overall_score'] = 'Overall Score';
$string['source_coverage'] = 'Source Coverage';
$string['learning_depth'] = 'Learning Depth';
$string['priority_reviews'] = 'Focus Your Review On';
$string['recommended_review_time'] = 'Estimated review time';
$string['quality_verified'] = 'Verified';
$string['quality_review'] = 'Review Recommended';
$string['quality_priority'] = 'Priority Review';
$string['quality_supplemented'] = 'Supplemented Content';
$string['high_confidence'] = 'High confidence - well-grounded in sources';
$string['medium_confidence'] = 'Medium confidence - recommended review';
$string['low_confidence'] = 'Low confidence - priority review needed';
$string['supplemented_note'] = 'Includes AI-supplemented content - verify against your context';

// Chat Restrictions (v1.0.2).
$string['chat_restrictions'] = 'Chat Restrictions';
$string['chat_restrictions_desc'] = 'Temporarily disable chat during exams or scheduled periods';
$string['add_quiz_restriction'] = 'Link to Quiz';
$string['add_manual_restriction'] = 'Add Time Range';
$string['restriction_type_quiz'] = 'Quiz-linked';
$string['restriction_type_manual'] = 'Manual time range';
$string['select_quiz'] = 'Select Quiz';
$string['select_groups'] = 'Apply to Groups';
$string['all_students'] = 'All Students';
$string['restriction_name'] = 'Restriction Name';
$string['restriction_name_placeholder'] = 'e.g., Midterm Exam Period';
$string['restriction_start'] = 'Start Date/Time';
$string['restriction_end'] = 'End Date/Time';
$string['restriction_message'] = 'Message to Students';
$string['restriction_message_placeholder'] = 'Chat is temporarily unavailable...';
$string['chat_restricted_quiz_default'] = 'Chat is unavailable during this quiz. Focus on your assessment!';
$string['chat_restricted_manual_default'] = 'Chat is temporarily unavailable. Please check back later.';
$string['restriction_active'] = 'Active';
$string['restriction_scheduled'] = 'Scheduled';
$string['restriction_expired'] = 'Expired';
$string['restriction_disabled'] = 'Disabled';
$string['restriction_no_times'] = 'No times set';
$string['ends_in'] = 'Ends in {$a}';
$string['starts_in'] = 'Starts in {$a}';
$string['no_restrictions'] = 'No chat restrictions configured';
$string['no_quizzes_available'] = 'No quizzes with timing available in this course';
$string['chat_unavailable'] = 'Chat Temporarily Unavailable';
$string['resumes_in'] = 'Resumes in';
$string['resumes_at'] = 'Resumes at {$a}';
$string['check_back_later'] = 'Check back later';
$string['restriction_saved'] = 'Restriction saved successfully';
$string['restriction_deleted'] = 'Restriction deleted';
$string['restriction_toggled'] = 'Restriction status updated';
$string['error_quiz_no_timing'] = 'Selected quiz has no open/close times set';
$string['error_invalid_time_range'] = 'End time must be after start time';
$string['error_restriction_not_found'] = 'Restriction not found';
$string['quiz_deleted'] = '(Quiz deleted)';
$string['unnamed_restriction'] = 'Unnamed restriction';
$string['now'] = 'Now';
$string['hours_minutes_remaining'] = '{$a->hours}h {$a->minutes}m';
$string['minutes_remaining'] = '{$a} minutes';
$string['confirm_delete_restriction'] = 'Are you sure you want to delete this restriction?';
$string['quiz_timing'] = 'Quiz: {$a->name} ({$a->open} - {$a->close})';
$string['manual_timing'] = '{$a->start} - {$a->end}';
$string['groups_applied'] = 'Groups: {$a}';
$string['all_groups'] = 'All groups';
$string['edit_restriction'] = 'Edit Restriction';
$string['delete_restriction'] = 'Delete Restriction';
$string['enable_restriction'] = 'Enable';
$string['disable_restriction'] = 'Disable';
$string['chat_restricted'] = 'Chat is currently restricted';

// Learning Analytics (v1.1.0).
$string['send_analytics'] = 'Send Analytics Report';
$string['learning_analytics'] = 'Learning Analytics';
$string['analytics_report'] = 'Analytics Report';
$string['generate_analytics_report'] = 'Generate Analytics Report';
$string['at_risk_students'] = 'At-Risk Students';
$string['at_risk_student'] = 'At-Risk Student';
$string['risk_level'] = 'Risk Level';
$string['risk_score'] = 'Risk Score';
$string['risk_factors'] = 'Risk Factors';
$string['recommended_actions'] = 'Recommended Actions';
$string['course_recommendations'] = 'Course Recommendations';
$string['struggling_topics'] = 'Topics Needing Review';
$string['engagement_insights'] = 'Engagement Insights';
$string['average_engagement'] = 'Average Engagement';
$string['low_engagement'] = 'Low Engagement';
$string['high_performers'] = 'High Performers';
$string['report_period'] = 'Report Period';
$string['all_time'] = 'All Time';
$string['last_30_days'] = 'Last 30 Days';
$string['last_60_days'] = 'Last 60 Days';
$string['last_90_days'] = 'Last 90 Days';
$string['students_analyzed'] = 'Students Analyzed';
$string['report_id'] = 'Report ID';
$string['generated_at'] = 'Generated';
$string['report_sent_successfully'] = 'Report sent successfully';
$string['report_failed'] = 'Report failed to send';
$string['no_students_enrolled'] = 'No students enrolled in this course';
$string['generating_report'] = 'Generating Analytics Report...';
$string['processing_analytics'] = 'Processing analytics data...';
$string['analytics_description'] = 'Get AI-powered insights on student performance and engagement. Identify at-risk students who need intervention.';
$string['view_report_history'] = 'View Report History';
$string['analytics_reports'] = 'Analytics Reports';
$string['no_reports'] = 'No analytics reports have been sent for this course yet.';
$string['report_status'] = 'Status';
$string['report_type'] = 'Type';
$string['on_demand'] = 'On-Demand';
$string['scheduled'] = 'Scheduled';
$string['real_time'] = 'Real-Time';
$string['end_of_course'] = 'End of Course';
$string['report_pending'] = 'Pending';
$string['report_sending'] = 'Sending';
$string['report_sent'] = 'Sent';
$string['report_failed_status'] = 'Failed';
$string['view_insights'] = 'View Insights';
$string['retry_report'] = 'Retry';
$string['peak_activity_days'] = 'Peak Activity Days';
$string['peak_activity_hours'] = 'Peak Activity Hours';
$string['contact_by'] = 'Contact by';
$string['intervention_priority'] = 'Intervention Priority';
$string['urgent'] = 'Urgent';
$string['high'] = 'High';
$string['medium'] = 'Medium';
$string['low'] = 'Low';
$string['students_struggling'] = 'Students Struggling';
$string['avg_grade'] = 'Average Grade';
$string['suggested_contact_date'] = 'Suggested Contact Date';

// Scheduled Tasks.
$string['send_analytics_daily'] = 'Send Daily Analytics Reports';
$string['send_analytics_weekly'] = 'Send Weekly Analytics Reports';
$string['cleanup_old_analytics'] = 'Cleanup Old Analytics Data';

// Admin Settings - Analytics.
$string['analytics_settings'] = 'Learning Analytics Settings';
$string['analytics_enabled'] = 'Enable Learning Analytics';
$string['analytics_enabled_desc'] = 'Enable automatic analytics reporting to Savian AI for student insights';
$string['analytics_frequency'] = 'Analytics Frequency';
$string['analytics_frequency_desc'] = 'How often to automatically send analytics reports';
$string['analytics_frequency_manual'] = 'Manual only (no automatic reports)';
$string['analytics_frequency_daily'] = 'Daily (every day at 2:00 AM)';
$string['analytics_frequency_weekly'] = 'Weekly (every Sunday at 3:00 AM)';
$string['analytics_frequency_both'] = 'Both daily and weekly';
$string['analytics_retention_days'] = 'Report Retention Period (days)';
$string['analytics_retention_days_desc'] = 'How long to keep analytics reports before auto-deletion (default: 365 days)';
$string['analytics_require_consent'] = 'Require User Consent';
$string['analytics_require_consent_desc'] = 'Require students to consent before including their data in analytics';
$string['analytics_realtime_enabled'] = 'Enable Real-Time Analytics';
$string['analytics_realtime_enabled_desc'] = 'Send analytics updates when significant events occur (quiz submissions, assignments, etc.)';
$string['analytics_batch_threshold'] = 'Real-Time Event Threshold';
$string['analytics_batch_threshold_desc'] = 'Number of events before triggering real-time analytics (default: 10)';

// Privacy API - Analytics.
$string['privacy:metadata:analytics_reports'] = 'Analytics reports triggered by teachers';
$string['privacy:metadata:analytics_reports:course_id'] = 'Course for which analytics was generated';
$string['privacy:metadata:analytics_reports:user_id'] = 'User who triggered the report (if manual)';
$string['privacy:metadata:analytics_reports:report_type'] = 'Type of report (on_demand, scheduled, etc.)';
$string['privacy:metadata:analytics_reports:timecreated'] = 'When the report was created';
$string['privacy:metadata:analytics_events'] = 'Real-time analytics events';
$string['privacy:metadata:analytics_events:course_id'] = 'Course where event occurred';
$string['privacy:metadata:analytics_events:user_id'] = 'User who performed the action';
$string['privacy:metadata:analytics_events:event_name'] = 'Name of the event';
$string['privacy:metadata:analytics_events:timecreated'] = 'When the event occurred';
$string['privacy:metadata:external:anonymized_analytics'] = 'Anonymized student performance and engagement metrics (SHA256 hashed user IDs - not reversible)';
$string['privacy:analyticsreports'] = 'Analytics Reports';
$string['privacy:analyticsevents'] = 'Analytics Events';

// Tutorial strings - Overview.
$string['tutorial_welcome'] = 'Welcome to Savian AI Tutorials';
$string['tutorial_overview_intro'] = 'Select your role above to see relevant tutorials, or browse all tutorials below.';
$string['tutorial_administrators'] = 'Administrators';
$string['tutorial_admin_desc'] = 'Setup, configuration, and monitoring';
$string['tutorial_view_admin'] = 'View Admin Tutorials';
$string['tutorial_teachers'] = 'Teachers';
$string['tutorial_teacher_desc'] = 'Course generation, learning analytics, quality scores, and best practices';
$string['tutorial_view_teacher'] = 'View Teacher Tutorials';
$string['tutorial_students'] = 'Students';
$string['tutorial_student_desc'] = 'Using the AI chat tutor effectively';
$string['tutorial_view_student'] = 'View Student Tutorials';

// Tutorial strings - Admin.
$string['tutorial_admin_title'] = 'Administrator Tutorials';
$string['tutorial_quickstart'] = 'Quick Start (5 minutes)';
$string['tutorial_quickstart_intro'] = 'Get Savian AI running in 5 minutes:';
$string['tutorial_quickstart_step1'] = 'Configure API credentials';
$string['tutorial_quickstart_step2'] = 'Validate connection';
$string['tutorial_quickstart_step3'] = 'Assign capabilities to roles';
$string['tutorial_quickstart_step4'] = 'Enable chat widget';
$string['tutorial_quickstart_step5'] = 'Test with a course';
$string['tutorial_config_guide'] = 'Configuration Guide';
$string['tutorial_step1_access'] = 'Step 1: Access Settings';
$string['tutorial_step1_nav'] = 'Navigate to: <strong>Site Administration → Plugins → Local plugins → Savian AI</strong>';
$string['tutorial_step2_credentials'] = 'Step 2: Enter API Credentials';
$string['tutorial_api_url_label'] = '<strong>API Base URL</strong>: https://app.savian.ai.vn/api/moodle/v1/';
$string['tutorial_api_key_label'] = '<strong>API Key</strong>: Provided by Savian AI';
$string['tutorial_org_code_label'] = '<strong>Organization Code</strong>: Your org identifier';
$string['tutorial_step3_validate'] = 'Step 3: Validate Connection';
$string['tutorial_validate_desc'] = 'Click <strong>"Validate Connection"</strong> button to test credentials.';
$string['tutorial_validate_success'] = 'Success message = Ready to use!';
$string['tutorial_step4_chat'] = 'Step 4: Configure Chat (Optional)';
$string['tutorial_chat_enable'] = 'Enable chat widget: ✓';
$string['tutorial_chat_position'] = 'Default position: Bottom-right';
$string['tutorial_chat_welcome'] = 'Welcome message: Customize or use default';
$string['tutorial_capabilities'] = 'Assign Capabilities to Roles';
$string['tutorial_cap_intro'] = 'Give users access to features:';
$string['tutorial_cap_teachers'] = 'For Teachers:';
$string['tutorial_cap_teacher_step1'] = 'Site Admin → Users → Permissions → Define roles';
$string['tutorial_cap_teacher_step2'] = 'Click "Teacher" role (or "Editing teacher")';
$string['tutorial_cap_teacher_step3'] = 'Search for: "Savian"';
$string['tutorial_cap_teacher_step4'] = 'Check: <code>local/savian_ai:generate</code>';
$string['tutorial_cap_teacher_step5'] = 'Save changes';
$string['tutorial_cap_students'] = 'For Students:';
$string['tutorial_cap_student_desc'] = 'Already have <code>local/savian_ai:use</code> by default (chat access)';
$string['tutorial_cap_tip'] = 'Test with a teacher account before rolling out to all faculty';
$string['tutorial_monitoring'] = 'Monitoring Usage';
$string['tutorial_monitoring_intro'] = 'Track system-wide activity:';
$string['tutorial_monitoring_chat'] = '<strong>Chat Monitor</strong>: Site Admin → Local plugins → Savian AI → Chat Monitoring';
$string['tutorial_monitoring_view'] = 'View: Total conversations, active users, feedback statistics';
$string['tutorial_monitoring_filter'] = 'Filter by: Course, date range, user';
$string['tutorial_troubleshooting'] = 'Troubleshooting';
$string['tutorial_trouble_widget'] = 'Chat widget not appearing:';
$string['tutorial_trouble_widget1'] = 'Check: Chat widget enabled in settings';
$string['tutorial_trouble_widget2'] = 'Verify: User has <code>local/savian_ai:use</code> capability';
$string['tutorial_trouble_widget3'] = 'Purge caches: Site Admin → Development → Purge all caches';
$string['tutorial_trouble_connection'] = 'Connection validation fails:';
$string['tutorial_trouble_conn1'] = 'Verify API URL is correct and accessible';
$string['tutorial_trouble_conn2'] = 'Check API key is valid';
$string['tutorial_trouble_conn3'] = 'Ensure organization code matches';

// Tutorial strings - Teacher.
$string['tutorial_teacher_title'] = 'Teacher Tutorials';
$string['tutorial_upload_title'] = 'Tutorial 1: Uploading Documents (2 minutes)';
$string['tutorial_upload_why'] = 'Why Upload Documents?';
$string['tutorial_upload_why_desc'] = 'Your documents become the foundation for AI-generated courses and chat responses.';
$string['tutorial_upload_steps'] = 'Step-by-Step:';
$string['tutorial_upload_step1'] = 'Navigate to your course';
$string['tutorial_upload_step2'] = 'Click <strong>"Savian AI"</strong> in course navigation';
$string['tutorial_upload_step3'] = 'Click <strong>"Documents"</strong>';
$string['tutorial_upload_step4'] = 'Click <strong>"+ Upload Document"</strong> button';
$string['tutorial_upload_step5'] = 'Fill the form:
        <ul>
            <li><strong>Title</strong>: Descriptive name</li>
            <li><strong>File</strong>: Choose PDF/DOCX (max 50MB)</li>
            <li><strong>Description</strong>: Optional summary</li>
            <li><strong>Subject Area</strong>: e.g., "Healthcare Ethics"</li>
            <li><strong>Upload to</strong>: Select course or Global Library</li>
        </ul>';
$string['tutorial_upload_step6'] = 'Click <strong>"Upload"</strong>';
$string['tutorial_upload_step7'] = 'Wait for status to change: Uploading → Processing → <strong>Ready</strong> (30-60 seconds)';
$string['tutorial_upload_tip'] = 'Upload 2-3 related documents for comprehensive course generation';
$string['tutorial_generate_title'] = 'Tutorial 2: Generate Your First Course (10 minutes)';
$string['tutorial_generate_intro'] = 'Create a complete course structure with AI in 3-8 minutes:';
$string['tutorial_gen_step1'] = 'Step 1: Start Generation';
$string['tutorial_gen_step1_desc'] = 'From course → <strong>Savian AI → Generate Course Content</strong>';
$string['tutorial_gen_step2'] = 'Step 2: Fill the Form';
$string['tutorial_gen_basic'] = 'Basic Information:';
$string['tutorial_gen_target_course'] = '<strong>Target Course</strong>: Auto-filled (your course name)';
$string['tutorial_gen_description'] = '<strong>Description</strong>: Optional - add specific goals';
$string['tutorial_gen_context'] = '<strong>Additional Context</strong>: e.g., "First-year medical students" (optional)';
$string['tutorial_gen_learner'] = 'Learner Profile:';
$string['tutorial_gen_age'] = '<strong>Age Group</strong>: K-5, Middle, High School, Undergrad, Graduate, Professional';
$string['tutorial_gen_industry'] = '<strong>Industry</strong>: Healthcare, Technology, Business, K-12, etc.';
$string['tutorial_gen_prior'] = '<strong>Prior Knowledge</strong>: Beginner, Intermediate, Advanced';
$string['tutorial_gen_age_tip'] = 'Age group adapts vocabulary and reading level. Industry customizes terminology and examples.';
$string['tutorial_gen_source'] = 'Source Documents:';
$string['tutorial_gen_select_docs'] = 'Select 1-3 documents (Ctrl+Click for multiple)';
$string['tutorial_gen_duration'] = 'Duration: 4-8 weeks recommended';
$string['tutorial_gen_content_types'] = 'Content Types:';
$string['tutorial_gen_ct_sections'] = '✓ Sections (required) - Weekly/topical organization';
$string['tutorial_gen_ct_pages'] = '✓ Pages (required) - 400-800 words, age-adapted';
$string['tutorial_gen_ct_activities'] = 'Activities - Hands-on exercises';
$string['tutorial_gen_ct_discussions'] = 'Discussions - Forum prompts';
$string['tutorial_gen_ct_quizzes'] = '✓ Quizzes (recommended) - Section assessments';
$string['tutorial_gen_ct_assignments'] = 'Assignments - Projects with rubrics';
$string['tutorial_gen_step3'] = 'Step 3: Watch Progress (3-8 min)';
$string['tutorial_gen_progress_desc'] = 'Real-time progress bar shows ADDIE stages:';
$string['tutorial_gen_progress_2'] = '2% - Analyzing learner profile';
$string['tutorial_gen_progress_10'] = '10% - Course outline ready ✓';
$string['tutorial_gen_progress_45'] = '45% - Creating Week 3 content';
$string['tutorial_gen_progress_85'] = '85% - Adding quality markers';
$string['tutorial_gen_progress_100'] = '100% - Course ready! Auto-redirects to preview';
$string['tutorial_gen_step4'] = 'Step 4: Review Quality (2 min)';
$string['tutorial_gen_step4_desc'] = 'Preview shows comprehensive quality information. See "Understanding Quality Scores" tutorial for details.';
$string['tutorial_gen_step5'] = 'Step 5: Add to Course (1 min)';
$string['tutorial_gen_step5_1'] = 'Optionally uncheck items you don\'t want';
$string['tutorial_gen_step5_2'] = 'Click <strong>"Add to THIS Course"</strong>';
$string['tutorial_gen_step5_3'] = 'Wait 10-30 seconds for creation';
$string['tutorial_gen_step5_4'] = 'Success! View your course to see new sections';
$string['tutorial_quality_title'] = 'Tutorial 3: Understanding Quality Scores (5 minutes)';
$string['tutorial_quality_intro'] = 'Quality scores help you understand content reliability and guide your review efforts.';
$string['tutorial_quality_overall'] = 'Overall Score (0-100)';
$string['tutorial_quality_excellent'] = 'Excellent';
$string['tutorial_quality_excellent_desc'] = 'Minimal review needed';
$string['tutorial_quality_good'] = 'Good';
$string['tutorial_quality_good_desc'] = 'Review supplemented parts';
$string['tutorial_quality_fair'] = 'Fair';
$string['tutorial_quality_fair_desc'] = 'Significant review needed';
$string['tutorial_quality_poor'] = 'Poor';
$string['tutorial_quality_poor_desc'] = 'Upload more documents';
$string['tutorial_quality_source'] = 'Source Coverage (%)';
$string['tutorial_quality_source_desc'] = '<strong>What it measures:</strong> Percentage of content directly from your uploaded documents';
$string['tutorial_quality_source_80'] = '<strong>80%+</strong> = Excellent grounding, minimal AI supplementation';
$string['tutorial_quality_source_60'] = '<strong>60-79%</strong> = Good coverage, some gaps filled';
$string['tutorial_quality_source_below'] = '<strong><60%</strong> = Moderate supplementation, careful review needed';
$string['tutorial_quality_source_tip'] = 'Higher coverage = More trustworthy content from YOUR materials';
$string['tutorial_quality_depth'] = 'Learning Depth (0-100)';
$string['tutorial_quality_depth_desc'] = '<strong>What it measures:</strong> Bloom\'s taxonomy level - higher-order thinking';
$string['tutorial_quality_depth_75'] = '<strong>75+</strong> = Deep learning (analysis, evaluation, creation)';
$string['tutorial_quality_depth_50'] = '<strong>50-74</strong> = Moderate (mix of levels)';
$string['tutorial_quality_depth_below'] = '<strong><50</strong> = Surface (memorization focus)';
$string['tutorial_quality_tags'] = 'Page-Level Tags';
$string['tutorial_quality_verified'] = '85%+ from sources<br>Trust with light review';
$string['tutorial_quality_review'] = '70-84% from sources<br>Verify accuracy';
$string['tutorial_quality_priority'] = '<70% from sources<br>Thorough review';
$string['tutorial_quality_supplemented'] = 'AI-added context<br>Verify specifics';
$string['tutorial_quality_tip'] = 'Focus your review time on yellow/red items. Green items need minimal review.';
$string['tutorial_kb_title'] = 'Tutorial 4: Saving to Knowledge Base (3 minutes)';
$string['tutorial_kb_what'] = 'What is the Knowledge Feedback Loop?';
$string['tutorial_kb_what_desc'] = 'After adding generated content to your course, you can save it back to the knowledge base. This creates a virtuous cycle:';
$string['tutorial_kb_step1'] = 'Generate course from documents';
$string['tutorial_kb_step2'] = 'Review and approve content';
$string['tutorial_kb_step3'] = 'Add to your Moodle course';
$string['tutorial_kb_step4'] = '<strong>Save approved course to knowledge base</strong>';
$string['tutorial_kb_step5'] = 'Future courses can now use this approved content as a source!';
$string['tutorial_kb_benefits'] = 'Benefits:';
$string['tutorial_kb_benefit1'] = '✓ Future courses build on vetted content';
$string['tutorial_kb_benefit2'] = '✓ Students can chat with approved course materials';
$string['tutorial_kb_benefit3'] = '✓ Reduced review time (60 min → 40 min for similar courses)';
$string['tutorial_kb_benefit4'] = '✓ Quality improves over time (60% → 85%+ QM scores)';
$string['tutorial_kb_how'] = 'How to Save:';
$string['tutorial_kb_how1'] = 'After clicking "Add to THIS Course"';
$string['tutorial_kb_how2'] = 'Success page shows "Save to Knowledge Base" prompt';
$string['tutorial_kb_how3'] = 'Click <strong>"Save to Knowledge Base"</strong>';
$string['tutorial_kb_how4'] = 'Processing takes 2-3 minutes';
$string['tutorial_kb_how5'] = 'Approved course appears in documents as "[Title] (Instructor Approved)"';
$string['tutorial_kb_grows'] = 'Your knowledge base grows with each approved course!';
$string['tutorial_edit_title'] = 'Tutorial 5: Reviewing and Editing Content';
$string['tutorial_edit_view'] = 'View Content (Read-Only)';
$string['tutorial_edit_view1'] = 'In preview, click <strong>"View"</strong> button on any item';
$string['tutorial_edit_view2'] = 'Modal opens showing full content:
        <ul>
            <li>Pages: Complete 400-800 word content</li>
            <li>Activities: Detailed instructions</li>
            <li>Quizzes: All questions with answers marked</li>
            <li>Assignments: Instructions + rubric table</li>
        </ul>';
$string['tutorial_edit_view3'] = 'Click "Close" when done';
$string['tutorial_edit_heading'] = 'Edit Content';
$string['tutorial_edit_step1'] = 'Click <strong>"Edit"</strong> button';
$string['tutorial_edit_step2'] = 'Modal opens with editable fields:
        <ul>
            <li>Title: Modify if needed</li>
            <li>Content: Full textarea (15 rows)</li>
        </ul>';
$string['tutorial_edit_step3'] = 'Make your changes';
$string['tutorial_edit_step4'] = 'Click <strong>"Save"</strong>';
$string['tutorial_edit_step5'] = 'Changes persist - title updates in preview';
$string['tutorial_edit_step6'] = 'When you add to course, edited version is used';
$string['tutorial_edit_tip'] = 'Focus edits on items with yellow/red quality tags';
$string['tutorial_analytics_title'] = 'Tutorial 6: Learning Analytics - Identify At-Risk Students (NEW)';
$string['tutorial_analytics_what'] = 'What is Learning Analytics?';
$string['tutorial_analytics_what_desc'] = 'AI-powered analysis that identifies students who may be struggling, with personalized intervention recommendations.';
$string['tutorial_analytics_features'] = 'Key Features:';
$string['tutorial_analytics_feat1'] = '<strong>At-Risk Detection</strong>: AI analyzes 40+ metrics per student';
$string['tutorial_analytics_feat2'] = '<strong>Risk Scores</strong>: 0-100 scale with High/Medium/Low classification';
$string['tutorial_analytics_feat3'] = '<strong>Intervention Recommendations</strong>: Personalized suggestions for each student';
$string['tutorial_analytics_feat4'] = '<strong>Course Improvements</strong>: AI suggests ways to improve course design';
$string['tutorial_analytics_feat5'] = '<strong>CSV Export</strong>: Download reports for offline analysis';
$string['tutorial_analytics_access'] = 'How to Access:';
$string['tutorial_analytics_access1'] = 'Navigate to your course';
$string['tutorial_analytics_access2'] = 'Click <strong>"Savian AI"</strong> in course navigation';
$string['tutorial_analytics_access3'] = 'Click <strong>"Learning Analytics"</strong> tab';
$string['tutorial_analytics_access4'] = 'Click <strong>"Generate Analytics Report"</strong>';
$string['tutorial_analytics_access5'] = 'Wait 1-2 minutes for AI analysis';
$string['tutorial_analytics_report'] = 'Understanding the Report:';
$string['tutorial_analytics_high'] = 'High Risk Students (70-100):';
$string['tutorial_analytics_high1'] = 'Immediate intervention recommended';
$string['tutorial_analytics_high2'] = 'Multiple warning indicators present';
$string['tutorial_analytics_high3'] = 'Personal outreach suggested';
$string['tutorial_analytics_medium'] = 'Medium Risk Students (40-69):';
$string['tutorial_analytics_medium1'] = 'Monitor closely';
$string['tutorial_analytics_medium2'] = 'Some concerning patterns detected';
$string['tutorial_analytics_medium3'] = 'Consider proactive support';
$string['tutorial_analytics_low'] = 'Low Risk Students (0-39):';
$string['tutorial_analytics_low1'] = 'On track';
$string['tutorial_analytics_low2'] = 'Continue normal monitoring';
$string['tutorial_analytics_low3'] = 'May still benefit from engagement';
$string['tutorial_analytics_metrics'] = 'Metrics Analyzed:';
$string['tutorial_analytics_engagement'] = '<strong>Engagement</strong><ul><li>Login frequency</li><li>Time on course</li><li>Page views</li><li>Resource access</li></ul>';
$string['tutorial_analytics_performance'] = '<strong>Performance</strong><ul><li>Quiz scores</li><li>Assignment grades</li><li>Grade trends</li><li>Completion rate</li></ul>';
$string['tutorial_analytics_participation'] = '<strong>Participation</strong><ul><li>Forum posts</li><li>Discussion replies</li><li>Peer interactions</li><li>Group work</li></ul>';
$string['tutorial_analytics_patterns'] = '<strong>Patterns</strong><ul><li>Submission timing</li><li>Late submissions</li><li>Activity gaps</li><li>Drop-off points</li></ul>';
$string['tutorial_analytics_interventions'] = 'Intervention Recommendations:';
$string['tutorial_analytics_interventions_desc'] = 'For each at-risk student, AI provides specific recommendations:';
$string['tutorial_analytics_int_comm'] = '<strong>Communication</strong>: Email templates, meeting suggestions';
$string['tutorial_analytics_int_resources'] = '<strong>Resources</strong>: Additional materials, tutorials';
$string['tutorial_analytics_int_deadlines'] = '<strong>Deadlines</strong>: Extension recommendations';
$string['tutorial_analytics_int_support'] = '<strong>Support</strong>: Peer tutoring, study groups';
$string['tutorial_analytics_export'] = 'Export Reports:';
$string['tutorial_analytics_export1'] = 'Click <strong>"Export CSV"</strong> button';
$string['tutorial_analytics_export2'] = 'Download includes: Student ID, Risk Score, Contributing Factors, Recommendations';
$string['tutorial_analytics_export3'] = 'Use for offline analysis or sharing with advisors';
$string['tutorial_analytics_privacy'] = 'Student data is anonymized with SHA256 before AI processing. Full GDPR compliance with data export/deletion support.';
$string['tutorial_analytics_tip'] = 'Run analytics weekly to catch at-risk students early. Early intervention = better outcomes!';
$string['tutorial_chathistory_title'] = 'Tutorial 7: Monitoring Student Chat Conversations';
$string['tutorial_chathistory_why'] = 'Why Monitor Chat History?';
$string['tutorial_chathistory_why1'] = 'Understand what concepts students struggle with';
$string['tutorial_chathistory_why2'] = 'Identify common questions to address in class';
$string['tutorial_chathistory_why3'] = 'Spot students who need extra help';
$string['tutorial_chathistory_why4'] = 'Improve course materials based on questions';
$string['tutorial_chathistory_access'] = 'How to Access:';
$string['tutorial_chathistory_access1'] = 'Navigate to your course';
$string['tutorial_chathistory_access2'] = 'Click <strong>"Savian AI"</strong> in course navigation';
$string['tutorial_chathistory_access3'] = 'Click <strong>"Chat History"</strong>';
$string['tutorial_chathistory_access4'] = 'Filter by student, date range, or keyword';
$string['tutorial_chathistory_see'] = 'What You Can See:';
$string['tutorial_chathistory_see1'] = 'All student questions and AI responses';
$string['tutorial_chathistory_see2'] = 'Feedback ratings (thumbs up/down)';
$string['tutorial_chathistory_see3'] = 'Conversation timestamps';
$string['tutorial_chathistory_see4'] = 'Sources used in responses';
$string['tutorial_chathistory_tip'] = 'Review negative feedback (thumbs down) to identify areas where the AI or course materials need improvement.';

// Tutorial strings - Student.
$string['tutorial_student_title'] = 'Student Tutorials';
$string['tutorial_student_guide'] = 'Using Your AI Tutor - Complete Guide';
$string['tutorial_student_find'] = 'Finding the Chat';
$string['tutorial_student_find1'] = 'Go to any course';
$string['tutorial_student_find2'] = 'Look for purple chat bubble in <strong>bottom-right corner</strong>';
$string['tutorial_student_find3'] = 'Click to open';
$string['tutorial_student_asking'] = 'Asking Questions';
$string['tutorial_student_good'] = '<strong>Good questions to ask:</strong>';
$string['tutorial_student_good1'] = '"What is [concept]?"';
$string['tutorial_student_good2'] = '"Explain the difference between X and Y"';
$string['tutorial_student_good3'] = '"How do I apply this concept?"';
$string['tutorial_student_good4'] = '"Summarize Week 2 content"';
$string['tutorial_student_good5'] = '"What are the steps to implement Z?"';
$string['tutorial_student_notfor'] = '<strong>NOT for:</strong>';
$string['tutorial_student_notfor1'] = 'Homework answers (use for understanding, not solutions)';
$string['tutorial_student_notfor2'] = 'Quiz/test answers (learning tool only)';
$string['tutorial_student_notfor3'] = 'Personal information';
$string['tutorial_student_responses'] = 'Understanding Responses';
$string['tutorial_student_resp1'] = '<strong>Sources shown</strong>: Every answer includes which documents/pages were used';
$string['tutorial_student_resp2'] = '<strong>Click sources</strong>: See exactly where information came from';
$string['tutorial_student_resp3'] = '<strong>Verify</strong>: Cross-check important information';
$string['tutorial_student_feedback'] = 'Providing Feedback';
$string['tutorial_student_fb1'] = '<strong>Thumbs up</strong>: Answer was helpful';
$string['tutorial_student_fb2'] = '<strong>Thumbs down</strong>: Not helpful or inaccurate';
$string['tutorial_student_fb3'] = 'Your feedback improves the AI over time';
$string['tutorial_student_privacy'] = 'Your Privacy';
$string['tutorial_student_priv1'] = 'Your conversations are private';
$string['tutorial_student_priv2'] = 'Teachers can view for learning support';
$string['tutorial_student_priv3'] = 'Not shared with other students';
$string['tutorial_student_priv4'] = 'You can request data export or deletion';
$string['tutorial_student_best'] = 'Best Practices';
$string['tutorial_student_best1'] = 'Be specific in your questions';
$string['tutorial_student_best2'] = 'Ask one question at a time';
$string['tutorial_student_best3'] = 'Provide context if needed';
$string['tutorial_student_best4'] = 'Check the sources provided';
$string['tutorial_student_best5'] = 'Use for concept clarification and learning';
$string['tutorial_student_wise'] = 'The AI tutor is here to help you learn, not to do your work for you. Use it wisely!';
$string['tutorial_faq'] = 'Frequently Asked Questions';
$string['tutorial_faq_homework_q'] = 'Q: Can the AI do my homework?';
$string['tutorial_faq_homework_a'] = 'A: No. The AI is a learning tool to help you understand concepts, not to provide answers to assignments. Use it to clarify understanding, then do the work yourself.';
$string['tutorial_faq_private_q'] = 'Q: Are my chats private?';
$string['tutorial_faq_private_a'] = 'A: Yes. Your conversations are private to you. Teachers can view them for learning support, but they\'re not shared with other students.';
$string['tutorial_faq_source_q'] = 'Q: Where does the AI get its answers?';
$string['tutorial_faq_source_a'] = 'A: From your course materials - uploaded documents, course pages, and approved content. Sources are shown with each answer.';
$string['tutorial_faq_wrong_q'] = 'Q: What if the AI gives a wrong answer?';
$string['tutorial_faq_wrong_a'] = 'A: Use the thumbs down button and let your teacher know. Always verify important information against course materials.';

// Ad-hoc task.
$string['task_send_analytics_adhoc'] = 'Send analytics report (ad-hoc)';
