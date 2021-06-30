<?php

namespace Drupal\Tests\form_api_example\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Url;

/**
 * @group form_api_example
 *
 * @ingroup form_api_example
 */
class ModalFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Our module dependencies.
   *
   * @var string[]
   */
  public static $modules = ['form_api_example'];

  /**
   * Functional test of the modal form example.
   *
   * Steps:
   * - Visit form route.
   * - Click on 'see this form as a modal'.
   * - Check that modal exists.
   * - Enter a value.
   * - Click 'submit'
   * - Check that we have a new modal.
   * - Click the close X.
   * - Verify that the modal went away.
   */
  public function testModalForm() {
    // Visit form route.
    $modal_route_nojs = Url::fromRoute('form_api_example.modal_form', ['nojs' => 'nojs']);
    $this->drupalGet($modal_route_nojs);

    // Get Mink stuff.
    $assert = $this->assertSession();
    $session = $this->getSession();
    $page = $this->getSession()->getPage();

    // Click on 'see this form as a modal'.
    $this->clickLink('ajax-example-modal-link');
    $this->assertNotEmpty($assert->waitForElementVisible('css', '.ui-dialog'));

    // Enter a value.
    $this->assertNotEmpty($input = $page->find('css', 'div.ui-dialog input[name="title"]'));
    $input->setValue('test_title');

    // Click 'submit'.
    $this->assertNotEmpty($submit = $page->find('css', 'button.ui-button.form-submit'));
    $submit->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that we have a result modal.
    $assert->elementContains('css', 'span.ui-dialog-title', 'test_title');

    // Click the close X.
    $this->assertNotEmpty($close = $page->find('css', 'button.ui-dialog-titlebar-close'));
    $close->click();
    $assert->assertWaitOnAjaxRequest();

    // Verify that the modal went away.
    $assert->pageTextNotContains('appears in this modal dialog.');
  }

}
