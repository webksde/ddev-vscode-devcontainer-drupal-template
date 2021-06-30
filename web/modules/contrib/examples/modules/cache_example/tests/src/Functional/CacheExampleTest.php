<?php

namespace Drupal\Tests\cache_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the cache_example module.
 *
 * @ingroup cache_example
 *
 * @group cache_example
 * @group examples
 */
class CacheExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cache_example'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test menu links and routes.
   *
   * Test the following:
   * - A link to the cache_example in the Tools menu.
   * - That you can successfully access the cache_example form.
   */
  public function testCacheExampleMenu() {

    $assert = $this->assertSession();

    // Test for a link to the cache_example in the Tools menu.
    $this->drupalGet('');
    $assert->statusCodeEquals(200);

    $assert->linkByHrefExists('examples/cache-example');

    // Verify if the can successfully access the cache_example form.
    $this->drupalGet('examples/cache-example');
    $assert->statusCodeEquals(200);
  }

  /**
   * Test that our caches function.
   *
   * Does the following:
   * - Load cache example page and test if displaying uncached version.
   * - Reload once again and test if displaying cached version.
   * - Find reload link and click on it.
   * - Clear cache at the end and test if displaying uncached version again.
   */
  public function testCacheExampleBasic() {
    $assert = $this->assertSession();

    // We need administrative privileges to clear the cache.
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    // Get initial page cache example page, first time accessed,
    // and assert uncached output.
    $this->drupalGet('examples/cache-example');
    $assert->pageTextContains('Source: actual file search');

    // Reload the page; the number should be cached.
    $this->drupalGet('examples/cache-example');
    $assert->pageTextContains('Source: cached');

    // Now push the button to remove the count.
    $this->drupalPostForm('examples/cache-example', [], 'Explicitly remove cached file count');
    $assert->pageTextContains('Source: actual file search');

    // Create a cached item. First make sure it doesn't already exist.
    $assert->pageTextContains('Cache item does not exist');
    $this->drupalPostForm('examples/cache-example', ['expiration' => -10], 'Create a cache item with this expiration');
    // We should now have an already-expired item. Automatically invalid.
    $assert->pageTextContains('Cache_item is invalid');
    // Now do the expiration operation.
    $this->drupalPostForm('examples/cache-example', ['cache_clear_type' => 'expire'], 'Clear or expire cache');
    // And verify that it was removed.
    $assert->pageTextContains('Cache item does not exist');

    // Create a cached item. This time we'll make it not expire.
    $this->drupalPostForm('examples/cache-example', ['expiration' => 'never_remove'], 'Create a cache item with this expiration');
    // We should now have an never-remove item.
    $assert->pageTextContains('Cache item exists and is set to expire at Never expires');
    // Now do the expiration operation.
    $this->drupalPostForm('examples/cache-example', ['cache_clear_type' => 'expire'], 'Clear or expire cache');
    // And verify that it was not removed.
    $assert->pageTextContains('Cache item exists and is set to expire at Never expires');
    // Now do tag invalidation.
    $this->drupalPostForm('examples/cache-example', ['cache_clear_type' => 'remove_tag'], 'Clear or expire cache');
    // And verify that it was invalidated.
    $assert->pageTextContains('Cache_item is invalid');
    // Do the hard delete.
    $this->drupalPostForm('examples/cache-example', ['cache_clear_type' => 'remove_all'], 'Clear or expire cache');
    // And verify that it was removed.
    $assert->pageTextContains('Cache item does not exist');
  }

}
