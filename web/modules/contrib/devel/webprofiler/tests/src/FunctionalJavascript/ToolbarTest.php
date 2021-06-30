<?php

namespace Drupal\Tests\webprofiler\FunctionalJavascript;

/**
 * Tests the JavaScript functionality of webprofiler.
 *
 * @group webprofiler
 */
class ToolbarTest extends WebprofilerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webprofiler', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node')->save(TRUE);
  }

  /**
   * Tests if the toolbar appears on front page.
   */
  public function testToolbarOnFrontPage() {
    $this->loginForToolbar();

    $this->drupalGet('<front>');

    $this->waitForToolbar();

    $assert = $this->assertSession();
    $assert->responseContains('Configure Webprofiler');
    $assert->responseContains('View latest reports');
    $assert->responseContains('Drupal Documentation');
    $assert->responseContains('Get involved!');
  }

  /**
   * Tests the toolbar report page.
   */
  public function testToolbarReportPage() {
    $this->loginForDashboard();

    $this->drupalGet('<front>');

    $this->drupalGet('admin/reports/profiler/list');

    // @todo assert some content.
  }

  /**
   * Tests the toolbar not appears on excluded path.
   */
  public function testToolbarNotAppearsOnExcludedPath() {
    $this->loginForDashboard();

    $this->drupalGet('admin/config/development/devel');
    $this->waitForToolbar();
    $assert = $this->assertSession();
    $assert->responseContains('Configure Webprofiler');

    $this->config('webprofiler.config')
      ->set('exclude', '/admin/config/development/devel')
      ->save();
    $this->drupalGet('admin/config/development/devel');
    $this->assertSession()->responseNotContains('sf-toolbar');
  }

}
