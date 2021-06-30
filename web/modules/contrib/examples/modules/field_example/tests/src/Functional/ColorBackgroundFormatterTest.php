<?php

namespace Drupal\Tests\field_example\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Test the basic functionality of Color background  formatter.
 *
 * Create a content type with a field_example_rgb field, configure it with the
 * color_background_formatter, create a node and check for correct values.
 *
 * @group field_example
 * @group examples
 *
 * @ingroup field_example
 */
class ColorBackgroundFormatterTest extends FieldExampleBrowserTestBase {

  /**
   * Field example scenario tests.
   *
   * The following scenarios:
   * - Creates a content type.
   * - Adds a multivalued field_example_rgb to it.
   * - Creates a node of the new type.
   * - Populates the multivalued field with two items.
   * - Tests the result.
   */
  public function testSingleValueField() {
    $assert = $this->assertSession();
    // Login with Admin and create a field.
    $this->drupalLogin($this->administratorAccount);
    $this->fieldName = $this->createField('field_example_rgb', 'field_example_colorpicker', '1', 'field_example_color_background');

    // Login with Author user for content creation.
    $this->drupalLogin($this->authorAccount);
    $this->drupalGet('node/add/' . $this->contentTypeName);

    // Details to be submitted for content creation.
    $title = $this->randomMachineName(20);
    $edit = [
      'title[0][value]' => $title,
      'field_' . $this->fieldName . '[0][value]' => '#00ff00',
    ];

    // Submit the content creation form.
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert->pageTextContains((string) new FormattableMarkup('@type @title has been created', ['@type' => $this->contentTypeName, '@title' => $title]));

    // Verify color.
    $assert->pageTextContains('The content area color has been changed to #00ff00');
  }

  /**
   * Tests a multi-value field.
   *
   * Test the following scenarios in Field example:
   * - Creates a content type.
   * - Adds a multivalued field_example_rgb to it.
   * - Creates a node of the new type.
   * - Populates the multivalued field with two items.
   * - Tests the result.
   */
  public function testMultiValueField() {
    $assert = $this->assertSession();

    // Login with Admin and create a field.
    $this->drupalLogin($this->administratorAccount);
    $this->fieldName = $this->createField('field_example_rgb', 'field_example_colorpicker', '-1', 'field_example_color_background');

    // Login with Author user for content creation.
    $this->drupalLogin($this->authorAccount);
    $this->drupalGet('node/add/' . $this->contentTypeName);

    // Details to be submitted for content creation.
    $title = $this->randomMachineName(20);
    $edit = [
      'title[0][value]' => $title,
      'field_' . $this->fieldName . '[0][value]' => '#00ff00',
    ];

    // Add another field value.
    $this->drupalPostForm(NULL, $edit, 'Add another item');

    // Set value for newly added item.
    $edit = [
      'field_' . $this->fieldName . '[1][value]' => '#ffff4f',
    ];

    // Submit the content creation form.
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert->pageTextContains((string) new FormattableMarkup('@type @title has been created', ['@type' => $this->contentTypeName, '@title' => $title]));

    // Verify color.
    $assert->pageTextContains('The content area color has been changed to #00ff00');
    $assert->pageTextContains('The content area color has been changed to #ffff4f');
  }

}
