# Savian AI Moodle Plugin - Developer Guide

> **Version**: 2.2.0-beta
> **Last Updated**: January 2026
> **For**: Future developers and contributors
> **Moodle Version**: 4.5+

---

## 📋 Table of Contents

1. [Plugin Architecture](#plugin-architecture)
2. [Directory Structure](#directory-structure)
3. [Core Components](#core-components)
4. [Database Schema](#database-schema)
5. [External API Integration](#external-api-integration)
6. [Frontend Architecture](#frontend-architecture)
7. [Feature Documentation](#feature-documentation)
8. [Development Workflow](#development-workflow)
9. [Security & Privacy](#security-privacy)
10. [Testing Guide](#testing-guide)
11. [Extending the Plugin](#extending-the-plugin)

---

<a name="plugin-architecture"></a>
## 1. Plugin Architecture

### Overview

The Savian AI plugin is a **local Moodle plugin** that integrates AI-powered content generation and intelligent tutoring into Moodle 4.5+. It follows Moodle's plugin architecture standards and uses external API services for AI capabilities.

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    MOODLE FRONTEND                           │
│  ┌────────────────────┐  ┌───────────────────────────────┐  │
│  │  Web Pages (PHP)   │  │   AMD JavaScript Modules      │  │
│  │  - create_course   │  │   - chat_widget.js            │  │
│  │  - documents       │  │   - course_content_editor.js  │  │
│  │  - chat            │  │   - chat_interface.js         │  │
│  └─────────┬──────────┘  └──────────────┬────────────────┘  │
│            │                             │                    │
│  ┌─────────▼─────────────────────────────▼────────────────┐  │
│  │           PHP Classes (Namespaced)                      │  │
│  │  ┌──────────────┐  ┌─────────────┐  ┌──────────────┐  │  │
│  │  │ API Client   │  │Course Builder│  │Chat Manager  │  │  │
│  │  │              │  │              │  │              │  │  │
│  │  │ Communicates │  │ Creates      │  │ Manages      │  │  │
│  │  │ with external│  │ Moodle       │  │ conversations│  │  │
│  │  │ AI service   │  │ content      │  │              │  │  │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  │  │
│  │         │                  │                  │          │  │
│  └─────────┼──────────────────┼──────────────────┼──────────┘  │
│            │                  │                  │              │
└────────────┼──────────────────┼──────────────────┼──────────────┘
             │                  │                  │
   ┌─────────▼──────────────────▼──────────────────▼─────────┐
   │              Moodle Database (PostgreSQL/MySQL)          │
   │  - local_savian_documents                                │
   │  - local_savian_generations                              │
   │  - local_savian_chat_* (4 tables)                        │
   │  - course, course_sections, course_modules (Moodle core) │
   │  - question, question_answers (Moodle core)              │
   └────────────────────┬─────────────────────────────────────┘
                        │
             ┌──────────▼──────────┐
             │  External AI Service │
             │  (API-based)         │
             └──────────────────────┘
```

### Design Principles

1. **Separation of Concerns**: UI, business logic, data access, API communication are separate
2. **Moodle Standards**: Follows all Moodle coding and security standards
3. **Extensibility**: Easy to add new content types, features
4. **Security First**: No credentials in code, proper capability checks
5. **User Experience**: Progressive disclosure, real-time feedback, responsive design

---

<a name="directory-structure"></a>
## 2. Directory Structure

```
local/savian_ai/
├── version.php                   # Plugin metadata and version
├── lib.php                       # Core functions and callbacks
├── settings.php                  # Admin settings page
├── DEVELOPER_GUIDE.md            # This file
│
├── db/                           # Database and services
│   ├── install.xml              # Database schema
│   ├── upgrade.php              # Version upgrade scripts
│   ├── access.php               # Capabilities definition
│   ├── services.php             # External functions (AJAX)
│   ├── hooks.php                # Moodle 4.5+ hook callbacks
│   └── privacy.php              # Privacy API (GDPR compliance)
│
├── lang/                         # Language strings
│   ├── en/
│   │   └── local_savian_ai.php  # English (350+ strings)
│   └── vi/
│       └── local_savian_ai.php  # Vietnamese (350+ strings)
│
├── classes/                      # PHP Classes (PSR-4 autoloaded)
│   ├── api/
│   │   └── client.php           # External API communication
│   ├── content/
│   │   ├── course_builder.php   # Create Moodle course content
│   │   └── qbank_creator.php    # Question bank integration
│   ├── chat/
│   │   ├── manager.php          # Chat conversation management
│   │   └── analytics.php        # Chat statistics
│   ├── external/
│   │   ├── chat.php             # Chat AJAX services
│   │   └── generation.php       # Generation status services
│   ├── form/
│   │   └── upload_document_form.php  # Document upload form
│   └── hook_callbacks/
│       ├── before_standard_head_html.php  # CSS loading
│       └── before_footer_html.php         # Chat widget init
│
├── amd/                          # JavaScript (AMD modules)
│   ├── src/                     # Source files
│   │   ├── chat_widget.js       # Floating chat bubble
│   │   ├── chat_interface.js    # Full-page chat
│   │   ├── chat_history.js      # Teacher conversation viewer
│   │   └── course_content_editor.js  # View/edit modals
│   └── build/                   # Compiled/minified (auto-generated)
│
├── styles/                       # CSS Stylesheets
│   ├── savian.css               # Minimal plugin branding
│   └── chat_widget.css          # Complete chat UI styles
│
├── pix/                          # Images and icons
│   └── icon.png                 # Plugin icon (72×72)
│
└── [Page Files]                  # Top-level PHP pages
    ├── index.php                # Main dashboard
    ├── documents.php            # Document management
    ├── create_course.php        # Course generation (main feature)
    ├── generate.php             # Question generation
    ├── chat.php                 # Full-page chat interface
    ├── chat_history.php         # Teacher conversation history
    ├── chat_monitor.php         # Admin monitoring
    ├── chat_course_settings.php # Per-course chat config
    ├── course.php               # Course-specific dashboard
    └── save_to_knowledge_base.php  # KB feedback loop
```

---

<a name="core-components"></a>
## 3. Core Components

### 3.1 API Client (`classes/api/client.php`)

**Purpose**: Single point of communication with external AI service.

**Key Methods**:

```php
// Authentication
public function validate(): object
    // Validates API credentials
    // Returns: {success: bool, organization: string}

// Document Management
public function upload_document(string $filepath, string $title, array $metadata): object
    // Uploads file to external service for processing
    // Metadata: description, subject_area, tags, course_id, course_name
    // Returns: {success: bool, document_id: int, status: string}

public function get_documents(array $params = []): object
    // Lists uploaded documents
    // Params: page, per_page, status, course_id
    // Returns: {documents: array, pagination: object}

public function delete_document(int $doc_id): object
    // Deletes document from external service
    // Returns: {success: bool}

// Course Generation (ADDIE v2.0+)
public function generate_course_from_documents(
    array $document_ids,
    string $course_title,
    array $options = []
): object
    // Generates course structure using ADDIE framework
    // Options: course_id, description, target_audience, duration_weeks,
    //          age_group, industry, prior_knowledge_level, content_types
    // Returns: {success: bool, request_id: string, status: 'pending'}
    // Then poll get_generation_status() for results

public function get_generation_status(string $request_id): object
    // Polls async generation progress
    // Returns: {status: string, progress: int, course_structure: object}
    // Status: pending|processing|completed|failed
    // Progress: 0-100 with ADDIE stage indicators

// Question Generation
public function generate_questions(string $topic, array $options = []): object
    // Generates questions from topic (no documents)

public function generate_questions_from_docs(
    array $document_ids,
    string $topic,
    array $options = []
): object
    // RAG-based question generation from documents

// Chat
public function chat_send(
    string $message,
    string $conversation_uuid = null,
    array $options = []
): object
    // Sends chat message to AI tutor
    // Options: user_id, user_email, user_role, course_id, document_ids
    // Returns: {response: string, conversation_id: string, sources: array}

public function chat_conversations(array $params = []): object
    // Lists user's conversations

public function chat_feedback(
    string $message_uuid,
    int $feedback,
    string $comment = ''
): object
    // Submits thumbs up/down feedback

// Knowledge Feedback Loop (v2.2)
public function save_approved_course_to_knowledge_base(
    object $course_structure,
    string $course_title,
    int $course_id,
    string $approved_by,
    string $request_id = null
): object
    // Saves approved course back to external service as a document
    // Returns: {success: bool, document_id: int, status: 'processing'}
```

**Authentication**:
- Uses `X-API-Key` header
- API key stored in Moodle config: `get_config('local_savian_ai', 'api_key')`
- Never hardcoded in code

**Error Handling**:
```php
private function error_response($message, $http_code): object
    // Returns standardized error object
    // All methods return this on failure
```

---

### 3.2 Course Builder (`classes/content/course_builder.php`)

**Purpose**: Converts AI-generated course structures into Moodle course content.

**Main Method**:

```php
public function add_content_to_course(
    int $course_id,
    object $course_structure
): array
    // Creates sections and content in Moodle course
    // Returns: ['sections_created' => int, 'pages_created' => int, ...]
```

**Content Creation Methods**:

```php
// Section
protected function create_section($course_id, $section_data, $section_num): int
    // Uses Moodle's course_create_section()
    // Sets name, summary, learning objectives

// Page (resource)
protected function create_page($course_id, $section_num, $page_data): int
    // Creates 'page' module instance
    // Adds to section sequence
    // Rebuilds course cache

// Activity (label with instructions)
protected function create_activity($course_id, $section_num, $activity_data): int
    // Uses 'label' module for hands-on exercises
    // Styled with alert box

// Discussion (forum)
protected function create_discussion($course_id, $section_num, $discussion_data): int
    // Creates 'forum' module
    // Type: general, ungraded

// Formative Assessment (label with Q&A)
protected function create_formative_assessment($course_id, $section_num, $formative_data): int
    // Creates label with collapsible questions/answers
    // Uses <details> HTML5 element

// Quiz
protected function create_quiz($course_id, $section_num, $quiz_data): int
    // Creates 'quiz' module
    // Adds questions via qbank_creator
    // Configures quiz settings

// Assignment
protected function create_assignment($course_id, $section_num, $assignment_data): int
    // Creates 'assign' module
    // Sets due dates, grading
    // Rubrics: Future enhancement
```

**Moodle APIs Used**:
- `course_create_section()` - Create section
- `$DB->insert_record()` - Insert module instances
- `rebuild_course_cache()` - Refresh course structure
- Section sequence management

---

### 3.3 Question Bank Creator (`classes/content/qbank_creator.php`)

**Purpose**: Imports AI-generated questions into Moodle's question bank.

**Supported Question Types**:
1. **Multichoice** - Single or multiple answers
2. **True/False** - Binary choice
3. **Short Answer** - Text matching
4. **Essay** - Long-form response
5. **Matching** - Pair items

**Main Method**:

```php
public function add_to_question_bank(
    array $questions,
    int $courseid,
    int $categoryid = 0
): array
    // Imports questions to question bank
    // Creates category if needed
    // Returns: ['success' => [...], 'failed' => [...]]
```

**Question Data Format** (from API):
```json
{
    "type": "multichoice",
    "questiontext": "What is...?",
    "generalfeedback": "Explanation...",
    "answers": [
        {"text": "Answer 1", "fraction": 100, "feedback": "Correct!"},
        {"text": "Answer 2", "fraction": 0, "feedback": "Try again"}
    ]
}
```

**Key Features**:
- Auto-creates "Savian AI Generated" category
- Handles bilingual content (en/vi)
- Adds feedback fields to prevent warnings
- Truncates names to first 10 words for readability

---

### 3.4 Chat Manager (`classes/chat/manager.php`)

**Purpose**: Manages chat conversations and message persistence.

**Key Methods**:

```php
public function send_message(
    string $message,
    int $conversationid,
    int $courseid,
    array $documentids
): array
    // Sends message, saves to DB, returns formatted response

protected function create_conversation($courseid, $documentids): object
    // Creates new conversation record
    // Generates UUID for external API tracking

protected function format_content(string $content): string
    // Formats markdown to HTML
    // Sanitizes output
```

**Database Tables**:
- `local_savian_chat_conversations` - Conversation metadata
- `local_savian_chat_messages` - Individual messages
- `local_savian_chat_settings` - User preferences
- `local_savian_chat_course_config` - Per-course settings

---

### 3.5 External Functions (`classes/external/`)

**Purpose**: AJAX services for frontend JavaScript.

**Chat Services** (`external/chat.php`):
```php
// Send message
public static function send_message($message, $conversationid, $courseid, $documentids)
    // Called from chat_widget.js
    // Returns formatted messages with sources

// Get conversation
public static function get_conversation($conversationid)
    // Loads conversation history

// List conversations
public static function list_conversations($courseid)
    // For chat history page

// Submit feedback
public static function submit_feedback($messageid, $feedback, $comment)
    // Thumbs up/down on AI responses

// Save widget state
public static function save_widget_state($position, $minimized)
    // Persists user preferences

// Get course documents
public static function get_course_documents($courseid)
    // Loads document list for selector
```

**Generation Services** (`external/generation.php`):
```php
// Get status
public static function get_generation_status($requestid)
    // Polls async generation
    // Saves course_structure to session when complete
    // Returns progress and stage info

// Save structure
public static function save_course_structure($structure)
    // Saves edited course structure to session
    // Used by view/edit system
```

**Registration**: All services registered in `db/services.php` with:
- `methodname` - Function name
- `ajax` => true - Callable from JavaScript
- `loginrequired` => true - Authentication required

---

<a name="database-schema"></a>
## 4. Database Schema

### Table Definitions

#### `local_savian_documents`
Stores uploaded documents and processing status.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| savian_doc_id | INT | External service document ID |
| course_id | INT NULL | Moodle course ID (null = global) |
| title | VARCHAR(255) | Document title |
| description | TEXT | Document description |
| subject_area | VARCHAR(255) | Subject classification |
| status | VARCHAR(50) | processing_status from API |
| progress | INT | Processing progress (0-100) |
| chunk_count | INT | Number of chunks processed |
| qna_count | INT | Q&A pairs generated |
| file_size | BIGINT | File size in bytes |
| file_type | VARCHAR(50) | MIME type |
| tags | TEXT | JSON array of tags |
| is_active | TINYINT | Soft delete flag |
| last_synced | INT | Last sync timestamp |
| timecreated | INT | Creation timestamp |
| timemodified | INT | Modification timestamp |
| usermodified | INT | User ID who modified |

**Indexes**: savian_doc_id, course_id, status

---

#### `local_savian_generations`
History of AI generation requests.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| request_id | VARCHAR(100) | External request UUID |
| generation_type | VARCHAR(50) | 'questions' or 'course_content' |
| course_id | INT | Moodle course ID |
| user_id | INT | Requesting user |
| questions_count | INT | Number of questions generated |
| status | VARCHAR(50) | pending/completed/failed |
| response_data | TEXT | JSON response |
| timecreated | INT | Request timestamp |
| timemodified | INT | Update timestamp |

**Indexes**: request_id, course_id, user_id

---

#### `local_savian_chat_conversations`
Chat conversation sessions.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| conversation_uuid | VARCHAR(36) | External API conversation ID |
| user_id | INT | Moodle user ID |
| course_id | INT NULL | Course context (null = global) |
| title | VARCHAR(255) | Conversation title |
| message_count | INT | Number of messages |
| last_message_at | INT | Last activity timestamp |
| timecreated | INT | Creation timestamp |

**Indexes**: conversation_uuid, user_id, course_id

---

#### `local_savian_chat_messages`
Individual chat messages.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| conversation_id | INT | FK to conversations |
| message_uuid | VARCHAR(36) | External API message ID |
| role | VARCHAR(20) | 'user' or 'assistant' |
| content | TEXT | Message content |
| sources | TEXT | JSON array of sources |
| feedback | INT NULL | 1 (helpful) or -1 (not helpful) |
| feedback_comment | TEXT | Optional feedback text |
| response_time_ms | INT | API response time |
| timecreated | INT | Message timestamp |

**Indexes**: conversation_id, message_uuid

---

#### `local_savian_chat_settings`
User chat preferences.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| user_id | INT | Moodle user ID |
| widget_position | VARCHAR(20) | bottom-right/bottom-left |
| widget_minimized | TINYINT | 1 = minimized, 0 = normal |
| timemodified | INT | Last update |

**Indexes**: user_id (unique)

---

#### `local_savian_chat_course_config`
Per-course chat settings.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| course_id | INT | Moodle course ID |
| chat_enabled | TINYINT | Enable chat for this course |
| students_can_chat | TINYINT | Allow student access |
| welcome_message | TEXT | Custom welcome message |
| auto_include_docs | TINYINT | Auto-include course documents |
| timemodified | INT | Last update |

**Indexes**: course_id (unique)

---

### Relationships

```
course
  ↓ (1:many)
local_savian_documents (course_id)

user
  ↓ (1:many)
local_savian_chat_conversations
  ↓ (1:many)
local_savian_chat_messages

course
  ↓ (1:many)
local_savian_generations
```

---

<a name="external-api-integration"></a>
## 5. External API Integration

### API Communication Flow

```
Moodle Plugin
    ↓ HTTP Request (cURL)
    ├── Header: X-API-Key: [from config]
    ├── Header: Content-Type: application/json
    └── Body: JSON payload
    ↓
External AI Service
    ↓ Processing (async for generation)
    ↓
Response (JSON)
    ↓
Parse & Validate
    ↓
Use in Moodle (create content, display chat, etc.)
```

### Key Endpoints

#### 1. Document Upload
```
POST /api/moodle/v1/documents/upload/

Multipart form data:
- document: File
- title: string
- description: string
- subject_area: string
- tags: JSON array
- course_id: string (optional)
- course_name: string (optional)

Response:
{
    "success": true,
    "document_id": 24,
    "status": "processing"
}
```

#### 2. Course Generation (ADDIE)
```
POST /api/moodle/v1/generate/course-from-documents/

Request:
{
    "document_ids": [24, 25],
    "course_title": "Introduction to Healthcare",
    "course_id": "2",
    "description": "...",
    "target_audience": "Medical students",
    "duration_weeks": 8,
    "age_group": "undergrad",           // v2.0
    "industry": "healthcare",           // v2.0
    "prior_knowledge_level": "beginner", // v2.0
    "content_types": ["sections", "pages", "activities", ...],
    "language": "en"
}

Response:
{
    "success": true,
    "request_id": "uuid",
    "status": "pending",
    "status_url": "/api/moodle/v1/generate/status/{uuid}/"
}
```

#### 3. Generation Status (Polling)
```
GET /api/moodle/v1/generate/status/{request_id}/

Response (Processing):
{
    "success": true,
    "status": "processing",
    "progress": 45,
    "details": {
        "stage": "addie_dev_section_3",  // ADDIE stages
        "duration_weeks": 8
    }
}

Response (Completed):
{
    "success": true,
    "status": "completed",
    "progress": 100,
    "course_structure": {
        "title": "...",
        "ai_transparency_notice": "<div>...</div>",  // v2.0
        "quality_markers": {...},                    // v2.1
        "quality_report": {...},                     // v2.1
        "pedagogical_metadata": {...},               // v2.0
        "sections": [
            {
                "number": 1,
                "title": "Week 1: Introduction",
                "summary": "<p>...</p>",
                "learning_objectives": [...],
                "prerequisites": [...],              // v2.0
                "estimated_hours": 4,                // v2.0
                "coverage_info": {...},              // v2.1
                "learning_depth": {...},             // v2.1
                "content": [
                    {
                        "type": "page",
                        "title": "...",
                        "content": "<html>...</html>",
                        "quality_tags": {            // v2.1
                            "source_confidence": "high",
                            "supplemented_content": false
                        }
                    },
                    {"type": "activity", ...},
                    {"type": "discussion", ...},
                    {"type": "formative", ...},
                    {"type": "quiz", ...},
                    {"type": "assignment", ...}
                ]
            }
        ],
        "glossary_terms": [...]
    },
    "sources": [...],
    "metadata": {...}
}
```

#### 4. Chat Message
```
POST /api/moodle/v1/chat/send/

Request:
{
    "message": "What is personalized learning?",
    "conversation_id": "uuid" or null,
    "user_id": "123",
    "user_email": "user@example.com",
    "user_role": "teacher",
    "course_id": "2",
    "course_name": "Course Name",
    "document_ids": [24, 26],
    "language": "en"
}

Response:
{
    "response": "Personalized learning is...",
    "conversation_id": "uuid",
    "sources": [
        {"document_id": 24, "title": "...", "chunk_id": 123}
    ]
}
```

#### 5. Save to Knowledge Base
```
POST /api/moodle/v1/courses/save-approved/

Request:
{
    "course_title": "Introduction to Healthcare",
    "course_id": "2",
    "course_name": "Healthcare 101",
    "course_structure": {... full structure ...},
    "generation_request_id": "uuid",
    "approved_by": "Dr. Smith",
    "approval_date": "2026-01-02",
    "instructor_notes": "Reviewed and approved"
}

Response:
{
    "success": true,
    "document_id": 127,
    "status": "processing",
    "estimated_processing_time": "2-3 minutes"
}
```

---

### ADDIE Progress Stages (v2.0)

Returned in `details.stage` during polling:

| Stage | Progress | Description |
|-------|----------|-------------|
| `addie_analysis` | 2% | Analyzing learner profile and context |
| `addie_design_outline` | 5-10% | Designing course structure |
| `addie_design_completed` | 10% | Course outline ready |
| `addie_dev_section_1` to `_N` | 10-80% | Generating each section |
| `addie_development_completed` | 80% | All sections generated |
| `addie_implementation` | 85% | Adding quality markers |
| `addie_evaluation` | 90% | Calculating quality scores |
| `addie_completed` | 100% | Course ready |

JavaScript polling handles `addie_dev_section_X` pattern dynamically.

---

<a name="frontend-architecture"></a>
## 6. Frontend Architecture

### AMD JavaScript Modules

Moodle uses AMD (Asynchronous Module Definition) for JavaScript. All modules in `amd/src/` are compiled to `amd/build/`.

#### `chat_widget.js` - Floating Chat Bubble

**Initialization**:
```javascript
// In before_footer_html hook callback
$PAGE->requires->js_call_amd('local_savian_ai/chat_widget', 'init', [$config]);
```

**Config Object**:
```javascript
{
    courseId: 2,
    welcomeMessage: "Hi! I'm your AI tutor...",
    position: "bottom-right",
    minimized: true,
    canManageDocuments: false  // Based on capability
}
```

**Key Features**:
- 3 states: minimized (bubble), normal (380×600px), fullscreen (90% viewport)
- Auto-detect course from body class
- AJAX message sending
- Real-time typing indicator
- Markdown + code syntax highlighting (highlight.js)
- MathJax for LaTeX equations
- Source attribution display

**Main Methods**:
```javascript
init(config)              // Initialize widget
toggle()                  // Minimize/restore
toggleFullscreen()        // Enter/exit fullscreen
sendMessage()             // Send via AJAX
loadConversation(id)      // Load history
renderMessage(msg)        // Display message
showTypingIndicator()     // "AI is typing..."
```

---

#### `course_content_editor.js` - View/Edit Modals

**Purpose**: Preview and edit generated content before adding to course.

**Initialization**:
```javascript
// Reads course structure from data attribute
var structure = $('#course-structure-data').attr('data-structure');
```

**Features**:
- View modal: Read-only content preview
- Edit modal: Modify title and content
- Save changes to session via AJAX
- Type-specific formatting:
  - Pages: HTML content
  - Activities: Instructions
  - Discussions: Prompts
  - Formative: Questions with collapsible answers
  - Quizzes: Questions with answer lists
  - Assignments: Instructions + rubric table

**Key Functions**:
```javascript
showViewModal(sectionIdx, itemIdx)   // Display content
showEditModal(sectionIdx, itemIdx)   // Edit content
saveItemEdits()                       // Save to session via AJAX
formatQuizContent(quiz)               // Format quiz display
formatAssignmentContent(assignment)   // Format with rubric
formatFormativeContent(formative)     // Format self-checks
```

---

### AJAX Service Calls

**Pattern**:
```javascript
require(['core/ajax'], function(Ajax) {
    Ajax.call([{
        methodname: 'local_savian_ai_send_chat_message',
        args: {
            message: 'Hello',
            conversationid: 123,
            courseid: 2,
            documentids: [24, 26]
        }
    }])[0].done(function(response) {
        // Handle success
    }).fail(function(error) {
        // Handle error
    });
});
```

**Available Services**:
- `local_savian_ai_send_chat_message`
- `local_savian_ai_get_conversation`
- `local_savian_ai_list_conversations`
- `local_savian_ai_submit_feedback`
- `local_savian_ai_save_widget_state`
- `local_savian_ai_get_course_documents`
- `local_savian_ai_get_generation_status`
- `local_savian_ai_save_course_structure`

---

### CSS Organization

#### `styles/savian.css` - Minimal Branding

**Philosophy**: Blend with Moodle's default theme, subtle purple accents only.

```css
/* Primary button */
.btn-savian {
    background: linear-gradient(135deg, #6C3BAA 0%, #8B5AC8 100%);
    color: white !important;
}

.btn-savian:hover {
    background: linear-gradient(135deg, #8B5AC8 0%, #9D6FD4 100%);
    transform: translateY(-2px);
}

/* Accent border for feature cards */
.savian-accent-card {
    border-left: 4px solid #6C3BAA;
}

/* Status badge */
.badge-savian-processing {
    background-color: #F3F0F9;
    color: #6C3BAA;
}
```

---

#### `styles/chat_widget.css` - Complete Chat UI

**Components**:
- Floating bubble (60×60px, gradient)
- Chat window (380×600px normal, 90vw×85vh fullscreen)
- Messages (user/AI styling)
- Input area with auto-resize
- Typing indicator animation
- Source citations
- Dark backdrop for fullscreen
- Accessibility (focus states, screen reader labels)
- Responsive (mobile breakpoints)

---

<a name="feature-documentation"></a>
## 7. Feature Documentation

### 7.1 Course Generation (ADDIE Framework)

**User Flow**:
1. Navigate to course → Savian AI → Generate Course Content
2. Fill form with 4 fieldsets:
   - Basic Info (description, context)
   - Learner Profile (age, industry, knowledge level)
   - Source Documents (select docs, set duration)
   - Content Types (6 types in card grid)
3. Submit → Redirect to polling page
4. Watch real-time progress bar (ADDIE stages, 2.5s updates)
5. Auto-redirect to preview when complete
6. Preview shows:
   - AI transparency notice
   - QM alignment score
   - Quality report (v2.1)
   - Pedagogical metadata
   - Sections with coverage badges
   - Pages with quality tags
   - View/Edit buttons per item
7. Click "Add to Course" → Content created
8. Success page offers "Save to Knowledge Base"

**Technical Implementation**:
- Form: `create_course.php` (lines 664-974)
- Polling: JavaScript AJAX (lines 210-310)
- Preview: Enhanced display (lines 316-850)
- Creation: `course_builder.php`
- Success: Knowledge feedback loop (lines 852-914)

---

### 7.2 Chat System

**Features**:
- Floating widget on course pages
- Full-page interface (`chat.php`)
- Teacher conversation history
- Admin monitoring dashboard
- Per-course settings

**Conversation Flow**:
```
1. User types message
2. chat_widget.js captures input
3. AJAX call to local_savian_ai_send_chat_message
4. Chat manager creates/loads conversation
5. API client sends to external service
6. Response received
7. Save user + assistant messages to DB
8. Return formatted messages to frontend
9. Display with markdown formatting
10. Show sources if available
```

**Session Persistence**:
- Conversation UUID stored in DB
- Messages saved for history
- Teacher can view all conversations
- Feedback tracked per message

---

### 7.3 Quality Control System (v2.1)

**Components**:

**1. Overall Quality Report**:
```php
$structure->quality_report = {
    overall_score: 59/100,
    source_coverage_average: 0.25 (25%),
    hallucination_risk: "low",
    learning_depth_average: 78/100,
    instructor_summary: {
        priority_reviews: ["Section 2: Verify content"],
        recommended_review_time: "80-120 minutes",
        strengths: ["Deep learning focus: 78/100"]
    }
}
```

**2. Section Quality**:
```php
$section->coverage_info = {
    coverage_score: 0.92 (92%),
    status: "excellent",
    instructor_note: "Strongly grounded in sources"
}

$section->learning_depth = {
    depth_score: 82/100,
    depth_level: "deep",
    bloom_distribution: {...}
}
```

**3. Page Quality Tags**:
```php
$item->quality_tags = {
    source_confidence: "high"|"medium"|"low",
    review_priority: "low"|"medium"|"high",
    supplemented_content: true|false,
    instructor_note: "...",
    verification_score: 92
}
```

**Display Logic**:
- Green ✓ badges for high confidence
- Yellow ⚠️ for medium (supplemented)
- Red ❗ for low (priority review)
- Blue ℹ️ for supplemented content flag

---

### 7.4 Knowledge Feedback Loop (v2.2)

**Purpose**: Save approved courses back to external service as reusable documents.

**Flow**:
```
1. Course generated and added to Moodle
2. Redirect to success page with save prompt
3. Session stores: course_structure, title, id, request_id
4. User clicks "Save to Knowledge Base"
5. save_to_knowledge_base.php processes
6. API call to /courses/save-approved/
7. External service extracts text, chunks, embeds
8. New document created (tagged "instructor_approved")
9. Available for future generation & chat
10. Session cleared
```

**Benefits**:
- Institutional knowledge compounds
- Future courses build on vetted content
- Reduced review time (60-120 min → 40-80 min)
- Students chat with approved courses
- Quality improves over time (60% → 85%+)

---

<a name="development-workflow"></a>
## 8. Development Workflow

### Adding a New Feature

**Step-by-Step Process**:

1. **Plan the Feature**
   - Define requirements
   - Identify affected components
   - Check Moodle APIs needed

2. **Update Version**
   ```php
   // version.php
   $plugin->version = 2026010106;  // Increment
   $plugin->release = 'v2.3.0-beta';
   ```

3. **Database Changes** (if needed)
   ```xml
   <!-- db/install.xml -->
   <TABLE NAME="local_savian_newtable">
       <FIELDS>...</FIELDS>
   </TABLE>
   ```

   ```php
   // db/upgrade.php
   if ($oldversion < 2026010106) {
       // Create table
       upgrade_plugin_savepoint(true, 2026010106, 'local', 'savian_ai');
   }
   ```

4. **Add Language Strings**
   ```php
   // lang/en/local_savian_ai.php
   $string['new_feature'] = 'New Feature';

   // lang/vi/local_savian_ai.php
   $string['new_feature'] = 'Tính năng mới';
   ```

5. **Implement Backend**
   - Create/modify classes in `classes/`
   - Add external functions if AJAX needed
   - Register in `db/services.php`

6. **Implement Frontend**
   - Create AMD module in `amd/src/`
   - Copy to `amd/build/` (or use grunt)
   - Add CSS if needed

7. **Test Thoroughly**
   - PHP syntax check
   - Moodle code checker
   - Manual testing (all roles)
   - Database upgrade test
   - Both languages

8. **Document**
   - Add to CHANGES.md
   - Update DEVELOPER_GUIDE.md
   - Update README.md if user-facing

9. **Purge Caches**
   ```bash
   php admin/cli/purge_caches.php
   ```

---

### Testing Checklist

Before committing:

```bash
# PHP Syntax
php -l file.php

# Moodle Code Checker (if available)
php admin/cli/codechecker.php --path=local/savian_ai

# Database Upgrade
# 1. Note current version
# 2. Increment version.php
# 3. Visit Site Admin → Notifications
# 4. Verify upgrade succeeds
# 5. Check new tables/fields exist

# External Services
# 1. Increment version
# 2. Purge caches
# 3. Test AJAX calls from browser console
# 4. Check db/services.php registration

# JavaScript
# 1. Copy src/ to build/
# 2. Purge caches
# 3. Check browser console for errors
# 4. Test on mobile viewport

# Multi-language
# 1. Change user language to Vietnamese
# 2. Verify all strings translate
# 3. Check for English fallbacks
```

---

### Debugging Tips

**Enable Moodle Debugging**:
```
Site Administration → Development → Debugging
- Debug messages: DEVELOPER
- Display debug messages: Yes
```

**PHP Debugging**:
```php
// Temporary debugging (remove before commit)
error_log("Debug: " . print_r($variable, true));
```

**JavaScript Debugging**:
```javascript
// Browser console
console.log('Chat message:', message);
console.error('Error:', error);

// Check AJAX calls in Network tab
```

**Database Queries**:
```php
// Check what's in DB
$DB->get_records('local_savian_documents', ['course_id' => 2]);
```

**Common Issues**:

1. **"Too much data passed to js_call_amd"**
   - Solution: Use data attributes instead of parameters
   - Example: `create_course.php` line 840-850

2. **"Modal factory deprecated"**
   - Solution: Use `core/modal` and `core/modal_save_cancel`
   - Example: `course_content_editor.js` line 16-17

3. **External service not found**
   - Solution: Increment version, purge caches
   - Check `db/services.php` registration

4. **CSS not loading**
   - Solution: Use hook callbacks, not late loading
   - Example: `classes/hook_callbacks/before_standard_head_html.php`

---

<a name="security-privacy"></a>
## 9. Security & Privacy

### Data Protection

**Sensitive Data Storage**:
- API keys: Moodle config (encrypted)
- User data: Moodle database (encrypted at rest)
- Chat messages: DB with user_id scoping
- External API: Validate all responses

**Never Store**:
- Raw API keys in code
- Passwords or tokens
- Unencrypted PII

---

### Input Validation

**All Parameters**:
```php
// Use PARAM_* constants
$courseid = required_param('courseid', PARAM_INT);
$text = optional_param('text', '', PARAM_TEXT);
$alpha = optional_param('code', '', PARAM_ALPHA);
$array = optional_param_array('ids', [], PARAM_INT);
```

**Forms**:
```php
// Always verify session key
if (data_submitted() && confirm_sesskey()) {
    // Process form
}
```

**Output Sanitization**:
```php
// HTML output
echo s($user_input);           // Escape special chars
echo format_text($content);     // Format with security

// URL output
$url = new moodle_url('/path/', ['param' => $value]);  // Auto-escapes
```

---

### Privacy API (GDPR)

**Implementation**: `db/privacy.php`

**Required Methods**:

```php
// Declare what data is stored
public static function get_metadata(collection $collection): collection {
    $collection->add_database_table('local_savian_chat_messages', [
        'content' => 'privacy:metadata:messages:content',
        'timecreated' => 'privacy:metadata:messages:timecreated'
    ], 'privacy:metadata:messages');

    $collection->add_external_location_link('savian_api', [
        'message' => 'privacy:metadata:external:message',
        'userid' => 'privacy:metadata:external:userid'
    ], 'privacy:metadata:external');

    return $collection;
}

// Export user's data
public static function export_user_data(approved_contextlist $contextlist) {
    // Export chat conversations, messages, settings
}

// Delete user's data
public static function delete_data_for_user(approved_contextlist $contextlist) {
    // Delete chat data, anonymize if needed
}
```

**Privacy Strings** (`lang/en/local_savian_ai.php`):
```php
$string['privacy:metadata:messages'] = 'Chat messages';
$string['privacy:metadata:messages:content'] = 'Message content';
$string['privacy:metadata:external'] = 'Savian AI Service';
$string['privacy:metadata:external:message'] = 'Chat messages sent to AI';
```

---

### Capability Checks

**All Pages**:
```php
require_login();
require_capability('local/savian_ai:use', $context);
```

**Sensitive Operations**:
```php
// Generate content
require_capability('local/savian_ai:generate', $context);

// View others' chat
require_capability('local/savian_ai:viewchathistory', $context);

// Manage settings
require_capability('local/savian_ai:manage', $context);
```

**Capability Definitions** (`db/access.php`):
- `local/savian_ai:use` - Basic feature access (students + teachers)
- `local/savian_ai:generate` - Generate content (teachers only)
- `local/savian_ai:manage` - Admin settings (managers only)
- `local/savian_ai:viewchathistory` - View all conversations (teachers)

---

<a name="testing-guide"></a>
## 10. Testing Guide

### Unit Testing (Optional but Recommended)

**Structure**:
```
tests/
├── api_client_test.php
├── course_builder_test.php
├── qbank_creator_test.php
└── chat_manager_test.php
```

**Example**:
```php
<?php
namespace local_savian_ai;

class api_client_test extends \advanced_testcase {
    public function test_validate_connection() {
        $this->resetAfterTest(true);

        $client = new \local_savian_ai\api\client();
        $response = $client->validate();

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->http_code);
    }
}
```

**Run Tests**:
```bash
php admin/tool/phpunit/cli/util.php --install
php admin/tool/phpunit/cli/util.php --buildconfig
vendor/bin/phpunit local/savian_ai/tests/
```

---

### Manual Testing

**Feature Test Matrix**:

| Feature | Test As | Steps | Expected Result |
|---------|---------|-------|----------------|
| Document Upload | Teacher | Upload PDF → Check processing | Status → Ready |
| Course Generation | Teacher | Select docs → Generate | Preview shows content |
| Progress Tracking | Teacher | Watch progress bar | Updates 0-100% |
| Quality Scores | Teacher | Check preview | Shows QM, coverage, depth |
| Chat Widget | Student | Open chat → Ask question | Response with sources |
| View Content | Teacher | Click View on page | Modal with full content |
| Edit Content | Teacher | Click Edit → Modify → Save | Changes persist |
| Save to KB | Teacher | After creation → Save | Document created |
| Vietnamese | Any | Change language | All strings translate |

---

### Integration Testing

**Scenario 1: Fresh Install**
1. Clean Moodle 4.5 install
2. Copy plugin to local/savian_ai/
3. Visit Site Admin → Notifications
4. Configure API settings
5. Assign capabilities
6. Test all features as teacher and student

**Scenario 2: Upgrade**
1. Install v2.0
2. Generate some content
3. Upgrade to v2.2
4. Verify existing data intact
5. Test new features work

**Scenario 3: Multi-Language**
1. Create course as English user
2. Switch to Vietnamese
3. Verify UI translates
4. Generate content in Vietnamese
5. Check chat works in both languages

---

<a name="extending-the-plugin"></a>
## 11. Extending the Plugin

### Adding a New Content Type

**Example**: Add "Video" content type

**Step 1**: Update Course Builder
```php
// classes/content/course_builder.php

protected function create_video($course_id, $section_num, $video_data) {
    global $DB;

    // Create using 'url' or 'page' module
    $page = new \stdClass();
    $page->course = $course_id;
    $page->name = $this->extract_string($video_data->title);
    $page->content = '<iframe src="' . s($video_data->url) . '"></iframe>';
    $page->id = $DB->insert_record('page', $page);

    // Add to section...
    return $page->id;
}

// Update switch statement
case 'video':
    $this->create_video($course_id, $section_num, $content_item);
    $results['videos_created']++;
    break;
```

**Step 2**: Update Preview
```php
// create_course.php - Icon helper
$icons = [
    'video' => '🎥',  // Add icon
    // ...
];
```

**Step 3**: Update View Modal
```javascript
// amd/src/course_content_editor.js

case 'video':
    return formatVideoContent(item);

function formatVideoContent(video) {
    return '<iframe src="' + video.url + '"></iframe>';
}
```

**Step 4**: Language Strings
```php
$string['content_type_video'] = 'Video Lectures';
```

**Step 5**: Test end-to-end

---

### Adding an External Service

**Example**: Add "regenerate_section" service

**Step 1**: Create External Function
```php
// classes/external/generation.php

public static function regenerate_section_parameters() {
    return new external_function_parameters([
        'sectionid' => new external_value(PARAM_INT, 'Section ID'),
        'documentids' => new external_multiple_structure(
            new external_value(PARAM_INT, 'Document ID')
        )
    ]);
}

public static function regenerate_section($sectionid, $documentids) {
    // Validate
    $params = self::validate_parameters(...);

    // Check capability
    require_capability('local/savian_ai:generate', $context);

    // Call API
    $client = new \local_savian_ai\api\client();
    $response = $client->regenerate_section(...);

    return ['success' => true, ...];
}

public static function regenerate_section_returns() {
    return new external_single_structure([...]);
}
```

**Step 2**: Register Service
```php
// db/services.php
'local_savian_ai_regenerate_section' => [
    'classname' => 'local_savian_ai\external\generation',
    'methodname' => 'regenerate_section',
    'type' => 'write',
    'ajax' => true,
    'loginrequired' => true
],
```

**Step 3**: Call from JavaScript
```javascript
Ajax.call([{
    methodname: 'local_savian_ai_regenerate_section',
    args: {sectionid: 5, documentids: [24, 26]}
}])[0].done(function(response) {
    // Handle result
});
```

**Step 4**: Increment Version & Purge Caches

---

### Modifying API Client

**Add New API Method**:

```php
// classes/api/client.php

public function new_api_feature(array $params): object {
    $data = [
        'param1' => $params['param1'] ?? 'default',
        'param2' => $params['param2'] ?? []
    ];

    return $this->request('POST', 'new-endpoint/', $data);
}
```

**Error Handling**:
- Always return object with `http_code`
- Use `error_response()` for failures
- Validate responses before using

---

### Database Migrations

**Adding a Field**:
```php
// db/upgrade.php
if ($oldversion < 2026010107) {
    $table = new xmldb_table('local_savian_documents');
    $field = new xmldb_field('new_field', XMLDB_TYPE_TEXT, null, null, null, null, null, 'existing_field');

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    upgrade_plugin_savepoint(true, 2026010107, 'local', 'savian_ai');
}
```

**Modifying a Field**:
```php
if ($oldversion < 2026010108) {
    $table = new xmldb_table('local_savian_messages');
    $field = new xmldb_field('content', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL);

    $dbman->change_field_type($table, $field);

    upgrade_plugin_savepoint(true, 2026010108, 'local', 'savian_ai');
}
```

---

## 12. Common Patterns

### Session Storage

```php
global $SESSION;

// Store data
$SESSION->savian_ai_pending_request = $request_id;

// Retrieve
$request_id = $SESSION->savian_ai_pending_request ?? null;

// Clear
unset($SESSION->savian_ai_pending_request);
```

### Moodle URL Construction

```php
$url = new moodle_url('/local/savian_ai/page.php', [
    'courseid' => $courseid,
    'action' => 'edit'
]);

// Redirect
redirect($url, 'Success message', null, 'success');
```

### HTML Output

```php
// Use html_writer for consistency
echo html_writer::start_div('card');
echo html_writer::tag('h3', 'Title', ['class' => 'card-title']);
echo html_writer::end_div();

// Or direct output with sanitization
echo '<p>' . s($user_input) . '</p>';
```

### Language Strings

```php
// Simple
echo get_string('pluginname', 'local_savian_ai');

// With placeholder
echo get_string('estimated_hours', 'local_savian_ai', $hours);
// String: $string['estimated_hours'] = '{$a} hours';

// With object
$a = new stdClass();
$a->sections = 4;
$a->pages = 12;
echo get_string('content_created_details', 'local_savian_ai', $a);
```

---

## 13. Data Flow Diagrams

### Course Generation Flow

```
┌─────────────────────────────────────────────────────────────┐
│ USER                                                         │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ create_course.php                                            │
│ • Display form with 4 fieldsets                             │
│ • Validate input (documents selected, sesskey)              │
│ • Capture: age_group, industry, prior_knowledge, content_types│
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ API Client: generate_course_from_documents()                │
│ • Build request payload                                     │
│ • Send POST with API key header                            │
│ • Return request_id for polling                             │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ Polling Page (action=poll)                                   │
│ • Display progress bar (30px, striped, animated)            │
│ • JavaScript polls every 2.5 seconds                        │
│ • AJAX: local_savian_ai_get_generation_status              │
│ • Update progress: 0% → 100%                                │
│ • Show ADDIE stages                                         │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ External Service (get_generation_status)                    │
│ • Checks status: pending/processing/completed/failed       │
│ • When completed: saves course_structure to session         │
│ • Returns progress and stage info                           │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ Preview Page (action=preview)                               │
│ • Load course_structure from session                        │
│ • Display quality cards (AI notice, QM, metadata, report)  │
│ • Show sections with coverage/depth badges                  │
│ • Show pages with quality tags (✓/⚠️/❗)                   │
│ • Initialize course_content_editor.js (view/edit)          │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ User: Clicks "Add to THIS Course"                          │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ course_builder->add_content_to_course()                    │
│ • Loop through sections                                     │
│ • create_section() - Moodle course section                 │
│ • Loop through content items:                               │
│   - create_page()                                           │
│   - create_activity()                                       │
│   - create_discussion()                                     │
│   - create_formative_assessment()                          │
│   - create_quiz() → qbank_creator->add_to_question_bank() │
│   - create_assignment()                                     │
│ • rebuild_course_cache()                                    │
│ • Return results array                                      │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ Success Page (action=success)                              │
│ • Show creation summary                                     │
│ • Display "Save to Knowledge Base" prompt (v2.2)           │
│ • Store generation data in session                          │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ save_to_knowledge_base.php (Optional)                      │
│ • API: save_approved_course_to_knowledge_base()            │
│ • Course becomes reusable document                          │
│ • Future generations build on approved content              │
└─────────────────────────────────────────────────────────────┘
```

---

### Chat Message Flow

```
┌─────────────────────────────────────────────────────────────┐
│ USER: Types message in chat widget                          │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ chat_widget.js: sendMessage()                               │
│ • Capture message text                                      │
│ • Get conversation ID (if existing)                         │
│ • Course ID from body class or config                       │
│ • Document IDs: empty array (auto-include)                 │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ AJAX: local_savian_ai_send_chat_message                    │
│ • external/chat.php::send_message()                        │
│ • Validates parameters                                      │
│ • Checks capability                                         │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ chat/manager.php: send_message()                            │
│ • Load or create conversation                               │
│ • Get conversation UUID                                     │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ API Client: chat_send()                                     │
│ • Build payload with user context                           │
│ • POST to external service                                  │
│ • Parse response                                            │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ Chat Manager: Save Messages to DB                          │
│ • Insert user message                                       │
│ • Insert assistant response                                 │
│ • Update conversation metadata                              │
│ • Format content (markdown → HTML)                         │
└────┬────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│ Return to JavaScript                                        │
│ • Display user message                                      │
│ • Display AI response with formatting                       │
│ • Show sources if available                                 │
│ • Enable feedback buttons                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 14. Version History

### v2.2.0-beta (January 2026)
- ✅ Knowledge Feedback Loop
- ✅ Quality Control System (v2.1)
- ✅ Save approved courses to knowledge base
- ✅ Enhanced quality reporting

### v2.0.0-beta (January 2026)
- ✅ ADDIE Framework Implementation
- ✅ Age/Industry Adaptation (6 levels × 7 industries)
- ✅ Quality Matters alignment scoring
- ✅ AI transparency notices
- ✅ Pedagogical metadata

### v1.5.0-beta (January 2026)
- ✅ Enhanced course generation
- ✅ View/Edit content before adding
- ✅ Real-time progress tracking
- ✅ 6 content types (activities, discussions, formative)

### v1.1.0-beta (January 2026)
- ✅ Chat widget (floating bubble)
- ✅ Full-page chat interface
- ✅ Teacher conversation history
- ✅ Per-course chat settings

### v1.0.0-beta (December 2025)
- ✅ Initial release
- ✅ Document upload
- ✅ Basic course generation
- ✅ Question generation

---

## 15. Troubleshooting

### Common Issues

**Issue**: "Function not found" error for external service
- **Cause**: Service not registered or version not incremented
- **Fix**: Increment version.php, purge caches

**Issue**: CSS not loading
- **Cause**: Loaded after <head>
- **Fix**: Use hook callbacks for CSS in <head>

**Issue**: AMD module not loading
- **Cause**: Not compiled or cached
- **Fix**: Copy src/ to build/, purge caches

**Issue**: Database upgrade fails
- **Cause**: Syntax error in install.xml or upgrade.php
- **Fix**: Check XMLDB syntax, test upgrade path

**Issue**: "Too much data" warning for js_call_amd
- **Cause**: Passing large objects as parameters
- **Fix**: Use data attributes instead

**Issue**: Session mutation error
- **Cause**: Modifying session after page output
- **Fix**: Don't modify session in question bank operations

---

## 16. Performance Considerations

### Optimization Tips

**Database Queries**:
```php
// BAD: N+1 queries
foreach ($conversations as $conv) {
    $messages = $DB->get_records('local_savian_chat_messages', ['conversation_id' => $conv->id]);
}

// GOOD: Single query with JOIN
$sql = "SELECT c.*, m.content FROM {local_savian_chat_conversations} c
        LEFT JOIN {local_savian_chat_messages} m ON m.conversation_id = c.id
        WHERE c.user_id = ?";
$records = $DB->get_records_sql($sql, [$userid]);
```

**AJAX Polling**:
- Use 2.5 second interval (balance responsiveness vs server load)
- Exponential backoff on errors
- Stop polling when not visible (Page Visibility API)

**CSS/JS Loading**:
- Minimize CSS files
- Use hooks for proper timing
- AMD modules auto-minified

**Caching**:
- Course structure in session (temporary)
- Document list: sync periodically, not every page load
- Chat: Load recent messages only, paginate history

---

## 17. Security Checklist

Before each release:

- [ ] No API keys in code
- [ ] All user input validated (PARAM_*)
- [ ] All forms use sesskey
- [ ] All outputs sanitized (s(), format_text())
- [ ] Capability checks on all sensitive operations
- [ ] No raw SQL (use $DB API)
- [ ] External API responses validated
- [ ] Privacy API implemented
- [ ] HTTPS enforced for external calls
- [ ] Error messages don't leak sensitive info

---

## 18. Contribution Guidelines

### Code Style

- Follow Moodle coding standards
- 4-space indentation (no tabs)
- Max line length: 132 characters
- PHPDoc on all public methods
- Meaningful variable names
- Comments explain "why", not "what"

### Pull Request Process

1. Fork repository
2. Create feature branch: `feature/description`
3. Make changes following standards
4. Test thoroughly (both languages)
5. Update DEVELOPER_GUIDE.md if architecture changes
6. Create PR with:
   - Description of changes
   - Testing performed
   - Screenshots if UI changes
7. Address review feedback
8. Squash commits before merge

### Commit Messages

```
Format: [Component] Brief description

Body:
- Detailed explanation
- Why the change was needed
- Any breaking changes

Example:
[Chat] Hide document selector for all users

- Simplified UI by removing manual document selection
- Chat automatically uses course-scoped documents
- Updated AMD module and purged caches
```

---

## 19. Resources

### Moodle Documentation
- [Plugin Development](https://docs.moodle.org/dev/Plugin_files)
- [Database API](https://docs.moodle.org/dev/Data_manipulation_API)
- [External Functions](https://docs.moodle.org/dev/External_functions_API)
- [Privacy API](https://docs.moodle.org/dev/Privacy_API)
- [AMD JavaScript](https://docs.moodle.org/dev/Javascript_Modules)
- [Hooks](https://docs.moodle.org/dev/Hooks)

### Plugin Guidelines
- [Moodle Plugins Directory](https://moodle.org/plugins/guidelines.php)
- [Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- [Security](https://docs.moodle.org/dev/Security)

### Testing
- [PHPUnit Testing](https://docs.moodle.org/dev/PHPUnit)
- [Behat Testing](https://docs.moodle.org/dev/Acceptance_testing)

---

## 20. Contact & Support

**For Development Questions**:
- Read this guide first
- Check Moodle dev docs
- Review existing code for patterns

**For Bug Reports**:
- Check GitHub issues
- Provide steps to reproduce
- Include Moodle version, PHP version
- Attach error logs if available

**For Feature Requests**:
- Open GitHub issue
- Describe use case
- Explain expected behavior
- Consider contributing a PR

---

**Last Updated**: January 2026
**Maintained By**: Savian AI Development Team
**License**: GPL v3 or later

---

*This guide is a living document. Update it when architecture changes, new features are added, or patterns evolve.*
