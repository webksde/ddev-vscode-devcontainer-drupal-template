<?php

namespace Drupal\Tests\examples\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * A standardized base class for Examples tests.
 *
 * Use this base class if the Examples module being tested requires menus, local
 * tasks, and actions.
 */
abstract class ExamplesBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['examples', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the system menu blocks to appropriate regions.
    $this->setupExamplesMenus();
  }

  /**
   * Set up menus and tasks in their regions.
   *
   * Since menus and tasks are now blocks, we're required to explicitly set them
   * to regions. This method standardizes the way we do that for Examples.
   *
   * Note that subclasses must explicitly declare that the block module is a
   * dependency.
   */
  protected function setupExamplesMenus() {
    $this->drupalPlaceBlock('system_menu_block:tools', ['region' => 'primary_menu']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'secondary_menu']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
  }

}
