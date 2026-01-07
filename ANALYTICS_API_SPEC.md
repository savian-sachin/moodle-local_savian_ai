# Learning Analytics API Specification for Django Backend

**Version**: 1.0
**Date**: 2026-01-07
**Endpoint**: `POST /api/moodle/v1/analytics/course-data/`

---

## Overview

This endpoint receives anonymized student performance and engagement data from Moodle installations for AI-powered learning analytics. The API should:

1. **Receive** anonymized student metrics from Moodle
2. **Analyze** engagement patterns, performance trends, and risk indicators
3. **Identify** at-risk students using ML/heuristics
4. **Generate** actionable insights and recommendations
5. **Return** insights to Moodle for instructor action

**Use Cases**:
- Identify students at risk of failing or dropping out
- Recommend interventions for struggling students
- Highlight struggling topics that need review sessions
- Identify popular resources for curriculum improvement
- Provide course effectiveness metrics

---

## Authentication

**Method**: API Key in Header

```http
X-API-Key: {organization_api_key}
```

- Same authentication as existing endpoints
- Validate org_code from request data

---

## API Endpoints

### 1. Submit Analytics Data

**Endpoint:** `POST /api/moodle/v1/analytics/course-data/`

**Purpose:** Submit student analytics data for AI-powered analysis

**Behavior:**
- All requests return `report_id` immediately
- Async processing via Celery for ≥50 students
- Use `report_id` to poll for status

**Returns:**
- `report_id` - Use this to poll status
- `status: "pending"` - Processing started (check status endpoint)
- `insights` - Only included if processing completed immediately (rare)

### 2. Check Processing Status (Polling)

**Endpoint:** `GET /api/moodle/v1/analytics/status/<report_id>/`

**Purpose:** Poll for completion status of analytics processing

**Parameters:**
- `report_id` - The report_id returned from POST request

**Response:**
- `status: "pending"` - Queued for processing
- `status: "processing"` - Currently analyzing
- `status: "completed"` - Done, includes `insights` object
- `status: "failed"` - Error occurred

### 3. Get Latest Report

**Endpoint:** `GET /api/moodle/v1/analytics/course/<course_id>/latest/`

**Purpose:** Retrieve the most recent completed report for a course

**Returns:** Full report with insights and student data

### 4. Get Report History

**Endpoint:** `GET /api/moodle/v1/analytics/course/<course_id>/history/`

**Purpose:** Retrieve all analytics reports for a course

**Returns:** Array of report summaries

---

## Request Format

### HTTP Method
```
POST /api/moodle/v1/analytics/course-data/
```

### Headers
```http
Content-Type: application/json
X-API-Key: {api_key}
```

### Request Body Schema

```json
{
  "course_id": "string",
  "course_name": "string",
  "course_code": "string",
  "report_metadata": {
    "report_type": "string",
    "trigger_type": "string",
    "date_from": "string|null",
    "date_to": "string",
    "generated_at": "string",
    "moodle_version": "string",
    "plugin_version": "string"
  },
  "course_summary": {
    "start_date": "string|null",
    "end_date": "string|null",
    "total_students": "integer",
    "total_activities": "integer",
    "total_assessments": "integer",
    "completion_rate": "float"
  },
  "students": [
    {
      "anon_id": "string",
      "enrollment_date": "string",
      "role": "string",
      "engagement_metrics": {
        "total_logins": "integer",
        "total_views": "integer",
        "total_actions": "integer",
        "create_actions": "integer",
        "update_actions": "integer",
        "time_spent_minutes": "integer",
        "last_access": "string|null",
        "days_since_last_access": "integer|null",
        "active_days": "integer",
        "forum_posts": "integer",
        "forum_replies": "integer",
        "discussions_started": "integer",
        "assignment_submissions": "integer",
        "assignment_submissions_late": "integer",
        "quiz_attempts": "integer",
        "quizzes_attempted": "integer",
        "resources_accessed": "integer",
        "activity_completion_rate": "float",
        "completed_activities": "integer",
        "total_activities": "integer"
      },
      "grade_metrics": {
        "current_grade": "float|null",
        "quiz_average": "float",
        "assignment_average": "float",
        "highest_grade": "float",
        "lowest_grade": "float",
        "grade_percentile": "float",
        "grade_trend": "string",
        "graded_items": "integer",
        "passed_items": "integer"
      },
      "risk_indicators": {
        "at_risk": "boolean",
        "risk_score": "float",
        "risk_level": "string",
        "risk_factors": ["string"],
        "prediction_confidence": "float"
      },
      "activity_timeline": [
        {
          "date": "string",
          "logins": "integer",
          "actions": "integer",
          "time_spent_minutes": "integer"
        }
      ],
      "module_performance": []
    }
  ],
  "aggregated_insights": {
    "average_engagement": "float",
    "at_risk_count": "integer",
    "high_performers_count": "integer",
    "struggling_topics": [],
    "popular_resources": []
  },
  "completion_data": {
    "completed_count": "integer",
    "in_progress_count": "integer",
    "not_started_count": "integer",
    "avg_completion_time_days": "float",
    "completion_rate": "float"
  }
}
```

---

## Field Descriptions

### Top Level

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `course_id` | string | Yes | Moodle course ID (converted to string) |
| `course_name` | string | Yes | Full course name |
| `course_code` | string | Yes | Course short name/code |

### Report Metadata

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `report_type` | string | Yes | Type: `on_demand`, `scheduled`, `real_time`, `end_of_course` |
| `trigger_type` | string | Yes | Trigger: `manual`, `cron`, `event`, `completion` |
| `date_from` | string\|null | No | ISO 8601 timestamp or null for all time |
| `date_to` | string | Yes | ISO 8601 timestamp (end of report period) |
| `generated_at` | string | Yes | ISO 8601 timestamp when report was generated |
| `moodle_version` | string | Yes | Moodle version number |
| `plugin_version` | string | Yes | Savian AI plugin version |

### Course Summary

| Field | Type | Description |
|-------|------|-------------|
| `start_date` | string\|null | Course start date (ISO 8601) |
| `end_date` | string\|null | Course end date (ISO 8601) |
| `total_students` | integer | Number of enrolled students |
| `total_activities` | integer | Total course modules/activities |
| `total_assessments` | integer | Total graded assessments |
| `completion_rate` | float | Overall course completion rate (0.0-1.0) |

### Student Data

Each student object contains:

#### Engagement Metrics

| Field | Type | Description |
|-------|------|-------------|
| `total_logins` | integer | Total login count |
| `total_views` | integer | Total page views |
| `total_actions` | integer | Total actions performed |
| `time_spent_minutes` | integer | Estimated time spent (in minutes) |
| `last_access` | string\|null | Last access timestamp (ISO 8601) |
| `days_since_last_access` | integer\|null | Days since last activity |
| `active_days` | integer | Number of days with activity |
| `forum_posts` | integer | Total forum posts |
| `assignment_submissions` | integer | Assignments submitted |
| `assignment_submissions_late` | integer | Late submissions |
| `quiz_attempts` | integer | Total quiz attempts |
| `activity_completion_rate` | float | Completion rate (0.0-1.0) |

#### Grade Metrics

| Field | Type | Description |
|-------|------|-------------|
| `current_grade` | float\|null | Current course grade (0-100) or null |
| `quiz_average` | float | Average quiz score (0-100) |
| `assignment_average` | float | Average assignment score (0-100) |
| `highest_grade` | float | Highest grade achieved |
| `lowest_grade` | float | Lowest grade achieved |
| `grade_percentile` | float | Percentile rank (0.0-1.0) |
| `grade_trend` | string | Trend: `improving`, `stable`, `declining` |
| `graded_items` | integer | Number of graded items |
| `passed_items` | integer | Number of items passed |

#### Risk Indicators

| Field | Type | Description |
|-------|------|-------------|
| `at_risk` | boolean | Whether student is at risk |
| `risk_score` | float | Risk score (0.0-1.0, higher = more risk) |
| `risk_level` | string | Level: `low`, `medium`, `high` |
| `risk_factors` | array | List of risk factor descriptions |
| `prediction_confidence` | float | Confidence in prediction (0.0-1.0) |

#### Activity Timeline

Array of daily activity data (last 30 days):

```json
{
  "date": "2026-01-07",
  "logins": 2,
  "actions": 45,
  "time_spent_minutes": 65
}
```

---

## Response Format

### Synchronous Response (< 50 students)

**HTTP Status**: `200 OK`

**Returns insights immediately:**

```json
{
  "success": true,
  "report_id": "rep_abc123xyz789",
  "insights_generated": true,
  "insights": {
    "at_risk_students": [
      {
        "anon_id": "a1b2c3d4e5f6...",
        "risk_level": "high",
        "risk_score": 0.85,
        "recommended_actions": [
          "Schedule 1-on-1 meeting within 3 days",
          "Provide supplementary materials for Week 3 topics",
          "Enable peer tutoring or study group",
          "Consider extension for upcoming assignment"
        ],
        "risk_factors": [
          "No access in 14 days",
          "Declining grade trend (from 72% to 58%)",
          "Low quiz performance (avg 45%)",
          "Missing 3 assignments"
        ],
        "intervention_priority": "urgent",
        "suggested_contact_date": "2026-01-10"
      }
    ],
    "course_recommendations": [
      "15 students struggling with 'Machine Learning Basics' (Week 3) - consider adding review session",
      "High engagement on video tutorials - add more visual content",
      "Assignment 2 has 40% late submission rate - may need deadline extension or clearer instructions",
      "Forum participation low (35%) - consider adding discussion prompts or graded participation"
    ],
    "intervention_priority": [
      {
        "anon_id": "a1b2c3...",
        "priority": "urgent",
        "suggested_contact_date": "2026-01-10",
        "reason": "No activity for 14+ days and failing grade"
      }
    ],
    "struggling_topics": [
      {
        "topic": "Machine Learning Basics",
        "module_name": "Week 3: ML Fundamentals",
        "students_struggling": 15,
        "avg_grade": 58.5,
        "recommended_action": "Create review session or supplementary materials"
      }
    ],
    "high_performers": [
      {
        "anon_id": "xyz789...",
        "current_grade": 95.5,
        "completion_rate": 1.0,
        "recommendation": "Consider as peer tutor"
      }
    ],
    "engagement_insights": {
      "average_engagement_score": 0.72,
      "low_engagement_count": 8,
      "peak_activity_days": ["Monday", "Wednesday"],
      "peak_activity_hours": ["14:00-16:00", "19:00-21:00"]
    }
  },
  "processed_students": 45,
  "timestamp": "2026-01-07T15:30:00Z",
  "processing_time_ms": 1250
}
```

### Asynchronous Response (All requests)

**HTTP Status**: `200 OK`

**Returns report_id for polling:**

```json
{
  "success": true,
  "report_id": "rep_abc123xyz789",
  "status": "pending",
  "message": "Analytics processing started. Poll /analytics/status/<report_id>/ for results.",
  "estimated_time_seconds": 45,
  "student_count": 150
}
```

**Moodle will then poll:** `GET /api/moodle/v1/analytics/status/<report_id>/`

---

### Status Endpoint Responses

**GET /api/moodle/v1/analytics/status/<report_id>/**

#### While Processing

**HTTP Status**: `200 OK`

```json
{
  "success": true,
  "report_id": "rep_abc123xyz789",
  "status": "processing",
  "progress": 65,
  "message": "Analyzing student performance...",
  "students_processed": 98,
  "students_total": 150
}
```

#### Completed

**HTTP Status**: `200 OK`

```json
{
  "success": true,
  "report_id": "rep_abc123xyz789",
  "status": "completed",
  "insights_generated": true,
  "insights": {
    "at_risk_students": [...],
    "course_recommendations": [...],
    "engagement_insights": {...}
  },
  "processed_students": 150,
  "timestamp": "2026-01-07T15:30:00Z"
}
```

#### Failed

**HTTP Status**: `200 OK`

```json
{
  "success": false,
  "report_id": "rep_abc123xyz789",
  "status": "failed",
  "error": "LLM analysis failed",
  "timestamp": "2026-01-07T15:30:00Z"
}
```

---

### Error Responses

#### 400 Bad Request
```json
{
  "success": false,
  "error": "Invalid request format",
  "details": {
    "field": "students",
    "message": "students field is required and must be an array"
  }
}
```

#### 401 Unauthorized
```json
{
  "success": false,
  "error": "Invalid API key"
}
```

#### 500 Internal Server Error
```json
{
  "success": false,
  "error": "Internal server error processing analytics",
  "timestamp": "2026-01-07T15:30:00Z"
}
```

---

## Privacy & Security Notes

### Data Privacy

1. **Anonymization**: All user IDs are SHA256 hashed with a salt
   - Format: 64-character hexadecimal string
   - Irreversible: Cannot be converted back to original user IDs
   - Consistent: Same user always produces same hash
   - Example: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2`

2. **No PII**: Request contains NO personally identifiable information:
   - ❌ No names
   - ❌ No email addresses
   - ❌ No IP addresses
   - ❌ No usernames
   - ✅ Only: Anonymized IDs + aggregated metrics

3. **GDPR Compliance**:
   - Anonymized data is not considered personal data
   - Right to erasure: Hash ensures data cannot be linked back to individual
   - Data retention: Moodle auto-deletes reports after configurable period (default: 1 year)

### Security Requirements

1. **Authentication**: Validate API key and org_code
2. **Rate Limiting**: Implement rate limits (suggested: 100 requests/hour per org)
3. **Input Validation**: Validate all fields and data types
4. **SQL Injection**: Use parameterized queries
5. **XSS Prevention**: Sanitize all string inputs before storing/displaying

---

## Django Implementation Guide

### Recommended Architecture

```
views.py
  ↓
analytics/
├── analytics_processor.py    # Main processing logic
├── risk_analyzer.py          # ML/heuristic risk analysis
├── insights_generator.py     # Generate recommendations
├── models.py                 # Analytics report storage
└── serializers.py            # DRF serializers
```

### Sample Django View

```python
from rest_framework.decorators import api_view, authentication_classes
from rest_framework.response import Response
from rest_framework import status
from .serializers import AnalyticsReportSerializer
from .analytics_processor import AnalyticsProcessor

@api_view(['POST'])
@authentication_classes([APIKeyAuthentication])
def course_analytics(request):
    """
    Receive and process course analytics data from Moodle
    """
    # Validate request data
    serializer = AnalyticsReportSerializer(data=request.data)
    if not serializer.is_valid():
        return Response({
            'success': False,
            'error': 'Invalid request format',
            'details': serializer.errors
        }, status=status.HTTP_400_BAD_REQUEST)

    # Extract validated data
    report_data = serializer.validated_data

    # Process analytics
    processor = AnalyticsProcessor(report_data)

    try:
        # Analyze risk
        at_risk_students = processor.analyze_risk()

        # Generate insights
        insights = processor.generate_insights()

        # Store report
        report_id = processor.save_report()

        # Return response
        return Response({
            'success': True,
            'report_id': report_id,
            'insights_generated': True,
            'insights': insights,
            'processed_students': len(report_data['students']),
            'timestamp': timezone.now().isoformat(),
        }, status=status.HTTP_200_OK)

    except Exception as e:
        logger.error(f"Analytics processing error: {str(e)}")
        return Response({
            'success': False,
            'error': 'Internal server error processing analytics'
        }, status=status.HTTP_500_INTERNAL_SERVER_ERROR)
```

### Risk Analysis Algorithm (Suggested)

```python
def analyze_student_risk(student_data):
    """
    Analyze risk level for a single student

    Returns: {
        'at_risk': bool,
        'risk_score': float,
        'risk_level': str,
        'risk_factors': list,
        'recommended_actions': list
    }
    """
    risk_score = 0
    risk_factors = []
    actions = []

    # Factor 1: Inactivity (high weight)
    days_since_access = student_data['engagement_metrics']['days_since_last_access']
    if days_since_access and days_since_access > 14:
        risk_score += 0.30
        risk_factors.append(f'No access in {days_since_access} days')
        actions.append('Schedule immediate 1-on-1 check-in')
    elif days_since_access and days_since_access > 7:
        risk_score += 0.15
        risk_factors.append('Low recent activity')

    # Factor 2: Poor performance
    current_grade = student_data['grade_metrics'].get('current_grade')
    if current_grade and current_grade < 50:
        risk_score += 0.25
        risk_factors.append(f'Failing grade ({current_grade:.1f}%)')
        actions.append('Provide supplementary materials')
    elif current_grade and current_grade < 60:
        risk_score += 0.12
        risk_factors.append(f'Low grade ({current_grade:.1f}%)')

    # Factor 3: Low completion
    completion = student_data['engagement_metrics']['activity_completion_rate']
    if completion < 0.3:
        risk_score += 0.25
        risk_factors.append(f'Low completion ({completion*100:.0f}%)')
        actions.append('Review and simplify assignment instructions')

    # Factor 4: Declining trend
    if student_data['grade_metrics']['grade_trend'] == 'declining':
        risk_score += 0.10
        risk_factors.append('Declining grade trend')
        actions.append('Identify specific struggling topics')

    # Determine risk level
    at_risk = risk_score >= 0.5
    if risk_score >= 0.7:
        risk_level = 'high'
    elif risk_score >= 0.5:
        risk_level = 'medium'
    else:
        risk_level = 'low'

    return {
        'at_risk': at_risk,
        'risk_score': round(risk_score, 2),
        'risk_level': risk_level,
        'risk_factors': risk_factors,
        'recommended_actions': actions
    }
```

### Database Models (Suggested)

```python
from django.db import models

class AnalyticsReport(models.Model):
    report_id = models.CharField(max_length=50, unique=True)
    course_id = models.CharField(max_length=20)
    course_name = models.CharField(max_length=255)
    report_type = models.CharField(max_length=20)  # on_demand, scheduled, etc.
    student_count = models.IntegerField()
    at_risk_count = models.IntegerField()
    report_data = models.JSONField()  # Full report for reference
    insights_data = models.JSONField()  # Generated insights
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'analytics_reports'
        indexes = [
            models.Index(fields=['course_id', '-created_at']),
            models.Index(fields=['report_type']),
        ]

class StudentRiskProfile(models.Model):
    report = models.ForeignKey(AnalyticsReport, on_delete=models.CASCADE)
    anon_id = models.CharField(max_length=64)  # SHA256 hash
    risk_level = models.CharField(max_length=10)  # low, medium, high
    risk_score = models.FloatField()
    risk_factors = models.JSONField()
    recommended_actions = models.JSONField()
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'student_risk_profiles'
        indexes = [
            models.Index(fields=['anon_id']),
            models.Index(fields=['risk_level']),
        ]
```

---

## Testing

### Sample cURL Request

```bash
curl -X POST https://app.savian.ai.vn/api/moodle/v1/analytics/course-data/ \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -d @sample_analytics_request.json
```

### Test Data

See `sample_analytics_request.json` (sample file should be created with realistic test data)

---

## Performance Considerations

1. **Large Courses**: Reports can contain 100+ students
   - Use database transactions
   - Consider background task queue (Celery) for processing
   - Return immediate 202 Accepted with report_id, process async

2. **Database Queries**: Optimize for bulk inserts
   - Use bulk_create() in Django
   - Index on frequently queried fields

3. **Response Time**: Target < 2 seconds for < 100 students
   - Cache frequently accessed data
   - Use database connection pooling

---

## Future Enhancements

### Phase 2 Features (Planned)

1. **ML-Based Risk Prediction**:
   - Train model on historical data
   - Predict risk 2-3 weeks in advance
   - Confidence scores based on model performance

2. **Topic-Level Analysis**:
   - `struggling_topics` field will be populated
   - Identify specific course modules causing difficulty
   - Recommend targeted interventions

3. **Resource Popularity Analysis**:
   - `popular_resources` field will be populated
   - Identify most/least used materials
   - Recommendations for curriculum improvement

4. **Cohort Comparison**:
   - Compare current cohort to historical data
   - Benchmark against similar courses
   - Trend analysis over multiple semesters

---

## Contact & Support

For questions or issues with this API:
- **Email**: dev@savian.ai.vn
- **Slack**: #analytics-api channel
- **Documentation**: https://docs.savian.ai.vn/analytics

---

## Changelog

### v1.0 (2026-01-07)
- Initial specification
- Core analytics endpoint
- Risk identification
- Basic insights generation
