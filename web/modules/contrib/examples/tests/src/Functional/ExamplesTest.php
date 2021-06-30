<?php

namespace Drupal\Tests\examples\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Minimal test case for the examples module.
 *
 * @group examples
 *
 * @ingroup examples
 */
class ExamplesTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['examples', 'toolbar'];

  /**
   * Verify that the toolbar tab and tray are showing and functioning.
   */
  public function testExampleToolbar() {
    $assert = $this->assertSession();

    // Log in a user who can see the toolbar and all the routes in it.
    $this->drupalLogin($this->drupalCreateUser(['access content', 'access toolbar']));

    // All this should be on the front page.
    $this->drupalGet('');
    $assert->statusCodeEquals(200);

    // Assert that the toolbar tab registered by examples is present.
    $assert->linkExists('Examples');

    // Assert that the toolbar tab registered by examples is present.
    $this->assertNotEmpty($this->xpath('//nav/div/a[@data-toolbar-tray="toolbar-item-examples-tray"]'));

    // Assert that the toolbar tray registered by examples is present.
    $this->assertNotEmpty($this->xpath('//nav/div/div[@data-toolbar-tray="toolbar-item-examples-tray"]'));

    /* @var $module_installer \Drupal\Core\Extension\ModuleInstallerInterface */
    $module_installer = $this->container->get('module_installer');

    // Loop through all the routes. Check that they are not present in the
    // toolbar, enable the module, and then check that they are present in the
    // toolbar.
    foreach (_examples_toolbar_routes() as $module => $route) {
      // Convert the module name to the HTML class.
      $class = Html::getClass($module);
      $xpath = "//li/a[@class=\"$class\"]";

      // Assert that the toolbar link item isn't present.
      $this->assertEmpty($this->xpath($xpath), 'Found li with this class: ' . $class);

      // Install the module.
      $module_installer->install([$module], TRUE);
      $this->resetAll();

      // Load the route.
      $this->drupalGet(Url::fromRoute($route));

      // Assert that the toolbar link is present.
      $this->assertNotEmpty($this->xpath($xpath), 'Unable to find toolbar link for module: ' . $module);

      // Handle some special cases where modules depend on each other so they
      // might have already put the toolbar link in the toolbar.
      if ($module == 'file_example') {
        $module_installer->uninstall(['file_example', 'stream_wrapper_example']);
      }

    }
  }

}
