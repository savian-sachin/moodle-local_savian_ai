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

namespace local_savian_ai\chat;

/**
 * Unit tests for the chat restriction manager.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_savian_ai\chat\restriction_manager
 */
final class restriction_manager_test extends \advanced_testcase {
    /**
     * Test that no restriction returns null.
     */
    public function test_no_restriction_returns_null(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $manager = new restriction_manager();
        $result = $manager->get_active_restriction($course->id, $user->id);

        $this->assertNull($result);
    }

    /**
     * Test that an active manual restriction blocks chat.
     */
    public function test_active_manual_restriction_blocks_chat(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $generator->create_restriction([
            'course_id' => $course->id,
            'restriction_type' => 'manual',
            'name' => 'Exam period',
            'timestart' => time() - 3600,
            'timeend' => time() + 3600,
            'restriction_message' => 'Chat disabled during exam.',
            'is_enabled' => 1,
        ]);

        $manager = new restriction_manager();
        $result = $manager->get_active_restriction($course->id, $user->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->is_restricted);
        $this->assertEquals('Chat disabled during exam.', $result->message);
        $this->assertEquals('manual', $result->restriction_type);
    }

    /**
     * Test that an expired restriction allows chat.
     */
    public function test_expired_restriction_allows_chat(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $generator->create_restriction([
            'course_id' => $course->id,
            'restriction_type' => 'manual',
            'timestart' => time() - 7200,
            'timeend' => time() - 3600, // Ended 1 hour ago.
            'is_enabled' => 1,
        ]);

        $manager = new restriction_manager();
        $result = $manager->get_active_restriction($course->id, $user->id);

        $this->assertNull($result, 'Expired restriction should not block chat');
    }

    /**
     * Test that a future restriction allows chat.
     */
    public function test_future_restriction_allows_chat(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $generator->create_restriction([
            'course_id' => $course->id,
            'restriction_type' => 'manual',
            'timestart' => time() + 3600, // Starts in 1 hour.
            'timeend' => time() + 7200,
            'is_enabled' => 1,
        ]);

        $manager = new restriction_manager();
        $result = $manager->get_active_restriction($course->id, $user->id);

        $this->assertNull($result, 'Future restriction should not block chat');
    }

    /**
     * Test that a disabled restriction allows chat.
     */
    public function test_disabled_restriction_allows_chat(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $generator->create_restriction([
            'course_id' => $course->id,
            'restriction_type' => 'manual',
            'timestart' => time() - 3600,
            'timeend' => time() + 3600,
            'is_enabled' => 0, // Disabled.
        ]);

        $manager = new restriction_manager();
        $result = $manager->get_active_restriction($course->id, $user->id);

        $this->assertNull($result, 'Disabled restriction should not block chat');
    }
}
