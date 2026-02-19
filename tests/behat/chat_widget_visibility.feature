@local @local_savian_ai @javascript
Feature: Chat widget visibility
  As a user I should only see the Savian AI chat widget when authorized
  and on an appropriate course page with the widget enabled.

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Chat widget does not appear when explicitly disabled
    Given the following config values are set as admin:
      | enable_chat_widget | 0 | local_savian_ai |
    And I log in as "teacher1"
    When I am on "Course 1" course homepage
    Then "#savian-chat-widget" "css_element" should not exist

  Scenario: Chat widget appears for teacher on course page when enabled
    Given the following config values are set as admin:
      | enable_chat_widget | 1 | local_savian_ai |
    And I log in as "teacher1"
    When I am on "Course 1" course homepage
    And I wait "2" seconds
    Then "#savian-chat-widget" "css_element" should exist

  Scenario: Guest user does not see chat widget
    Given the following config values are set as admin:
      | enable_chat_widget | 1 | local_savian_ai |
    And I log in as "guest"
    When I am on site homepage
    Then "#savian-chat-widget" "css_element" should not exist
