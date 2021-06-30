<?php

namespace Drupal\Tests\ajax_example\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the behavior of the entity_autocomplete example.
 *
 * @group ajax_example
 */
class EntityAutocompleteTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_example'];

  /**
   * Test the behavior of the submit-driven AJAX example.
   *
   * Behaviors to test:
   * - GET the route ajax_example.autocomplete_user.
   * - Examine the DOM to make sure our change hasn't happened yet.
   * - Send an event to the DOM to trigger the autocomplete.
   * - Wait for the autocomplete request to complete.
   * - Examine the DOM to see if our expected change happened.
   * - Submit some names to see if our form processed the user properly.
   */
  public function testSubmitDriven() {
    // Set up some accounts with known names.
    $names = ['bb', 'bc'];
    foreach ($names as $name) {
      $this->createUser([], $name);
    }

    // Get our various Mink elements.
    $assert = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();
    // We'll be using the users field quite a bit, so let's make it a variable.
    $users_field_id = 'edit-users';

    // Get the form.
    $this->drupalGet(Url::fromRoute('ajax_example.autocomplete_user'));
    // Examine the DOM to make sure our change hasn't happened yet.
    $assert->fieldValueEquals($users_field_id, '');

    // Send an event to the DOM. This will start the autocomplete process.
    $autocomplete_field = $page->findById($users_field_id);
    // We can cause the autocomplete to happen by setting the value in the
    // element.
    $autocomplete_field->setValue('b');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');

    // Wait for the autocomplete request to complete. Our code is much quicker
    // than the request time.
    $assert->waitOnAutocomplete();

    // Examine the DOM to see if our expected change happened.
    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(2, $results);
    foreach ($results as $result) {
      $this->assertContains($result->getText(), $names);
    }

    // Submit to see if our form processed the user properly.
    $autocomplete_field->blur();
    $this->submitForm([$users_field_id => 'bb, bc'], 'Submit');
    $assert->pageTextContains('These are your users: bb bc');
  }

}
