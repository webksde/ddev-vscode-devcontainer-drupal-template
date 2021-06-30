<?php

namespace Drupal\Tests\webprofiler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Class WebprofilerTestBase.
 *
 * @group webprofiler
 */
abstract class WebprofilerTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Wait until the toolbar is present on page.
   */
  protected function waitForToolbar() {
    $assert_session = $this->assertSession();
    $assert_session->waitForText(\Drupal::VERSION);
  }

  /**
   * Login with a user that can see the toolbar.
   */
  protected function loginForToolbar() {
    $admin_user = $this->drupalCreateUser(
      [
        'view webprofiler toolbar',
      ]
    );
    $this->drupalLogin($admin_user);
  }

  /**
   * Login with a user that can see the toolbar and the dashboard.
   */
  protected function loginForDashboard() {
    $admin_user = $this->drupalCreateUser(
      [
        'view webprofiler toolbar',
        'access webprofiler',
      ]
    );
    $this->drupalLogin($admin_user);
  }

  /**
   * Flush cache.
   */
  protected function flushCache() {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('cache_flush');
  }

}
