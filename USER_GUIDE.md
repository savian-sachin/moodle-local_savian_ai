# Savian AI - User Guide

**AI-Powered Course Creation and Intelligent Tutoring for Moodle**

> **For**: Administrators, Teachers, and Students
> **Version**: 1.0.1
> **Last Updated**: January 2026

---

## 🎯 Choose Your Role

- [**👨‍💼 I'm an Administrator**](#admin-guide) - Setup and manage the plugin
- [**🎓 I'm a Teacher**](#teacher-guide) - Create courses and manage content
- [**📚 I'm a Student**](#student-guide) - Use the AI chat tutor

---

<a name="admin-guide"></a>
## 👨‍💼 Administrator Guide

### Quick Start (5 Minutes)

**What you'll do:**
1. ✅ Install the plugin
2. ✅ Configure API credentials
3. ✅ Verify connection
4. ✅ Enable features
5. ✅ Test it works

---

### Step 1: Installation

**From Moodle Plugins Directory:**
```
1. Site Administration → Plugins → Install plugins
2. Search for "Savian AI"
3. Click "Install"
4. Follow the wizard
```

**Manual Installation:**
```
1. Upload ZIP file via: Site Admin → Plugins → Install plugins
2. Click "Install plugin from ZIP"
3. Complete installation
```

---

### Step 2: Configuration

**Navigate to:**
```
Site Administration → Plugins → Local plugins → Savian AI
```

**Fill in 3 fields:**

**API Base URL:**
```
https://app.savian.ai.vn/api/moodle/v1/
```
*Pre-filled, just verify it's correct*

**API Key:**
```
[Your API key from Savian AI]
```
*Contact Savian AI for your organization's key*

**Organization Code:**
```
[Your organization code]
```
*Provided by Savian AI with your API key*

**Click:** `Save changes`

---

### Step 3: Validate Connection

**Test your setup:**
```
Click: "Validate Connection" button

✅ Success message:
   "Connection successful! Organization: [Your Org Name]"

❌ Error message:
   Check API key and URL are correct
```

---

### Step 4: Configure Features (Optional)

**Chat Widget Settings:**
- ✅ Enable chat widget (recommended)
- Choose default position (bottom-right recommended)
- Set welcome message (or use default)

**All settings have helpful descriptions - just follow along!**

---

### Step 5: Test

**Quick Test:**
```
1. Navigate to any course
2. Look for "Savian AI" in course navigation
3. You should see:
   - Dashboard
   - Documents
   - Generate Course Content
   - Generate Questions
   - Help & Tutorials

4. Check chat bubble appears (bottom-right corner)

Everything visible? ✅ You're done!
```

---

### Monitoring Usage

**View system-wide statistics:**
```
Site Administration → Local plugins → Savian AI → Chat Monitoring

See:
- Total conversations
- Active users
- Engagement metrics
- Feedback statistics
```

**Perfect for reporting and ROI measurement!**

---

### Troubleshooting

**Chat widget not appearing?**
- Check: Chat enabled in settings ✓
- Verify: Users have `local/savian_ai:use` capability
- Try: Purge all caches (Site Admin → Development)

**Teachers can't generate content?**
- Check: Teachers have `local/savian_ai:generate` capability
- Navigate: Site Admin → Users → Define roles → Teacher
- Find: "Generate content with Savian AI"
- Set: Allow

---

<a name="teacher-guide"></a>
## 🎓 Teacher Guide

### What Can I Do?

✅ **Upload course documents** (PDFs, DOCX)
✅ **Generate complete course content** in minutes
✅ **Review AI-generated materials** with quality scores
✅ **Edit content** before adding to course
✅ **Generate quiz questions** from documents
✅ **View student chat conversations** for support
✅ **Save approved courses** to build institutional knowledge

---

### Tutorial 1: Upload Your First Document (2 minutes)

**Why Upload Documents?**
Your documents become the foundation for AI-generated course content. Upload textbooks, lecture notes, or study guides.

**Steps:**
```
1. Navigate to your course

2. Click "Savian AI" in course navigation

3. Click "Documents"

4. Click "+ Upload Document" button

5. Fill the form:
   - Title: Descriptive name (e.g., "Intro to Psychology Textbook")
   - Choose file: Select your PDF or DOCX (max 50MB)
   - Description: Optional summary
   - Subject Area: Your subject (e.g., "Psychology")

6. Click "Upload"

7. Wait for status:
   "Uploading..." → "Processing..." → "Ready" ✓
   (Usually 30-90 seconds)

8. Done! Document ready for course generation
```

**💡 Pro Tip:** Upload 2-3 related documents for best course generation results.

---

### Tutorial 2: Generate Your First Course (10 minutes)

**What You'll Get:**
Complete course structure with sections, teaching pages, activities, discussions, quizzes, and assignments - all from your documents!

**Steps:**

**Part 1: Fill the Form (3 minutes)**
```
1. Navigate: Course → Savian AI → Generate Course Content

2. Basic Information:
   - Target Course: [Auto-filled with your course name]
   - Description: Optional brief summary
   - Additional Context: Optional (e.g., "First-year students")

3. Learner Profile (IMPORTANT - Adapts content):
   - Age Group: Choose your students' level
     • K-5 Elementary → Simple vocabulary, playful
     • Middle School → Age-appropriate language
     • High School → Academic, college-prep
     • Undergraduate → College-level, professional
     • Graduate → Advanced, research-oriented
     • Professional → Career-focused, practical

   - Industry: Choose your subject area
     • Healthcare → Medical terminology, clinical examples
     • Technology → Tech terms, coding examples
     • Business → Corporate context, case studies
     • K-12 Education → Teaching pedagogy focus
     • etc.

   - Prior Knowledge: Your students' background
     • Beginner → No assumptions, from scratch
     • Intermediate → Some background expected
     • Advanced → Deep technical content

4. Source Documents:
   [Visual cards showing your documents]
   ✓ Check the ones you want to use (2-3 recommended)

5. Duration:
   Select weeks (4-8 weeks recommended for full courses)

6. Content Types:
   ✓ Sections (always included)
   ✓ Pages (always included)
   ☐ Activities - Hands-on exercises
   ☐ Discussions - Forum prompts
   ✓ Quizzes (recommended)
   ☐ Assignments - Projects with rubrics

   Choose what you need!

7. Click: "🎨 Generate Course Content"
```

**Part 2: Watch Progress (3-8 minutes)**
```
Real-time progress bar appears:

2% - "Analyzing learner profile and context..."
   ↳ AI is understanding your students

10% - "Course outline ready ✓"
    ↳ Structure planned

30% - "Creating Week 2 content..."
    ↳ Generating pages and activities

80% - "All sections generated ✓"
    ↳ Content complete

90% - "Calculating quality scores..."
    ↳ Checking QM alignment

100% - "Course ready! ✓"
     ↳ Auto-redirects to preview

☕ Time to grab coffee! This takes 3-8 minutes depending on course length.
```

**Part 3: Review Quality (5 minutes)**
```
Preview page shows comprehensive quality information:

📊 Quality Report Card:
   - Overall Score: 87/100 (Good!)
   - Source Coverage: 92% (Excellent - well-grounded in your docs)
   - Learning Depth: 85/100 (Deep learning - promotes critical thinking)
   - Hallucination Risk: ✓ Low (Content verified)

What this means:
✅ High scores = Trust the content, light review needed
⚠️ Medium scores = Review recommended
❌ Low scores = Thorough review needed

You'll also see:
- QM Alignment: [75-90%] - Quality Matters certification ready!
- Course Specifications: Age level, industry, reading level
- Strengths: What's working well
- Priority Reviews: What to focus on (saves time!)
- Estimated review time: 40-90 minutes

Each section shows:
  📖 Week 1: Introduction [✅ 94% coverage] [🎯 88 depth]

  Prerequisites: [Lists any]
  Estimated time: 4 hours
  Learning Objectives:
  • Objective 1
  • Objective 2

  Content items:
  📄 Understanding Concepts [✓ Verified]  ← High confidence, trust this
  🎯 Practice Activity [⚠️ Review]       ← Check this one
  💬 Discussion Forum [✓ Verified]
  ✓ Self-Check Questions
  ❓ Section Quiz

Color-coded badges guide your review:
✓ Green = Verified (85%+ from your documents)
⚠️ Yellow = Review recommended (some AI supplementation)
❗ Red = Priority review needed (low source coverage)
```

**Part 4: View & Edit (Optional, 15-30 min)**
```
For any content item:

👁️ Click "View" button:
   - See full content in a popup
   - Pages: Complete 400-800 word content
   - Activities: Full instructions
   - Quizzes: All questions with answers

✏️ Click "Edit" button:
   - Modify title or content
   - Click "Save"
   - Changes persist when you add to course

💡 Smart Strategy:
   - View green items (quick check)
   - Edit yellow items (fix supplemented parts)
   - Thoroughly review red items (low confidence)
```

**Part 5: Add to Course (1 minute)**
```
1. Optionally uncheck items you don't want
2. Click "Add to THIS Course" (big purple button)
3. Wait 10-30 seconds
4. Success! ✅

Navigate to your course:
   → See 8 new sections
   → 24 pages of content
   → Activities, discussions, quizzes
   → All ready for students!
```

**Part 6: Save to Knowledge Base (Optional, 1 minute)**
```
After adding to course, you'll see:

💡 Save to Knowledge Base?

Why save?
✓ Future courses can build on this approved content
✓ Students can chat with this course material
✓ Your next course takes 40 minutes to review (not 90!)
✓ Quality improves: 72% → 85% → 91% over time

Click "Save to Knowledge Base" or "Skip"

If you save:
- Processes in 2-3 minutes
- Appears as "[Your Course] (Instructor Approved)" in documents
- Available for future generation and chat

Your knowledge base grows! 📈
```

---

### Tutorial 3: Understanding Quality Scores

**Why Quality Scores Matter:**
They tell you where to focus your review time. Instead of reviewing everything equally, focus on what needs it most!

**Overall Score (0-100):**
```
80-100 = Excellent
  ✅ Use with minimal review
  ✅ High confidence

60-79 = Good
  ⚠️ Review supplemented sections
  ✅ Mostly well-sourced

40-59 = Fair
  ⚠️ Significant review needed
  ⚠️ Limited source coverage

0-39 = Poor
  ❗ Upload more documents!
  ❗ Too much AI supplementation
```

**Source Coverage (%):**
```
What it measures:
How much content comes directly from YOUR documents vs AI-generated

80%+ = Excellent
  → Trust it! Most content from your materials

60-79% = Good
  → AI filled some gaps, check those parts

<60% = Moderate
  → AI added significant content
  → Review carefully to ensure accuracy
```

**Learning Depth (0-100):**
```
Measures Bloom's Taxonomy levels:

75+ = Deep Learning
  → Analysis, evaluation, creation
  → Critical thinking promoted
  → Excellent pedagogy ✓

50-74 = Moderate
  → Mix of remember, understand, apply
  → Acceptable for intro courses

<50 = Surface
  → Mostly memorization
  → Consider adding critical thinking activities
```

**Page Quality Tags:**
```
✓ Verified (Green) = 85%+ from your sources
   → Light review sufficient
   → High confidence

⚠️ Review (Yellow) = 70-84% from sources
   → Includes some AI supplementation
   → Verify accuracy and relevance

❗ Priority (Red) = <70% from sources
   → Significant AI supplementation
   → Thorough review required

ℹ️ Supplemented (Blue) = AI added examples/context
   → Verify against your specific needs
```

**How to Use Quality Scores:**
```
1. Look at overall score - get the big picture
2. Check priority reviews list - focus here first
3. Review yellow/red items carefully
4. Spot-check green items
5. Customize to your teaching style

Result: Efficient review in 40-90 minutes (not 8 hours!)
```

---

### Tutorial 4: Generate Questions from Documents

**Quick process:**
```
1. Course → Savian AI → Generate Questions

2. Choose tab: "From Documents" (for your course materials)

3. Select documents (visual cards):
   ✓ Check 1-2 documents

4. Fill form:
   - Topic: "Chapter 3: Nervous System"
   - Learning objectives: Optional specific goals
   - Question types: ✓ Multiple choice, ✓ True/False
   - Count: 5 questions
   - Difficulty: Medium
   - Bloom's level: Understand

5. Click "Generate"

6. Wait 10-20 seconds

7. Preview questions:
   - See all 5 questions
   - Review answers
   - Check quality

8. Click "Add to Question Bank"

9. Questions imported! ✅
   Use them in any quiz
```

**Smart Features:**
- Questions align with Bloom's level you chose
- Match difficulty setting
- Include feedback for each answer
- Based on YOUR document content

---

### Tutorial 5: Monitor Student Chat

**View student conversations for learning support:**

```
1. Course → Savian AI → Chat History

2. See list of conversations:
   - Student name
   - Number of messages
   - Last active
   - Topics discussed

3. Click "View" on any conversation

4. See full chat history:
   - Student questions
   - AI responses
   - Sources cited
   - Feedback given

5. Use insights to:
   - Identify common questions
   - See where students struggle
   - Provide additional support
   - Improve course materials
```

**Privacy Note:** Students know teachers can view chats for learning support.

---

### Best Practices for Teachers

**Document Upload:**
✅ Upload 2-3 comprehensive documents (not just 1)
✅ Use textbooks, guides, or detailed notes
✅ Ensure documents cover course topics
✅ Course-specific uploads for each course

**Course Generation:**
✅ Choose correct age group (affects vocabulary)
✅ Match industry to your subject
✅ Select appropriate prior knowledge level
✅ Use all recommended content types
✅ Plan 4-8 weeks for full courses

**Quality Review:**
✅ Start with Priority Reviews list (saves time)
✅ Focus on yellow/red items
✅ Spot-check green items
✅ Customize to your teaching style
✅ Add local examples and context

**Knowledge Base:**
✅ Save approved courses
✅ Build over time
✅ Each course improves the next
✅ Reduce review time dramatically

---

<a name="student-guide"></a>
## 📚 Student Guide

### Using Your AI Tutor

**What is it?**
An intelligent chat tutor that answers questions about your course using the course materials. Think of it as a 24/7 teaching assistant!

---

### Tutorial: How to Use the Chat Tutor

**Step 1: Find the Chat**
```
1. Go to any course
2. Look in the bottom-right corner
3. See purple chat bubble
4. Click to open
```

**Step 2: Ask Your Question**
```
Type questions like:
✅ "What is [concept]?"
✅ "Explain the difference between X and Y"
✅ "How do I apply this concept?"
✅ "Can you summarize Week 2 content?"
✅ "What are the main points of [topic]?"

The AI will:
→ Search your course materials
→ Find relevant information
→ Give you a clear answer
→ Show you which documents/pages it used
```

**Step 3: Review the Answer**
```
The AI response includes:
- Clear explanation
- Examples from course materials
- Sources cited (which document, which page)

Check the sources:
→ Click to see where info came from
→ Verify important information
→ Cross-reference with course materials
```

**Step 4: Provide Feedback**
```
After each answer:
👍 Thumbs up = Helpful answer
👎 Thumbs down = Not helpful

Your feedback:
→ Helps improve the AI
→ Lets teachers know what's working
→ Makes the tutor better over time
```

---

### What to Ask (Good Questions)

**Concept Clarification:**
```
✅ "What does 'neural plasticity' mean?"
✅ "Explain photosynthesis in simple terms"
✅ "What's the difference between mitosis and meiosis?"
```

**Application Questions:**
```
✅ "How do I solve this type of problem?"
✅ "When would I use this formula?"
✅ "Can you give me an example of this concept?"
```

**Summary Requests:**
```
✅ "Summarize Week 3 key points"
✅ "What are the main themes of Chapter 5?"
✅ "List the steps in this process"
```

---

### What NOT to Ask

**Homework Solutions:**
```
❌ "What's the answer to assignment question 3?"
❌ "Do my homework"
❌ "Solve this problem for me"
```

**Quiz/Test Answers:**
```
❌ "What's the answer to quiz question 5?"
❌ "Tell me the test answers"
```

**Personal Information:**
```
❌ "What's my grade?"
❌ "When is the assignment due?" (check course page)
❌ Personal questions unrelated to course
```

---

### Chat Features

**Conversation History:**
```
Your chats are saved!
→ Close and reopen anytime
→ Pick up where you left off
→ Review past Q&A for studying
```

**Sources:**
```
Every answer shows sources:
→ "Source: Introduction to Biology, Chapter 3"
→ Click to verify
→ Build trust in answers
```

**Privacy:**
```
Your conversations are private
→ Teachers can view for learning support (you're informed)
→ Not shared with other students
→ You can request deletion (GDPR)
```

---

### Best Practices for Students

**Do:**
✅ Be specific in your questions
✅ Ask one question at a time
✅ Provide context if needed
✅ Check the sources provided
✅ Use for understanding concepts
✅ Give feedback

**Don't:**
❌ Ask for homework answers
❌ Expect answers to quizzes
❌ Rely solely on AI (verify important info)
❌ Share personal information

**Remember:** The AI tutor helps you **learn**, not do your work for you!

---

## 🔮 Coming Soon

### Exciting Features in Development

**1. Incremental Course Building**
```
Already have Week 1-2?
→ Generate Week 3-4 to add after them
→ Build courses over time
→ No need to regenerate everything
```

**2. AI Assessment Evaluation**
```
Submit your essay:
→ AI provides draft grade and feedback
→ Teacher reviews and finalizes
→ 70% faster grading
→ More detailed feedback
```

**3. Personalized Learning Support**
```
System identifies:
→ 25% of students struggling with Week 3
→ Generate targeted review materials
→ Auto-assign to those students
→ Personalized learning at scale
```

---

## ❓ Frequently Asked Questions

### For Administrators

**Q: How much does it cost?**
A: Contact Savian AI for pricing. Educational institutions get special rates.

**Q: Is our data secure?**
A: Yes. Full Privacy API implementation, GDPR compliant, all data transmission disclosed.

**Q: What Moodle versions are supported?**
A: Moodle 4.5 and higher. PHP 8.1, 8.2, or 8.3.

**Q: Can we use our own AI models?**
A: The plugin connects to Savian AI's service. Custom model integration available for enterprise licenses.

---

### For Teachers

**Q: How long does course generation take?**
A: 3-8 minutes depending on course length (4 weeks = 3-5 min, 8 weeks = 5-8 min).

**Q: Can I edit the generated content?**
A: Yes! View and edit any content before adding to your course. Changes persist.

**Q: What if quality is low?**
A: Upload more documents! 1 document = 60% coverage, 3 documents = 80%+ coverage.

**Q: Can I regenerate if I don't like it?**
A: Yes, just click "Regenerate" in preview. Try different settings or documents.

**Q: Do I have to use all generated content?**
A: No! Uncheck items you don't want. Pick and choose what fits your course.

**Q: How do I delete a document?**
A: Only your own course documents. Click delete button in document list.

---

### For Students

**Q: Is the chat tutor always available?**
A: Yes! 24/7 access from any course page.

**Q: Will it give me homework answers?**
A: No. It's a learning tool to help you understand concepts, not to do your work.

**Q: Are my chats private?**
A: Yes. Only you and your teachers can see your conversations (for learning support).

**Q: Can I trust the AI's answers?**
A: Answers are based on course materials, but always verify important information. Check the sources provided!

**Q: What if the AI gives a wrong answer?**
A: Use thumbs down and inform your teacher. Always cross-check with course materials.

---

## 📞 Need Help?

### In-App Tutorials

```
From any Savian AI page:
→ Click "Help & Tutorials" button
→ Choose your role (Admin/Teacher/Student)
→ Follow step-by-step guides
```

### Support Resources

**For Users:**
- In-app tutorials (role-based)
- This user guide
- Your Moodle administrator

**For Administrators:**
- README.md (technical setup)
- Savian AI support team
- GitHub issues (if open source)

---

## 🎊 Summary

### For Administrators
- 5-minute setup
- One-time configuration
- Monitor usage easily
- Works out-of-the-box for teachers and students

### For Teachers
- Upload documents once
- Generate courses in minutes
- Review with quality guidance
- Save time, maintain quality

### For Students
- 24/7 AI tutor access
- Instant answers from course materials
- Better understanding
- Learn at your own pace

---

**Savian AI: Transforming Moodle with Intelligent Course Creation and Tutoring**

**Version**: 1.0.1 - Stable
**License**: GPL v3
**Support**: Available through your institution

---

*This guide focuses on using the plugin. For installation and configuration details, see README.md*
