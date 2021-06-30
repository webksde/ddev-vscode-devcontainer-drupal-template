<?php

namespace Drupal\Tests\admin_toolbar_search\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\system\Entity\Menu;

/**
 * Base class for testing the functionality of admin toolbar search.
 *
 * @group admin_toolbar
 * @group admin_toolbar_search
 */
abstract class AdminToolbarSearchTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_toolbar_search',
    'node',
    'media',
    'field_ui',
    'menu_ui',
    'block',
  ];

  /**
   * A user with the 'Use Admin Toolbar search' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userWithAccess;

  /**
   * A test user without the 'Use Admin Toolbar search' permission..
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noAccessUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $baby_names = [
      'ada' => 'Ada',
      'amara' => 'Amara',
      'amelia' => 'Amelia',
      'arabella' => 'Arabella',
      'asher' => 'Asher',
      'astrid' => 'Astrid',
      'atticus' => 'Atticus',
      'aurora' => 'Aurora',
      'ava' => 'Ava',
      'cora' => 'Cora',
      'eleanor' => 'Eleanor',
      'eloise' => 'Eloise',
      'felix' => 'Felix',
      'freya' => 'Freya',
      'genevieve' => 'Genevieve',
      'isla' => 'Isla',
      'jasper' => 'Jasper',
      'luna' => 'Luna',
      'maeve' => 'Maeve',
      'milo' => 'Milo',
      'nora' => 'Nora',
      'olivia' => 'Olivia',
      'ophelia' => 'Ophelia',
      'posie' => 'Posie',
      'rose' => 'Rose',
      'silas' => 'Silas',
      'soren' => 'Soren',
    ];

    foreach ($baby_names as $id => $label) {
      $menu = Menu::create([
        'id' => $id,
        'label' => $label,
      ]);
      $menu->save();
    }

    $this->drupalPlaceBlock('local_tasks_block');

    $permissions = [
      'access toolbar',
      'administer menu',
      'access administration pages',
      'administer site configuration',
      'administer content types',
    ];
    $this->noAccessUser = $this->drupalCreateUser($permissions);
    $permissions[] = 'use admin toolbar search';
    $this->userWithAccess = $this->drupalCreateUser($permissions);
  }

  /**
   * Assert that the search suggestions contain a given string with given input.
   *
   * @param string $search
   *   The string to search for.
   * @param string $contains
   *   Some HTML that is expected to be within the suggestions element.
   */
  protected function assertSuggestionContains($search, $contains) {
    $this->resetSearch();
    $page = $this->getSession()->getPage();
    $page->fillField('admin-toolbar-search-input', $search);
    $this->getSession()->getDriver()->keyDown('//input[@id="admin-toolbar-search-input"]', ' ');
    $page->waitFor(3, function () use ($page) {
      return ($page->find('css', 'ul.ui-autocomplete')->isVisible() === TRUE);
    });
    $suggestions_markup = $page->find('css', 'ul.ui-autocomplete')->getHtml();
    $this->assertStringContainsString($contains, $suggestions_markup);
  }

  /**
   * Assert that the search suggestions does not contain a given string.
   *
   * Assert that the search suggestions does not contain a given string with a
   * given input.
   *
   * @param string $search
   *   The string to search for.
   * @param string $contains
   *   Some HTML that is not expected to be within the suggestions element.
   */
  protected function assertSuggestionNotContains($search, $contains) {
    $this->resetSearch();
    $page = $this->getSession()->getPage();
    $page->fillField('admin-toolbar-search-input', $search);
    $this->getSession()->getDriver()->keyDown('//input[@id="admin-toolbar-search-input"]', ' ');
    $page->waitFor(3, function () use ($page) {
      return ($page->find('css', 'ul.ui-autocomplete')->isVisible() === TRUE);
    });
    if ($page->find('css', 'ul.ui-autocomplete')->isVisible() === FALSE) {
      return;
    }
    else {
      $suggestions_markup = $page->find('css', 'ul.ui-autocomplete')->getHtml();
      $this->assertNotContains($contains, $suggestions_markup);
    }
  }

  /**
   * Search for an empty string to clear out the autocomplete suggestions.
   */
  protected function resetSearch() {
    $page = $this->getSession()->getPage();
    // Empty out the suggestions.
    $page->fillField('admin-toolbar-search-input', '');
    $this->getSession()->getDriver()->keyDown('//input[@id="admin-toolbar-search-input"]', ' ');
    $page->waitFor(3, function () use ($page) {
      return ($page->find('css', 'ul.ui-autocomplete')->isVisible() === FALSE);
    });
  }

  /**
   * Checks that there is a link with the specified url in the admin toolbar.
   *
   * @param string $url
   *   The url to assert exists in the admin menu.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function assertMenuHasHref($url) {
    $this->assertSession()
      ->elementExists('xpath', '//div[@id="toolbar-item-administration-tray"]//a[contains(@href, "' . $url . '")]');
  }

  /**
   * Checks that there is no link with the specified url in the admin toolbar.
   *
   * @param string $url
   *   The url to assert exists in the admin menu.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertMenuDoesNotHaveHref($url) {
    $this->assertSession()
      ->elementNotExists('xpath', '//div[@id="toolbar-item-administration-tray"]//a[contains(@href, "' . $url . '")]');
  }

}
