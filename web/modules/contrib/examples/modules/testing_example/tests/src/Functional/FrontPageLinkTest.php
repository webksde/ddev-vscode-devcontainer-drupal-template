<?php

namespace Drupal\Tests\testing_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the existence of links on the front page.
 *
 * This test declares that the 'standard' profile should be used when installing
 * Drupal. Because the standard profile also specifies which theme to install,
 * Bartik, we don't need to specify a $defaultTheme like we do in other
 * functional tests.
 *
 * This test is meant to support a Drupalize.me tutorial.
 *
 * @group testing_example
 *
 * @see \Drupal\Tests\testing_example\Functional\FrontPageLinkDependenciesTest
 */
class FrontPageLinkTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

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
        'create article content',
      ])
    );

    // Step 2: Visit the home path.
    $this->drupalGet($this->buildUrl(''));
    // Step 3: Look on the page for the 'Add content' link.
    $this->assertSession()->linkExists('Add content');
  }

}
