<?php

namespace Drupal\field_example\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_example_colorpicker' widget.
 *
 * @FieldWidget(
 *   id = "field_example_colorpicker",
 *   module = "field_example",
 *   label = @Translation("Color Picker"),
 *   field_types = {
 *     "field_example_rgb"
 *   }
 * )
 */
class ColorPickerWidget extends TextWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value'] += [
      '#suffix' => '<div class="field-example-colorpicker"></div>',
      '#attributes' => ['class' => ['edit-field-example-colorpicker']],
      '#attached' => [
        // Add Farbtastic color picker and javascript file to trigger the
        // colorpicker.
        'library' => [
          'core/jquery.farbtastic',
          'field_example/colorpicker',
        ],
      ],
    ];

    return $element;
  }

}
