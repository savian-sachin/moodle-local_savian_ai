# Savian AI Moodle Plugin - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.2.0-beta] - 2026-01-02

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
