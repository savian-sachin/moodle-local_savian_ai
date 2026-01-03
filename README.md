# Savian AI - Moodle Plugin

**AI-Powered Course Content Generation and Intelligent Tutoring for Moodle 4.5+**

[![Moodle Version](https://img.shields.io/badge/Moodle-4.5%2B-orange)](https://moodle.org/)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](LICENSE.txt)
[![Version](https://img.shields.io/badge/version-1.0.0-brightgreen)](CHANGES.md)
[![Stability](https://img.shields.io/badge/stability-stable-green)](https://github.com/savian-sachin/moodle-local-savian-ai/releases)

---

## 🎯 Features

### 🎓 ADDIE Framework Course Generation
- Generate complete course structures from your documents
- Professional instructional design (Analysis → Design → Development → Implementation → Evaluation)
- Age-appropriate adaptation (K-5 through Professional)
- Industry-specific customization (Healthcare, Technology, Business, K-12, Corporate, etc.)
- Real-time progress tracking with stage indicators

### 💬 Intelligent AI Tutor
- Floating chat widget on course pages
- Context-aware responses using your course materials
- Source attribution for transparency
- Conversation history for teachers
- Feedback system for continuous improvement

### 📊 Quality Control System
- Quality Matters (QM) alignment scoring (85%+ certifiable)
- Source coverage analysis
- Learning depth assessment (Bloom's taxonomy)
- Hallucination risk detection
- Per-page confidence tags (✓ Verified, ⚠️ Review, ❗ Priority)
- Priority review guidance for instructors

### 🔄 Knowledge Feedback Loop
- Save approved courses back to knowledge base
- Future generations build on vetted content
- Institutional knowledge compounds over time
- Reduced review time for similar courses

### 🌍 Multi-Language Support
- English (complete)
- Vietnamese (complete)
- Easy to add additional languages

---

## 📋 Requirements

- **Moodle**: 4.5 or higher
- **PHP**: 8.1, 8.2, or 8.3 (8.4+ not officially supported)
- **Database**: PostgreSQL or MySQL
- **Savian AI Account**: API credentials required ([Contact Savian AI](mailto:support@savian.ai))

---

## 📥 Installation

### Method 1: Via Moodle Plugin Directory (Recommended)

1. Log in as administrator
2. Navigate to **Site Administration → Plugins → Install plugins**
3. Search for **"Savian AI"**
4. Click **Install**
5. Follow the installation wizard

### Method 2: Manual Installation

1. Download the latest release from [GitHub Releases](../../releases)
2. Extract the ZIP file
3. Upload to `[moodleroot]/local/savian_ai/`
4. Navigate to **Site Administration → Notifications**
5. Click **Upgrade Moodle database now**
6. Complete the installation

### Method 3: Git Clone (For Developers)

```bash
cd [moodleroot]/local/
git clone https://github.com/[your-org]/savian-moodle-plugin.git savian_ai
cd savian_ai
# Visit Site Administration → Notifications to complete install
```

---

## ⚙️ Configuration

### 1. API Credentials

Navigate to **Site Administration → Plugins → Local plugins → Savian AI**

**Required Settings:**
- **API Base URL**: Your Savian AI API endpoint
- **API Key**: Your organization's API key
- **Organization Code**: Your organization identifier

**Optional Settings:**
- Enable/disable chat widget
- Default chat position
- Custom welcome message
- Auto-save approved courses

### 2. Assign Capabilities

Navigate to **Site Administration → Users → Permissions → Define roles**

**Recommended Role Assignments:**
- **Student**: `local/savian_ai:use` (chat access)
- **Teacher**: `local/savian_ai:use` + `local/savian_ai:generate` (full access)
- **Manager**: `local/savian_ai:manage` (admin settings)

### 3. Test Connection

1. Go to **Site Administration → Plugins → Local plugins → Savian AI**
2. Click **Validate Connection**
3. Verify success message appears

---

## 🚀 Usage

### For Teachers

#### Generate Course Content

1. Navigate to a course
2. Click **Savian AI** in course menu
3. Select **Generate Course Content from Documents**
4. Fill the form:
   - Upload documents (or select existing)
   - Set learner profile (age, industry, knowledge level)
   - Choose content types (pages, activities, discussions, quizzes, assignments)
   - Set duration (weeks)
5. Click **Generate**
6. Watch real-time progress (3-8 minutes)
7. Preview generated content with quality scores
8. Review and edit if needed
9. Add to course
10. (Optional) Save approved course to knowledge base

#### Use AI Chat

- Chat widget appears on course pages
- Ask questions about course materials
- Get AI-powered assistance
- View conversation history

### For Students

#### AI Tutor Chat

- Click the floating chat bubble (bottom-right of course pages)
- Ask questions about course content
- Get instant, contextual answers with source citations
- Continue conversations across sessions

---

## 📊 Quality Indicators

### What the Quality Scores Mean

**Overall Score (0-100)**:
- **80-100**: Excellent - Ready to use with minimal review
- **60-79**: Good - Review supplemented content
- **40-59**: Fair - Significant review needed
- **0-39**: Poor - Consider uploading more source documents

**Source Coverage**:
- **80%+**: Excellent grounding in your documents
- **60-79%**: Good coverage with minor gaps
- **<60%**: Moderate supplementation required

**Page Quality Badges**:
- ✅ **Verified** (Green): High confidence, well-sourced
- ⚠️ **Review** (Yellow): Medium confidence, includes supplementation
- ❗ **Priority** (Red): Low confidence, needs thorough review
- ℹ️ **Supplemented** (Blue): Includes AI-added context

---

## 🔒 Privacy & Data Protection

### What Data is Stored

**Locally in Moodle**:
- Chat conversations and messages
- Document metadata (not content)
- Generation history
- User preferences

**Sent to External Service**:
- User ID and email (for context)
- Course ID and name
- Chat messages
- Document content (for processing)

### GDPR Compliance

- ✅ Full Privacy API implementation
- ✅ User data export available
- ✅ User data deletion supported
- ✅ Clear disclosure of external data transmission
- ✅ No data sharing without user interaction

**Users can request**:
- Export of all their chat data
- Deletion of all personal data
- View what data is stored

---

## 🛠️ Troubleshooting

### Common Issues

**"Connection failed" error**:
- Check API credentials in settings
- Verify API URL is correct and accessible
- Test with validation button

**Chat widget not appearing**:
- Check chat is enabled: Site Admin → Savian AI → Enable chat widget
- Verify user has `local/savian_ai:use` capability
- Check course-level settings (may be disabled for specific course)
- Purge caches: Site Admin → Development → Purge all caches

**Generation takes too long**:
- Normal: 3-8 minutes depending on duration and content types
- If stuck: Check browser console for JavaScript errors
- Verify API service is online

**Vietnamese strings not showing**:
- Set your language preference to Vietnamese
- Purge caches
- Check `lang/vi/local_savian_ai.php` exists

---

## 🤝 Support

- **Documentation**: See [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) for technical details
- **Issues**: [GitHub Issues](../../issues)
- **Contact**: support@savian.ai

---

## 📝 License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

See [LICENSE.txt](LICENSE.txt) for the full license text.

---

## 👥 Credits

**Developed by**: Savian AI
**Copyright**: 2026 Savian AI
**Contributors**: See [GitHub Contributors](../../graphs/contributors)

---

## 📚 Documentation

- **User Guide**: This README
- **Developer Guide**: [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)
- **Version History**: [CHANGES.md](CHANGES.md)
- **Privacy Policy**: [classes/privacy/provider.php](classes/privacy/provider.php)

---

## 🔄 Version

**Current Version**: 1.0.0 (January 2026) - **Stable Release** ✨

See [CHANGES.md](CHANGES.md) for complete version history.
