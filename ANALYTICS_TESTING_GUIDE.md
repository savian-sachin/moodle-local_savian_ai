# Learning Analytics - Comprehensive Testing Guide

**Version**: 1.1.0
**Date**: 2026-01-07
**Feature**: Learning Analytics System

---

## Overview

This guide provides step-by-step instructions for testing all components of the Savian AI Learning Analytics system.

**System Components to Test:**
1. ✅ Database schema and upgrades
2. ✅ Data extraction and anonymization
3. ✅ Manual analytics trigger (UI)
4. ✅ Scheduled tasks (daily/weekly cron)
5. ✅ Real-time event observers
6. ✅ End-of-course reports
7. ✅ Privacy compliance (GDPR)
8. ✅ CSV export functionality
9. ✅ Test data generator

---

## Prerequisites

### Required Configuration

1. **Moodle Installation**: Version 4.5 or higher
2. **PHP**: 8.1-8.3
3. **Database**: MySQL/PostgreSQL with proper permissions
4. **Plugin Version**: v1.1.0 or higher
5. **API Access**: Valid Savian AI API key configured

### Initial Setup

```bash
# 1. Navigate to plugin directory
cd /Users/sachinsharmap/PycharmProjects/moodle/moodle/local/savian_ai

# 2. Verify PHP server running
ps aux | grep "php -S"
# Should show: php -S localhost:8002

# 3. Check database connection
php admin/cli/check_database_schema.php
```

---

## Test Suite 1: Database Schema & Upgrades

### Test 1.1: Fresh Installation

**Objective**: Verify database tables are created correctly on plugin installation

**Steps:**
1. Navigate to: `http://localhost:8002/admin/index.php`
2. Moodle should detect the new plugin
3. Click "Upgrade Moodle database now"
4. Verify success message

**Expected Result:**
```
✓ Plugin installed successfully
✓ 3 new analytics tables created:
  - mdl_local_savian_analytics_reports
  - mdl_local_savian_analytics_cache
  - mdl_local_savian_analytics_events
```

**Verification SQL:**
```sql
-- Check tables exist
SELECT table_name
FROM information_schema.tables
WHERE table_name LIKE 'mdl_local_savian_analytics%';

-- Should return 3 rows
```

### Test 1.2: Upgrade from v1.0.x

**Objective**: Verify upgrade script works for existing installations

**Steps:**
1. Ensure version.php shows version >= 2026010700
2. Visit: `http://localhost:8002/admin/index.php`
3. Click "Upgrade Moodle database now"

**Expected Result:**
```
✓ Upgrade completed
✓ 3 analytics tables added
✓ No errors in upgrade log
```

### Test 1.3: Table Structure Validation

**Objective**: Verify table fields and indexes

**SQL Checks:**
```sql
-- Verify analytics_reports table structure
DESCRIBE mdl_local_savian_analytics_reports;

-- Expected fields:
-- id, course_id, report_type, trigger_type, date_from, date_to,
-- student_count, activity_count, status, api_response, error_message,
-- retry_count, user_id, timecreated, timemodified

-- Verify indexes exist
SHOW INDEXES FROM mdl_local_savian_analytics_reports;

-- Expected indexes:
-- PRIMARY (id), course_id (FK), report_type, status, timecreated
```

**Pass Criteria:** ✅ All fields present, indexes created, foreign keys valid

---

## Test Suite 2: Test Data Generation

### Test 2.1: Basic Test Data Generation

**Objective**: Generate 50 test students with realistic activity

**Command:**
```bash
php cli/generate_test_data.php --course=3 --students=50 --weeks=12
```

**Expected Output:**
```
========================================
 Test Data Generator for Analytics
========================================

Course: Test Course (ID: 3)
Students to create: 50
Activity period: 12 weeks

Student profiles:
  - High performers: 10
  - Average students: 30
  - At-risk students: 10

Found in course:
  - Quizzes: 5
  - Assignments: 3
  - Forums: 2

Creating test users and enrolling...
[Progress bar]

Generating activity logs...
[Progress bar]

========================================
 Test Data Generation Complete!
========================================

Summary:
  - Users created: 50
  - Activity logs: 8,500+
  - Quiz attempts: 250
  - Assignment submissions: 150
  - Forum posts: 200
  - Completion records: 1,800
```

**Verification:**
```sql
-- Count test users
SELECT COUNT(*) FROM mdl_user WHERE username LIKE 'teststudent_%';
-- Expected: 50

-- Count activity logs
SELECT COUNT(*) FROM mdl_logstore_standard_log
WHERE userid IN (SELECT id FROM mdl_user WHERE username LIKE 'teststudent_%');
-- Expected: 8,000-10,000

-- Check student profiles (should have varied grades)
SELECT
    CASE
        WHEN qg.grade/q.sumgrades >= 0.8 THEN 'High'
        WHEN qg.grade/q.sumgrades >= 0.6 THEN 'Average'
        ELSE 'At-Risk'
    END as profile,
    COUNT(*) as count
FROM mdl_quiz_grades qg
JOIN mdl_quiz q ON q.id = qg.quiz
WHERE qg.userid IN (SELECT id FROM mdl_user WHERE username LIKE 'teststudent_%')
GROUP BY profile;

-- Expected: Roughly 20% high, 60% average, 20% at-risk
```

**Pass Criteria:** ✅ 50 users created, varied activity patterns, realistic grade distribution

### Test 2.2: Large Scale Test Data

**Objective**: Test with larger dataset (200 students)

**Command:**
```bash
php cli/generate_test_data.php --course=3 --students=200 --weeks=16 --at-risk-percentage=25
```

**Expected**: 200 users, 30,000+ logs, 25% at-risk

**Pass Criteria:** ✅ Completes without errors, database handles large volume

---

## Test Suite 3: Data Extraction & Anonymization

### Test 3.1: Anonymizer Functionality

**Test Script:**
```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/anonymizer.php');

$anonymizer = new \local_savian_ai\analytics\anonymizer();

// Test 1: Consistency
$user_id = 123;
$hash1 = $anonymizer->anonymize_user_id($user_id);
$hash2 = $anonymizer->anonymize_user_id($user_id);

echo "Test 1 - Consistency:\n";
echo "  Hash 1: {$hash1}\n";
echo "  Hash 2: {$hash2}\n";
echo "  Match: " . ($hash1 === $hash2 ? 'PASS ✓' : 'FAIL ✗') . "\n\n";

// Test 2: Uniqueness
$hash_different = $anonymizer->anonymize_user_id(456);
echo "Test 2 - Uniqueness:\n";
echo "  Different hash: " . ($hash1 !== $hash_different ? 'PASS ✓' : 'FAIL ✗') . "\n\n";

// Test 3: Length
echo "Test 3 - Hash Length:\n";
echo "  Length: " . strlen($hash1) . "\n";
echo "  Expected: 64\n";
echo "  Match: " . (strlen($hash1) === 64 ? 'PASS ✓' : 'FAIL ✗') . "\n\n";

// Test 4: Validation
$validation = $anonymizer->validate_anonymization(123);
print_r($validation);
?>
```

**Expected Output:**
```
Test 1 - Consistency:
  Hash 1: a1b2c3d4e5f6g7h8i9j0...
  Hash 2: a1b2c3d4e5f6g7h8i9j0...
  Match: PASS ✓

Test 2 - Uniqueness:
  Different hash: PASS ✓

Test 3 - Hash Length:
  Length: 64
  Expected: 64
  Match: PASS ✓

Array (
    [salt_exists] => 1
    [hash_length] => 1
    [consistency] => 1
    [uniqueness] => 1
    [no_reversibility] => 1
)
```

**Pass Criteria:** ✅ All tests return PASS

### Test 3.2: Data Extraction

**Test Script:**
```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/data_extractor.php');

$extractor = new \local_savian_ai\analytics\data_extractor();

$course_id = 3;
$user_id = $DB->get_field('user', 'id', ['username' => 'teststudent_0001']);

// Test activity extraction
$activity = $extractor->get_user_activity($course_id, $user_id);
print_r($activity);

// Test grade extraction
$grades = $extractor->get_user_grades($course_id, $user_id);
print_r($grades);

// Test completion
$completion = $extractor->get_completion_status($course_id, $user_id);
print_r($completion);
?>
```

**Expected Output:**
```
stdClass Object (
    [total_actions] => 250
    [total_views] => 180
    [first_access] => 1701388800
    [last_access] => 1736208000
    [total_logins] => 45
    [days_since_last_access] => 2
)

stdClass Object (
    [avg_grade] => 75.5
    [graded_items] => 8
    [highest_grade] => 95.0
    [lowest_grade] => 60.0
    [current_grade] => 78.5
)

stdClass Object (
    [total_activities] => 25
    [completed_activities] => 22
    [completion_rate] => 0.88
)
```

**Pass Criteria:** ✅ All queries return data, no SQL errors, metrics are realistic

---

## Test Suite 4: Manual Analytics Trigger (UI)

### Test 4.1: Access Analytics Page

**Steps:**
1. Login as teacher with course access
2. Navigate to: `http://localhost:8002/local/savian_ai/course.php?courseid=3`
3. Verify "📊 Learning Analytics" card is visible
4. Click "📊 Send Analytics Report"

**Expected Result:**
```
✓ Page loads: send_analytics.php
✓ Shows form with date range selector
✓ Shows student count (e.g., "50 students enrolled")
✓ Shows "Generate Analytics Report" button
```

**Pass Criteria:** ✅ Page accessible, form displays correctly

### Test 4.2: Generate Report (Without API - Database Test)

**Steps:**
1. On send_analytics.php, select "All Time"
2. Click "Generate Analytics Report"
3. Wait for processing

**Expected Behavior:**
```
1. Loading spinner appears
2. report_builder executes
3. Data extracted for all students
4. Report stored in database
5. API call attempted (may fail if endpoint not ready)
```

**Verification SQL:**
```sql
-- Check report was created
SELECT * FROM mdl_local_savian_analytics_reports
ORDER BY timecreated DESC LIMIT 1;

-- Expected fields:
-- report_type = 'on_demand'
-- trigger_type = 'manual'
-- status = 'sent' OR 'failed' (depending on API)
-- student_count = 50
-- user_id = {teacher_id}
```

**Pass Criteria:** ✅ Report created in database, student_count correct

### Test 4.3: View Report History

**Steps:**
1. Navigate to: `http://localhost:8002/local/savian_ai/analytics_reports.php?courseid=3`
2. Verify report list displays

**Expected Result:**
```
✓ Table shows generated reports
✓ Columns: Date, Type, Students, Status, Actions
✓ Most recent report at top
✓ Export CSV button available for sent reports
```

**Pass Criteria:** ✅ History displays, reports visible

### Test 4.4: CSV Export

**Steps:**
1. On analytics_reports.php
2. Click 📥 icon for a sent report
3. File should download

**Expected File:**
```csv
Savian AI Learning Analytics Report
Course,Test Course
Course ID,3
Report Date,07 January 2026 15:30
Students Analyzed,50

AT-RISK STUDENTS
Student ID (Anonymized),Risk Level,Risk Score,Risk Factors,Recommended Actions
a1b2c3d4e5f6...,HIGH,85%,No access in 21 days; Failing grade,Schedule 1-on-1; Provide materials

COURSE RECOMMENDATIONS
Recommendation
15 students struggling with Module 3 - add review session
...
```

**Pass Criteria:** ✅ CSV downloads, contains all insights, properly formatted

---

## Test Suite 5: Scheduled Tasks

### Test 5.1: Daily Task Execution

**Command:**
```bash
# Run task manually (don't wait for cron)
php admin/cli/scheduled_task.php \
  --execute=\\local_savian_ai\\task\\send_analytics_daily
```

**Expected Output:**
```
Starting daily analytics task...
Found 5 courses to process.
Processing course: Test Course (ID: 3)
  ✓ Report sent successfully (Report ID: 42)
Processing course: Another Course (ID: 5)
  Skipping - report sent within last 24 hours
...
Daily analytics task completed.
  Success: 3
  Errors: 0
```

**Verification:**
```sql
-- Check reports were created
SELECT course_id, status, trigger_type, timecreated
FROM mdl_local_savian_analytics_reports
WHERE trigger_type = 'cron'
  AND report_type = 'scheduled'
ORDER BY timecreated DESC;
```

**Pass Criteria:** ✅ Task completes, reports created, no errors

### Test 5.2: Weekly Task Execution

**Command:**
```bash
php admin/cli/scheduled_task.php \
  --execute=\\local_savian_ai\\task\\send_analytics_weekly
```

**Expected**: Similar to daily but processes all courses (no 24-hour skip)

**Pass Criteria:** ✅ All courses processed, comprehensive reports sent

### Test 5.3: Cleanup Task Execution

**Command:**
```bash
php admin/cli/scheduled_task.php \
  --execute=\\local_savian_ai\\task\\cleanup_old_analytics
```

**Expected Output:**
```
Starting analytics data cleanup task...
Retention period: 365 days
Deleting data older than: 07 January 2025 15:00
  ✓ Deleted 0 old analytics reports
  ✓ Deleted 15 old processed events
  ✓ Deleted 8 stale cache entries
Analytics cleanup task completed.
```

**Pass Criteria:** ✅ Cleanup runs, old data deleted

---

## Test Suite 6: Real-Time Event Observers

### Test 6.1: Enable Real-Time Analytics

**Steps:**
1. Navigate to: `Site admin → Plugins → Local plugins → Savian AI`
2. Check "Enable Real-Time Analytics"
3. Save changes

### Test 6.2: Trigger Quiz Submission Event

**Steps:**
1. Login as test student: `teststudent_0001` (password: `Test123!`)
2. Navigate to a quiz in the test course
3. Complete and submit quiz
4. Check database

**Verification:**
```sql
-- Check event was recorded
SELECT * FROM mdl_local_savian_analytics_events
ORDER BY timecreated DESC LIMIT 5;

-- Expected:
-- event_name = '\mod_quiz\event\attempt_submitted'
-- course_id = 3
-- user_id = {student_id}
-- processed = 0 (initially)
```

**Pass Criteria:** ✅ Event recorded in database

### Test 6.3: Batch Processing (Threshold Test)

**Steps:**
1. Submit 10 quiz attempts (or assignments) with different test students
2. On the 10th submission, analytics should be triggered

**Expected:**
```
Logs should show:
Event threshold reached for course 3. Sending analytics...
  ✓ Real-time analytics sent for course 3
```

**Verification:**
```sql
-- Check events marked as processed
SELECT processed, COUNT(*) as count
FROM mdl_local_savian_analytics_events
WHERE course_id = 3
GROUP BY processed;

-- After threshold:
-- processed = 1, count = 10
```

**Pass Criteria:** ✅ After 10 events, analytics sent, events marked processed

---

## Test Suite 7: End-of-Course Reports

### Test 7.1: Course Completion Event

**Setup:**
```sql
-- Manually trigger course completion for test user
INSERT INTO mdl_course_completions (course, userid, timecompleted, timestarted)
VALUES (3, (SELECT id FROM mdl_user WHERE username = 'teststudent_0001'), UNIX_TIMESTAMP(), UNIX_TIMESTAMP() - 86400);
```

**Expected:**
```
Logs show:
Course completion detected for course ID: 3
  ✓ End-of-course report sent successfully (Report ID: 45)
```

**Verification:**
```sql
SELECT * FROM mdl_local_savian_analytics_reports
WHERE report_type = 'end_of_course'
  AND trigger_type = 'completion';

-- Expected: 1 row with comprehensive data
```

**Pass Criteria:** ✅ Event triggers report, report_type = 'end_of_course'

---

## Test Suite 8: Privacy & GDPR Compliance

### Test 8.1: Data Export (GDPR Right to Access)

**Steps:**
1. Navigate to: `Site admin → Users → Privacy and policies → Data requests`
2. Create export request for test user
3. Process request
4. Download exported data

**Expected in Export:**
- ✓ Chat conversations
- ✓ Chat messages
- ✓ Generation history
- ✓ Analytics reports (if user triggered any)
- ✓ Analytics events

**Pass Criteria:** ✅ All analytics data exported correctly

### Test 8.2: Data Deletion (GDPR Right to Erasure)

**Steps:**
1. Create deletion request for test user
2. Process request
3. Verify deletion

**Verification SQL:**
```sql
-- After deletion, check analytics data removed
SELECT COUNT(*) FROM mdl_local_savian_analytics_reports
WHERE user_id = {deleted_user_id};
-- Expected: 0

SELECT COUNT(*) FROM mdl_local_savian_analytics_events
WHERE user_id = {deleted_user_id};
-- Expected: 0

SELECT COUNT(*) FROM mdl_local_savian_analytics_cache
WHERE anon_user_id = '{anonymized_id}';
-- Expected: 0
```

**Pass Criteria:** ✅ All user analytics data deleted

### Test 8.3: Anonymization Verification

**Objective**: Verify no PII in reports

**Steps:**
1. Generate analytics report
2. Check database for api_response field
3. Parse JSON and verify no PII

**Verification:**
```sql
SELECT api_response FROM mdl_local_savian_analytics_reports
WHERE id = {report_id};
```

**Check JSON for:**
- ❌ No "firstname", "lastname", "email", "username"
- ❌ No IP addresses
- ✅ Only "anon_id" (64-char hash)
- ✅ Only aggregated metrics

**Pass Criteria:** ✅ No PII found in any report data

---

## Test Suite 9: API Integration

### Test 9.1: API Connection Test

**Test Script:**
```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/api/client.php');

$client = new \local_savian_ai\api\client();

// Test API connectivity
$response = $client->validate();

if ($response->http_code === 200 && $response->success) {
    echo "API Connection: PASS ✓\n";
} else {
    echo "API Connection: FAIL ✗\n";
    echo "Error: " . ($response->error ?? 'Unknown') . "\n";
}
?>
```

**Pass Criteria:** ✅ API connection successful

### Test 9.2: Send Analytics Payload

**Prerequisites:** Django endpoint implemented and running

**Test Script:**
```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/local/savian_ai/classes/analytics/report_builder.php');

$builder = new \local_savian_ai\analytics\report_builder();
$result = $builder->build_and_send_report(3, 'on_demand', 'manual');

echo "Success: " . ($result->success ? 'YES ✓' : 'NO ✗') . "\n";
echo "Report ID: " . $result->report_id . "\n";

if ($result->success && isset($result->insights)) {
    echo "\nInsights Received:\n";
    print_r($result->insights);
}
?>
```

**Expected Output:**
```
Success: YES ✓
Report ID: 42

Insights Received:
stdClass Object (
    [at_risk_students] => Array (
        [0] => stdClass Object (
            [anon_id] => a1b2c3...
            [risk_level] => high
            [risk_score] => 0.85
            [recommended_actions] => Array (...)
        )
    )
    [course_recommendations] => Array (...)
)
```

**Pass Criteria:** ✅ API accepts payload, returns insights

---

## Test Suite 10: Performance & Scale

### Test 10.1: Large Course Performance

**Setup:**
```bash
php cli/generate_test_data.php --course=3 --students=500 --weeks=12
```

**Test:**
```php
$start = microtime(true);
$result = $builder->build_and_send_report(3, 'on_demand', 'manual');
$duration = microtime(true) - $start;

echo "Duration: {$duration} seconds\n";
echo "Students: 500\n";
echo "Time per student: " . round($duration / 500, 3) . " seconds\n";
```

**Expected:**
- Total time: < 30 seconds for 500 students
- Batch processing activated
- No memory errors
- Database handles load

**Pass Criteria:** ✅ Completes within reasonable time, no errors

### Test 10.2: Concurrent Report Generation

**Test**: Trigger multiple reports simultaneously from different courses

**Expected**: All complete successfully without database locks or conflicts

**Pass Criteria:** ✅ Concurrent execution works

---

## Test Suite 11: Error Handling

### Test 11.1: API Unavailable

**Test:**
1. Temporarily disable Django API
2. Trigger manual report
3. Check retry logic

**Expected:**
```
Attempt 1: Failed (connection error)
Sleep 2 seconds...
Attempt 2: Failed
Sleep 4 seconds...
Attempt 3: Failed
Report marked as 'failed' with error message
```

**Verification:**
```sql
SELECT status, retry_count, error_message
FROM mdl_local_savian_analytics_reports
ORDER BY timecreated DESC LIMIT 1;

-- Expected:
-- status = 'failed'
-- retry_count = 3
-- error_message = 'Connection error...'
```

**Pass Criteria:** ✅ Retry logic works, graceful failure

### Test 11.2: No Students Enrolled

**Test:**
1. Create empty course (no students)
2. Try to send analytics

**Expected:**
```
Warning shown: "No students enrolled in this course"
Generate button disabled OR
Error message after click: "No enrolled students found"
```

**Pass Criteria:** ✅ Graceful handling, clear error message

---

## Test Suite 12: End-to-End Integration Test

### Complete Workflow Test

**Scenario**: Full analytics lifecycle from data generation to insights

**Steps:**
1. ✅ Generate test data (100 students, 12 weeks)
2. ✅ Configure analytics in admin settings
3. ✅ Trigger manual report
4. ✅ Verify insights displayed
5. ✅ Export to CSV
6. ✅ Run scheduled task manually
7. ✅ Trigger real-time events
8. ✅ View report history
9. ✅ Test GDPR export
10. ✅ Verify anonymization

**Timeline**: 30-45 minutes

**Pass Criteria:** ✅ All steps complete successfully

---

## Troubleshooting Common Issues

### Issue 1: Tables Not Created

**Symptoms**: Plugin installed but tables missing

**Solution:**
```bash
# Force upgrade
php admin/cli/upgrade.php

# Or reinstall plugin
# 1. Uninstall from admin
# 2. Delete from local/savian_ai
# 3. Re-upload and install
```

### Issue 2: Anonymization Salt Missing

**Symptoms**: Different hashes for same user on each run

**Solution:**
```php
// Check salt exists
$salt = get_config('local_savian_ai', 'anonymization_salt');
if (empty($salt)) {
    // Regenerate
    $anonymizer = new \local_savian_ai\analytics\anonymizer();
    $anonymizer->regenerate_salt();
}
```

### Issue 3: No Data Extracted

**Symptoms**: Reports show 0 students or no metrics

**Check:**
```sql
-- Verify students enrolled
SELECT COUNT(*) FROM mdl_user_enrolments ue
JOIN mdl_enrol e ON e.id = ue.enrolid
WHERE e.courseid = 3;

-- Verify activity logs exist
SELECT COUNT(*) FROM mdl_logstore_standard_log
WHERE courseid = 3;
```

### Issue 4: Scheduled Tasks Not Running

**Check:**
```bash
# Verify cron is running
php admin/cli/cron.php

# Check task is registered
php admin/cli/scheduled_task.php --list | grep savian
```

**Expected:**
```
local_savian_ai\task\send_analytics_daily
local_savian_ai\task\send_analytics_weekly
local_savian_ai\task\cleanup_old_analytics
```

---

## Performance Benchmarks

### Expected Performance

| Scenario | Students | Time | Pass Criteria |
|----------|----------|------|---------------|
| Small course | 50 | < 5s | ✓ |
| Medium course | 100 | < 10s | ✓ |
| Large course | 500 | < 30s | ✓ |
| Very large | 1000 | < 60s | ✓ |

### Memory Usage

| Students | Memory | Pass Criteria |
|----------|--------|---------------|
| 50 | < 64 MB | ✓ |
| 500 | < 128 MB | ✓ |
| 1000 | < 256 MB | ✓ |

---

## Test Checklist Summary

```
☐ Database Schema
  ☐ Fresh install creates tables
  ☐ Upgrade from v1.0.x works
  ☐ All fields and indexes correct

☐ Test Data Generation
  ☐ 50 students generated successfully
  ☐ 200 students generated successfully
  ☐ Activity patterns realistic
  ☐ Grade distribution correct (20/60/20)

☐ Data Extraction & Anonymization
  ☐ Anonymization consistent
  ☐ Hashes are 64 characters
  ☐ No PII in outputs
  ☐ Data extraction accurate

☐ Manual Trigger (UI)
  ☐ Page accessible
  ☐ Form functional
  ☐ Insights display correctly
  ☐ Report history works
  ☐ CSV export works

☐ Scheduled Tasks
  ☐ Daily task runs and sends reports
  ☐ Weekly task runs
  ☐ Cleanup task removes old data
  ☐ Incremental updates work

☐ Real-Time Events
  ☐ Events captured
  ☐ Batching works (threshold: 10)
  ☐ Analytics sent after threshold

☐ End-of-Course
  ☐ Course completion triggers report
  ☐ Comprehensive data included

☐ Privacy & GDPR
  ☐ Data export includes analytics
  ☐ Data deletion works
  ☐ No PII leaks

☐ API Integration
  ☐ Connection successful
  ☐ Payload accepted
  ☐ Insights returned

☐ Error Handling
  ☐ API failure handled gracefully
  ☐ Retry logic works
  ☐ Empty course handled
  ☐ Database errors logged

☐ Performance
  ☐ 500 students < 30 seconds
  ☐ Batch processing works
  ☐ No memory issues
```

---

## Sign-Off Criteria

### For Production Deployment:

- ✅ All test suites pass (100%)
- ✅ No critical or major bugs
- ✅ Performance benchmarks met
- ✅ GDPR compliance verified
- ✅ API integration tested
- ✅ Documentation complete
- ✅ Admin approved

---

## Contact for Issues

- **Developer**: Savian AI Team
- **Email**: dev@savian.ai.vn
- **Documentation**: See ANALYTICS_USER_GUIDE.md
- **API Spec**: See ANALYTICS_API_SPEC.md

---

## Changelog

### v1.1.0 (2026-01-07)
- Initial testing guide
- All 12 test suites documented
- Performance benchmarks established
