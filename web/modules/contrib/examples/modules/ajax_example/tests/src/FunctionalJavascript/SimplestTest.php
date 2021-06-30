<?php

namespace Drupal\Tests\ajax_example\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the user interactions for the Simplest example.
 *
 * @group ajax_example
 */
class SimplestTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_example'];

  /**
   * Test AJAX behavior for the dropdown selector.
   *
   * We'll perform the following steps:
   * - Open the simplest example page.
   * - Verify that our AJAX wrapper element is present, but is empty.
   * - Cause events by changing the value of the dropdown for all the different
   *   values.
   * - Verify that the prompt text changes when the dropdown changes.
   */
  public function testAutotextfields() {
    // Get the page.
    $this->drupalGet(Url::fromRoute('ajax_example.simplest'));

    // Get our Mink stuff.
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    // Don't repeat ourselves. This makes it easier if we change the markup
    // later.
    $description_selector = '#replace-textfield-container div.description';

    // Check our initial state.
    $assert->elementExists('css', '#replace-textfield-container');
    $assert->elementNotExists('css', $description_selector);

    // Cause events by changing the value of the dropdown for all the different
    // values. Start with three so the change event is triggered.
    foreach (['three', 'two', 'one'] as $value) {
      // Select the dropdown value.
      $page->selectFieldOption('changethis', $value);
      // Wait for AJAX to happen.
      $assert->assertWaitOnAjaxRequest();
      // Assert that the description exists.
      $assert->elementExists('css', $description_selector);
      // Get the description element from the page.
      $prompt_element = $page->find('css', $description_selector);
      // Verify that the prompt text changes when the dropdown changes.
      $this->assertEquals(
        "Say why you chose '$value'",
        $prompt_element->getText()
      );
    }
  }

}
