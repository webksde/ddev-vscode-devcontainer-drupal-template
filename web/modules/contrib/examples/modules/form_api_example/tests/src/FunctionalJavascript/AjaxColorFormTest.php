<?php

namespace Drupal\Tests\form_api_example\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Url;

/**
 * @group form_api_example
 *
 * @ingroup form_api_example
 */
class AjaxColorFormTest extends WebDriverTestBase {

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
   * Functional test of the color temperature AJAX dropdown form.
   */
  public function testModalForm() {
    // Visit form route.
    $this->drupalGet(Url::fromRoute('form_api_example.ajax_color_demo'));

    // Get Mink stuff.
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Before the color temperature dropdown is selected, we should not have a
    // color dropdown.
    $this->assertEmpty($page->find('css', 'select[name="color"]'));

    $color_matrix = [
      'warm' => ['red', 'orange', 'yellow'],
      'cool' => ['blue', 'purple', 'green'],
    ];

    foreach ($color_matrix as $temperature => $colors) {
      // Enter a color temperature.
      $this->assertNotEmpty(
        $color_temperature = $page->find('css', 'select[name="temperature"]')
      );
      $color_temperature->setValue($temperature);
      $assert->assertWaitOnAjaxRequest();

      // Find the color dropdown.
      $this->assertNotEmpty(
        $color_select = $page->find('css', 'select[name="color"]')
      );

      // Make sure all the correct color options are present.
      $this->assertNotEmpty(
        $color_options = $color_select->findAll('css', 'option')
      );
      $this->assertCount(count($colors), $color_options);
      foreach ($color_options as $color_element) {
        $this->assertContains($color_element->getValue(), $colors);
      }

      // Submit all the colors.
      foreach ($colors as $color) {
        $page->find('css', 'select[name="temperature"]')->setValue($temperature);
        $assert->assertWaitOnAjaxRequest();
        $page->find('css', 'select[name="color"]')->setValue($color);
        $page->findButton('Submit')->click();
        $assert->pageTextContains("Value for Temperature: $temperature");
        $assert->pageTextContains("Value for color: $color");
      }
    }

    // Finally, we can make sure that when we 'unset' the temperature dropdown,
    // the color dropdown goes away.
    $this->getSession()->getPage()->find('css', 'select[name="temperature"]')
      ->setValue('');
    $assert->assertWaitOnAjaxRequest();
    $this->assertEmpty($page->find('css', 'select[name="color"]'));
  }

}
