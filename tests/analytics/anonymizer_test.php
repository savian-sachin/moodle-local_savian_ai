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

namespace local_savian_ai\analytics;

/**
 * Unit tests for the anonymizer class.
 *
 * @package    local_savian_ai
 * @category   test
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_savian_ai\analytics\anonymizer
 */
final class anonymizer_test extends \advanced_testcase {
    /**
     * Test that anonymize_user_id returns a 64-character hex string.
     */
    public function test_anonymize_returns_64_char_hex(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $result = $anonymizer->anonymize_user_id(42);

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
        $this->assertTrue(ctype_xdigit($result), 'Result should be a valid hex string');
    }

    /**
     * Test that the same user ID always produces the same hash.
     */
    public function test_anonymize_is_consistent(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $first = $anonymizer->anonymize_user_id(99);
        $second = $anonymizer->anonymize_user_id(99);

        $this->assertSame($first, $second);
    }

    /**
     * Test that different user IDs produce different hashes.
     */
    public function test_anonymize_is_unique(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $hash1 = $anonymizer->anonymize_user_id(1);
        $hash2 = $anonymizer->anonymize_user_id(2);
        $hash3 = $anonymizer->anonymize_user_id(3);

        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash2, $hash3);
        $this->assertNotEquals($hash1, $hash3);
    }

    /**
     * Test that anonymize_user_ids returns a correct mapping.
     */
    public function test_anonymize_user_ids_returns_mapping(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $ids = [10, 20, 30];
        $result = $anonymizer->anonymize_user_ids($ids);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Verify keys are original IDs.
        $this->assertArrayHasKey(10, $result);
        $this->assertArrayHasKey(20, $result);
        $this->assertArrayHasKey(30, $result);

        // Verify values are valid hashes.
        foreach ($result as $hash) {
            $this->assertEquals(64, strlen($hash));
            $this->assertTrue(ctype_xdigit($hash));
        }

        // Verify consistency with individual calls.
        $this->assertSame($anonymizer->anonymize_user_id(10), $result[10]);
    }

    /**
     * Test is_anonymized_id validates correctly.
     */
    public function test_is_anonymized_id_validates_correctly(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $validhash = $anonymizer->anonymize_user_id(1);

        // Valid SHA256 hex hash.
        $this->assertTrue(anonymizer::is_anonymized_id($validhash));

        // Invalid inputs.
        $this->assertFalse(anonymizer::is_anonymized_id('not-a-hash'));
        $this->assertFalse(anonymizer::is_anonymized_id(''));
        $this->assertFalse(anonymizer::is_anonymized_id('abc')); // Too short.
        $this->assertFalse(anonymizer::is_anonymized_id(123)); // Not a string.
        $this->assertFalse(anonymizer::is_anonymized_id(str_repeat('g', 64))); // Non-hex chars.
    }

    /**
     * Test reverse_lookup finds the correct user ID.
     */
    public function test_reverse_lookup_finds_user(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $anonid = $anonymizer->anonymize_user_id(42);

        $found = $anonymizer->reverse_lookup($anonid, [10, 20, 42, 50]);
        $this->assertEquals(42, $found);
    }

    /**
     * Test reverse_lookup returns null for unknown hash.
     */
    public function test_reverse_lookup_returns_null_for_unknown(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $anonid = $anonymizer->anonymize_user_id(999);

        // Search in candidates that don't include 999.
        $found = $anonymizer->reverse_lookup($anonid, [1, 2, 3, 4, 5]);
        $this->assertNull($found);
    }

    /**
     * Test that regenerating salt invalidates previous hashes.
     */
    public function test_regenerate_salt_invalidates_previous_hashes(): void {
        $this->resetAfterTest();

        $anonymizer = new anonymizer();
        $hashbefore = $anonymizer->anonymize_user_id(42);

        $anonymizer->regenerate_salt();
        $hashafter = $anonymizer->anonymize_user_id(42);

        $this->assertNotEquals(
            $hashbefore,
            $hashafter,
            'Hash should change after salt regeneration'
        );
        // Both should still be valid hashes.
        $this->assertEquals(64, strlen($hashafter));
        $this->assertTrue(ctype_xdigit($hashafter));
    }
}
