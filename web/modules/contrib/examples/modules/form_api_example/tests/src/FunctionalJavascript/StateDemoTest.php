<?php

namespace Drupal\Tests\form_api_example\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Url;

/**
 * @group form_api_example
 *
 * @ingroup form_api_example
 */
class StateDemoTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Our module dependencies.
   *
   * @var string[]
   */
  public static $modules = ['form_api_example'];

  /**
   * Functional tests for the StateDemo example form.
   */
  public function testStateForm() {
    // Visit form route.
    $route = Url::fromRoute('form_api_example.state_demo');
    $this->drupalGet($route);

    // Get Mink stuff.
    $page = $this->getSession()->getPage();

    // Verify we can find the diet restrictions textfield, and that by default
    // it is not visible.
    $this->assertNotEmpty($checkbox = $page->find('css', 'input[name="diet"]'));
    $this->assertFalse($checkbox->isVisible(), 'Diet restrictions field is not visible.');

    // Check the needs special accommodation checkbox.
    $page->checkField('needs_accommodation');

    // Verify the textfield is visible now.
    $this->assertTrue($checkbox->isVisible(), 'Diet restrictions field is visible.');
  }

}
