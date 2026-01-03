# Moodle Plugins Directory Submission Guide
## Savian AI Plugin - Complete Submission Package

> **Plugin Version**: 1.0.0
> **Submission Date**: January 2026
> **Repository**: https://github.com/savian-sachin/moodle-local-savian-ai

---

## 📋 Table of Contents

1. [Pre-Submission Checklist](#pre-submission-checklist)
2. [Account Registration](#account-registration)
3. [Plugin Submission Form](#plugin-submission-form)
4. [Field-by-Field Content](#field-by-field-content)
5. [Screenshots](#screenshots)
6. [Review Process](#review-process)
7. [Post-Approval Steps](#post-approval-steps)

---

<a name="pre-submission-checklist"></a>
## 1. Pre-Submission Checklist

Before submitting, verify:

### Code Requirements:
- [x] No PHP errors or warnings
- [x] Privacy API implemented (GDPR)
- [x] All strings in language files
- [x] GPL v3 compatible license
- [x] Proper capability checks
- [x] No hardcoded credentials
- [x] Secure coding practices

### Documentation:
- [x] README.md with installation guide
- [x] DEVELOPER_GUIDE.md (technical docs)
- [x] CHANGES.md (version history)
- [x] LICENSE.txt (GPL v3)
- [x] PHPDoc comments

### Repository:
- [x] GitHub repository public
- [x] Clean Git history
- [x] Tagged release (v1.0.0)
- [x] Issues enabled

### Testing:
- [x] Tested on Moodle 4.5
- [x] All features functional
- [x] Both languages work (EN + VI)
- [x] No JavaScript console errors

**Status**: ✅ ALL REQUIREMENTS MET

---

<a name="account-registration"></a>
## 2. Account Registration

### Step 1: Create Moodle.org Account

1. Visit: https://moodle.org/login/signup.php
2. Fill registration form:
   - **Email**: [Your email]
   - **Username**: [Choose username]
   - **First name**: [Your name]
   - **Last name**: [Your name]
   - **Country**: [Your country]
3. Confirm email
4. Complete profile

### Step 2: Request Plugin Developer Access

1. Visit: https://moodle.org/plugins/
2. Click **"Register as plugin developer"**
3. Agree to:
   - Plugin contribution guidelines
   - GPL license requirements
   - Code of conduct
4. Wait for approval (usually 1-2 days)

---

<a name="plugin-submission-form"></a>
## 3. Plugin Submission Form

Once approved as developer:

1. Visit: https://moodle.org/plugins/
2. Click **"Add a plugin"**
3. Fill the submission form (details below)

---

<a name="field-by-field-content"></a>
## 4. Field-by-Field Content (Copy/Paste Ready)

### Basic Information

**Plugin Name:**
```
Savian AI
```

**Component Name:**
```
local_savian_ai
```

**Plugin Type:**
```
Local plugin
```

**Short Description (140 characters max):**
```
AI-powered course generation and intelligent tutoring using ADDIE framework with quality control and multi-language support.
```

---

### Detailed Description

**Full Description:**
```markdown
## AI-Powered Course Content Generation and Intelligent Tutoring for Moodle

Savian AI transforms course creation with professional-grade AI that generates complete, quality-controlled course content following the ADDIE instructional design framework.

### Key Features

**🎓 ADDIE Framework Course Generation**
- Generate complete course structures from your documents
- Age-appropriate adaptation (K-5 through Professional)
- Industry-specific customization (Healthcare, Technology, Business, Education, Corporate)
- Prior knowledge level adaptation
- Real-time progress tracking with ADDIE stage indicators

**Content Types Generated:**
- Course sections with learning objectives and prerequisites
- Teaching pages (400-800 words, age-adapted)
- Hands-on learning activities
- Discussion forum prompts
- Formative assessments (self-check questions)
- Section quizzes (5 question types)
- Assignments with rubrics

**📊 Quality Control System**
- Quality Matters (QM) alignment scoring (85%+ certifiable)
- Source coverage analysis (% grounding in your documents)
- Learning depth assessment (Bloom's taxonomy)
- Hallucination risk detection
- Per-page confidence tags (Verified/Review/Priority)
- Supplementation indicators
- Priority review guidance with time estimates

**💬 Intelligent AI Tutor**
- Floating chat widget on course pages
- Context-aware responses using your course materials
- Source attribution for transparency
- Conversation history for teachers
- Multi-language support (English + Vietnamese)
- Student engagement analytics

**🔄 Knowledge Feedback Loop**
- Save approved courses to knowledge base
- Future generations build on vetted content
- Institutional knowledge compounds over time
- Reduced review time for similar courses

### Use Cases

- **Course Development**: Generate complete courses from textbooks, research papers, or documentation
- **Content Refresh**: Update existing courses with new materials
- **Student Support**: 24/7 AI tutor answering questions about course content
- **Quality Assurance**: QM-aligned content with transparency scores
- **Time Savings**: 80-120 minutes vs weeks of manual creation

### Requirements

- Moodle 4.5 or higher
- Savian AI API account (for external AI service)
- PHP 8.1, 8.2, or 8.3

### Privacy & GDPR

Full Privacy API implementation. User data stored locally in Moodle with option to export/delete. External AI service usage clearly disclosed. GDPR compliant.
```

---

### Technical Information

**Source Control URL:**
```
https://github.com/savian-sachin/moodle-local-savian-ai
```

**Bug Tracker URL:**
```
https://github.com/savian-sachin/moodle-local-savian-ai/issues
```

**Documentation URL:**
```
https://github.com/savian-sachin/moodle-local-savian-ai/blob/main/README.md
```

**Demo URL:** (Optional)
```
[Leave blank or provide demo site URL if you have one]
```

---

### Supported Moodle Versions

**Minimum Moodle Version:**
```
4.5 (2024100700)
```

**Maximum Moodle Version:**
```
[Leave blank for "any future version"]
```

**Supported PHP Versions:**
```
8.1, 8.2, 8.3
```

**Supported Databases:**
```
PostgreSQL, MySQL, MariaDB
```

---

### Maturity & Languages

**Maturity Level:**
```
Stable
```

**Supported Languages:**
```
English (en)
Tiếng Việt (vi)
```

---

### License & Copyright

**License:**
```
GPL v3 or later
```

**Copyright Holder:**
```
Savian AI
```

**Copyright Year:**
```
2026
```

---

### Categories & Tags

**Categories** (Select all that apply):
- [x] Content
- [x] Artificial Intelligence
- [x] Communication
- [x] Course activities
- [x] Teaching and learning

**Tags** (Comma-separated):
```
ai, artificial intelligence, course generation, chat, tutor, addie, quality matters, instructional design, content creation, automation
```

---

### Dependencies

**Required Plugins:**
```
None
```

**Optional Plugins:**
```
None
```

**External Libraries:**
```
None (uses Moodle's built-in libraries)
```

---

### Additional Information

**Video URL:** (Optional but recommended)
```
[YouTube demo video if you create one]
```

**External Service Used:**
```
Yes - Savian AI API for content generation and chat
Service URL: [Configured by admin]
Purpose: AI-powered content generation and natural language processing
Data sent: Course content, chat messages, user context (ID, email, role)
Privacy: Disclosed to users, GDPR compliant
```

**Special Notes for Reviewers:**
```
This plugin requires an external API key (Savian AI service) which must be obtained separately and configured in plugin settings. The plugin will not function without valid API credentials.

A test API key can be provided to reviewers upon request for validation purposes.

All external API communication is clearly documented in the Privacy API (classes/privacy/provider.php).
```

---

<a name="screenshots"></a>
## 5. Screenshots (Recommended)

### Required Screenshots:

**Screenshot 1: Course Generation Form**
- File: `screenshot-1-generation-form.png`
- Caption: "ADDIE framework course generation with age/industry adaptation"
- Shows: Complete form with 4 organized fieldsets

**Screenshot 2: Real-time Progress Tracking**
- File: `screenshot-2-progress-tracking.png`
- Caption: "Real-time progress with ADDIE stages (3-8 minutes)"
- Shows: Progress bar at ~60% with stage indicator

**Screenshot 3: Quality Control Preview**
- File: `screenshot-3-quality-preview.png`
- Caption: "Quality report with QM alignment, source coverage, and learning depth scores"
- Shows: Preview page with quality cards and badges

**Screenshot 4: Generated Content**
- File: `screenshot-4-generated-content.png`
- Caption: "Complete course structure with sections, pages, activities, discussions, and quizzes"
- Shows: Expanded section with multiple content types

**Screenshot 5: AI Chat Tutor**
- File: `screenshot-5-chat-widget.png`
- Caption: "Intelligent AI tutor with source attribution and conversation history"
- Shows: Chat widget with conversation and sources

**Screenshot 6: Vietnamese Interface**
- File: `screenshot-6-vietnamese.png`
- Caption: "Full Vietnamese language support"
- Shows: UI in Vietnamese

### How to Take Screenshots:

```bash
# 1. Generate course content
http://localhost:8002/local/savian_ai/create_course.php?courseid=2

# 2. Fill form and take screenshot
# 3. Watch progress bar and capture
# 4. Preview page with all quality cards
# 5. View generated course sections
# 6. Open chat widget
# 7. Switch to Vietnamese and capture UI
```

**Screenshot Specs:**
- Format: PNG
- Size: 1200×800 px (or similar 3:2 ratio)
- Quality: High
- File size: <500KB each

---

<a name="review-process"></a>
## 6. Review Process

### What to Expect

**Timeline:**
1. **Submission**: Immediate
2. **Automated Checks**: 1-2 hours
   - License verification
   - File structure check
   - Basic syntax validation
3. **Queue Assignment**: 1-3 days
4. **Human Review**: 1-2 weeks
   - Code quality review
   - Security audit
   - Documentation review
   - Privacy API check
5. **Feedback/Revisions**: If needed (3-7 days)
6. **Approval**: After all checks pass
7. **Publication**: Immediate after approval

### Common Review Feedback

**Typical Requests:**
1. "Add more screenshots" - Easy to address
2. "Clarify external service usage" - Update description
3. "Fix coding standard violations" - Usually minor
4. "Improve documentation" - Add examples

**How to Respond:**
- Address feedback promptly
- Make requested changes
- Update version if code changes
- Resubmit with explanation

---

### During Review

**You'll Be Asked About:**

**Q1: What external service does this use?**
**A**:
```
This plugin integrates with Savian AI's external service for:
1. Content generation using AI models
2. Natural language chat responses
3. Document processing and embedding

The service requires API credentials (obtained from Savian AI).
All data transmission is documented in the Privacy API.
Users are informed that content is processed by external AI service.
```

**Q2: How is user data protected?**
**A**:
```
User privacy is protected through:
1. Full Privacy API implementation (classes/privacy/provider.php)
2. User data export/deletion supported
3. External data transmission clearly disclosed
4. Capability checks on all sensitive operations
5. Session keys on all forms
6. Input validation on all parameters
7. Output sanitization throughout

Chat messages and generations are scoped to the user and course.
No data shared without explicit user interaction.
```

**Q3: Can we test without API key?**
**A**:
```
The plugin requires valid API credentials to function.

For review purposes, I can provide:
- Test API key (email me)
- Demo video showing all features
- Screenshots of all functionality

Alternatively, reviewers can:
- Review code quality and structure
- Verify Privacy API implementation
- Check security practices
- Validate documentation
```

---

<a name="post-approval"></a>
## 7. Post-Approval Steps

### After Plugin is Approved

**1. Celebrate!** 🎉

**2. Promote Your Plugin**
- Tweet about it
- Blog post
- Moodle forums announcement
- LinkedIn post
- Community newsletter

**3. Monitor**
- Watch GitHub issues
- Respond to user questions
- Track download statistics
- Gather feedback

**4. Maintain**
- Bug fixes as needed
- Moodle version updates
- Feature enhancements
- Documentation updates

### Future Releases

**For Each New Version:**
1. Update version.php
2. Update CHANGES.md
3. Commit and tag (e.g., v1.1.0)
4. Push to GitHub
5. Create GitHub release
6. Upload new version to Moodle plugins directory
7. Announce in plugin page announcements

**Versioning Guide:**
- **Major** (2.0.0): Breaking changes, major new features
- **Minor** (1.1.0): New features, backward compatible
- **Patch** (1.0.1): Bug fixes only

---

## 📝 Complete Submission Checklist

### Before Submission:
- [x] GitHub repository public
- [x] All code committed and pushed
- [x] Tagged release (v1.0.0)
- [x] README.md complete
- [x] DEVELOPER_GUIDE.md complete
- [x] LICENSE.txt present
- [x] Privacy API implemented
- [x] Multi-language support
- [x] No sensitive information in code

### During Submission:
- [ ] Create moodle.org account
- [ ] Request developer access
- [ ] Fill submission form
- [ ] Upload screenshots (6 images)
- [ ] Submit for review

### After Submission:
- [ ] Monitor email for review feedback
- [ ] Respond to reviewer questions
- [ ] Make revisions if requested
- [ ] Wait for approval
- [ ] Celebrate when approved!

---

## 🎯 Ready-to-Use Content

### Short Description (for listings):
```
AI-powered course generation with ADDIE framework, quality control, and intelligent chat tutor. Multi-language support.
```

### Elevator Pitch (for announcements):
```
Transform course creation with Savian AI - generate complete, quality-controlled courses from your documents in minutes instead of weeks. Features ADDIE instructional design, Quality Matters alignment, and an intelligent chat tutor. GDPR compliant with multi-language support.
```

### Key Selling Points:
```
✅ Save 90% time on course creation
✅ Quality Matters (QM) certified-ready content (85%+ alignment)
✅ Age and industry-adapted for 6 age groups × 7 industries
✅ Built-in quality control with source verification
✅ AI chat tutor for student support
✅ Knowledge base that improves over time
✅ Full GDPR compliance with Privacy API
✅ Multi-language (English + Vietnamese)
```

---

## 📧 Sample Reviewer Communication

### If Reviewer Asks for Test Access:

```
Dear Reviewer,

Thank you for reviewing the Savian AI plugin.

To facilitate testing, I can provide:
1. Test API credentials (valid for 30 days)
2. Demo video showing all features
3. Step-by-step testing guide

Alternatively, you can:
- Review the comprehensive DEVELOPER_GUIDE.md
- Examine the Privacy API implementation
- Verify code quality and security practices
- Check documentation completeness

Please let me know your preference, and I'll be happy to assist.

Best regards,
[Your name]
```

### If Feedback Received:

```
Dear Reviewer,

Thank you for the feedback on the Savian AI plugin.

I have addressed all points:

1. [Issue 1]: [What you changed]
2. [Issue 2]: [What you changed]
3. [Issue 3]: [What you changed]

Changes committed: [commit hash]
Updated version: [if version changed]

The plugin is ready for re-review. Please let me know if anything else is needed.

Best regards,
[Your name]
```

---

## 🚀 Submission Checklist Timeline

### Week 0: Pre-Submission
- [x] Complete all development
- [x] Create documentation
- [x] Implement Privacy API
- [x] Test thoroughly
- [x] Push to GitHub

### Week 1: Submission
- [ ] Day 1: Register account, request developer access
- [ ] Day 2-3: Developer access approved
- [ ] Day 3: Submit plugin with all info and screenshots
- [ ] Day 3-7: Automated checks complete

### Week 2-3: Review
- [ ] Week 2: Human code review begins
- [ ] Week 2-3: Respond to any feedback
- [ ] Week 3: Final approval (if all checks pass)

### Week 3+: Publication
- [ ] Plugin published on moodle.org/plugins
- [ ] Available for download
- [ ] Appears in plugin directory searches
- [ ] Start receiving users!

---

## 📊 Success Metrics to Track

After publication:

**From Moodle Plugins Directory:**
- Downloads per week/month
- Star ratings (1-5)
- User reviews
- Support questions

**From GitHub:**
- Stars
- Forks
- Issues opened
- Pull requests

**From Your Analytics:**
- Active installations
- API usage
- Feature adoption
- User satisfaction

---

## 🎓 Marketing After Approval

### Announcement Template:

```markdown
🎉 Excited to announce Savian AI v1.0.0 for Moodle!

Transform your course creation with AI-powered content generation:

✅ Generate complete courses in minutes (not weeks)
✅ ADDIE framework with Quality Matters alignment
✅ 7 content types: pages, activities, discussions, quizzes, assignments
✅ Built-in quality control and source verification
✅ Intelligent AI chat tutor for students
✅ Multi-language support (English + Vietnamese)

📥 Download: https://moodle.org/plugins/local_savian_ai
📚 Docs: https://github.com/savian-sachin/moodle-local-savian-ai
⭐ Free and open source (GPL v3)

#Moodle #EdTech #AI #ADDIE #Education
```

---

## ✅ Final Pre-Submission Verification

Run these checks one more time:

```bash
# 1. No PHP syntax errors
find . -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# 2. Git status clean
git status

# 3. Latest commit pushed
git log --oneline -n 3

# 4. Tag exists
git tag -l

# 5. Remote URL correct
git remote -v

# 6. Files present
ls -la README.md DEVELOPER_GUIDE.md CHANGES.md LICENSE.txt .gitignore classes/privacy/
```

**All checks passing?** ✅ **Ready to submit!**

---

## 📞 Support During Submission

**Need Help?**
- Moodle Plugins Documentation: https://moodle.org/plugins/guidelines.php
- Developer Forums: https://moodle.org/mod/forum/view.php?id=55
- Email Support: plugins@moodle.org

**Questions About Savian AI Plugin?**
- GitHub Issues: https://github.com/savian-sachin/moodle-local-savian-ai/issues
- Developer Guide: See DEVELOPER_GUIDE.md

---

**Good luck with your submission!** 🍀

The Savian AI plugin is professional, well-documented, and ready for the Moodle community!

---

**Last Updated**: January 3, 2026
**Plugin Version**: 1.0.0 - Stable
**Submission Status**: Ready ✅
