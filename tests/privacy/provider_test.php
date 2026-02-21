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

namespace local_savian_ai\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Unit tests for the privacy provider.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_savian_ai\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Test that get_metadata declares all plugin tables.
     */
    public function test_get_metadata_returns_all_tables(): void {
        $collection = new collection('local_savian_ai');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();

        // Count database tables (should be 12) plus 1 external location.
        $tables = [];
        $externals = [];
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tables[] = $item->get_name();
            } else if ($item instanceof \core_privacy\local\metadata\types\external_location) {
                $externals[] = $item->get_name();
            }
        }

        $expectedtables = [
            'local_savian_ai_chat_conversations',
            'local_savian_ai_chat_messages',
            'local_savian_ai_chat_settings',
            'local_savian_ai_generations',
            'local_savian_ai_documents',
            'local_savian_ai_chat_course_config',
            'local_savian_ai_chat_restrictions',
            'local_savian_ai_analytics_reports',
            'local_savian_ai_analytics_events',
            'local_savian_ai_analytics_cache',
            'local_savian_ai_writing_tasks',
            'local_savian_ai_writing_submissions',
        ];

        foreach ($expectedtables as $table) {
            $this->assertContains($table, $tables, "Table {$table} should be declared in metadata");
        }

        $this->assertContains('savian_api', $externals, 'External API should be declared');
    }

    /**
     * Test get_contexts_for_userid returns course contexts for a user with data.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $generator->create_conversation([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contextids = array_map('intval', $contextlist->get_contextids());

        // Should include the course context.
        $coursecontext = \context_course::instance($course->id);
        $this->assertContains((int) $coursecontext->id, $contextids);
    }

    /**
     * Test get_contexts_for_userid returns only system context when user has no course data.
     */
    public function test_get_contexts_for_userid_no_data(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contextids = array_map('intval', $contextlist->get_contextids());

        // Should include system context (always added).
        $systemcontext = \context_system::instance();
        $this->assertContains((int) $systemcontext->id, $contextids);

        // Should not include any course contexts.
        $this->assertCount(1, $contextids);
    }

    /**
     * Test export_user_data produces conversation data.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $conversation = $generator->create_conversation([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'title' => 'Privacy export test',
        ]);
        $generator->create_message([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello AI',
        ]);

        $coursecontext = \context_course::instance($course->id);
        $contextlist = new approved_contextlist($user, 'local_savian_ai', [$coursecontext->id]);

        provider::export_user_data($contextlist);

        $writer = writer::with_context($coursecontext);
        $data = $writer->get_data([
            get_string('privacy:chatdata', 'local_savian_ai'),
            $conversation->id,
        ]);

        $this->assertNotEmpty($data);
        $this->assertEquals('Privacy export test', $data->title);
        $this->assertNotEmpty($data->messages);
        $this->assertEquals('user', $data->messages[0]['role']);
        $this->assertEquals('Hello AI', $data->messages[0]['content']);
    }

    /**
     * Test delete_data_for_user removes all user records.
     */
    public function test_delete_data_for_user(): void {
        $this->resetAfterTest();
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');
        $conversation = $generator->create_conversation([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        $generator->create_message([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Message to be deleted',
        ]);
        $generator->create_generation([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Verify data exists.
        $this->assertEquals(1, $DB->count_records('local_savian_ai_chat_conversations', [
            'user_id' => $user->id,
        ]));
        $this->assertEquals(1, $DB->count_records('local_savian_ai_generations', [
            'user_id' => $user->id,
        ]));

        // Delete.
        $coursecontext = \context_course::instance($course->id);
        $contextlist = new approved_contextlist($user, 'local_savian_ai', [$coursecontext->id]);
        provider::delete_data_for_user($contextlist);

        // Verify data is gone.
        $this->assertEquals(0, $DB->count_records('local_savian_ai_chat_conversations', [
            'user_id' => $user->id,
        ]));
        $this->assertEquals(0, $DB->count_records('local_savian_ai_generations', [
            'user_id' => $user->id,
        ]));
    }

    /**
     * Test get_users_in_context finds users with data in a course.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_savian_ai');

        // User1 has a conversation.
        $generator->create_conversation([
            'user_id' => $user1->id,
            'course_id' => $course->id,
        ]);

        // User2 has a generation.
        $generator->create_generation([
            'user_id' => $user2->id,
            'course_id' => $course->id,
        ]);

        // User3 has no data.

        $coursecontext = \context_course::instance($course->id);
        $userlist = new userlist($coursecontext, 'local_savian_ai');
        provider::get_users_in_context($userlist);

        $userids = array_map('intval', $userlist->get_userids());

        $this->assertContains((int) $user1->id, $userids);
        $this->assertContains((int) $user2->id, $userids);
        $this->assertNotContains((int) $user3->id, $userids);
    }
}
