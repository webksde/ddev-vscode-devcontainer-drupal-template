<?php

namespace Drupal\Tests\rest_example\Funtional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the settings in Rest Example.
 *
 * @ingroup rest_example
 * @group rest_example
 * @group examples
 */
class RestExampleSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rest_example'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test that the form can be submitted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRestExampleSettings() {
    global $base_url;

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $this->drupalGet('examples/rest-client-settings');

    $edit = [
      'server_url' => $base_url,
      'server_username' => $account->get('name')->value,
      'server_password' => $account->passRaw,
    ];

    $this->drupalPostForm(base_path() . 'examples/rest-client-settings', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');

    $config_factory = \Drupal::configFactory();

    $rest_config = $config_factory->get('rest_example.settings');

    self::assertEquals($base_url, $rest_config->get('server_url'));
    self::assertEquals($account->get('name')->value, $rest_config->get('server_username'));
    self::assertEquals($account->passRaw, $rest_config->get('server_password'));

  }

}
