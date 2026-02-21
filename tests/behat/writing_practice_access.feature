@local @local_savian_ai
Feature: Writing practice access control
  Teachers can manage writing tasks; students can submit writing.

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

  Scenario: Teacher can access writing practice task manager
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage
    And I navigate to "Savian AI" in current page administration
    And I follow "Writing Practice"
    Then I should see "Writing Practice"
    And I should see "Create Writing Task"

  Scenario: Student sees writing practice in course navigation
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    And I navigate to "Savian AI" in current page administration
    And I follow "Writing Practice"
    Then I should see "Writing Practice"
    And I should not see "Create Writing Task"
