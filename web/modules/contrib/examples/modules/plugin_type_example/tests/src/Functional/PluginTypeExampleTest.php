<?php

namespace Drupal\Tests\plugin_type_example\Functional;

use Drupal\plugin_type_example\Plugin\Sandwich\ExampleHamSandwich;
use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Test the functionality of the Plugin Type Example module.
 *
 * @ingroup plugin_type_example
 *
 * @group plugin_type_example
 * @group examples
 */
class PluginTypeExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['plugin_type_example'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test the plugin manager can be loaded, and the plugins are registered.
   *
   * @todo: https://www.drupal.org/project/examples/issues/2985705
   */
  public function testPluginExample() {
    /* @var $manager \Drupal\plugin_type_example\SandwichPluginManager */
    $manager = $this->container->get('plugin.manager.sandwich');

    $sandwich_plugin_definitions = $manager->getDefinitions();

    $this->assertCount(2, $sandwich_plugin_definitions, 'There are not two sandwich plugins defined.');

    // Check some of the properties of the ham sandwich plugin definition.
    $sandwich_plugin_definition = $sandwich_plugin_definitions['ham_sandwich'];
    $this->assertEquals(426, $sandwich_plugin_definition['calories'], 'The ham sandwich plugin definition\'s calories property is not set.');

    // Create an instance of the ham sandwich plugin to check it works.
    $plugin = $manager->createInstance('ham_sandwich', ['of' => 'configuration values']);

    $this->assertInstanceOf(ExampleHamSandwich::class, $plugin);

    // Create a meatball sandwich so we can check it's special behavior on
    // Sundays.
    /* @var $meatball \Drupal\plugin_type_example\SandwichInterface */
    $meatball = $manager->createInstance('meatball_sandwich');
    // Set the $day property to 'Sun'.
    $ref_day = new \ReflectionProperty($meatball, 'day');
    $ref_day->setAccessible(TRUE);
    $ref_day->setValue($meatball, 'Sun');
    // Check the special description on Sunday.
    $this->assertEqual($meatball->description(), 'Italian style meatballs drenched in irresistible marinara sauce, served on day old bread.');
  }

  /**
   * Test the output of the example page.
   */
  public function testPluginExamplePage() {
    $assert = $this->assertSession();

    $this->drupalGet('examples/plugin-type-example');
    $assert->statusCodeEquals(200);

    // Check we see the plugin id.
    $assert->pageTextContains('ham_sandwich', 'The plugin ID was not output.');

    // Check we see the plugin description.
    $assert->pageTextContains('Ham, mustard, rocket, sun-dried tomatoes.', 'The plugin description was not output.');
  }

}
