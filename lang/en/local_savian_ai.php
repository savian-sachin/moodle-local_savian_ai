<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

// Plugin name
$string['pluginname'] = 'Savian AI';

// Capabilities
$string['savian_ai:use'] = 'Use Savian AI features';
$string['savian_ai:manage'] = 'Manage Savian AI settings';
$string['savian_ai:generate'] = 'Generate content with Savian AI';

// Settings
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

// Navigation
$string['dashboard'] = 'Dashboard';
$string['documents'] = 'Documents';
$string['generate'] = 'Generate Questions';
$string['usage'] = 'Usage Statistics';
$string['chat'] = 'Document Q&A';

// Documents page
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
// Simplified status labels for end users
$string['status_ready'] = 'Ready';
$string['status_uploading'] = 'Uploading...';
$string['status_processing_simple'] = 'Processing...';
$string['status_failed'] = 'Failed';

// Technical status labels (kept for backward compatibility)
$string['status_pending'] = 'Pending';
$string['status_processing'] = 'Processing';
$string['status_embedding'] = 'Generating Embeddings';
$string['status_generating_questions'] = 'Generating Questions';
$string['status_generating_qnas'] = 'Generating Q&As';
$string['status_completed'] = 'Completed';
$string['no_documents'] = 'No documents uploaded yet';
$string['auto_refresh_notice'] = 'This page will automatically refresh in 30 seconds to update processing status';

// Question generation
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

// Question types
$string['qtype_multichoice'] = 'Multiple Choice';
$string['qtype_truefalse'] = 'True/False';
$string['qtype_shortanswer'] = 'Short Answer';
$string['qtype_essay'] = 'Essay';
$string['qtype_matching'] = 'Matching';

// Usage statistics
$string['quota_heading'] = 'Monthly Quota Usage';
$string['quota_questions'] = 'Questions';
$string['quota_documents'] = 'Documents';
$string['quota_course_content'] = 'Course Content';
$string['quota_used'] = 'Used';
$string['quota_limit'] = 'Limit';
$string['quota_remaining'] = 'Remaining';
$string['billing_period'] = 'Billing Period';
$string['days_remaining'] = '{$a} days remaining';

// Errors
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

// Chat widget
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

// Chat settings
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

// Chat history and monitoring
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

// Course-level chat settings
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

// Capabilities
$string['savian_ai:use'] = 'Use Savian AI features';
$string['savian_ai:generate'] = 'Generate content with Savian AI';
$string['savian_ai:manage'] = 'Manage Savian AI settings';
$string['savian_ai:viewchathistory'] = 'View conversation history';
$string['savian_ai:managechatdocuments'] = 'Manage documents in chat';

// Enhanced course generation
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

// Progress tracking
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

// Preview
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

// Content created
$string['content_created_success'] = 'Course content created successfully';
$string['content_created_details'] = 'Created: {$a->sections} sections, {$a->pages} pages, {$a->activities} activities, {$a->discussions} discussions, {$a->quizzes} quizzes, {$a->assignments} assignments';
$string['content_created_simple'] = 'Course content created: {$a->sections} sections, {$a->pages} pages, {$a->quizzes} quizzes, {$a->assignments} assignments';
$string['content_created_with_errors'] = 'Course content created with {$a} errors';

// Generation errors
$string['generation_failed'] = 'Generation failed: {$a}';
$string['generation_timeout'] = 'Generation timeout after {$a} seconds';
$string['no_documents_selected'] = 'No documents selected. Please select at least one document.';
$string['no_title_provided'] = 'No course title provided';

// Based on documents
$string['based_on_documents'] = 'Based on documents';
$string['chunks_used'] = '{$a} chunks used';

// ADDIE v2.0 - Age/Industry Adaptation
$string['age_group'] = 'Target Learner Age Group';
$string['age_group_help'] = 'Adapts vocabulary, reading level, and pedagogy to learner age';
$string['industry'] = 'Industry Context';
$string['industry_help'] = 'Customizes terminology, examples, and compliance requirements';
$string['prior_knowledge'] = 'Prior Knowledge Level';
$string['prior_knowledge_help'] = 'Adjusts content difficulty based on learner background';

// ADDIE progress stages
$string['progress_addie_analysis'] = 'Analyzing learner profile and context...';
$string['progress_addie_design_outline'] = 'Designing course structure...';
$string['progress_addie_design_completed'] = 'Course outline ready ✓';
$string['progress_addie_development'] = 'Generating content...';
$string['progress_addie_development_completed'] = 'All sections generated ✓';
$string['progress_addie_implementation'] = 'Adding quality markers...';
$string['progress_addie_evaluation'] = 'Calculating quality scores...';
$string['progress_addie_completed'] = 'Course ready! ✓';

// Quality Matters
$string['qm_alignment'] = 'Quality Matters Alignment';
$string['qm_score'] = 'QM Score';
$string['qm_certified_ready'] = 'QM Certification Ready';
$string['qm_below_threshold'] = 'Below QM certification threshold';
$string['qm_recommendations'] = 'QM Recommendations';
$string['qm_standards_met'] = '{$a->met} of {$a->total} standards met';
$string['verify_media_accessibility'] = 'Verify all media has accessibility features';

// Quality markers
$string['pedagogical_metadata'] = 'Course Specifications';
$string['source_confidence'] = 'Source Confidence';
$string['ai_transparency'] = 'AI-Generated Content Notice';

// Pedagogical metadata fields (ADDIE v2.0 updated)
$string['designed_for'] = 'Designed For';
$string['subject_area'] = 'Subject Area';
$string['content_level'] = 'Content Level';
$string['instructional_approach'] = 'Instructional Approach';
$string['thinking_skills'] = 'Thinking Skills';
$string['generation_method'] = 'Generation Method';
$string['human_review_required'] = 'Human Review Required';

// Section metadata
$string['prerequisites'] = 'Prerequisites';
$string['estimated_hours'] = '{$a} hours';
$string['qm_notes'] = 'QM Notes';
$string['source_documents_used'] = 'Source Documents';

// Content types
$string['content_type_formative'] = 'Self-Check Questions (Formative)';

// Privacy API
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

// Knowledge Feedback Loop (v2.2)
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

// Tutorials
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

// ADDIE v2.1: Quality Control
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

// Chat Restrictions (v1.0.2)
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

// Learning Analytics (v1.1.0)
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

// Scheduled Tasks
$string['send_analytics_daily'] = 'Send Daily Analytics Reports';
$string['send_analytics_weekly'] = 'Send Weekly Analytics Reports';
$string['cleanup_old_analytics'] = 'Cleanup Old Analytics Data';

// Admin Settings - Analytics
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

// Privacy API - Analytics
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
