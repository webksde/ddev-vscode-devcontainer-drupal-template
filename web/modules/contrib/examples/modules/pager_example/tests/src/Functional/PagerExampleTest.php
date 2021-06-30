<?php

namespace Drupal\Tests\pager_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Tests paging.
 *
 * @group pager_example
 * @group examples
 */
class PagerExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['pager_example', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in a user to prevent caching from affecting the results.
    $normalUser = $this->drupalCreateUser();
    $this->drupalLogin($normalUser);
  }

  /**
   * Confirms nodes paging works correctly on page "pager_example".
   */
  public function testPagerExamplePage() {
    $assert = $this->assertSession();

    $nodes = [];
    $nodes[] = $this->drupalCreateNode();

    $this->drupalGet('examples/pager-example');
    $assert->linkNotExists('Next');
    $assert->linkNotExists('Previous');

    // Create 5 new nodes.
    for ($i = 1; $i <= 5; $i++) {
      $nodes[] = $this->drupalCreateNode([
        'title' => "Node number $i",
      ]);
    }

    // The pager pages are cached, so flush to see the 5 more nodes.
    drupal_flush_all_caches();

    // Check 'Next' link on first page.
    $this->drupalGet('examples/pager-example');
    $assert->statusCodeEquals(200);
    $assert->linkByHrefExists('?page=1');
    $assert->pageTextContains($nodes[5]->getTitle());

    // Check the last page.
    $this->drupalGet('examples/pager-example', ['query' => ['page' => 2]]);
    $assert->statusCodeEquals(200);
    $assert->linkNotExists('Next');
    $assert->linkByHrefExists('?page=1');
    $assert->pageTextContains($nodes[1]->getTitle());
  }

}
