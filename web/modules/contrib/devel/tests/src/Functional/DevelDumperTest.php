<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests pluggable dumper feature.
 *
 * @group devel
 */
class DevelDumperTest extends DevelBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['devel', 'devel_dumper_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test dumpers configuration page.
   */
  public function testDumpersConfiguration() {
    $this->drupalGet('admin/config/development/devel');

    // Ensures that the dumper input is present on the config page.
    $this->assertSession()->fieldExists('dumper');

    // Ensures that the 'default' dumper is enabled by default.
    $this->assertSession()->checkboxChecked('edit-dumper-default');

    // Ensures that all dumpers declared by devel are present on the config page
    // and that only the available dumpers are selectable.
    $dumpers = [
      'default',
      'var_dumper',
    ];
    $available_dumpers = ['default', 'var_dumper'];

    foreach ($dumpers as $dumper) {
      $this->assertFieldsByValue($this->xpath('//input[@type="radio" and @name="dumper"]'), $dumper);
      if (in_array($dumper, $available_dumpers)) {
        $this->assertFieldsByValue($this->xpath('//input[@name="dumper" and not(@disabled="disabled")]'), $dumper);
      }
      else {
        $this->assertFieldsByValue($this->xpath('//input[@name="dumper" and @disabled="disabled"]'), $dumper);
      }
    }

    // Ensures that dumper plugins declared by other modules are present on the
    // config page and that only the available dumpers are selectable.
    $this->assertFieldsByValue($this->xpath('//input[@name="dumper"]'), 'available_test_dumper');
    $this->assertSession()->pageTextContains('Available test dumper.');
    $this->assertSession()->pageTextContains('Drupal dumper for testing purposes (available).');
    $this->assertFieldsByValue($this->xpath('//input[@name="dumper" and not(@disabled="disabled")]'), 'available_test_dumper', 'Available dumper input not is disabled.');

    $this->assertFieldsByValue($this->xpath('//input[@name="dumper"]'), 'not_available_test_dumper');
    $this->assertSession()->pageTextContains('Not available test dumper.');
    $this->assertSession()->pageTextContains('Drupal dumper for testing purposes (not available).Not available. You may need to install external dependencies for use this plugin.');
    $this->assertFieldsByValue($this->xpath('//input[@name="dumper" and @disabled="disabled"]'), 'not_available_test_dumper', 'Non available dumper input is disabled.');

    // Ensures that saving of the dumpers configuration works as expected.
    $edit = [
      'dumper' => 'var_dumper',
    ];
    $this->drupalPostForm('admin/config/development/devel', $edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxChecked('Symfony var-dumper');

    $config = \Drupal::config('devel.settings')->get('devel_dumper');
    $this->assertEquals('var_dumper', $config, 'The configuration options have been properly saved');
  }

  /**
   * Test variable is dumped in page.
   */
  public function testDumpersOutput() {
    $edit = [
      'dumper' => 'available_test_dumper',
    ];
    $this->drupalPostForm('admin/config/development/devel', $edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('devel_dumper_test/dump');
    $elements = $this->xpath('//body/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::dump() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/message');
    $elements = $this->xpath('//div[@aria-label="Status message"]/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/export');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/export_renderable');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::exportAsRenderable() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message is present.');
    // Ensures that plugins can add libraries to the page when the
    // ::exportAsRenderable() method is used.
    $this->assertSession()->responseContains('devel_dumper_test/css/devel_dumper_test.css');
    $this->assertSession()->responseContains('devel_dumper_test/js/devel_dumper_test.js');

    // @todo Cater for deprecated code where the replacement has not been
    // backported. Remove this when support for core 8.7 is no longer required.
    // @see https://www.drupal.org/project/devel/issues/3118851
    if (version_compare(\Drupal::VERSION, 8.8, '>=')) {
      // For 8.8+.
      $debug_filename = \Drupal::service('file_system')->getTempDirectory() . '/' . 'drupal_debug.txt';
    }
    else {
      // Up to 8.7.
      $debug_filename = file_directory_temp() . '/drupal_debug.txt';
    }

    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEquals($file_content, $expected, 'Dumped message is present.');

    // Ensures that the DevelDumperManager::debug() is not access checked and
    // that the dump is written in the debug file even if the user has not the
    // 'access devel information' permission.
    file_put_contents($debug_filename, '');
    $this->drupalLogout();
    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEquals($file_content, $expected, 'Dumped message is present.');
  }

}
