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
$string['savian_ai:use'] = 'Sử dụng tính năng Savian AI';
$string['savian_ai:manage'] = 'Quản lý cài đặt Savian AI';
$string['savian_ai:generate'] = 'Tạo nội dung với Savian AI';

// Settings
$string['settings_heading'] = 'Cấu hình Savian AI';
$string['api_url'] = 'URL API';
$string['api_url_desc'] = 'Địa chỉ API của Savian AI (ví dụ: https://app.savian.ai.vn/api/moodle/v1/)';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'Khóa API Savian AI của bạn (định dạng: moodle_orgcode_xxx)';
$string['org_code'] = 'Mã Tổ chức';
$string['org_code_desc'] = 'Mã định danh tổ chức của bạn';
$string['validate_connection'] = 'Kiểm tra Kết nối';
$string['connection_valid'] = 'Kết nối thành công! Tổ chức: {$a}';
$string['connection_invalid'] = 'Kết nối thất bại: {$a}';

// Navigation
$string['dashboard'] = 'Bảng điều khiển';
$string['documents'] = 'Tài liệu';
$string['generate'] = 'Tạo Câu hỏi';
$string['usage'] = 'Thống kê Sử dụng';
$string['chat'] = 'Hỏi đáp Tài liệu';

// Documents page
$string['upload_document'] = 'Tải lên Tài liệu';
$string['document_title'] = 'Tiêu đề';
$string['document_description'] = 'Mô tả';
$string['document_subject'] = 'Lĩnh vực';
$string['document_tags'] = 'Thẻ';
$string['document_tags_help'] = 'Các thẻ cách nhau bằng dấu phẩy';
$string['document_uploaded'] = 'Tài liệu đã được tải lên thành công';
$string['document_upload_failed'] = 'Tải lên tài liệu thất bại: {$a}';
$string['document_list'] = 'Danh sách Tài liệu';
$string['document_status'] = 'Trạng thái';
$string['document_chunks'] = 'Phân đoạn';
$string['document_size'] = 'Kích thước';
$string['document_delete'] = 'Xóa';
$string['document_deleted'] = 'Đã xóa tài liệu thành công';
$string['document_delete_failed'] = 'Xóa tài liệu thất bại: {$a}';
$string['document_reprocess'] = 'Xử lý lại';
$string['status_ready'] = 'Sẵn sàng';
$string['status_uploading'] = 'Đang tải lên...';
$string['status_processing_simple'] = 'Đang xử lý...';
$string['status_failed'] = 'Thất bại';
$string['no_documents'] = 'Chưa có tài liệu nào';
$string['auto_refresh_notice'] = 'Trang sẽ tự động làm mới sau 30 giây';

// Generate page
$string['generate_questions'] = 'Tạo Câu hỏi';
$string['generate_from_topic'] = 'Từ Chủ đề';
$string['generate_from_documents'] = 'Từ Tài liệu (RAG)';
$string['topic'] = 'Chủ đề';
$string['select_documents'] = 'Chọn Tài liệu';
$string['question_count'] = 'Số lượng Câu hỏi';
$string['difficulty'] = 'Độ khó';
$string['bloom_level'] = 'Mức độ Bloom';
$string['question_types'] = 'Loại Câu hỏi';
$string['learning_objectives'] = 'Mục tiêu Học tập';
$string['difficulty_easy'] = 'Dễ';
$string['difficulty_medium'] = 'Trung bình';
$string['difficulty_hard'] = 'Khó';

// Bloom's taxonomy
$string['bloom_remember'] = 'Nhớ';
$string['bloom_understand'] = 'Hiểu';
$string['bloom_apply'] = 'Áp dụng';
$string['bloom_analyze'] = 'Phân tích';
$string['bloom_evaluate'] = 'Đánh giá';
$string['bloom_create'] = 'Sáng tạo';

// Question types
$string['qtype_multichoice'] = 'Trắc nghiệm';
$string['qtype_truefalse'] = 'Đúng/Sai';
$string['qtype_shortanswer'] = 'Câu trả lời ngắn';
$string['qtype_essay'] = 'Tự luận';
$string['qtype_matching'] = 'Nối';

// Generation results
$string['generation_complete'] = 'Tạo hoàn tất!';
$string['questions_generated'] = 'Đã tạo {$a} câu hỏi';
$string['add_to_question_bank'] = 'Thêm vào Ngân hàng Câu hỏi';
$string['questions_added'] = 'Đã thêm {$a} câu hỏi vào ngân hàng câu hỏi';
$string['questions_failed'] = '{$a} câu hỏi thất bại';

// Usage statistics
$string['usage_stats'] = 'Thống kê Sử dụng';
$string['questions_generated_total'] = 'Tổng số Câu hỏi Tạo ra';
$string['documents_processed'] = 'Tài liệu Đã xử lý';
$string['api_calls'] = 'Số lần Gọi API';
$string['quota_remaining'] = 'Hạn mức Còn lại';

// Course generation
$string['generate_course_content'] = 'Tạo Nội dung Khóa học';
$string['course_title'] = 'Tiêu đề Khóa học';
$string['target_course'] = 'Khóa học Đích';
$string['duration_weeks'] = 'Thời lượng (tuần)';
$string['preview_course_structure'] = 'Xem trước Cấu trúc Khóa học';
$string['section_content'] = 'Nội dung';
$string['add_to_this_course'] = 'Thêm vào Khóa học NÀY';
$string['regenerate'] = 'Tạo lại';

// Enhanced course generation
$string['target_audience'] = 'Đối tượng Học viên';
$string['target_audience_help'] = 'Khóa học này được thiết kế cho ai? (ví dụ: Sinh viên năm nhất, Lập trình viên mới)';
$string['target_audience_placeholder'] = 'Ví dụ: Sinh viên y khoa năm nhất, Lập trình viên mới';
$string['content_types_select'] = 'Chọn Loại Nội dung';
$string['content_types_help'] = 'Chọn các loại nội dung cần tạo';
$string['content_type_sections'] = 'Chương Khóa học';
$string['content_type_pages'] = 'Trang Giảng dạy (400-600 từ)';
$string['content_type_activities'] = 'Hoạt động Thực hành';
$string['content_type_discussions'] = 'Diễn đàn Thảo luận';
$string['content_type_quizzes'] = 'Bài Kiểm tra';
$string['content_type_assignments'] = 'Bài Tập với Rubric';
$string['content_type_formative'] = 'Câu hỏi Tự kiểm tra';

// Progress tracking
$string['generating_course_content'] = 'Đang Tạo Nội dung Khóa học';
$string['progress_analyzing'] = 'Đang phân tích tài liệu và tạo đề cương...';
$string['progress_outline_complete'] = 'Đề cương hoàn tất, đang tạo các chương...';
$string['progress_generating_section'] = 'Đang tạo nội dung chương {$a}...';
$string['progress_sections_complete'] = 'Tất cả chương đã được tạo, đang hoàn thiện...';
$string['progress_finalizing'] = 'Đang thêm bảng thuật ngữ và hoàn thiện...';
$string['progress_complete'] = 'Tạo khóa học hoàn tất!';
$string['progress_unknown'] = 'Đang xử lý...';
$string['estimated_time'] = 'Thời gian ước tính: {$a} phút';
$string['estimated_time_4weeks'] = 'Thời gian ước tính: 3-5 phút';
$string['estimated_time_8weeks'] = 'Thời gian ước tính: 5-8 phút';
$string['estimated_time_12weeks'] = 'Thời gian ước tính: 8-12 phút';
$string['cancel_generation'] = 'Hủy Tạo';

// Preview
$string['content_summary'] = 'Tóm tắt Nội dung';
$string['summary_sections'] = 'Chương';
$string['summary_pages'] = 'Trang';
$string['summary_activities'] = 'Hoạt động';
$string['summary_discussions'] = 'Thảo luận';
$string['summary_quizzes'] = 'Bài kiểm tra';
$string['summary_assignments'] = 'Bài tập';
$string['include_item'] = 'Bao gồm mục này';
$string['edit_item'] = 'Chỉnh sửa';
$string['expand_all'] = 'Mở rộng Tất cả';
$string['collapse_all'] = 'Thu gọn Tất cả';
$string['add_selected'] = 'Thêm Đã chọn vào Khóa học';
$string['will_create'] = 'Sẽ tạo';

// Content created
$string['content_created_success'] = 'Nội dung khóa học đã được tạo thành công';
$string['content_created_details'] = 'Đã tạo: {$a->sections} chương, {$a->pages} trang, {$a->activities} hoạt động, {$a->discussions} thảo luận, {$a->quizzes} bài kiểm tra, {$a->assignments} bài tập';
$string['content_created_simple'] = 'Nội dung khóa học đã tạo: {$a->sections} chương, {$a->pages} trang, {$a->quizzes} bài kiểm tra, {$a->assignments} bài tập';
$string['content_created_with_errors'] = 'Nội dung khóa học đã tạo với {$a} lỗi';

// Generation errors
$string['generation_failed'] = 'Tạo thất bại: {$a}';
$string['generation_timeout'] = 'Hết thời gian chờ sau {$a} giây';
$string['no_documents_selected'] = 'Chưa chọn tài liệu. Vui lòng chọn ít nhất một tài liệu.';
$string['no_title_provided'] = 'Chưa cung cấp tiêu đề khóa học';

// Based on documents
$string['based_on_documents'] = 'Dựa trên tài liệu';
$string['chunks_used'] = '{$a} đoạn đã sử dụng';

// ADDIE v2.0 - Age/Industry Adaptation
$string['age_group'] = 'Nhóm Tuổi Học viên';
$string['age_group_help'] = 'Điều chỉnh từ vựng, mức độ đọc và phương pháp giảng dạy theo độ tuổi';
$string['industry'] = 'Ngữ cảnh Ngành nghề';
$string['industry_help'] = 'Tùy chỉnh thuật ngữ, ví dụ và yêu cầu tuân thủ';
$string['prior_knowledge'] = 'Mức độ Kiến thức Nền';
$string['prior_knowledge_help'] = 'Điều chỉnh độ khó nội dung dựa trên kiến thức của học viên';

// ADDIE progress stages
$string['progress_addie_analysis'] = 'Đang phân tích hồ sơ học viên và ngữ cảnh...';
$string['progress_addie_design_outline'] = 'Đang thiết kế cấu trúc khóa học...';
$string['progress_addie_design_completed'] = 'Đề cương khóa học đã sẵn sàng ✓';
$string['progress_addie_development'] = 'Đang tạo nội dung...';
$string['progress_addie_development_completed'] = 'Tất cả chương đã được tạo ✓';
$string['progress_addie_implementation'] = 'Đang thêm chỉ số chất lượng...';
$string['progress_addie_evaluation'] = 'Đang tính điểm chất lượng...';
$string['progress_addie_completed'] = 'Khóa học sẵn sàng! ✓';

// Quality Matters
$string['qm_alignment'] = 'Tuân thủ Quality Matters';
$string['qm_score'] = 'Điểm QM';
$string['qm_certified_ready'] = 'Sẵn sàng Chứng nhận QM';
$string['qm_below_threshold'] = 'Dưới ngưỡng chứng nhận QM';
$string['qm_recommendations'] = 'Khuyến nghị QM';
$string['qm_standards_met'] = '{$a->met} trên {$a->total} tiêu chuẩn đạt';
$string['verify_media_accessibility'] = 'Xác minh tất cả phương tiện có tính năng hỗ trợ tiếp cận';

// Pedagogical metadata
$string['pedagogical_metadata'] = 'Thông số Khóa học';
$string['source_confidence'] = 'Độ tin cậy Nguồn';
$string['ai_transparency'] = 'Thông báo Nội dung do AI Tạo';
$string['designed_for'] = 'Thiết kế cho';
$string['subject_area'] = 'Lĩnh vực Chủ đề';
$string['content_level'] = 'Mức độ Nội dung';
$string['instructional_approach'] = 'Phương pháp Giảng dạy';
$string['thinking_skills'] = 'Kỹ năng Tư duy';
$string['generation_method'] = 'Phương pháp Tạo';
$string['human_review_required'] = 'Yêu cầu Xem xét của Giáo viên';

// Section metadata
$string['prerequisites'] = 'Kiến thức Tiên quyết';
$string['estimated_hours'] = '{$a} giờ';
$string['qm_notes'] = 'Ghi chú QM';
$string['source_documents_used'] = 'Tài liệu Nguồn';

// Privacy API
$string['privacy:metadata:conversations'] = 'Cuộc trò chuyện với AI gia sư';
$string['privacy:metadata:conversations:user_id'] = 'Người dùng tạo cuộc trò chuyện';
$string['privacy:metadata:conversations:course_id'] = 'Ngữ cảnh khóa học cho cuộc trò chuyện';
$string['privacy:metadata:conversations:title'] = 'Tiêu đề cuộc trò chuyện';
$string['privacy:metadata:conversations:timecreated'] = 'Thời điểm tạo';
$string['privacy:metadata:messages'] = 'Tin nhắn trò chuyện (người dùng và phản hồi AI)';
$string['privacy:metadata:messages:conversation_id'] = 'Cuộc trò chuyện cha';
$string['privacy:metadata:messages:role'] = 'Người gửi tin nhắn';
$string['privacy:metadata:messages:content'] = 'Nội dung tin nhắn';
$string['privacy:metadata:messages:feedback'] = 'Phản hồi của người dùng về câu trả lời AI';
$string['privacy:metadata:messages:feedback_comment'] = 'Nhận xét phản hồi';
$string['privacy:metadata:messages:timecreated'] = 'Thời điểm gửi';
$string['privacy:metadata:settings'] = 'Tùy chọn widget trò chuyện';
$string['privacy:metadata:settings:user_id'] = 'ID người dùng';
$string['privacy:metadata:settings:widget_position'] = 'Vị trí widget';
$string['privacy:metadata:settings:widget_minimized'] = 'Trạng thái thu nhỏ';
$string['privacy:metadata:generations'] = 'Lịch sử yêu cầu tạo nội dung AI';
$string['privacy:metadata:generations:user_id'] = 'Người yêu cầu tạo';
$string['privacy:metadata:generations:course_id'] = 'Khóa học đích';
$string['privacy:metadata:generations:generation_type'] = 'Loại nội dung được tạo';
$string['privacy:metadata:generations:status'] = 'Trạng thái tạo';
$string['privacy:metadata:generations:timecreated'] = 'Thời điểm yêu cầu';
$string['privacy:metadata:external'] = 'Dịch vụ Bên ngoài Savian AI';
$string['privacy:metadata:external:user_id'] = 'ID người dùng gửi đến dịch vụ AI';
$string['privacy:metadata:external:user_email'] = 'Email người dùng cho ngữ cảnh';
$string['privacy:metadata:external:course_id'] = 'ID khóa học';
$string['privacy:metadata:external:chat_message'] = 'Tin nhắn trò chuyện gửi đến AI';
$string['privacy:metadata:external:document_content'] = 'Nội dung tài liệu để xử lý';
$string['privacy:chatdata'] = 'Cuộc trò chuyện';
$string['privacy:chatsettings'] = 'Cài đặt Trò chuyện';
$string['privacy:generationdata'] = 'Yêu cầu Tạo nội dung';

// Knowledge Feedback Loop
$string['save_to_knowledge_base'] = 'Lưu vào Cơ sở Tri thức';
$string['knowledge_feedback_loop'] = 'Vòng lặp Phản hồi Tri thức';
$string['build_knowledge_base'] = 'Xây dựng cơ sở tri thức của tổ chức!';
$string['save_benefits'] = 'Lợi ích khi lưu khóa học đã phê duyệt:';
$string['benefit_future_courses'] = 'Các khóa học tương lai có thể dựa trên nội dung đã phê duyệt này';
$string['benefit_student_chat'] = 'Sinh viên có thể trò chuyện với tài liệu khóa học này';
$string['benefit_reduce_review'] = 'Giảm thời gian xem xét cho các khóa học tương tự';
$string['benefit_preserve_expertise'] = 'Bảo tồn chuyên môn giảng dạy của bạn';
$string['processing_time_kb'] = 'Thời gian xử lý: 2-3 phút';
$string['skip_and_continue'] = 'Bỏ qua và Đến Khóa học';
$string['course_saved_kb'] = 'Khóa học Đã lưu vào Cơ sở Tri thức!';
$string['kb_save_success'] = 'Nội dung khóa học đã phê duyệt của bạn đã được lưu và đang được xử lý';
$string['kb_save_failed'] = 'Không thể lưu khóa học vào cơ sở tri thức';
$string['no_course_data'] = 'Không tìm thấy dữ liệu khóa học. Vui lòng tạo khóa học trước.';
$string['what_happens_next'] = 'Điều gì xảy ra tiếp theo';
$string['kb_processing'] = 'Xử lý: 2-3 phút (phân đoạn và nhúng)';
$string['kb_availability'] = 'Sẽ xuất hiện trong danh sách tài liệu như khóa học đã phê duyệt';
$string['kb_usage'] = 'Các khóa học tương lai có thể sử dụng nội dung này';
$string['kb_chat'] = 'Sinh viên có thể đặt câu hỏi về khóa học này';

// Quality Control (v2.1)
$string['quality_report'] = 'Báo cáo Chất lượng Khóa học';
$string['overall_score'] = 'Điểm Tổng thể';
$string['source_coverage'] = 'Độ Bao phủ Nguồn';
$string['learning_depth'] = 'Độ Sâu Học tập';
$string['priority_reviews'] = 'Tập trung Xem xét';
$string['recommended_review_time'] = 'Thời gian xem xét ước tính';
$string['quality_verified'] = 'Đã xác minh';
$string['quality_review'] = 'Khuyến nghị Xem xét';
$string['quality_priority'] = 'Ưu tiên Xem xét';
$string['quality_supplemented'] = 'Nội dung Bổ sung';
$string['high_confidence'] = 'Độ tin cậy cao - dựa vững trên nguồn';
$string['medium_confidence'] = 'Độ tin cậy trung bình - khuyến nghị xem xét';
$string['low_confidence'] = 'Độ tin cậy thấp - cần xem xét ưu tiên';
$string['supplemented_note'] = 'Bao gồm nội dung bổ sung AI - xác minh theo ngữ cảnh của bạn';

// Chat widget
$string['chat'] = 'Trò chuyện AI';
$string['openchat'] = 'Mở trò chuyện';
$string['closechat'] = 'Đóng trò chuyện';
$string['minimize'] = 'Thu nhỏ';
$string['maximize'] = 'Phóng to toàn màn hình';
$string['send'] = 'Gửi';
$string['type_message'] = 'Nhập tin nhắn...';
$string['default_welcome_message'] = 'Xin chào! Tôi là gia sư AI của bạn. Hỏi tôi bất cứ điều gì về tài liệu khóa học.';
$string['new_conversation'] = 'Cuộc trò chuyện Mới';
$string['no_messages'] = 'Chưa có tin nhắn';
$string['ai_is_typing'] = 'AI đang nhập...';
$string['source_documents'] = 'Tài liệu Nguồn';
$string['helpful'] = 'Hữu ích';
$string['not_helpful'] = 'Không hữu ích';
$string['feedback_submitted'] = 'Cảm ơn phản hồi của bạn!';

// Chat settings
$string['enable_chat_widget'] = 'Kích hoạt widget trò chuyện';
$string['enable_chat_widget_desc'] = 'Hiển thị bong bóng trò chuyện nổi trên các trang khóa học';
$string['chat_course_pages_only'] = 'Chỉ các trang khóa học';
$string['chat_course_pages_only_desc'] = 'Chỉ hiển thị trò chuyện trên các trang khóa học (không phải trang quản trị)';
$string['chat_welcome_message'] = 'Tin nhắn Chào mừng';
$string['chat_welcome_message_desc'] = 'Tin nhắn mặc định khi bắt đầu cuộc trò chuyện mới';
$string['chat_primary_color'] = 'Màu Chính';
$string['chat_default_position'] = 'Vị trí Mặc định';
$string['chat_widget_size'] = 'Kích thước Widget';

// Chat history
$string['chat_history'] = 'Lịch sử Trò chuyện';
$string['conversation_list'] = 'Danh sách Cuộc trò chuyện';
$string['conversation_title'] = 'Tiêu đề';
$string['message_count'] = 'Tổng số Tin nhắn';
$string['avg_messages_per_conv'] = 'Trung bình Tin nhắn/Cuộc trò chuyện';
$string['unique_users'] = 'Người dùng Duy nhất';
$string['feedback_stats'] = 'Thống kê Phản hồi';
$string['positive_feedback'] = 'Tích cực';
$string['negative_feedback'] = 'Tiêu cực';
$string['feedback_rate'] = 'Tỷ lệ Phản hồi';
$string['user_engagement'] = 'Tương tác Người dùng';
$string['last_active'] = 'Hoạt động Gần nhất';
$string['view_conversation'] = 'Xem';
$string['archive_conversation'] = 'Lưu trữ';
$string['export_data'] = 'Xuất Dữ liệu';

// Capabilities
$string['savian_ai:viewchathistory'] = 'Xem lịch sử cuộc trò chuyện';
$string['savian_ai:managechatdocuments'] = 'Quản lý tài liệu trong trò chuyện';
