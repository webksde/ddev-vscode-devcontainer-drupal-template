<?php

namespace Drupal\Tests\admin_toolbar_search\FunctionalJavascript;

use Drupal\media\Entity\MediaType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Test the functionality of admin toolbar search.
 *
 * @group admin_toolbar
 * @group admin_toolbar_search
 */
class AdminToolbarToolsSearchTest extends AdminToolbarSearchTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_toolbar_tools',
    'admin_toolbar_search',
    'node',
    'media',
    'field_ui',
    'menu_ui',
    'block',
  ];

  /**
   * The admin user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $dog_names = [
      'archie' => 'Archie',
      'bailey' => 'Bailey',
      'bella' => 'Bella',
      'buddy' => 'Buddy',
      'charlie' => 'Charlie',
      'coco' => 'Coco',
      'daisy' => 'Daisy',
      'frankie' => 'Frankie',
      'jack' => 'Jack',
      'lola' => 'Lola',
      'lucy' => 'Lucy',
      'max' => 'Max',
      'milo' => 'Milo',
      'molly' => 'Molly',
      'ollie' => 'Ollie',
      'oscar' => 'Oscar',
      'rosie' => 'Rosie',
      'ruby' => 'Ruby',
      'teddy' => 'Teddy',
      'toby' => 'Toby',
      'tonga' => 'Tonga',
      'tracey' => 'Tracey',
      'tuna' => 'Tuna',
      'uno' => 'Uno',
      'venus' => 'Venus',
      'vicky' => 'Vicky',
      'wimpy' => 'Wimpy',
      'yellow' => 'Yellow',
      'zac' => 'zac',
      'zora' => 'zora',
    ];

    foreach ($dog_names as $machine_name => $label) {
      $this->createMediaType('image', [
        'id' => $machine_name,
        'label' => $label,
      ]);
    }

    $this->adminUser = $this->drupalCreateUser([
      'access toolbar',
      'administer menu',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer node fields',
      'access media overview',
      'administer media',
      'administer media fields',
      'administer media form display',
      'administer media display',
      'administer media types',
      'use admin toolbar search',
    ]);
  }

  /**
   * Tests search functionality with admin_toolbar_tools enabled.
   */
  public function testToolbarSearch() {
    $search_tab = '#admin-toolbar-search-tab';
    $search_toolbar_item = '#toolbar-item-administration-search';
    $search_tray = '#toolbar-item-administration-search-tray';

    $this->drupalLogin($this->adminUser);
    $assert_session = $this->assertSession();
    $assert_session->responseContains('admin.toolbar_search.css');
    $assert_session->responseContains('admin_toolbar_search.js');
    $assert_session->waitForElementVisible('css', $search_tab);
    $assert_session->waitForElementVisible('css', $search_toolbar_item);
    $assert_session->waitForElementVisible('css', $search_tray);

    $this->assertSuggestionContains('basic', 'admin/config/system/site-information');

    // Rebuild menu items.
    drupal_flush_all_caches();

    // Test that the route admin_toolbar.search returns expected json.
    $this->drupalGet('/admin/admin-toolbar-search');

    $search_menus = [
      'maeve',
      'milo',
      'nora',
      'olivia',
      'ophelia',
      'posie',
      'rose',
      'silas',
      'soren',
    ];

    $toolbar_menus = [
      'ada',
      'amara',
      'amelia',
      'arabella',
      'asher',
      'astrid',
      'atticus',
      'aurora',
      'ava',
    ];

    foreach ($search_menus as $menu_id) {
      $assert_session->responseContains('\/admin\/structure\/menu\/manage\/' . $menu_id);
    }

    foreach ($toolbar_menus as $menu_id) {
      $assert_session->responseNotContains('\/admin\/structure\/menu\/manage\/' . $menu_id);
    }

    $this->drupalGet('/admin');

    foreach ($search_menus as $menu_id) {
      $this->assertMenuDoesNotHaveHref('/admin/structure/menu/manage/' . $menu_id);
    }

    foreach ($toolbar_menus as $menu_id) {
      $this->assertMenuHasHref('/admin/structure/menu/manage/' . $menu_id);
    }

    $this->drupalGet('admin/structure/types/manage/article/fields');
    $assert_session->waitForElementVisible('css', $search_tray);

    $this->assertSuggestionContains('article manage fields', '/admin/structure/types/manage/article/fields');

    $suggestions = $assert_session
      ->waitForElementVisible('css', 'ul.ui-autocomplete');

    // Assert there is only one suggestion with a link to
    // /admin/structure/types/manage/article/fields.
    $count = count($suggestions->findAll('xpath', '//span[contains(text(), "/admin/structure/types/manage/article/fields")]'));
    $this->assertEquals(1, $count);

    // Test that bundle within admin toolbar appears in search.
    $this->assertSuggestionContains('lola', 'admin/structure/media/manage/lola/fields');

    // Assert that a link after the limit doesn't appear in admin toolbar.
    $zora_url = '/admin/structure/media/manage/zora/fields';
    $assert_session->elementNotContains('css', '#toolbar-administration', $zora_url);

    // Assert that a link excluded from admin toolbar appears in search.
    $this->assertSuggestionContains('zora', $zora_url);

    // Test that adding a new bundle updates the extra links loaded from
    // admin_toolbar.search route.
    $this->createMediaType('image', [
      'id' => 'zuzu',
      'label' => 'Zuzu',
    ]);

    $this->drupalGet('admin');
    $assert_session->waitForElementVisible('css', $search_tray);
    $this->assertSuggestionContains('zuzu', '/admin/structure/media/manage/zuzu/fields');

    // Test that deleting a bundle updates the extra links loaded from
    // admin_toolbar.search route.
    $zora = MediaType::load('zora');
    $zora->delete();

    $this->getSession()->reload();
    $assert_session->waitForElementVisible('css', $search_tray);
    $this->assertSuggestionNotContains('zora', $zora);
  }

}
