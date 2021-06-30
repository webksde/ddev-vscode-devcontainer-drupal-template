<?php

namespace Drupal\Tests\node_type_example\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Test that our content types are successfully created.
 *
 * @ingroup node_type_example
 *
 * @group node_type_example
 * @group examples
 */
class NodeTypeExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'node_type_example'];

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
   * Data provider for testing menu links.
   *
   * @return array
   *   Array of page -> link relationships to check for.
   *   - The key is the path to the page where our link should appear.
   *   - The value is the link that should appear on that page.
   */
  protected function providerMenuLinks() {
    return [
      '' => '/examples/node-type-example',
    ];
  }

  /**
   * Verify and validate that default menu links were loaded for this module.
   */
  public function testNodeTypeExample() {
    $assert = $this->assertSession();
    // Test that our page loads.
    $this->drupalGet('/examples/node-type-example');
    $assert->statusCodeEquals(200);

    // Test that our menu links were created.
    $links = $this->providerMenuLinks();
    foreach ($links as $page => $path) {
      $this->drupalGet($page);
      $assert->linkByHrefExists($path);
    }
  }

  /**
   * Test our new content types.
   *
   * Tests for the following:
   *
   * - That our content types appear in the user interface.
   * - That our unlocked content type is unlocked.
   * - That our locked content type is locked.
   * - That we can create content using the user interface.
   * - That our created content does appear in the database.
   */
  public function testNodeTypes() {
    $assert = $this->assertSession();

    // Log in an admin user.
    $admin_user = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($admin_user);

    // Get a list of content types.
    $this->drupalGet('/admin/structure/types');
    // Verify that these content types show up in the user interface.
    $assert->pageTextContains('Example: Basic Content Type', 'Basic content type found.');
    $assert->pageTextContains('Example: Locked Content Type', 'Locked content type found.');

    // Check for the locked status of our content types.
    // $nodeType will be of type Drupal\node\NodeTypeInterface.
    $node_type = NodeType::load('basic_content_type');
    $this->assertInstanceOf(NodeTypeInterface::class, $node_type, 'basic_content_type exists.');
    if ($node_type) {
      $this->assertFalse($node_type->isLocked(), 'basic_content_type is not locked.');
    }
    $node_type = NodeType::load('locked_content_type');
    $this->assertInstanceOf(NodeTypeInterface::class, $node_type, 'locked_content_type exists.');
    if ($node_type) {
      $this->assertEquals('locked_content_type', $node_type->isLocked());
    }

    // Log in a content creator.
    $creator_user = $this->drupalCreateUser(['create basic_content_type content']);
    $this->drupalLogin($creator_user);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('/node/add/basic_content_type', $edit, 'Save');

    // Check that the Basic page has been created.
    $assert->pageTextContains((string) new FormattableMarkup('@post @title has been created.', [
      '@post' => 'Example: Basic Content Type',
      '@title' => $edit['title[0][value]'],
    ]));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertInstanceOf(NodeInterface::class, $node, 'Node found in database.');
  }

  /**
   * Test that all fields are displayed when content is created.
   */
  public function testNodeCreation() {
    // Login content creator.
    $this->drupalLogin(
      $this->drupalCreateUser([
        'create basic_content_type content',
        'create locked_content_type content',
      ])
    );

    // Create random strings to insert data into fields.
    $title = 'Test title.';
    $body = 'Test body.';
    $edit = [];
    $edit['title[0][value]'] = $title;
    $edit['body[0][value]'] = $body;

    // Create a basic_content_type content.
    $this->drupalPostForm('/node/add/basic_content_type', $edit, 'Save');
    // Verify all fields and data of created content is shown.
    $this->assertText($title);
    $this->assertText($body);

    // Create a locked_content_type content.
    $this->drupalPostForm('/node/add/locked_content_type', $edit, 'Save');
    // Verify all fields and data of created content is shown.
    $this->assertText($title);
    $this->assertText($body);
  }

  /**
   * Verify that user can uninstall and then reinstall node_type_example.
   */
  public function testUninstallReinstall() {
    $session = $this->assertSession();

    // Uninstalling module.
    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall(['node_type_example']);
    $this->drupalGet('examples/node-type-example');
    $session->statusCodeEquals(404);

    // Re-installing module.
    $module_installer->install(['node_type_example']);
    $this->drupalGet('examples/node-type-example');
    $session->statusCodeEquals(200);
  }

}
