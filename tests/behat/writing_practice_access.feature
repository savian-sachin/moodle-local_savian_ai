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

  Scenario: Student cannot access teacher writing practice page
    Given I log in as "student1"
    When I visit "/local/savian_ai/writing_practice.php?courseid=1"
    Then I should see "Sorry, but you do not currently have permissions"

  Scenario: Student can access writing submit page
    Given I log in as "student1"
    When I visit "/local/savian_ai/writing_submit.php?courseid=1"
    Then I should see "Writing Practice"
    And I should not see "Create Writing Task"

  Scenario: Writing Practice card visible on teacher dashboard
    Given I log in as "teacher1"
    When I visit "/local/savian_ai/course.php?courseid=1"
    Then I should see "Writing Practice"
    And I should see "Manage Writing Tasks"
