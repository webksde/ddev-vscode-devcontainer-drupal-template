<?php

namespace Drupal\Tests\hooks_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the functionality of the Hooks Example module.
 *
 * @ingroup hooks_example
 *
 * @group hooks_example
 * @group examples
 */
class HooksExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['help', 'hooks_example'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);
  }

  /**
   * Test the output of the example page.
   */
  public function testHooksExample() {
    // Make sure our menus and links work.
    $this->drupalGet('<front>');
    $this->assertSession()->linkExists('Hooks Example');

    // Test the description page at examples/hook-example returns a 200.
    $this->drupalGet('examples/hooks-example');
    $this->assertSession()->statusCodeEquals(200);

    // Test that our implementation of hook_help() works.
    $this->drupalGet('admin/help/hooks_example');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This text is provided by the function hooks_example_help()');

    // Test that our implementation of hook_node_view() works.
    // Create a new node.
    $settings = [
      'type' => 'page',
      'title' => 'Hooks Example Testing Node',
      'status' => 1,
    ];
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet($node->toUrl());

    // Test that the output added to the page by hooks_example_node_view() is
    // present. Which also tests that our page view counting was initialized.
    $this->assertSession()->pageTextContains('You have viewed this node 1 times this session.');
    // Tests that the message set by
    // hooks_example_hooks_example_count_incremented() is displayed on the page.
    // Which also has the effect of testing to see wehther or not our custom
    // hook is being invoked.
    $this->assertSession()->pageTextContains('This is the first time you have viewed the node ' . $node->label() . '.');

    // Navigate to a new page, and then back and verify the counter was updated.
    $this->drupalGet('<front>');
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('You have viewed this node 2 times this session.');
    $this->assertSession()->pageTextNotContains('This is the first time you have viewed the node ' . $node->label() . '.');

    // Test our implementation of hook_form_alter().
    $this->drupalLogout();
    $this->drupalGet('user/login');
    $this->assertSession()->pageTextContains('This text has been altered by hooks_example_form_alter().');
  }

}
