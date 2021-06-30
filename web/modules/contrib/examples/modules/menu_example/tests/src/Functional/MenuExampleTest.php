<?php

namespace Drupal\Tests\menu_example\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Test the functionality for the menu Example.
 *
 * @ingroup menu_example
 *
 * @group menu_example
 * @group examples
 */
class MenuExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_example'];

  /**
   * The installation profile to use with this test.
   *
   * We use 'minimal' because we want the tools menu to be available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Always call the parent setUp().
    parent::setUp();
    // Add the main menu block, as provided by the Block module.
    $this->placeBlock('system_menu_block:main');
  }

  /**
   * Test all the routes.
   */
  public function testMenuExampleRoutes() {
    $assert = $this->assertSession();
    // Key is route, value is page contents.
    $routes = [
      'examples.menu_example' => 'This page is displayed by the simplest (and base) menu example.',
      'examples.menu_example.permissioned' => 'A menu item that requires the "access protected menu example" permission',
      'examples.menu_example.custom_access' => 'A menu item that requires the user to posess',
      'examples.menu_example.custom_access_page' => 'This menu entry will not be visible and access will result in a 403',
      'examples.menu_example.route_only' => 'A menu entry with no menu link is',
      'examples.menu_example.use_url_arguments' => 'This page demonstrates using arguments in the url',
      'examples.menu_example.title_callbacks' => 'The title of this page is dynamically changed by the title callback',
      'examples.menu_example.placeholder_argument' => 'Demonstrate placeholders by visiting',
      'example.menu_example.path_override' => 'This menu item was created strictly to allow the RouteSubscriber',
      'examples.menu_example.alternate_menu' => 'This will be in the Main menu instead of the default Tools menu',
    ];
    $this->drupalLogin($this->createUser());
    $this->drupalGet(Url::fromRoute('<front>'));
    // Check that all the links appear in the tools menu.
    foreach (array_keys($routes) as $route) {
      $assert->linkByHrefExists(Url::fromRoute($route)->getInternalPath());
    }

    // Add routes that are not in the tools menu.
    $routes['examples.menu_example.route_only.callback'] = 'The route entry has no corresponding menu links entry';
    // Check that all the routes are reachable and contain content.
    foreach ($routes as $route => $content) {
      $this->drupalGet(Url::fromRoute($route));
      $assert->statusCodeEquals(200);
      $assert->pageTextContains($content);
    }

    // Check some special-case routes. First is the required argument path.
    $arg = 2377;
    $this->drupalGet(Url::fromRoute('examples.menu_example.placeholder_argument.display', ['arg' => $arg]));
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($arg);

    // Check the generated route_callbacks tabs.
    $dynamic_routes = [
      'examples.menu_example.tabs_second',
      'examples.menu_example.tabs_third',
      'examples.menu_example.tabs_fourth',
      'examples.menu_example.tabs_default_second',
      'examples.menu_example.tabs_default_third',
    ];
    $this->drupalGet(Url::fromRoute('examples.menu_example.tabs'));
    foreach ($dynamic_routes as $route) {
      $assert->linkByHrefExists(Url::fromRoute($route)->getInternalPath());
    }
    foreach ($dynamic_routes as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $assert->statusCodeEquals(200);
    }

    // Check the special permission route.
    $this->drupalGet(Url::fromRoute('examples.menu_example.permissioned_controlled'));
    $assert->statusCodeEquals(403);
    $this->drupalLogin($this->createUser(['access protected menu example']));
    $this->drupalGet(Url::fromRoute('examples.menu_example.permissioned_controlled'));
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('This menu entry will not show and the page will not be accessible');

    // We've already determined that the custom access route is reachable so now
    // we log out and make sure it tells us 403 because we're not authenticated.
    $this->drupalLogout();
    $this->drupalGet(Url::fromRoute('examples.menu_example.custom_access_page'));
    $assert->statusCodeEquals(403);
  }

}
