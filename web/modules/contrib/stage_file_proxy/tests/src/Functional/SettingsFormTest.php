<?php

namespace Drupal\Tests\stage_file_proxy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form validation.
 *
 * @group stage_file_proxy
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['stage_file_proxy'];

  /**
   * A user with the permissions to edit the stage file proxy settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer stage_file_proxy settings']);
  }

  /**
   * Tests if the origin URL gets correctly trimmed.
   */
  public function testOriginTrailingslashIsRemoved() {
    $settings_path = Url::fromRoute('stage_file_proxy.admin_form');

    $this->drupalLogin($this->adminUser);

    $testOrigin = 'http://example.com';
    $edit = [
      // Test with adding a slash.
      'origin' => $testOrigin . '/',
    ];
    $this->drupalPostForm($settings_path, $edit, 'Save configuration');

    // Test if the form was saved without error.
    $this->assertText('Your settings have been saved.');

    // Test if the stored value has the trailing slash removed.
    $newOrigin = $this->config('stage_file_proxy.settings')->get('origin');
    $this->assertIdentical($newOrigin, $testOrigin);
  }

}
