<?php

namespace Drupal\Tests\tabledrag_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify functionalities of tabledrag_example.
 *
 * @group tabledrag_example
 * @group examples
 *
 * @ingroup tabledrag_example
 *
 * @todo Add more tests in
 *   https://www.drupal.org/project/examples/issues/2925368
 */
class TabledragMenuTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['tabledrag_example'];

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
   * Tests tabledrag_example menus.
   */
  public function testTabledragInteractions() {
    $links = [
      'examples/tabledrag-example',
      'examples/tabledrag-example/row',
      'examples/tabledrag-example/nested',
      'examples/tabledrag-example/roots-and-leaves',
      'examples/tabledrag-example/reset',
    ];

    // Login a user that can access content.
    $this->drupalLogin(
      $this->createUser(['access content'])
    );

    $assertion = $this->assertSession();

    // Get the front page, which should only have the links in the sidebar.
    $this->drupalGet('');
    foreach ($links as $path) {
      $assertion->linkByHrefExists($path);
    }

    // Get each path and verify a 200 response.
    foreach ($links as $path) {
      $this->drupalGet($path);
      $assertion->statusCodeEquals(200);
    }

    // Click all the submit and cancel buttons.
    $pages = [
      'tabledrag_example.simple_form' => ['Save All Changes', 'Cancel'],
      'tabledrag_example.parent_form' => ['Save All Changes', 'Cancel'],
      'tabledrag_example.rootleaf_form' => ['Save All Changes', 'Cancel'],
      'tabledrag_example.reset_form' => ['Yes, Reset It!'],
    ];
    foreach ($pages as $route => $buttons) {
      $path = Url::fromRoute($route);
      foreach ($buttons as $button) {
        $this->drupalPostForm($path, [], $button);
        $assertion->statusCodeEquals(200);
      }
    }
    // The reset form implements 'Cancel' as a link.
    $this->drupalGet(Url::fromRoute('tabledrag_example.reset_form'));
    $this->clickLink('Cancel');
    $assertion->statusCodeEquals(200);
  }

}
