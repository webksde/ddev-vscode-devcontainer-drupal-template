<?php

namespace Drupal\Tests\ajax_example\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Functional test of dependent dropdown example.
 *
 * @group ajax_example
 *
 * @see \Drupal\Tests\ajax_example\FunctionalJavascript\DependentDropdownTest
 */
class DependentDropdownTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_example'];

  /**
   * Test the dependent dropdown form with AJAX.
   */
  public function testDependentDropdown() {
    // Get the Mink stuff.
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Get a URL object for the form, specifying AJAX.
    $dropdown_url = Url::fromRoute('ajax_example.dependent_dropdown', ['nojs' => 'ajax']);

    // Get the form.
    $this->drupalGet($dropdown_url);
    // Check for the initial state.
    $assert->fieldDisabled('instrument_dropdown');
    $assert->fieldValueEquals('instrument_dropdown', 'none');
    $submit_button = $page->findButton('edit-submit');
    $this->assertTrue($submit_button->hasAttribute('disabled'));

    // Run through the matrix of families.
    $families = [
      'String' => ['Violin', 'Viola', 'Cello', 'Double Bass'],
      'Woodwind' => ['Flute', 'Clarinet', 'Oboe', 'Bassoon'],
      'Brass' => ['Trumpet', 'Trombone', 'French Horn', 'Euphonium'],
      'Percussion' => ['Bass Drum', 'Timpani', 'Snare Drum', 'Tambourine'],
    ];

    foreach ($families as $family => $instruments) {
      // Select a family.
      $family_dropdown = $assert->fieldExists('instrument_family_dropdown');
      $family_dropdown->setValue($family);
      $assert->assertWaitOnAjaxRequest();

      // Get the instrument dropdown elements.
      $instrument_options = $page->findAll('css', 'select[name="instrument_dropdown"] option');
      $this->assertCount(count($instruments), $instrument_options);
      // Make sure all the instruments are in the select dropdown.
      foreach ($instrument_options as $instrument) {
        $this->assertContains($instrument->getAttribute('value'), $instruments);
      }

      // Post each instrument.
      foreach ($instruments as $instrument) {
        $this->drupalGet($dropdown_url);
        $family_dropdown->setValue($family);
        $assert->assertWaitOnAjaxRequest();
        $this->drupalPostForm(NULL, ['instrument_dropdown' => $instrument], 'Submit');
        $assert->pageTextContains("Your values have been submitted. Instrument family: $family, Instrument: $instrument");
      }
    }

  }

}
