<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_savian_ai\analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Anonymizer class for anonymizing user data in analytics
 *
 * Uses SHA256 hashing with a persistent salt to ensure:
 * - User IDs cannot be reversed back to original values
 * - Same user always produces same hash (consistent across reports)
 * - Different users produce different hashes
 *
 * @package    local_savian_ai
 * @copyright  2026 Savian AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anonymizer {

    /**
     * @var string Salt configuration key
     */
    const SALT_CONFIG_KEY = 'anonymization_salt';

    /**
     * @var string|null Cached salt value
     */
    private static $salt = null;

    /**
     * Anonymize a user ID using SHA256 with salt
     *
     * @param int $user_id The Moodle user ID to anonymize
     * @return string 64-character hexadecimal hash (SHA256)
     */
    public function anonymize_user_id($user_id) {
        $salt = $this->get_or_create_salt();
        return hash('sha256', $user_id . $salt);
    }

    /**
     * Anonymize multiple user IDs at once
     *
     * @param array $user_ids Array of user IDs
     * @return array Associative array mapping user_id => anon_id
     */
    public function anonymize_user_ids($user_ids) {
        $result = [];
        foreach ($user_ids as $user_id) {
            $result[$user_id] = $this->anonymize_user_id($user_id);
        }
        return $result;
    }

    /**
     * Get or create the anonymization salt
     *
     * The salt is generated once and stored in plugin configuration.
     * This ensures consistent hashing across all reports.
     *
     * @return string The salt value
     */
    private function get_or_create_salt() {
        // Use cached salt if available
        if (self::$salt !== null) {
            return self::$salt;
        }

        // Try to get existing salt from config
        $salt = get_config('local_savian_ai', self::SALT_CONFIG_KEY);

        // Generate new salt if not exists
        if (empty($salt)) {
            $salt = bin2hex(random_bytes(32)); // 64-character hex string
            set_config(self::SALT_CONFIG_KEY, $salt, 'local_savian_ai');
        }

        // Cache for this request
        self::$salt = $salt;

        return $salt;
    }

    /**
     * Verify that anonymization is working correctly
     *
     * Used for testing and validation
     *
     * @param int $user_id Test user ID
     * @return array Validation results
     */
    public function validate_anonymization($user_id = 123) {
        $anon1 = $this->anonymize_user_id($user_id);
        $anon2 = $this->anonymize_user_id($user_id);
        $anon_different = $this->anonymize_user_id($user_id + 1);

        return [
            'salt_exists' => !empty($this->get_or_create_salt()),
            'hash_length' => strlen($anon1) === 64,
            'consistency' => $anon1 === $anon2,
            'uniqueness' => $anon1 !== $anon_different,
            'no_reversibility' => !is_numeric($anon1),
        ];
    }

    /**
     * Check if a string looks like an anonymized ID
     *
     * @param string $value Value to check
     * @return bool True if it looks like a SHA256 hash
     */
    public static function is_anonymized_id($value) {
        return is_string($value) && strlen($value) === 64 && ctype_xdigit($value);
    }

    /**
     * Get salt information (for admin/debugging)
     *
     * WARNING: Do not expose the actual salt value to users!
     *
     * @return array Salt metadata (not the actual value)
     */
    public function get_salt_info() {
        $salt = $this->get_or_create_salt();
        return [
            'exists' => !empty($salt),
            'length' => strlen($salt),
            'created' => true,
            // DO NOT include actual salt value here for security
        ];
    }

    /**
     * Regenerate the anonymization salt
     *
     * WARNING: This will make all previous anonymized IDs invalid!
     * Only use this if there's a security breach or for testing.
     *
     * @return string The new salt
     */
    public function regenerate_salt() {
        $new_salt = bin2hex(random_bytes(32));
        set_config(self::SALT_CONFIG_KEY, $new_salt, 'local_savian_ai');
        self::$salt = $new_salt;
        return $new_salt;
    }

    /**
     * Reverse lookup: Find user ID from anonymized ID
     *
     * Note: This only works within Moodle where we have access to user IDs.
     * Used for displaying actual student names to teachers.
     *
     * @param string $anon_id Anonymized user ID (SHA256 hash)
     * @param array $candidate_user_ids Array of possible user IDs to check
     * @return int|null User ID if found, null otherwise
     */
    public function reverse_lookup($anon_id, $candidate_user_ids) {
        foreach ($candidate_user_ids as $user_id) {
            if ($this->anonymize_user_id($user_id) === $anon_id) {
                return $user_id;
            }
        }
        return null;
    }

    /**
     * Bulk reverse lookup: Map anonymized IDs to user IDs
     *
     * @param array $anon_ids Array of anonymized IDs
     * @param array $candidate_user_ids Array of possible user IDs
     * @return array Associative array mapping anon_id => user_id
     */
    public function bulk_reverse_lookup($anon_ids, $candidate_user_ids) {
        $mapping = [];

        foreach ($anon_ids as $anon_id) {
            $user_id = $this->reverse_lookup($anon_id, $candidate_user_ids);
            if ($user_id !== null) {
                $mapping[$anon_id] = $user_id;
            }
        }

        return $mapping;
    }
}
