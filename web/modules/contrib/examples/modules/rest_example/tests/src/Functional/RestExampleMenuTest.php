<?php

namespace Drupal\Tests\rest_example\Funtional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the user-facing menus in Rest Example.
 *
 * @ingroup rest_example
 * @group rest_example
 * @group examples
 */
class RestExampleMenuTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rest_example'];

  /**
   * The installation profile to use with this test.
   *
   * This test class requires the "Tools" block.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test for a link to the rest example in the Tools menu.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRestExampleLink() {
    $this->drupalGet('');
    $this->assertSession()->linkByHrefExists('examples/rest-client-actions');
    $this->assertSession()->linkByHrefExists('examples/rest-client-settings');
  }

  /**
   * Tests rest_example menus.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRestExampleMenu() {
    $this->drupalGet('examples/rest-client-actions');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('examples/rest-client-settings');
    $this->assertSession()->statusCodeEquals(200);
  }

}
