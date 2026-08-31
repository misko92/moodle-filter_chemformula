@filter @filter_chemformula
Feature: Format chemical formulas in text with the chemformula filter
  In order to display chemistry correctly
  As a user
  I need formulas, charges, isotopes and reaction arrows marked up automatically

  Background:
    Given the "chemformula" filter is "on"

  @javascript
  Scenario: A simple formula gets subscripts
    Given the following "user" exists:
      | username    | chem1                |
      | description | <p>Water is H2O.</p> |
    When I am on the "chem1" "user > profile" page logged in as "chem1"
    Then "//sub[normalize-space(text())='2']" "xpath_element" should exist

  @javascript
  Scenario: An ionic charge gets a superscript
    Given the following "user" exists:
      | username    | chem2                           |
      | description | <p>The calcium ion is Ca2+.</p> |
    When I am on the "chem2" "user > profile" page logged in as "chem2"
    Then "//sup[normalize-space(text())='2+']" "xpath_element" should exist

  @javascript
  Scenario: Reaction-arrow shorthand becomes a real arrow
    Given the following "user" exists:
      | username    | chem3                                    |
      | description | <p>Burning hydrogen: 2H2 + O2 -> 2H2O</p> |
    When I am on the "chem3" "user > profile" page logged in as "chem3"
    Then I should see "2H2 + O2 → 2H2O"

  @javascript
  Scenario: Scientific notation gets a superscript exponent
    Given the following "user" exists:
      | username    | chemsci                                                  |
      | description | <p>Avogadro's number is about 6.02x10^23 per mole.</p>    |
    When I am on the "chemsci" "user > profile" page logged in as "chemsci"
    Then I should see "6.02 × 10"
    And "//sup[normalize-space(text())='23']" "xpath_element" should exist

  @javascript
  Scenario: Formulas inside a code block are left untouched
    Given the following "user" exists:
      | username    | chem4                                               |
      | description | <p>H2O</p><pre><code>H2O stays as typed</code></pre> |
    When I am on the "chem4" "user > profile" page logged in as "chem4"
    Then "//p//sub[normalize-space(text())='2']" "xpath_element" should exist
    And "//code[contains(normalize-space(text()), 'H2O stays as typed')]" "xpath_element" should exist

  @javascript
  Scenario: A manual override forces its own rendering
    Given the following config values are set as admin:
      | config    | value               | plugin             |
      | overrides | IT = I<sub>2</sub>T | filter_chemformula |
    And the following "user" exists:
      | username    | chem5                          |
      | description | <p>My made-up compound IT.</p> |
    When I am on the "chem5" "user > profile" page logged in as "chem5"
    Then "//sub[normalize-space(text())='2']" "xpath_element" should exist

  @javascript
  Scenario: A self-mapping override exempts text from automatic formatting
    Given the following config values are set as admin:
      | config    | value     | plugin             |
      | overrides | H2O = H2O | filter_chemformula |
    And the following "user" exists:
      | username    | chem6                     |
      | description | <p>Plain old H2O here.</p> |
    When I am on the "chem6" "user > profile" page logged in as "chem6"
    Then I should see "Plain old H2O here."
    And "//div[contains(@class, 'no-overflow')]//sub" "xpath_element" should not exist
