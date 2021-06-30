<?php

namespace Drupal\Tests\ajax_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional test of non-AJAX dependent dropdown example.
 *
 * @group ajax_example
 * @group examples
 *
 * @ingroup ajax_example
 *
 * @see \Drupal\Tests\ajax_example\FunctionalJavascript\DependentDropdownTest
 */
class DependentDropdownTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_example'];

  /**
   * Test the dependent dropdown form without AJAX.
   */
  public function testDependentDropdown() {
    // Get the Mink stuff.
    $session = $this->getSession();
    $assert = $this->assertSession();
    $page = $session->getPage();

    // Get a URL object for the form, specifying no JS.
    $dropdown_url = Url::fromRoute('ajax_example.dependent_dropdown', ['nojs' => 'nojs']);

    // Get the form.
    $this->drupalGet($dropdown_url);
    // Check for the initial state.
    $assert->fieldDisabled('instrument_dropdown');
    $assert->fieldValueEquals('instrument_dropdown', 'none');
    $submit_button = $page->findButton('edit-submit');
    $this->assertTrue($submit_button->hasAttribute('disabled'));

    // Run through the matrix of form submissions.
    $families = [
      'String' => ['Violin', 'Viola', 'Cello', 'Double Bass'],
      'Woodwind' => ['Flute', 'Clarinet', 'Oboe', 'Bassoon'],
      'Brass' => ['Trumpet', 'Trombone', 'French Horn', 'Euphonium'],
      'Percussion' => ['Bass Drum', 'Timpani', 'Snare Drum', 'Tambourine'],
    ];

    foreach ($families as $family => $instruments) {
      // Post the form for the instrument family.
      $this->drupalPostForm($dropdown_url, ['instrument_family_dropdown' => $family], 'Choose');
      // Get the instrument dropdown elements.
      $instrument_options = $page->findAll('css', '#edit-instrument-dropdown option');
      $this->assertCount(count($instruments), $instrument_options);
      // Make sure all the instruments are in the select dropdown.
      foreach ($instrument_options as $instrument) {
        $this->assertContains($instrument->getAttribute('value'), $instruments);
      }
      // Post each instrument. We have to 'choose' again in order to unlock the
      // instrument dropdown.
      foreach ($instruments as $instrument) {
        $this->drupalPostForm($dropdown_url, ['instrument_family_dropdown' => $family], 'Choose');
        $this->drupalPostForm(NULL, ['instrument_dropdown' => $instrument], 'Submit');
        $assert->pageTextContains("Your values have been submitted. Instrument family: $family, Instrument: $instrument");
      }
    }

  }

}
