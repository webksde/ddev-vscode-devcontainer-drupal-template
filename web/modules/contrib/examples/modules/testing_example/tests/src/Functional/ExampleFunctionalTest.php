<?php

namespace Drupal\Tests\testing_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Class ExampleFunctionalTest.
 *
 * You likely will want to see the various pages and forms navigated by this
 * test. To do so, run PHPUnit with the equivalent of:
 *
 * @code
 * vendor/phpunit/phpunit/phpunit -c core/phpunit.xml --printer '\Drupal\Tests\Listeners\HtmlOutputPrinter' modules/examples/testing_example/tests/src/Functional
 * @endcode
 *
 * @group testing_example
 * @group examples
 */
class ExampleFunctionalTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user'];

  /**
   * Fixture user with administrative powers.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Fixture authenticated user with no permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * {@inheritdoc}
   *
   * The setUp() method is run before every other test method, so commonalities
   * should go here.
   */
  protected function setUp() {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'administer permissions',
      'administer nodes',
      'administer content types',
    ]);
    $this->authUser = $this->drupalCreateUser([], 'authuser');

    // We have to create a content type because testing uses the 'testing'
    // profile, which has no content types by default.
    // Although we could have visited admin pages and pushed buttons to create
    // the content type, there happens to be function we can use in this case.
    $this->createContentType(['type' => 'test_content_type']);
  }

  /**
   * Demonstrate node creation through UI interaction.
   */
  public function testNewPage() {
    // We log in an administrator because they will have permissions to create
    // content.
    $this->drupalLogin($this->adminUser);

    // For many assertions, we need a WebAssert object. This object gives us
    // assertion types for the HTTP requests we make, such as content and the
    // HTTP status code.
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Get the page that lets us add new content.
    $this->drupalGet('node/add/test_content_type');
    // Use the WebAssert object to assert the HTTP status code.
    $assert->statusCodeEquals(200);

    // Set up our new piece of content.
    $nodeTitle = 'Test node for testNewPage';
    $edit = [
      'title[0][value]' => $nodeTitle,
      'body[0][value]' => 'Body of test node',
    ];
    // Tell Drupal to post our new content. We post to NULL for the URL which
    // tells drupalPostForm() to use the current page.
    $this->drupalPostForm(NULL, $edit, 'op');
    // Check our expectations.
    $assert->statusCodeEquals(200);
    $assert->linkExists($nodeTitle);

    // Log in our non-admin user and navigate to the node.
    $this->drupalLogin($this->authUser);

    // We can search for the node by its title. Since the node object can also
    // tell us its URL, we can just feed that information into drupalGet().
    /** @var \Drupal\node\NodeInterface $createdNode */
    $createdNode = $this->drupalGetNodeByTitle($nodeTitle);
    $url = $createdNode->toUrl();
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Look at the page title.
    $assert->titleEquals("{$nodeTitle} | Drupal");

    // Find the title of the node itself.
    $nodeTitleElement = $this->getSession()
      ->getPage()
      ->find('css', 'h1 span');
    $this->assertEquals($nodeTitleElement->getText(), $nodeTitle);
  }

  /**
   * Demonstrate node creation via NodeCreationTrait::createNode.
   */
  public function testNewPageApiCreate() {
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $nodeTitle = 'Test node for testNewPageApiCreate';

    // Create new node using API.
    $node = $this->drupalCreateNode([
      'type' => 'test_content_type',
      'title' => $nodeTitle,
      'body' => [
        [
          'format' => filter_default_format($this->adminUser),
          'value' => 'Body of test node',
        ],
      ],
    ]);
    $node->save();
    $url = $node->toUrl();

    // Confirm page creation.
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Log in our normal user and navigate to the node.
    $this->drupalLogin($this->authUser);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Look at the *page* title.
    $assert->titleEquals("{$nodeTitle} | Drupal");

    // Find the title of the node itself.
    $nodeTitleElement = $this->getSession()
      ->getPage()
      ->find('css', 'h1 span');
    $this->assertEquals($nodeTitleElement->getText(), $nodeTitle);
  }

}
