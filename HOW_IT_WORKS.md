# How Savian AI Works - Complete Process Tutorial

> **A comprehensive guide to understanding how the Savian AI Moodle plugin works**
> **For**: Administrators, Teachers, and curious minds
> **Version**: 1.0.1
> **Last Updated**: January 2026

---

## 📚 Table of Contents

1. [Overview](#overview)
2. [The Big Picture](#big-picture)
3. [Process 1: Document Upload](#document-upload)
4. [Process 2: Course Generation](#course-generation)
5. [Process 3: Chat Conversations](#chat-conversations)
6. [Process 4: Question Generation](#question-generation)
7. [Process 5: Knowledge Feedback Loop](#knowledge-feedback-loop)
8. [Behind the Scenes](#behind-the-scenes)
9. [Data Flow](#data-flow)
10. [Security & Privacy](#security-privacy)

---

<a name="overview"></a>
## 1. Overview

### What is Savian AI?

Savian AI is a Moodle plugin that uses artificial intelligence to:
- 🎓 **Generate complete course content** from your documents
- 💬 **Provide an intelligent chat tutor** for students
- 📊 **Ensure quality** with scoring and verification
- 🔄 **Build institutional knowledge** over time

### How Does It Work?

```
Your Documents → AI Processing → Moodle Content
     ↓              ↓                 ↓
 Upload PDFs    Analyzes &        Sections
 Upload DOCX    Generates         Pages
 Course Docs    Content           Activities
                                  Quizzes
                                  Assignments
```

The plugin acts as a **bridge** between your Moodle site and Savian's AI service, handling all the complexity behind the scenes.

---

<a name="big-picture"></a>
## 2. The Big Picture

### The Complete Ecosystem

```
┌─────────────────────────────────────────────────────────────┐
│                    YOUR MOODLE SITE                          │
│                                                              │
│  Teachers                      Students                      │
│  ├─ Upload documents          ├─ Use chat tutor            │
│  ├─ Generate courses          ├─ Get instant answers       │
│  ├─ Review quality            ├─ See sources               │
│  └─ Save to knowledge base    └─ Learn 24/7                │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              SAVIAN AI MOODLE PLUGIN                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ API Client: Sends requests to external service      │   │
│  │ Course Builder: Creates Moodle content              │   │
│  │ Chat Manager: Handles conversations                 │   │
│  │ Quality Control: Verifies content                   │   │
│  └──────────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              SAVIAN AI SERVICE (External)                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ • Processes documents (chunking, embedding)         │   │
│  │ • ADDIE framework course generation                 │   │
│  │ • Natural language chat responses                   │   │
│  │ • Quality analysis (QM, source coverage, depth)     │   │
│  │ • Knowledge base management                         │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

<a name="document-upload"></a>
## 3. Process 1: Document Upload

### User Journey

**Step 1: Teacher Uploads Document**
```
Teacher navigates to: Course → Savian AI → Documents
Clicks: "+ Upload Document"
Fills form:
  - Title: "Healthcare Ethics Textbook"
  - File: ethics_textbook.pdf (20 MB)
  - Subject Area: "Healthcare"
  - Course: [Current course only]
Clicks: Submit
```

**Step 2: Document Processing**
```
What happens:
1. File uploaded to Moodle temp directory
2. Plugin sends to Savian AI service via API
3. Service receives file and starts processing:

   Processing Pipeline:
   ├─ Extract text from PDF (30 seconds)
   ├─ Split into semantic chunks (15 seconds)
   ├─ Generate embeddings for search (30 seconds)
   ├─ Create document summary (15 seconds)
   └─ Mark as "Ready" (90 seconds total)

4. Moodle syncs status every 30 seconds
5. Page shows: "Ready" ✓
```

**What Gets Stored:**

**In Moodle Database:**
```sql
local_savian_documents table:
- savian_doc_id: 24 (external service ID)
- course_id: 3 (this course only)
- title: "Healthcare Ethics Textbook"
- status: "completed"
- chunk_count: 156
- file_size: 20971520 bytes
- usermodified: teacher_id
```

**In External Service:**
- Original PDF file
- Extracted text
- 156 semantic chunks
- Vector embeddings for each chunk
- Document metadata

---

### Technical Flow

```
Teacher's Browser
     ↓ [Form Submit]
documents.php (Moodle)
     ↓ [Prepare multipart form data]
classes/api/client.php::upload_document()
     ↓ [cURL POST with API key header]
External Service: POST /api/moodle/v1/documents/upload/
     ↓ [Save file, queue processing]
Response: {document_id: 24, status: "processing"}
     ↓ [Store in Moodle DB]
Moodle shows: "Processing..." (auto-refresh every 30s)
     ↓ [Background: Service processes file]
     ↓ [Poll: GET /documents/24/]
Response: {status: "completed", chunk_count: 156}
     ↓ [Update Moodle DB]
Moodle shows: "Ready" ✓

Teacher sees: Document ready for course generation!
```

---

<a name="course-generation"></a>
## 4. Process 2: Course Generation (ADDIE Framework)

### User Journey - Step by Step

**Step 1: Teacher Fills Generation Form**
```
Navigate to: Course → Savian AI → Generate Course Content

Form has 4 sections:

📚 Basic Information:
  - Target Course: Healthcare Ethics (auto-filled)
  - Description: "Comprehensive medical ethics"
  - Context: "First-year medical students"

👥 Learner Profile (ADDIE):
  - Age Group: Undergraduate ✓
  - Industry: Healthcare ✓
  - Prior Knowledge: Beginner ✓

📄 Source Documents (Visual cards):
  [☑ Healthcare Ethics Textbook]
  [☑ Medical Case Studies]
  [☐ Clinical Guidelines]
  Duration: 8 weeks ✓

🎨 Content Types:
  [☑ Sections] (required)
  [☑ Pages] (required)
  [☑ Activities]
  [☑ Discussions]
  [☑ Quizzes]
  [☑ Assignments]

Clicks: "Generate Course Content"
```

**Step 2: ADDIE Processing (3-8 minutes)**
```
Real-time progress bar updates every 2.5 seconds:

Progress: 2% - Analyzing learner profile and context...
  ↳ AI analyzes: Age=undergrad, Industry=healthcare, Knowledge=beginner
  ↳ Determines: College-level vocabulary, clinical examples, beginner-friendly

Progress: 5% - Designing course structure...
  ↳ Creates outline: 8 weeks, Healthcare focus
  ↳ Plans sections: Week 1-8 topics

Progress: 10% - Course outline ready ✓
  ↳ Outline complete with learning objectives

Progress: 20% - Creating Week 1 content...
  ↳ Generates: Introduction pages, activities, discussions
  ↳ Pulls from: Healthcare Ethics Textbook chunks
  ↳ Adapts to: Undergraduate reading level

Progress: 30% - Creating Week 2 content...
Progress: 40% - Creating Week 3 content...
  ... (continues for each week) ...

Progress: 80% - All sections generated ✓

Progress: 85% - Adding quality markers...
  ↳ Calculates: QM alignment score
  ↳ Analyzes: Source coverage per page
  ↳ Measures: Learning depth (Bloom's taxonomy)
  ↳ Tags: Each page with confidence level

Progress: 90% - Calculating quality scores...
  ↳ Overall score: 87/100
  ↳ Source coverage: 92%
  ↳ Learning depth: 85/100
  ↳ Hallucination risk: Low

Progress: 100% - Course ready! ✓
  ↳ Auto-redirects to preview
```

**Step 3: Quality Preview**
```
Preview Page Shows:

1. AI Transparency Notice (Yellow box):
   "🤖 This course was generated using AI. Review required."

2. Quality Report Card:
   ┌────────────────────────────────────────────┐
   │ Overall: 87/100 | Coverage: 92% | Depth: 85│
   │ Risk: ✓ Low                                │
   │                                             │
   │ ✅ Strengths:                               │
   │ • Strong QM alignment (87%)                │
   │ • Excellent source grounding               │
   │ • Deep learning focus                      │
   │                                             │
   │ 📝 Focus Review On:                        │
   │ • Section 4: Verify clinical examples     │
   │ • Estimated review: 60-90 minutes         │
   └────────────────────────────────────────────┘

3. QM Alignment: 87% (38/44 standards) ✅ Certification Ready

4. Course Specifications:
   - Designed For: Undergraduate Students
   - Subject Area: Healthcare/Medical
   - Content Level: College Level
   - Instructional Approach: Case-Based Learning
   - Thinking Skills: Analysis & Application

5. Content Summary:
   8 Sections | 24 Pages | 8 Activities | 8 Discussions |
   8 Quizzes | 8 Assignments

6. Section Preview:
   📖 Week 1: Introduction to Healthcare Ethics [✅ 94%] [🎯 88]

   Prerequisites: None
   Estimated time: 4 hours
   QM: Clear objectives, measurable outcomes
   Sources: Healthcare Ethics Textbook

   Learning Objectives:
   • Define key ethical principles in healthcare
   • Analyze ethical dilemmas using frameworks
   • Apply ethics to clinical scenarios

   Content:
   📄 Understanding Healthcare Ethics [✓ Verified]
   🎯 Ethical Dilemma Analysis [✓ Verified]
   💬 Real-World Challenges Discussion [⚠️ Review]
   ✓ Self-Check Questions
   ❓ Section Quiz

   [👁 View] [✏️ Edit] buttons on each item

Teacher can:
- View full content in modals
- Edit before adding
- Uncheck unwanted items
```

**Step 4: Add to Course**
```
Teacher clicks: "Add to THIS Course"

Behind the scenes (10-30 seconds):
├─ Create 8 course sections
├─ Add 24 teaching pages (400-800 words each)
├─ Create 8 hands-on activities (as labels)
├─ Create 8 discussion forums
├─ Add 8 self-check formative assessments
├─ Create 8 quizzes (import 40 questions total)
├─ Create 8 assignments with rubrics
└─ Rebuild course cache

Success! ✅
Redirects to success page
```

**Step 5: Save to Knowledge Base (Optional)**
```
Success page prompts:

💡 Save to Knowledge Base?

Benefits:
✓ Future courses can use this approved content
✓ Students can chat with this material
✓ Reduces review time for similar courses

Clicks: "Save to Knowledge Base"

What happens:
1. Course structure sent to API
2. Service extracts text from all pages/activities
3. Creates new document: "Healthcare Ethics (Instructor Approved)"
4. Processes in 2-3 minutes
5. Available for future generation & chat

Knowledge base grows! 📈
```

---

### Technical Flow - Course Generation

```
User Submits Form
  ↓
create_course.php validates input
  ↓
Calls: API Client::generate_course_from_documents()
  ↓
POST https://app.savian.ai.vn/api/moodle/v1/generate/course-from-documents/

Request Body:
{
  "document_ids": [24, 25],
  "course_title": "Healthcare Ethics",
  "course_id": "3",
  "age_group": "undergrad",
  "industry": "healthcare",
  "prior_knowledge_level": "beginner",
  "duration_weeks": 8,
  "content_types": ["sections", "pages", "activities", ...]
}

Response:
{
  "success": true,
  "request_id": "uuid-here",
  "status": "pending"
}

  ↓
Save request_id to session
Redirect to polling page
  ↓
JavaScript polls every 2.5 seconds:
AJAX → local_savian_ai_get_generation_status
  ↓
GET /api/moodle/v1/generate/status/{uuid}/

Response updates:
{
  "status": "processing",
  "progress": 45,
  "details": {
    "stage": "addie_dev_section_3"
  }
}

JavaScript updates:
- Progress bar: 45%
- Stage text: "Creating Week 3 content..."

  ↓ (repeats every 2.5s)
  ↓
When status = "completed":
{
  "status": "completed",
  "progress": 100,
  "course_structure": {
    "title": "...",
    "quality_report": {...},
    "pedagogical_metadata": {...},
    "sections": [
      {
        "number": 1,
        "title": "Week 1: Introduction",
        "summary": "<p>...</p>",
        "learning_objectives": [...],
        "content": [
          {"type": "page", "title": "...", "content": "..."},
          {"type": "activity", ...},
          {"type": "quiz", ...}
        ]
      }
    ]
  }
}

  ↓
External service saves course_structure to session
JavaScript redirects to preview page
  ↓
Preview displays all quality info and content
Teacher reviews, clicks "Add to Course"
  ↓
course_builder->add_content_to_course()

Loops through sections:
  For each section:
    - create_section() → Moodle course section
    - For each content item:
      switch (type):
        'page' → create_page() → Moodle page module
        'activity' → create_activity() → Moodle label
        'discussion' → create_discussion() → Moodle forum
        'formative' → create_formative_assessment() → Label with Q&A
        'quiz' → create_quiz() → Moodle quiz + questions
        'assignment' → create_assignment() → Moodle assignment

  - rebuild_course_cache()

  ↓
Success! Course has 8 new sections with all content
Teacher sees success page with save option
```

---

<a name="chat-conversations"></a>
## 5. Process 3: Chat Conversations

### User Journey

**Student Opens Chat:**
```
On any course page:
1. Sees purple bubble (bottom-right)
2. Clicks to open
3. Chat window appears

Welcome message: "Hi! I'm your AI tutor for Healthcare Ethics.
                  Ask me anything about the course materials!"
```

**Student Asks Question:**
```
Student types: "What are the four principles of biomedical ethics?"
Clicks send (or presses Enter)
```

**What Happens Behind the Scenes:**

```
1. Frontend (chat_widget.js):
   - Captures message
   - Gets conversation ID (if existing) or null
   - Gets course ID from page
   - Shows "AI is typing..." indicator

2. AJAX Call:
   Ajax.call([{
     methodname: 'local_savian_ai_send_chat_message',
     args: {
       message: "What are the four principles...",
       conversationid: 123 or 0,
       courseid: 3,
       documentids: []  // Auto-includes course docs
     }
   }])

3. External Service (classes/external/chat.php):
   - Validates parameters
   - Checks capability (student has :use)

4. Chat Manager (classes/chat/manager.php):
   - Loads or creates conversation
   - Gets conversation UUID

5. API Client:
   POST https://app.savian.ai.vn/api/moodle/v1/chat/send/

   {
     "message": "What are the four principles...",
     "conversation_id": "uuid" or null,
     "user_id": "456",
     "user_email": "student@example.com",
     "user_role": "student",
     "course_id": "3",
     "course_name": "Healthcare Ethics",
     "document_ids": [],  // Service auto-includes course docs
     "language": "en"
   }

6. External Service:
   - Searches course documents for relevant content
   - Finds chunks about biomedical ethics
   - Generates contextual response
   - Returns sources used

   Response:
   {
     "response": "The four principles of biomedical ethics are:
                  1. Autonomy - Respect patient's right to choose
                  2. Beneficence - Do good for the patient
                  3. Non-maleficence - Do no harm
                  4. Justice - Fair distribution of resources

                  These principles guide medical decision-making...",
     "conversation_id": "uuid",
     "sources": [
       {
         "document_id": 24,
         "title": "Healthcare Ethics Textbook",
         "chunk_id": 45,
         "relevance": 0.94
       }
     ]
   }

7. Chat Manager:
   - Saves user message to DB
   - Saves AI response to DB
   - Formats content (markdown → HTML)
   - Returns to frontend

8. Frontend:
   - Displays user message
   - Displays AI response with formatting
   - Shows sources: "Source: Healthcare Ethics Textbook"
   - Enables feedback buttons

Student sees answer with sources! ✓
```

**Conversation Persistence:**
```
Database stores:

local_savian_chat_conversations:
- id: 123
- conversation_uuid: "uuid-here"
- user_id: 456
- course_id: 3
- title: "Biomedical Ethics Q&A"
- message_count: 2
- last_message_at: timestamp

local_savian_chat_messages:
- id: 1, conversation_id: 123, role: "user", content: "What are..."
- id: 2, conversation_id: 123, role: "assistant", content: "The four..."

Next time student opens chat:
→ Loads conversation 123
→ Shows full history
→ Can continue conversation
```

---

<a name="question-generation"></a>
## 6. Process 4: Question Generation

### Quick Flow

```
Teacher: Generate Questions → From Documents
Selects: Documents (visual cards)
Topic: "Ethical Principles"
Question count: 5
Difficulty: Medium
Bloom's level: Apply

Submits
  ↓
API generates 5 questions from selected documents
Questions returned in 10-20 seconds
  ↓
Preview shows:
Q1: Multiple choice about autonomy
Q2: True/False on beneficence
Q3: Short answer on applying principles
... etc

Teacher reviews
Clicks: "Add to Question Bank"
  ↓
qbank_creator imports to Moodle question bank
5 questions added ✓
```

**What Makes It Smart:**
- Uses document content (RAG)
- Respects Bloom's taxonomy level
- Aligns with difficulty setting
- Creates proper Moodle question format
- Includes feedback for each answer

---

<a name="knowledge-feedback-loop"></a>
## 7. Process 5: Knowledge Feedback Loop

### The Virtuous Cycle

```
Generation 1:
Documents: [Healthcare Ethics Textbook]
  ↓ Generate
Course: Healthcare Ethics 101
  ↓ Review (90 min)
Quality: 72/100, Coverage: 65%
  ↓ Approve & Save to KB
Knowledge Base: [Textbook] + [Approved Course 101]

Generation 2 (2 weeks later):
Documents: [Textbook] + [Approved Course 101]  ← Better sources!
  ↓ Generate
Course: Advanced Healthcare Ethics
  ↓ Review (60 min)  ← Less time!
Quality: 85/100, Coverage: 88%  ← Better quality!
  ↓ Approve & Save to KB
Knowledge Base: [Textbook] + [Course 101] + [Advanced Course]

Generation 3 (1 month later):
Documents: [Textbook] + 2 Approved Courses  ← Even better!
  ↓ Generate
Course: Clinical Ethics Applications
  ↓ Review (40 min)  ← Even less time!
Quality: 91/100 QM Certified! ✓
Coverage: 94%

Knowledge compounds over time! 📈
Review time decreases: 90 → 60 → 40 minutes
Quality increases: 72 → 85 → 91
```

**Technical Process:**

```
After course added to Moodle:
  ↓
Success page shows: "💡 Save to Knowledge Base?"
Teacher clicks: "Save"
  ↓
save_to_knowledge_base.php
  ↓
API Client::save_approved_course_to_knowledge_base()
  ↓
POST /api/moodle/v1/courses/save-approved/

{
  "course_title": "Healthcare Ethics 101",
  "course_structure": {... full structure ...},
  "approved_by": "Dr. Smith",
  "approval_date": "2026-01-03"
}

  ↓
External Service:
- Extracts text from all pages, activities, discussions
- Chunks into searchable segments
- Generates embeddings
- Creates document: "Healthcare Ethics 101 (Instructor Approved)"
- Tags: ['approved', 'instructor_reviewed', 'healthcare']

  ↓
Response: {document_id: 127, status: "processing"}
  ↓
Moodle shows: "Course saved! Available in 2-3 minutes"
  ↓
After processing:
- Appears in documents list
- Available for course generation
- Available for chat context
```

---

<a name="behind-the-scenes"></a>
## 8. Behind the Scenes

### What Moodle Does

**Database Tables (6 total):**
```sql
1. local_savian_documents
   - Stores document metadata (not content)
   - Links to external service document ID
   - Tracks processing status

2. local_savian_generations
   - History of generation requests
   - Links request_id to course
   - Tracks completion

3. local_savian_chat_conversations
   - Conversation metadata
   - UUID for external service tracking
   - User and course scoping

4. local_savian_chat_messages
   - Individual messages (user + AI)
   - Content, sources, feedback
   - Timestamps

5. local_savian_chat_settings
   - User widget preferences
   - Position, minimized state

6. local_savian_chat_course_config
   - Per-course chat settings
   - Enable/disable, welcome message
```

### What External Service Does

**1. Document Processing:**
- Text extraction (PDF, DOCX, TXT)
- Semantic chunking (overlap for context)
- Vector embeddings (for search)
- Summary generation

**2. Course Generation (ADDIE):**
```
A - Analysis:
  ↳ Analyze learner profile (age, industry, knowledge)
  ↳ Analyze document content and structure
  ↳ Determine appropriate pedagogy

D - Design:
  ↳ Create course outline (sections, topics)
  ↳ Plan learning objectives
  ↳ Design assessment strategy

D - Development:
  ↳ Generate each section's content
  ↳ Create activities, discussions, assessments
  ↳ Adapt to age/industry
  ↳ Maintain 400-800 word page length

I - Implementation:
  ↳ Add quality markers
  ↳ Calculate QM alignment
  ↳ Tag confidence levels

E - Evaluation:
  ↳ Final quality scoring
  ↳ Identify priority reviews
  ↳ Generate instructor guidance
```

**3. Quality Control (v2.1):**
```
For each page:
├─ Source coverage check: How much from documents?
├─ Hallucination detection: Any unsupported claims?
├─ Learning depth analysis: Bloom's taxonomy level?
└─ Assign confidence tag: ✓ High, ⚠️ Medium, ❗ Low

Overall:
├─ QM alignment: 44 standards checked
├─ Learning depth: Bloom's distribution
└─ Recommendations: What to review
```

**4. Chat Processing:**
```
Message received:
  ↓
Retrieve relevant chunks (vector similarity search)
  ↓
Context: 3-5 most relevant chunks from course documents
  ↓
Generate response using retrieved context
  ↓
Cite sources used
  ↓
Return response + sources
```

---

<a name="data-flow"></a>
## 9. Data Flow - What Goes Where

### Data Stored in Moodle

**Local Only:**
- Document metadata (titles, status, not content)
- Generation history
- Chat conversations and messages
- User preferences
- Course configurations

**Not Stored Locally:**
- Document file content
- Generated course structures (session only, then deleted)
- AI model responses (except chat messages)

### Data Sent to External Service

**On Document Upload:**
- ✅ File content (PDF/DOCX)
- ✅ Course ID and name
- ✅ User ID (teacher who uploaded)
- ❌ Student data

**On Course Generation:**
- ✅ Document IDs (not content - already uploaded)
- ✅ Course title, description
- ✅ Generation parameters (age, industry, etc.)
- ✅ User ID (teacher requesting)
- ❌ Student data
- ❌ Existing course content

**On Chat:**
- ✅ Message text
- ✅ User ID, email (for context)
- ✅ Course ID (for document scoping)
- ✅ Conversation history (for context)
- ❌ Other students' chats
- ❌ Grades or assessment data

**Privacy:**
- All transmission disclosed in Privacy API
- Users informed via transparency notices
- GDPR compliant (export/deletion available)

---

<a name="security-privacy"></a>
## 10. Security & Privacy

### How Your Data is Protected

**1. API Authentication:**
```
Every request includes:
Header: X-API-Key: [encrypted_key_from_config]

No API key in code, only in database configuration
```

**2. User Permissions:**
```
Before any action:
- require_login() - Must be logged in
- require_capability() - Must have permission
- confirm_sesskey() - CSRF protection on forms

Examples:
- View documents: Checks 'local/savian_ai:use'
- Generate content: Checks 'local/savian_ai:generate'
- View chat history: Checks 'local/savian_ai:viewchathistory'
```

**3. Data Scoping:**
```
Documents:
- Scoped to course (course_id)
- Teachers see only their course docs
- Can only delete their own uploads

Chat:
- Scoped to user (user_id)
- Students see only their chats
- Teachers can view for support

Generations:
- Scoped to user and course
- History tracks who generated what
```

**4. GDPR Compliance:**
```
Privacy API implementation:
- Declares what data is stored
- Declares what's sent to external service
- Provides data export
- Provides data deletion

User rights:
✓ Right to know (Privacy API declarations)
✓ Right to access (export their data)
✓ Right to be forgotten (delete their data)
✓ Right to object (opt-out of features)
```

---

## 11. Performance & Scalability

### How Fast Is It?

**Document Upload:**
- Upload: 5-30 seconds (depends on file size)
- Processing: 30-90 seconds (chunking + embedding)
- Total: ~2 minutes for 20MB PDF

**Course Generation:**
- 4-week course: 3-5 minutes
- 8-week course: 5-8 minutes
- 12-week course: 8-12 minutes

**Question Generation:**
- 5 questions: 10-20 seconds
- 10 questions: 20-40 seconds

**Chat Response:**
- Instant to user (shows typing indicator)
- Actual response: 2-5 seconds
- With sources: 3-7 seconds

### Optimization

**Caching:**
- Document metadata cached in Moodle
- Course structures in session (temporary)
- Chat conversations in database

**Async Processing:**
- Document processing: Background
- Course generation: Async with polling
- Question generation: Synchronous (fast enough)

**Scalability:**
- Handles 100+ documents per course
- Supports 1000+ students chatting
- Scales with external service capacity

---

## 12. Troubleshooting - When Things Go Wrong

### Common Issues & Solutions

**Issue: "Connection failed" error**
```
Cause: API credentials incorrect or service unreachable

Fix:
1. Check API URL: https://app.savian.ai.vn/api/moodle/v1/
2. Verify API key is valid
3. Click "Validate Connection"
4. Check service status
```

**Issue: Teacher can't access features**
```
Cause: Missing capabilities

Fix:
1. Site Admin → Users → Define roles
2. Edit "Teacher" role
3. Grant: local/savian_ai:use and local/savian_ai:generate
4. Save
```

**Issue: Document stuck "Processing"**
```
Cause: Processing error or timeout

Fix:
1. Wait 5 minutes (sometimes just slow)
2. Refresh page
3. If still stuck: Delete and re-upload
4. Check file is valid PDF/DOCX
```

**Issue: Chat not responding**
```
Cause: No documents uploaded or service issue

Fix:
1. Ensure at least 1 document uploaded to course
2. Document status = "Ready"
3. Check API connection
4. Try asking simpler question
```

**Issue: Generated content quality low**
```
Cause: Insufficient source documents

Fix:
1. Upload 2-3 comprehensive documents (not 1)
2. Ensure documents cover course topics
3. Check source coverage score in preview
4. Higher coverage = better quality
```

---

## 13. Best Practices

### For Administrators

✅ **Set up once, works for everyone:**
- Configure API credentials carefully
- Test with one course first
- Grant capabilities to appropriate roles
- Monitor usage via Chat Monitor dashboard

### For Teachers

✅ **Document Upload:**
- Upload 2-3 related documents for best results
- Use comprehensive textbooks or guides
- Course-specific documents preferred
- Wait for "Ready" status before generating

✅ **Course Generation:**
- Choose correct age group (affects vocabulary)
- Match industry to your subject
- Select 4-8 weeks for best quality
- Use all recommended content types

✅ **Quality Review:**
- Focus on yellow/red flagged items
- Green items need minimal review
- Verify clinical/technical examples
- Customize to your teaching style

✅ **Knowledge Base:**
- Save approved courses
- Future generations improve
- Build institutional knowledge
- Reduce review time over time

### For Students

✅ **Using Chat:**
- Be specific in questions
- One question at a time
- Check sources provided
- Use for understanding, not homework answers
- Provide feedback (thumbs up/down)

---

## 14. The Future - What's Next

### Planned Features

**1. Insert Content Between Topics (v1.1)**
- Generate Week 2, 3 to add after existing Week 1
- Incremental course building
- No need to regenerate entire course

**2. AI Assessment Evaluation (v1.2)**
- Automatic grading of short answers
- Draft feedback on essays
- Rubric-based suggestions
- 70% reduction in grading time

**3. Student Analytics & Personalization (v1.3)**
- Identify struggling students
- See which topics need help
- Generate personalized review content
- Auto-assign to students who need it

**4. Document Copying (v1.4)**
- Copy documents between courses
- Reuse approved content
- Share resources across teachers

---

## 15. Summary - How It All Comes Together

### The Complete Picture

**Phase 1: Setup** (One-time)
```
Admin: Configure API → Grant capabilities → Enable chat
→ Ready for teachers! ✓
```

**Phase 2: Content Creation** (Per Course)
```
Teacher: Upload docs → Generate course → Review quality → Add to Moodle
→ 8-week course created in 1 hour (vs 40 hours manual)
→ QM-aligned, age-appropriate, industry-specific
```

**Phase 3: Student Learning** (Ongoing)
```
Students: Use chat tutor → Get instant answers → Learn 24/7
→ Better understanding, less teacher support needed
```

**Phase 4: Continuous Improvement** (Over Time)
```
Teacher: Save approved course → Knowledge base grows
→ Next generation: Better quality, less review time
→ Institutional knowledge compounds
```

### The Value Proposition

**Time Savings:**
- Course creation: 40 hours → 1 hour (97% reduction)
- Question generation: 2 hours → 5 minutes (95% reduction)
- Grading support: Chat reduces clarification time

**Quality Improvements:**
- QM alignment: 85%+ (certifiable)
- Consistent pedagogy (ADDIE framework)
- Age-appropriate content
- Industry-relevant examples

**Student Benefits:**
- 24/7 AI tutor access
- Personalized learning support
- Source-backed answers
- Improved learning outcomes

**Institutional Benefits:**
- Knowledge base grows over time
- Best practices captured
- Reduced faculty workload
- Scalable quality content

---

## 16. Conclusion

Savian AI transforms Moodle from a content platform into an **intelligent learning ecosystem** where:

- 🎓 **Teachers** create courses in minutes, not weeks
- 💬 **Students** get instant, contextual help
- 📊 **Quality** is built-in, not bolted on
- 🔄 **Knowledge** compounds over time

All while maintaining **security**, **privacy**, and **Moodle standards**.

---

**Version**: 1.0.1 - Stable
**Last Updated**: January 2026
**For Questions**: See [tutorials.php](tutorials.php) or [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)
