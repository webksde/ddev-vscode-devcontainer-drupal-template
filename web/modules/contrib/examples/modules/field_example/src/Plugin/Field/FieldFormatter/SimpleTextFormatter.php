<?php

namespace Drupal\field_example\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "field_example_simple_text",
 *   module = "field_example",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "field_example_rgb"
 *   }
 * )
 */
class SimpleTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        // We create a render array to produce the desired markup,
        // "<p style="color: #hexcolor">The color code ... #hexcolor</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'style' => 'color: ' . $item->value,
        ],
        '#value' => $this->t('The color code in this field is @code', ['@code' => $item->value]),
      ];
    }

    return $elements;
  }

}
