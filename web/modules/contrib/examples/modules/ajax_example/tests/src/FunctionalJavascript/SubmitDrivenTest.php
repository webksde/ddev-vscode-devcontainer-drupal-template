<?php

namespace Drupal\Tests\ajax_example\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the behavior of the submit-driven AJAX example.
 *
 * @group ajax_example
 */
class SubmitDrivenTest extends WebDriverTestBase {

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
   * - GET the route ajax_example.submit_driven_ajax.
   * - Examine the DOM to make sure our change hasn't happened yet.
   * - Submit the form.
   * - Wait for the AJAX request to complete.
   * - Examine the DOM to see if our expected change happened.
   */
  public function testSubmitDriven() {
    // Get the session assertion object.
    $assert = $this->assertSession();
    // Get the page.
    $this->drupalGet(Url::fromRoute('ajax_example.submit_driven_ajax'));
    // Examine the DOM to make sure our change hasn't happened yet.
    $assert->pageTextNotContains('Clicked submit (Submit):');
    // Submit the form.
    $this->submitForm([], 'Submit');
    // Wait on the AJAX request.
    $assert->assertWaitOnAjaxRequest();
    // Compare DOM to our expectations.
    $assert->pageTextContains('Clicked submit (Submit):');
  }

}
