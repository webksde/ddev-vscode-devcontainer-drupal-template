<?php

namespace Drupal\Tests\testing_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests core menu behavior.
 *
 * This test uses BrowserTestBase to set up a fixture site so that we can test
 * for the existence of the 'Add content' link normally provided by the Standard
 * profile.
 *
 * The 'bonus points' are a reward for the effort to explicitly build up the
 * dependencies, rather than simply using the Standard profile.
 *
 * This test is meant to support a Drupalize.me tutorial.
 *
 * @group testing_example
 *
 * @see \Drupal\Tests\testing_example\Functional\FrontPageLinkTest
 */
class FrontPageLinkDependenciesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'block', 'user'];

  /**
   * Our node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Always call the parent setUp().
    parent::setUp();
    // Add the Tools menu block, as provided by the Block module.
    $this->placeBlock('system_menu_block:tools');
    // Add a content type.
    $this->contentType = $this->createContentType();
  }

  /**
   * Tests for the existence of a default menu item on the home page.
   *
   * We'll open the home page and look for the Tools menu link called 'Add
   * content.'
   */
  public function testAddContentMenuItem() {
    // Step 1: Log in a user who can add content.
    $this->drupalLogin(
      $this->createUser([
        'create ' . $this->contentType->id() . ' content',
      ])
    );

    // Step 2: Visit the home path.
    $this->drupalGet($this->buildUrl(''));
    // Step 3: Look on the page for the 'Add content' link.
    $this->assertSession()->linkExists('Add content');
  }

}
