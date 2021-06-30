<?php

namespace Drupal\Tests\session_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the basic functions of the Session Example module.
 *
 * @ingroup session_example
 *
 * @group session_example
 * @group examples
 */
class SessionExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['session_example', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Place our blocks.
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'content']);
    $this->drupalPlaceBlock('system_menu_block:tools', []);
    // Login a user that can access content.
    $this->drupalLogin(
      $this->createUser(['access content'])
    );
  }

  /**
   * Test all the routes, and ensure that forms can be submitted.
   */
  public function testSessionExampleLinks() {
    $assert = $this->assertSession();

    // Routes with menu links, and their form buttons.
    $routes_with_menu_links = [
      'session_example.form' => ['Save', 'Clear Session'],
    ];

    // Ensure the links appear in the tools menu sidebar.
    $this->drupalGet('');
    foreach (array_keys($routes_with_menu_links) as $route) {
      $assert->linkByHrefExists(Url::fromRoute($route)->getInternalPath());
    }

    // All our routes with their form buttons.
    $routes = [
      'session_example.view' => [],
    ];

    // Go to all the routes and click all the buttons.
    $routes = array_merge($routes_with_menu_links, $routes);
    foreach ($routes as $route => $buttons) {
      $url = Url::fromRoute($route);
      $this->drupalGet($url);
      $assert->statusCodeEquals(200);
      foreach ($buttons as $button) {
        $this->drupalPostForm($url, [], $button);
        $assert->statusCodeEquals(200);
      }
    }
  }

  /**
   * Functional tests for the session example.
   */
  public function testSessionExample() {
    $assert = $this->assertSession();
    // Get the form and verify that it has placeholders.
    $this->drupalGet(Url::fromRoute('session_example.form'));
    $assert->responseContains('placeholder="Your name."', 'Name placeholder contains Your name');
    $assert->responseContains('placeholder="Your email address."', 'Email placeholder contains Your email address.');
    $assert->responseContains('placeholder="What is your quest?"', 'Quest placeholder contains What is your quest?');

    // Get the report and verify that it doesn't show any session information.
    $this->clickLink('View');
    $assert->pageTextContains('No name');
    $assert->pageTextContains('No email');
    $assert->pageTextContains('No quest');
    $assert->pageTextContains('No color');

    // Save an empty session submission.
    $this->drupalPostForm(Url::fromRoute('session_example.form'), [], 'Save');
    $assert->pageTextContains('The session has been saved successfully.');

    // Make sure an empty session submission still has no reported information.
    $this->clickLink('Check here');
    $assert->pageTextContains('No name');
    $assert->pageTextContains('No email');
    $assert->pageTextContains('No quest');
    $assert->pageTextContains('No color');

    // Submit some session information.
    $form_data = [
      'name' => 'Sir Lancelot',
      'quest' => 'To seek the Grail',
      'color' => 'blue',
    ];
    $this->drupalPostForm(Url::fromRoute('session_example.form'), $form_data, 'Save');

    // Check that the report shows our information.
    $this->clickLink('Check here');
    foreach ($form_data as $value) {
      $assert->pageTextContains($value);
    }

    // Clear the session.
    $this->drupalPostForm(Url::fromRoute('session_example.form'), [], 'Clear Session');
    $assert->pageTextContains('Session is cleared.');

    // Verify that the session information doesn't show Sir Lancelot (or anyone
    // else).
    $this->clickLink('View');
    $assert->pageTextContains('No name');
    $assert->pageTextContains('No email');
    $assert->pageTextContains('No quest');
    $assert->pageTextContains('No color');
  }

  /**
   * Ensure the session data does not follow different users around.
   */
  public function testUserIsolation() {
    $assert = $this->assertSession();
    // Our setUp() method has already logged in a user, so let's add some data.
    $form_data = [
      'name' => 'Sir Lancelot',
      'quest' => 'To seek the Grail',
      'color' => 'blue',
    ];
    $this->drupalPostForm(Url::fromRoute('session_example.form'), $form_data, 'Save');

    // Check that the report shows our information.
    $this->clickLink('Check here');
    foreach ($form_data as $value) {
      $assert->pageTextContains($value);
    }

    // Let's log in a new user and make sure they can't see the other user's
    // data.
    $this->drupalLogin(
      $this->createUser(['access content'])
    );
    $this->drupalGet(Url::fromRoute('session_example.view'));
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('No name');
    $assert->pageTextContains('No email');
    $assert->pageTextContains('No quest');
    $assert->pageTextContains('No color');
  }

}
