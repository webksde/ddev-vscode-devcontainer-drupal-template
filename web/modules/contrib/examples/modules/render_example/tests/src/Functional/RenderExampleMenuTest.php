<?php

namespace Drupal\Tests\render_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the user-facing menus in Render Example.
 *
 * @ingroup render_example
 *
 * @group render_example
 * @group examples
 */
class RenderExampleMenuTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['render_example'];

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Verify and validate that default menu links were loaded for this module.
   */
  public function testRenderExampleLinksExist() {
    // Login a user that can access content.
    $this->drupalLogin(
      $this->createUser(['access content', 'access user profiles'])
    );

    $assertion = $this->assertSession();

    // Routes with menu links, and their form buttons.
    $routes = [
      'render_example.description' => [],
      'render_example.altering' => ['Save configuration'],
      'render_example.arrays' => [],
    ];

    // Ensure the links appear in the tools menu sidebar.
    $this->drupalGet('');
    foreach (array_keys($routes) as $route) {
      $assertion->linkByHrefExists(Url::fromRoute($route)->getInternalPath());
    }

    // Go to all the routes and click all the buttons.
    $routes = array_merge($routes, $routes);
    foreach ($routes as $route => $buttons) {
      $path = Url::fromRoute($route);
      $this->drupalGet($path);
      $assertion->statusCodeEquals(200);
      foreach ($buttons as $button) {
        $this->drupalPostForm($path, [], $button);
        $assertion->statusCodeEquals(200);
      }
    }
  }

}
