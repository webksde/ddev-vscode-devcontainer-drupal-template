<?php

namespace Drupal\Tests\js_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Functional tests for the js_example module.
 *
 * @ingroup js_example
 *
 * @group js_example
 * @group examples
 */
class JsExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['js_example', 'node'];

  /**
   * Test all the paths defined by our module.
   */
  public function testJsExample() {
    $assert = $this->assertSession();

    $paths = [
      'examples/js-example',
      'examples/js-example/weights',
      'examples/js-example/accordion',
    ];
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $assert->statusCodeEquals(200);
    }
  }

}
