<?php

namespace Drupal\Tests\phpunit_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the user-facing menus in PHPUnit Example.
 *
 * Note that this is _not_ a PHPUnit-based test. It's a functional
 * test of whether this module can be enabled properly.
 *
 * @ingroup phpunit_example
 *
 * @group phpunit_example
 * @group examples
 */
class PHPUnitExampleMenuTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['phpunit_example'];

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
  public function testLinksAndPages() {
    $this->drupalLogin($this->createUser(['access content']));
    $assert = $this->assertSession();
    $links = [
      '' => Url::fromRoute('phpunit_example.description'),
    ];
    // Go to the page and see if the link appears on it.
    foreach ($links as $page => $path) {
      $this->drupalGet($page);
      $assert->linkByHrefExists($path->getInternalPath());
    }
    // Visit all the links and make sure they return 200.
    foreach ($links as $path) {
      $this->drupalGet($path);
      $assert->statusCodeEquals(200);
    }
  }

}
