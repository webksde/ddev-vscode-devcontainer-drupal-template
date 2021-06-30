<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Note: Drush must be installed. Add it to your require-dev in composer.json.
 */

/**
 * @coversDefaultClass \Drupal\devel\Commands\DevelCommands
 * @group devel
 */
class DevelCommandsTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['devel'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests drush commands.
   */
  public function testCommands() {
    $this->drush('devel:token', [], ['format' => 'json']);
    $output = $this->getOutputFromJSON();
    $tokens = array_column($output, 'token');
    $this->assertContains('account-name', $tokens);

    $this->drush('devel:services', [], ['format' => 'json']);
    $output = $this->getOutputFromJSON();
    $this->assertContains('current_user', $output);
  }

}
