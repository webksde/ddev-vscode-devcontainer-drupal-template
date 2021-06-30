<?php

namespace Drupal\Tests\field_example\Functional;

/**
 * Test the user-facing menus in Field Example.
 *
 * @group field_example
 * @group examples
 *
 * @ingroup field_example
 */
class FieldExampleMenuTest extends FieldExampleBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_example'];

  /**
   * The installation profile to use with this test.
   *
   * This test class requires the "Tools" block.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test for a link to the block example in the Tools menu.
   */
  public function testFieldExampleLink() {
    $assert = $this->assertSession();
    $this->drupalGet('');
    $assert->linkByHrefExists('examples/field-example');
  }

  /**
   * Tests field_example menus.
   */
  public function testBlockExampleMenu() {
    $assert = $this->assertSession();
    $this->drupalGet('examples/field-example');
    $assert->statusCodeEquals(200);
  }

}
