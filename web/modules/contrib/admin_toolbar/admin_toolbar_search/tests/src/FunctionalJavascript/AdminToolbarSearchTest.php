<?php

namespace Drupal\Tests\admin_toolbar_search\FunctionalJavascript;

/**
 * Test the functionality of admin toolbar search.
 *
 * @group admin_toolbar
 * @group admin_toolbar_search
 */
class AdminToolbarSearchTest extends AdminToolbarSearchTestBase {

  /**
   * Tests search functionality without admin_toolbar_tools enabled.
   */
  public function testToolbarSearch() {
    $search_tab = '#admin-toolbar-search-tab';
    $search_toolbar_item = '#toolbar-item-administration-search';
    $search_tray = '#toolbar-item-administration-search-tray';

    $this->drupalLogin($this->userWithAccess);
    $assert_session = $this->assertSession();
    $assert_session->responseContains('admin.toolbar_search.css');
    $assert_session->responseContains('admin_toolbar_search.js');
    $assert_session->waitForElementVisible('css', $search_tab);
    $assert_session->waitForElementVisible('css', $search_toolbar_item);
    $assert_session->waitForElementVisible('css', $search_tray);

    $this->assertSuggestionContains('perfor', 'admin/config/development/performance');
    $this->assertSuggestionContains('develop', 'admin/config/development/maintenance');
    $this->assertSuggestionContains('types', 'admin/structure/types');
  }

  /**
   * Tests a user without the search permission can't use search.
   */
  public function testNoAccess() {
    $search_tab = '#admin-toolbar-search-tab';
    $search_toolbar_item = '#toolbar-item-administration-search';
    $search_tray = '#toolbar-item-administration-search-tray';

    $this->drupalLogin($this->noAccessUser);
    $assert_session = $this->assertSession();
    $assert_session->responseNotContains('admin.toolbar_search.css');
    $assert_session->responseNotContains('admin_toolbar_search.js');
    $assert_session->elementNotExists('css', $search_tab);
    $assert_session->elementNotExists('css', $search_toolbar_item);
    $assert_session->elementNotExists('css', $search_tray);
  }

}
