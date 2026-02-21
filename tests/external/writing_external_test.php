<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_savian_ai\external;

use advanced_testcase;
use context_course;
use external_function_parameters;
use external_single_structure;

/**
 * Unit tests for the writing external API.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_savian_ai\external\writing
 * @runTestsInSeparateProcesses
 */
final class writing_external_test extends advanced_testcase {

    /**
     * Test that get_submission_status_parameters returns correct structure.
     */
    public function test_get_submission_status_parameters_defined(): void {
        $params = writing::get_submission_status_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
        $this->assertArrayHasKey('submissionuuid', $params->keys);
        $this->assertArrayHasKey('courseid', $params->keys);
    }

    /**
     * Test that get_submission_status_returns returns correct structure.
     */
    public function test_get_submission_status_returns_defined(): void {
        $returns = writing::get_submission_status_returns();
        $this->assertInstanceOf(external_single_structure::class, $returns);
        $this->assertArrayHasKey('status', $returns->keys);
        $this->assertArrayHasKey('progress', $returns->keys);
        $this->assertArrayHasKey('stage', $returns->keys);
    }

    /**
     * Test that calling get_submission_status without the use capability raises an exception.
     */
    public function test_get_submission_status_requires_use_capability(): void {
        global $DB;

        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Enrol as a guest-like role with no savian_ai:use capability.
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'guest');
        $context  = context_course::instance($course->id);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest']);
        role_change_permission($guestrole->id, $context, 'local/savian_ai:use', CAP_PROHIBIT);

        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        writing::get_submission_status('test-uuid-1234', $course->id);
    }

    /**
     * Test that a completed submission returns from DB without making an API call.
     */
    public function test_get_submission_status_returns_cached_completed(): void {
        global $DB;

        $this->resetAfterTest();

        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Grant the use capability to student role.
        $context    = context_course::instance($course->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        role_change_permission($studentrole->id, $context, 'local/savian_ai:use', CAP_ALLOW);

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');

        $task = $generator->create_writing_task([
            'course_id'       => $course->id,
            'teacher_user_id' => $teacher->id,
        ]);

        $uuid = 'completed-uuid-' . uniqid();
        $generator->create_writing_submission([
            'submission_uuid' => $uuid,
            'writing_task_id' => $task->id,
            'api_task_id'     => $task->api_task_id,
            'moodle_user_id'  => $student->id,
            'status'          => 'completed',
            'progress'        => 100,
            'stage'           => 'completed',
        ]);

        $this->setUser($student);

        $result = writing::get_submission_status($uuid, $course->id);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(100, $result['progress']);
    }
}
