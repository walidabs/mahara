@javascript @core @core_artefact
Feature: Mahara users can export collections with bulk option
  As a Mahara user
  I want to export collections in bulk
  So that I can have the same options of exporting as I when exporting pages.

Background:
Given the following "institutions" exist:
       | name | displayname | registerallowed | registerconfirm |
       | instone | Institution One | ON | OFF |

And the following "users" exist:
  | username | password | email | firstname | lastname | institution | authname | role |
  | UserA | Kupuh1pa! | UserA@example.org | Angela | User | instone | internal | Admin |
  | UserB | Kupuh1pa! | UserB@example.org | Bob | User | instone | internal | member |

And the following "pages" exist:
  | title | description | ownertype | ownername |
  | Page UserA_01 | Page 01 | user | UserA |
  | Page UserA_02 | Page 02 | user | UserA |
  | Page UserA_03 | Page 02 | user | UserA |

And the following "collections" exist:
  | title | description| ownertype | ownername | pages |
  | Collection UserA_01 | Collection 01 | user | UserA | Page UserA_01 |
  | Collection UserA_02 | Collection 02 | user | UserA | Page UserA_02 |
  | Collection UserA_03 | Collection 02 | user | UserA | Page UserA_03 |

Scenario: Export collections in bulk as HTML
  Given I log in as "UserA" with password "Kupuh1pa!"
  And I choose "Export" in "Manage" from main menu
  # this tests the page default option "Standalone HTML website"
  When I select the radio "Just some of my collections"
  Then I should see "Select all"
  Then I should see "Reverse selection"
  When I follow "selection_all_collections"
  Then the "Collection UserA_01" checkbox should be checked
  And the "Collection UserA_02" checkbox should be checked
  And the "Collection UserA_03" checkbox should be checked
  When I follow "selection_reverse_collections"
  Then the "Collection UserA_01" checkbox should not be checked
  And the "Collection UserA_02" checkbox should not be checked
  And the "Collection UserA_03" checkbox should not be checked
  When I click on "Generate export"
  Then I should see "You must select at least one collection to export"
  And I should see "There was an error with submitting this form. Please check the marked fields and try again."

Scenario: Export collections in bulk as Leap2A
  Given I log in as "UserA" with password "Kupuh1pa!"
  And I choose "Export" in "Manage" from main menu
  # this tests the Leap2A export
  When I select the radio "Leap2A"
  And I select the radio "Just some of my collections"
  Then I should see "Select all"
  Then I should see "Reverse selection"
  When I follow "selection_all_collections"
  Then the "Collection UserA_01" checkbox should be checked
  And the "Collection UserA_02" checkbox should be checked
  And the "Collection UserA_03" checkbox should be checked
  When I follow "selection_reverse_collections"
  Then the "Collection UserA_01" checkbox should not be checked
  And the "Collection UserA_02" checkbox should not be checked
  And the "Collection UserA_03" checkbox should not be checked
  When I follow "selection_all_collections"
  Then the "Collection UserA_01" checkbox should be checked
  And the "Collection UserA_02" checkbox should be checked
  And the "Collection UserA_03" checkbox should be checked
  When I click on "Generate export"
  Then I should see "Please wait while your export is being generated..."

Scenario: Institution One admin locks First name, Last name fields
    I want to lock fields
    So that institution fields will not change when users upload Leap2a portfolios
    # Admin sets Institution lock fields (First name, Last name)
    Given I log in as "admin" with password "Kupuh1pa!"
    When I choose "Settings" in "Institutions" from administration menu
    And I click on "Edit" in "Institution One" row
    And I expand the section "Locked fields"
    And I enable the switch "First name"
    And I enable the switch "Last name"
    And I enable the switch "Email address"
    And I press "Submit"
    Then I log out
    Given I log in as "UserB" with password "Kupuh1pa!"
    When I choose "Import" in "Manage" from main menu
    # Upload the file "UserA.xml"  Leap2A file
    And I attach the file "leap2a.xml" to "import_leap2afile"
    And I press "Import"
    Then I should see "Choose the way to import your portfolio items"
    When I expand "About me" node
    # user should see ignore for all of the Locked fields for inst
    And I should see "Ignore" in the "Import First name" property
    And I should see "Ignore" in the "Import Last name" property
    # Student ID field was not locked so user should see additional option of "Add new"
    And I should see "Ignore" in the "Import Student ID" property
    And I should see "Add new" in the "Import Student ID" property
    When I expand "Contact information" node
    Then I should see "Ignore" in the "Import Email address" property