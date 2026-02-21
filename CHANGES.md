# Savian AI Moodle Plugin - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.0] - 2026-02-21 - Writing Practice & GDPR Data Minimisation

### Added
- **AI Writing Practice**: Students submit written work in Moodle and receive instant AI feedback with CEFR/IELTS scoring, grammar highlights, and an improved version of their text — integrated with the Moodle gradebook
- **Legal & Procurement documentation**: New section in README for universities covering DPA requests, GDPR compliance summary, and procurement steps

### Changed
- **Free trial extended to 30 days** (was 14 days)

### Security
- **GDPR data minimisation**: `user_email` is no longer sent to the external Savian AI API in chat messages or writing submissions — user session is tracked by numeric user ID only
- Privacy provider and language strings updated to reflect that email is no longer transmitted

### Upgrade Notes
- **New database tables**: `local_savian_ai_writing_tasks` and `local_savian_ai_writing_submissions` — created automatically on upgrade
- No configuration changes required

---

## [1.2.0] - 2026-02-13 - Plugin Review Compliance

### Added
- **GitHub Actions CI**: Automated testing with moodle-plugin-ci (linting, codechecker, PHPUnit, Behat, Grunt)
- **Ad-hoc Task**: `send_analytics_adhoc` task for non-blocking analytics event processing
- **Cache Definitions**: `db/caches.php` with `MODE_SESSION` cache for session data
- **AMD Module**: `tutorial_search.js` for tutorials page search functionality

### Changed
- **CSS Namespacing**: All selectors prefixed with `savian-` (e.g. `.chat-message` → `.savian-chat-message`) to avoid theme conflicts
- **API Client**: Replaced direct `curl_init` calls with Moodle's `\curl` class from `lib/filelib.php` — proper proxy support and file uploads
- **Privacy Provider**: Added `core_userlist_provider` interface, corrected method signatures
- **JS Loading**: CDN scripts (highlight.js, MathJax) loaded via `$PAGE->requires->js()` instead of raw script tags
- **Language Strings**: Moved ~180 hard-coded strings from `tutorials.php` to `lang/en/local_savian_ai.php`
- **Function Namespacing**: Global functions renamed with `local_savian_ai_` frankenstyle prefix (e.g. `show_overview()` → `local_savian_ai_show_overview()`)
- **Event Triggering**: `course_builder.php` now fires `course_module_created` events after creating pages, quizzes, assignments, and forums
- **Observer Refactor**: Analytics observer queues ad-hoc tasks instead of inline processing — no more sleep/retry blocking
- **Session Management**: Migrated all `$SESSION->savian_*` usage to Moodle Cache API (`MODE_SESSION`)

### Fixed
- **Performance**: Added 90-day time-range cap and `LIMIT 10000` on raw log queries in `data_extractor.php`
- **File Uploads**: Upload document button now works correctly (was broken due to raw curl usage)

---

## [1.1.1] - 2026-02-04 - Document Sync & Tutorials Update

### Added
- **Connection Status Indicator**: Settings page now shows API connection status
  - ✅ Connected (shows organization name)
  - ❌ Failed (shows error message)
  - ⚠️ Not Configured (prompts for credentials)
- **Document Sync Improvements**:
  - Sync documents on dashboard (index.php) visit
  - Sync documents immediately after organization code change
  - Reusable `local_savian_ai_sync_documents()` function
- **Learning Analytics Tutorials**: Added comprehensive tutorials for teachers
  - Tutorial 6: Learning Analytics - Identify At-Risk Students
  - Tutorial 7: Monitoring Student Chat Conversations
- **API Setup Documentation**: README now includes step-by-step guide
  - Registration URL: https://app.savian.ai.vn/en/content-generation/moodle-plugin/register/
  - 30-day free trial information
  - Credential setup instructions

### Fixed
- **Organization Change Handling**: Automatically clear all documents when organization code is changed in settings
  - Documents are tied to the organization in the external API
  - Prevents errors when trying to generate content with invalid document references
  - Shows warning message in settings about document deletion
  - Displays notification with count of deleted documents after org change

### Security
- Removed test files from production release (`cli/generate_test_data.php`, `test_chat_service.php`)

---

## [1.1.0] - 2026-01-07 - Learning Analytics Release

### 🎯 Major Feature: AI-Powered Learning Analytics

**NEW: Comprehensive learning analytics system for identifying at-risk students and improving course effectiveness.**

### Added

#### Analytics System
- **Data Extraction**: 40+ metrics per student (engagement, grades, completion, forum activity, quiz performance)
- **AI Risk Analysis**: LLM-powered identification of at-risk students with personalized intervention recommendations
- **Anonymization**: SHA256 hashing for GDPR compliance (data sent to API is irreversible)
- **Reverse Lookup**: Display actual student names in teacher view (within Moodle only)
- **Real-time Progress**: Polling with live progress updates (3-4 min for 50 students)
- **Combined Dashboard**: Single page for generating reports and viewing history
- **Detailed Insights Display**:
  - At-risk students with risk scores, factors, and recommended actions
  - Course improvement recommendations (6-8 AI-generated suggestions)
  - Engagement insights (peak activity days/hours, averages)
  - Struggling topics identification
- **CSV Export**: Download full reports with all student details
- **Automation**: 4 trigger types (manual, daily, weekly, real-time, end-of-course)

#### New Classes (10)
- `classes/analytics/anonymizer.php` - SHA256 anonymization with reverse lookup
- `classes/analytics/data_extractor.php` - Extract metrics from Moodle database
- `classes/analytics/metrics_calculator.php` - Calculate engagement, grades, risk scores
- `classes/analytics/report_builder.php` - Orchestrate extraction, anonymization, API submission
- `classes/task/send_analytics_daily.php` - Daily automated reports (2 AM)
- `classes/task/send_analytics_weekly.php` - Weekly automated reports (Sunday 3 AM)
- `classes/task/cleanup_old_analytics.php` - GDPR data retention cleanup (4 AM)
- `classes/observer/analytics_observer.php` - Real-time event capture (quiz, assignment, completion)

#### New Pages (3)
- `analytics_reports.php` - Combined analytics dashboard (generate + history + detailed view)
- `export_analytics_csv.php` - CSV export functionality
- `send_analytics.php` - Redirect to analytics_reports.php (backward compatibility)

#### New Database Tables (3)
- `local_savian_analytics_reports` - Track sent reports with status, API response, insights
- `local_savian_analytics_cache` - Performance caching for metrics (1-hour TTL)
- `local_savian_analytics_events` - Real-time event tracking for batched sending

#### New API Endpoints (4)
- `POST /analytics/course-data/` - Submit anonymized student data for AI analysis
- `GET /analytics/status/<report_id>/` - Poll processing status
- `GET /analytics/course/<course_id>/latest/` - Get most recent completed report
- `GET /analytics/course/<course_id>/history/` - Get all report history

#### CLI Tools
- `cli/generate_test_data.php` - Generate realistic test students with varied profiles (high performers, average, at-risk)

#### Documentation (110+ pages)
- `ANALYTICS_API_SPEC.md` (25 pages) - Complete Django API specification with Python examples
- `ANALYTICS_TESTING_GUIDE.md` (40 pages) - 12 comprehensive test suites
- `ANALYTICS_USER_GUIDE.md` (45 pages) - Admin, teacher, and student guides with FAQ
- `END_TO_END_INTEGRATION_TEST.md` - Integration testing procedures
- `PLUGIN_DIAGNOSTIC_REPORT.md` - Validation and compliance report

#### Admin Settings
- **Enable Learning Analytics** - Global on/off toggle
- **Analytics Frequency** - Manual / Daily / Weekly / Both
- **Enable Real-Time Analytics** - Event-driven reports
- **Report Retention Period** - GDPR compliance (default: 365 days)
- **Require User Consent** - Optional student consent mechanism

### Changed

#### Analytics Integration
- Combined `send_analytics.php` and `analytics_reports.php` into single dashboard
- Enhanced polling page with progress bar, student counter, time elapsed
- Improved report history with auto-sync from Django API
- Status badges now distinguish "Processing" vs "Completed" intelligently

#### Privacy Enhancements
- Extended privacy provider to include analytics data export/deletion
- Added analytics tables to GDPR compliance
- Anonymization ensures data cannot be re-identified after user deletion

#### Database Compatibility
- Fixed PostgreSQL compatibility in analytics queries
- Changed `FROM_UNIXTIME()` to `to_timestamp()`
- Changed `DATE()` to `to_char(to_timestamp(), 'YYYY-MM-DD')`
- Changed `HOUR()` to `EXTRACT(HOUR FROM to_timestamp())`

#### UI/UX Improvements
- Chat widget now restricted to course content pages only (not settings, admin, preferences)
- Student role blocked from accessing Savian AI dashboard (teachers only)
- Navigation menu only shows for teachers with `local/savian_ai:generate` capability
- "Coming Soon" section updated to reflect that Learning Analytics is now live

#### Performance
- Batch processing for courses with 100+ students (50 per batch)
- Caching system for frequently calculated metrics
- Async processing via Django Celery for large courses (≥50 students)
- Retry logic accepts HTTP 202 (async accepted) as success

### Fixed
- PostgreSQL SQL compatibility issues in data extraction
- Form action URLs now properly retain courseid parameter
- JavaScript `toggleInsights()` function defined before use
- `html_writer::tag()` vs `html_writer::empty_tag()` usage
- Missing user fields in fullname() queries (phonetic, middlename, alternatename)
- Retry logic treating HTTP 202 as failure (now accepts 202 and 200)

### Security
- All SQL queries use parameterized statements (no injection risks)
- SHA256 anonymization prevents PII exposure to external API
- Only aggregated metrics sent to Django (no names, emails, IPs)
- Teacher-only access to analytics dashboards
- Student data properly anonymized for external transmission

---

## [1.0.0] - 2026-01-03 - First Stable Release

### 🎉 Production Release

This is the first stable release of Savian AI for Moodle, combining all features
from development versions into a production-ready package.

**Complete Feature Set:**

### Added
- **Knowledge Feedback Loop**: Save approved courses to knowledge base for reuse
- Success page with "Save to Knowledge Base" prompt after course creation
- API method: `save_approved_course_to_knowledge_base()`
- Handler: `save_to_knowledge_base.php`
- Institutional knowledge compounds over time

### Changed
- Course creation now redirects to success page instead of direct course view
- Session management improved for knowledge feedback workflow

---

## [2.1.0-beta] - 2026-01-02

### Added
- **Quality Control System**: 3-layer QC (source coverage, hallucination detection, learning depth)
- Quality Report card with overall score, source coverage, learning depth, hallucination risk
- Strengths and priority review lists
- Per-section coverage and depth badges
- Per-page quality tags (✓ Verified, ⚠️ Review, ❗ Priority)
- Supplementation indicators for AI-added content
- Review time estimates

### Changed
- Pedagogical metadata field names updated to match API v2.1
  - `age_group_name` → `designed_for`
  - `industry_name` → `subject_area`
  - `reading_level` → `content_level`
  - `pedagogy_approach` → `instructional_approach`
  - Added: `thinking_skills`

---

## [2.0.0-beta] - 2026-01-01

### Added
- **ADDIE Framework**: Professional instructional design pipeline
- Age group selection (6 levels: K-5 through Professional)
- Industry context selection (7 industries)
- Prior knowledge level selection
- AI transparency notices in generated content
- Quality Matters (QM) alignment scoring
- Pedagogical metadata display
- Enhanced rubrics with performance levels
- Section prerequisites and estimated hours
- Formative assessment content type (self-check questions)

### Changed
- Progress tracking now shows ADDIE stages instead of generic stages
- API client updated with `age_group`, `industry`, `prior_knowledge_level` parameters
- Course builder supports formative assessment creation
- View modal enhanced with rubric levels display

---

## [1.5.0-beta] - 2026-01-01

### Added
- Enhanced course generation form with organized fieldsets
- View and Edit functionality for all content items before adding to course
- Real-time AJAX progress tracking (2.5s polling interval)
- Summary statistics card in preview
- Expand/Collapse controls for sections
- Quality badges and indicators
- Modal-based content preview and editing
- Support for 6 content types: sections, pages, activities, discussions, quizzes, assignments

### Changed
- Form reorganized into 4 logical fieldsets (Basic Info, Learner Profile, Source Documents, Content Types)
- Content type selection now uses visual cards
- Submit button enhanced with gradient and hover animations
- Course title auto-filled from existing course (not editable)

### Fixed
- Modal API updated to Moodle 4.5+ (no deprecation warnings)
- Question naming improved (first 10 words instead of full text)
- Question feedback fields added to prevent warnings
- Button hover state improved (lighter gradient, white text)

---

## [1.1.0-beta] - 2025-12-31

### Added
- Floating chat widget (minimizable, fullscreen mode)
- Full-page chat interface
- Teacher conversation history viewer
- Admin monitoring dashboard
- Per-course chat settings
- Chat widget preferences (position, minimized state)
- Conversation persistence
- Feedback system (thumbs up/down)

### Changed
- Migrated to Moodle 4.5 hook system (from deprecated callbacks)
- Chat widget auto-detects course context
- Document selector hidden (auto-includes course documents)

### Fixed
- CSS loading timing (uses hook callbacks)
- External API return structure validation
- Session management for chat data

---

## [1.0.0-beta] - 2025-12-30

### Added
- Initial plugin release
- Document upload with metadata
- Document processing status tracking
- Course content generation from documents
- Question generation (topic-based and document-based)
- Question bank integration (5 question types)
- Usage statistics tracking
- Admin configuration page
- Capability system (use, generate, manage)
- Multi-language framework

### Features
- Upload PDF, DOCX, TXT documents
- Track processing status with auto-refresh
- Generate course sections, pages, and quizzes
- Generate quiz questions with Bloom's taxonomy levels
- Import questions to Moodle question bank
- Support for: multichoice, true/false, short answer, essay, matching questions

---

## [Unreleased]

### Planned Features
- Mobile app support
- Offline mode for chat
- Batch course generation
- Advanced analytics dashboard
- Integration with Moodle Workplace
- LTI support for external LMS integration

---

## Upgrade Notes

### From 1.2.0 to 1.3.0
- **New database tables**: `local_savian_ai_writing_tasks` and `local_savian_ai_writing_submissions` — created automatically on upgrade (visit Site Administration → Notifications)
- No configuration changes required
- `user_email` is no longer sent to the external API — no action needed

### From 1.1.1 to 1.2.0
- New cache definitions in `db/caches.php` (auto-registered on upgrade)
- New ad-hoc task class `send_analytics_adhoc` (no DB changes)
- CSS class names changed — custom theme overrides may need updating
- Global function names changed — any external code calling old names must update

### From 2.0 to 2.2
- No database changes
- API client methods enhanced (backward compatible)
- New optional features (QC, KB loop)
- Privacy API added (automatic)

### From 1.5 to 2.0
- Database schema unchanged
- New API parameters (optional, have defaults)
- Enhanced UI/UX (non-breaking)

### From 1.0 to 1.5
- New database tables for chat (auto-created on upgrade)
- Hook callbacks replace deprecated callbacks
- External services added (registered automatically)

---

**For detailed technical information, see [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)**
