<?php

namespace Drupal\Tests\stream_wrapper_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;
use Drupal\Core\Url;

/**
 * Functional tests for the stream wrapper example.
 *
 * @ingroup stream_wrapper_example
 *
 * @group stream_wrapper_example
 * @group examples
 */
class StreamWrapperExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['stream_wrapper_example'];

  /**
   * Make sure all the public routes behave the way they should.
   */
  public function testRoutes() {
    $assert = $this->assertSession();

    $this->drupalLogin($this->createUser(['access content']));

    $links = [
      '' => Url::fromRoute('stream_wrapper_example.description'),
    ];

    // Check for the toolbar links.
    foreach ($links as $page => $path) {
      $this->drupalGet($page);
      $assert->linkByHrefExists($path->getInternalPath());
    }

    // Visit each route.
    foreach ($links as $path) {
      $this->drupalGet($path);
      $assert->statusCodeEquals(200);
    }
  }

}
