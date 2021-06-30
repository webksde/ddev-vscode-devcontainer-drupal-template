<?php

namespace Drupal\Tests\dbtng_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Tests for the dbtng_example module.
 *
 * @group dbtng_example
 * @group examples
 *
 * @ingroup dbtng_example
 */
class DbtngExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dbtng_example'];

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
   * Regression test for dbtng_example.
   *
   * We'll verify the following:
   * - Assert that two entries were inserted at install.
   * - Test the example description page.
   * - Verify that the example pages have links in the Tools menu.
   */
  public function testDbtngExample() {
    $assert = $this->assertSession();

    // Assert that two entries were inserted at install.
    $result = $this->container->get('dbtng_example.repository')->load();
    $this->assertCount(2, $result, 'Did not find two entries in the table after installing the module.');

    // Test the example description page.
    $this->drupalGet('/examples/dbtng-example');
    $assert->statusCodeEquals(200);

    // Verify and validate that default menu links were loaded for this module.
    $links = $this->providerMenuLinks();
    foreach ($links as $page => $hrefs) {
      foreach ($hrefs as $href) {
        $this->drupalGet($page);
        $assert->linkByHrefExists($href);
      }
    }
  }

  /**
   * Data provider for testing menu links.
   *
   * @return array
   *   Array of page -> link relationships to check for:
   *   - The key is the path to the page where our link should appear.
   *   - The value is an array of links that should appear on that page.
   */
  protected function providerMenuLinks() {
    return [
      '' => [
        '/examples/dbtng-example',
      ],
      '/examples/dbtng-example' => [
        '/examples/dbtng-example/add',
        '/examples/dbtng-example/update',
        '/examples/dbtng-example/advanced',
      ],
    ];
  }

  /**
   * Test the UI.
   */
  public function testUi() {
    $assert = $this->assertSession();

    $this->drupalLogin($this->createUser());
    // Test the basic list.
    $this->drupalGet('/examples/dbtng-example');
    $assert->statusCodeEquals(200);
    $assert->pageTextMatches('%John[td/<>\w\s]+Doe%');

    // Test the add tab.
    // Add the new entry.
    $this->drupalPostForm(
      '/examples/dbtng-example/add',
      [
        'name' => 'Some',
        'surname' => 'Anonymous',
        'age' => 33,
      ],
      'Add'
    );
    // Now find the new entry.
    $this->drupalGet('/examples/dbtng-example');
    $assert->pageTextMatches('%Some[td/<>\w\s]+Anonymous%');
    // Try the update tab.
    // Find out the pid of our "anonymous" guy.
    $result = $this->container->get('dbtng_example.repository')->load(['surname' => 'Anonymous']);
    $this->drupalGet('/examples/dbtng-example');
    $this->assertCount(1, $result, 'Did not find one entry in the table with surname = "Anonymous".');
    $entry = $result[0];
    unset($entry->uid);

    $entry = ['name' => 'NewFirstName', 'age' => 22];
    $this->drupalPostForm('/examples/dbtng-example/update', $entry, 'Update');
    // Now find the new entry.
    $this->drupalGet('/examples/dbtng-example');
    $assert->pageTextMatches('%NewFirstName[td/<>\w\s]+Anonymous%');

    // Try the advanced tab.
    $this->drupalGet('/examples/dbtng-example/advanced');
    $rows = $this->xpath("//*[@id='dbtng-example-advanced-list'][1]/tbody/tr");
    $this->assertCount(1, $rows);

    $field = $this->xpath("//*[@id='dbtng-example-advanced-list'][1]/tbody/tr/td[4]");
    $this->assertEquals('Roe', $field[0]->getText());

    // Try to add an entry while logged out.
    $this->drupalLogout();
    $this->drupalPostForm(
      '/examples/dbtng-example/add',
      [
        'name' => 'Anonymous',
        'surname' => 'UserCannotPost',
        'age' => 'not a number',
      ],
      'Add'
    );
    $assert->pageTextContains('You must be logged in to add values to the database.');
    $assert->pageTextContains('Age needs to be a number');
  }

}
