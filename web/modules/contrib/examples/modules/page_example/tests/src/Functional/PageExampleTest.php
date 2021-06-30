<?php

namespace Drupal\Tests\page_example\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Creates page and render the content based on the arguments passed in the URL.
 *
 * @group page_example
 * @group examples
 */
class PageExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['page_example'];

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
   * User object for our test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Generates a random string of ASCII numeric characters (values 48 to 57).
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   */
  protected static function randomNumber($length = 8) {
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= chr(mt_rand(48, 57));
    }
    return $str;
  }

  /**
   * Verify that current user has no access to page.
   *
   * @param string $url
   *   URL to verify.
   */
  public function pageExampleVerifyNoAccess($url) {
    // Test that page returns 403 Access Denied.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Data provider for testing menu links.
   *
   * @return array
   *
   *   Array of page -> link relationships to check for, with the permissions
   *   required to access them:
   *   - Permission machine name. Empty string means no login.
   *   - Array of link information:
   *     - Key is path to the page where the link should appear.
   *     - Value is the link that should appear on the page.
   */
  public function providerMenuLinks() {
    return [
      [
        '',
        ['' => '/examples/page-example'],
      ],
      [
        'access simple page',
        ['/examples/page-example' => '/examples/page-example/simple'],
      ],
    ];
  }

  /**
   * Verify and validate that default menu links were loaded for this module.
   *
   * @dataProvider providerMenuLinks
   */
  public function testPageExampleLinks($permission, $links) {
    if ($permission) {
      $user = $this->drupalCreateUser([$permission]);
      $this->drupalLogin($user);
    }
    foreach ($links as $page => $path) {
      $this->drupalGet($page);
      $this->assertSession()->linkByHrefExists($path);
    }
    if ($permission) {
      $this->drupalLogout();
    }
  }

  /**
   * Main test.
   *
   * Login user, create an example node, and test page functionality through
   * the admin and user interfaces.
   */
  public function testPageExample() {
    $assert_session = $this->assertSession();
    // Verify that anonymous user can't access the pages created by
    // page_example module.
    $this->pageExampleVerifyNoAccess('examples/page-example/simple');
    $this->pageExampleVerifyNoAccess('examples/page-example/arguments/1/2');

    // Create a regular user and login.
    $this->webUser = $this->drupalCreateUser();
    $this->drupalLogin($this->webUser);

    // Verify that regular user can't access the pages created by
    // page_example module.
    $this->pageExampleVerifyNoAccess('examples/page-example/simple');
    $this->pageExampleVerifyNoAccess('examples/page-example/arguments/1/2');

    // Create a user with permissions to access 'simple' page and login.
    $this->webUser = $this->drupalCreateUser(['access simple page']);
    $this->drupalLogin($this->webUser);

    // Verify that user can access simple content.
    $this->drupalGet('/examples/page-example/simple');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The quick brown fox jumps over the lazy dog.');

    // Check if user can't access arguments page.
    $this->pageExampleVerifyNoAccess('examples/page-example/arguments/1/2');

    // Create a user with permissions to access 'simple' page and login.
    $this->webUser = $this->drupalCreateUser(['access arguments page']);
    $this->drupalLogin($this->webUser);

    // Verify that user can access arguments content.
    $first = self::randomNumber(3);
    $second = self::randomNumber(3);
    $this->drupalGet('/examples/page-example/arguments/' . $first . '/' . $second);
    $assert_session->statusCodeEquals(200);
    // Verify argument usage.
    $assert_session->pageTextContains((string) new FormattableMarkup('First number was @number.', ['@number' => $first]));
    $assert_session->pageTextContains((string) new FormattableMarkup('Second number was @number.', ['@number' => $second]));
    $assert_session->pageTextContains((string) new FormattableMarkup('The total was @number.', ['@number' => $first + $second]));

    // Verify incomplete argument call to arguments content.
    $this->drupalGet('/examples/page-example/arguments/' . $first . '/');
    $assert_session->statusCodeEquals(404);

    // Verify 403 for invalid second argument.
    $this->drupalGet('/examples/page-example/arguments/' . $first . '/non-numeric-argument');
    $assert_session->statusCodeEquals(403);

    // Verify 403 for invalid first argument.
    $this->drupalGet('/examples/page-example/arguments/non-numeric-argument/' . $second);
    $assert_session->statusCodeEquals(403);

    // Check if user can't access simple page.
    $this->pageExampleVerifyNoAccess('examples/page-example/simple');
  }

}
