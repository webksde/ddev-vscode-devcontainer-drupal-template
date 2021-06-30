<?php

namespace Drupal\Tests\block_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the configuration options and block created by Block Example module.
 *
 * @ingroup block_example
 *
 * @group block_example
 * @group examples
 */
class BlockExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'block_example'];

  /**
   * Tests block_example functionality.
   */
  public function testBlockExampleBasic() {
    $assert = $this->assertSession();

    // Create user.
    $web_user = $this->drupalCreateUser(['administer blocks']);
    // Login the admin user.
    $this->drupalLogin($web_user);

    $theme_name = $this->config('system.theme')->get('default');

    // Verify the blocks are listed to be added.
    $this->drupalGet('/admin/structure/block/library/' . $theme_name, ['query' => ['region' => 'content']]);
    $assert->pageTextContains('Example: configurable text');
    $assert->pageTextContains('Example: empty block');
    $assert->pageTextContains('Example: uppercase this please');

    // Define and place blocks.
    $settings_configurable = [
      'label' => 'Configurable text',
      'id' => 'block_example_example_configurable_text',
      'theme' => $theme_name,
    ];
    $this->drupalPlaceBlock('example_configurable_text', $settings_configurable);

    $settings_uppercase = [
      'label' => 'Configurable block to be uppercased',
      'id' => 'block_example_example_uppercased',
      'theme' => $theme_name,
    ];
    $this->drupalPlaceBlock('example_uppercase', $settings_uppercase);

    $settings_empty = [
      'label' => 'Empty block',
      'id' => 'block_example_example_empty',
      'theme' => $theme_name,
    ];
    $this->drupalPlaceBlock('example_empty', $settings_empty);

    // Verify that blocks are there. Empty block will not be shown, because it
    // holds an empty array.
    $this->drupalGet('');
    $assert->pageTextContains($settings_configurable['label']);
    $assert->pageTextContains($settings_uppercase['label']);
    $assert->pageTextContains(mb_strtoupper($settings_uppercase['label']));
    $assert->pageTextNotContains($settings_empty['label']);

    // Change content of configurable text block.
    $edit = [
      'settings[block_example_string_text]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('/admin/structure/block/manage/' . $settings_configurable['id'], $edit, 'Save block');
    $assert->statusCodeEquals(200);

    // Verify that new content is shown.
    $this->drupalGet('');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($edit['settings[block_example_string_text]']);
  }

}
