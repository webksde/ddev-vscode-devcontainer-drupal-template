<?php

namespace Drupal\Tests\rest_example\Funtional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the Views are accessible.
 *
 * @ingroup rest_example
 * @group rest_example
 * @group examples
 */
class RestExampleActionTest extends BrowserTestBase {

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
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setup();

    global $base_url;

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $config_factory = \Drupal::configFactory();

    $rest_config = $config_factory->getEditable('rest_example.settings');
    $rest_config
      ->set('server_url', $base_url)
      ->set('server_username', $account->get('name')->value)
      ->set('server_password', $account->passRaw)
      ->save();
  }

  /**
   * Test that we access the client side View.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testClientNode() {

    $this->drupalGet('examples/rest-client-actions');
    $this->assertSession()->responseContains('Nodes on the remote Drupal server');
  }

  /**
   * Test that we can access the server side View.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testServerView() {
    $this->drupalGet('rest/node');
    $this->assertSession()->responseContains('[]');
  }

}
