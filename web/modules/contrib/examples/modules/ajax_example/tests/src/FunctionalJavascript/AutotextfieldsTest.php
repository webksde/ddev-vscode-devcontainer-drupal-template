<?php

namespace Drupal\Tests\ajax_example\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the user interactions for the Autotextfields example.
 *
 * @group ajax_example
 */
class AutotextfieldsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_example'];

  /**
   * Test the user interactions for the Autotextfields example.
   */
  public function testAutotextfields() {
    // Get our Mink stuff.
    $session = $this->getSession();
    $page = $session->getPage();
    $assert = $this->assertSession();

    // Get the page.
    $form_url = Url::fromRoute('ajax_example.autotextfields');
    $this->drupalGet($form_url);

    // Check our initial state.
    $assert->checkboxNotChecked('ask_first_name');
    $assert->checkboxNotChecked('ask_last_name');
    $assert->fieldNotExists('first_name');
    $assert->fieldNotExists('last_name');
    // Submit the form. This tests what happens when there are no user
    // interactions because drupalPostForm() reloads the form.
    $this->drupalPostForm($form_url, [], 'Click Me');
    $assert->pageTextContains('Submit handler: First name: n/a Last name: n/a');

    // Ask for the first name.
    $page->checkField('ask_first_name');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldExists('first_name');
    $assert->fieldNotExists('last_name');
    // Submit the form. We have to find the field and set its value rather than
    // use drupalPostForm(), because when we post the form, it will be rebuilt.
    // We are testing the form state after AJAX has modified it, so we must
    // preserve that.
    $page->fillField('first_name', 'Dries');
    $page->pressButton('Click Me');
    $assert->pageTextContains('Submit handler: First name: Dries Last name: n/a');

    // Ask for the first and last name.
    $page->checkField('ask_first_name');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldExists('first_name');
    $page->checkField('ask_last_name');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldExists('last_name');
    // Submit the form.
    $page->fillField('first_name', 'Dries');
    $page->fillField('last_name', 'Buytaert');
    $page->pressButton('Click Me');
    $assert->pageTextContains('Submit handler: First name: Dries Last name: Buytaert');

    // Ask for only the last name.
    $page->checkField('ask_last_name');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldNotExists('first_name');
    $assert->fieldExists('last_name');
    // Submit the form.
    $page->fillField('last_name', 'Buytaert');
    $page->pressButton('Click Me');
    $assert->pageTextContains('Submit handler: First name: n/a Last name: Buytaert');
  }

}
