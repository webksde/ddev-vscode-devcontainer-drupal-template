<?php

namespace Drupal\Tests\theming_example\Functional;

use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Verify the tablesort functionality.
 *
 * @group tablesort_example
 * @group examples
 *
 * @ingroup tablesort_example
 */
class ThemingExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['theming_example'];

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';
  /**
   * Verify the functionality of the example module.
   */
  public function testThemingPage() {
    // No need to login for this test.
    // Check that the main page has been themed (first line with <b>) and has
    // content.
    $this->drupalGet('/examples/theming_example');
    $this->assertRaw('Some examples of pages and forms that are run through theme functions.</h1>');
    $this->assertRaw('examples/theming_example/form_select">Simple form 1</a>');
    $this->assertRaw('examples/theming_example/form_text">Simple form 2</a>');

    // Visit the list demonstration page and check that css gets loaded
    // and do some spot checks on how the two lists were themed.
    $this->drupalGet('/examples/theming_example/list');
    // CSS should be always injected, because preprocess is set to false in *.libraries.yml
    $this->assertSession()->responseMatches('/<link rel="stylesheet".*theming_example.css/');
    $li_list = $this->xpath('//ul[contains(@class,"render-version-list")]/li');
    $this->assertTrue($li_list[0]->getText() == 'First item');
    $li_list = $this->xpath('//ol[contains(@class,"theming-example-list")]/li');
    $this->assertTrue($li_list[1]->getText() == 'Second item');

    // Visit the select form page to do spot checks.
    $this->drupalGet('/examples/theming_example/form_select');
    // Choice element title should be output separately, as h3 header.
    $this->assertRaw('<h3 data-drupal-selector="edit-title">Choose which ordering you want</h3>');
    // Choice element should be wrapped with <strong> tag.
    $this->assertRaw('<strong>Choose which ordering you want</strong>');
    // Form elements should be wrapped with container-inline div.
    $this->assertSession()->responseMatches('/<div class="container-inline choice-wrapper"><div class="[a-zA-Z- ]* form-item-choice/');
    $this->assertSession()->responseNotMatches('/<link rel="stylesheet".*theming_example.css/');

    // Visit the text form page and do spot checks.
    $this->drupalGet('/examples/theming_example/form_text');
    $this->assertText('Please input something!');
  }

}
