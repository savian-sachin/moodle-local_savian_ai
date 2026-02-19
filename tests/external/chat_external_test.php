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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Unit tests for the chat external web services.
 *
 * Tests parameter definitions and capability enforcement without
 * calling the real Savian AI API.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_savian_ai\external\chat
 */
final class chat_external_test extends \advanced_testcase {
    /**
     * Test that send_message parameter definition is valid.
     */
    public function test_send_message_parameters_defined(): void {
        $params = chat::send_message_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
        $keys = array_keys($params->keys);
        $this->assertContains('message', $keys);
        $this->assertContains('conversationid', $keys);
        $this->assertContains('courseid', $keys);
        $this->assertContains('documentids', $keys);
    }

    /**
     * Test that send_message requires the use capability.
     */
    public function test_send_message_requires_capability(): void {
        $this->resetAfterTest();
        global $DB;

        // Create user without any capabilities.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'guest');

        // Remove the capability from guest role.
        $context = \context_course::instance($course->id);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest']);
        role_change_permission($guestrole->id, $context, 'local/savian_ai:use', CAP_PROHIBIT);

        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        chat::send_message('test', 0, $course->id, []);
    }

    /**
     * Test that list_conversations parameter definition is valid.
     */
    public function test_list_conversations_parameters_defined(): void {
        $params = chat::list_conversations_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
        $keys = array_keys($params->keys);
        $this->assertContains('courseid', $keys);
    }

    /**
     * Test that save_widget_state parameter definition is valid.
     */
    public function test_save_widget_state_parameters_defined(): void {
        $params = chat::save_widget_state_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
        $keys = array_keys($params->keys);
        $this->assertContains('position', $keys);
        $this->assertContains('minimized', $keys);
    }

    /**
     * Test that get_course_documents parameter definition is valid.
     */
    public function test_get_course_documents_parameters_defined(): void {
        $params = chat::get_course_documents_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
        $keys = array_keys($params->keys);
        $this->assertContains('courseid', $keys);
    }
}
