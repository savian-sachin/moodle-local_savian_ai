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
$string['api_url_desc'] = 'Savian AI API endpoint (e.g., https://api.savian.ai/moodle/v1/)';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'Your Savian AI API key (format: moodle_orgcode_xxx)';
$string['org_code'] = 'Organization Code';
$string['org_code_desc'] = 'Your organization code';
$string['validate_connection'] = 'Validate Connection';
$string['connection_valid'] = 'Connection successful! Organization: {$a}';
$string['connection_invalid'] = 'Connection failed: {$a}';

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
