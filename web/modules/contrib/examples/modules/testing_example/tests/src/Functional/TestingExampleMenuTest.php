<?php

namespace Drupal\Tests\testing_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the user-facing menus in Testing Example.
 *
 * Note that this is not an example test. We use this test to verify that
 * testing_example's links and routes all work.
 *
 * @ingroup testing_example
 *
 * @group testing_example
 * @group examples
 */
class TestingExampleMenuTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['testing_example'];

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
  public function testTestingNavigation() {
    foreach (['' => '/examples/testing-example'] as $page => $path) {
      $this->drupalGet($page);
      $this->assertLinkByHref($path);
    }
    $this->drupalGet('/examples/testing-example');
    $this->assertResponse(200);
  }

}
