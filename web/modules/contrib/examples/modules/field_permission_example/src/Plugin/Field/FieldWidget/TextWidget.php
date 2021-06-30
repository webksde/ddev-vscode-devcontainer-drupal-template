<?php

namespace Drupal\field_permission_example\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_permission_example_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_permission_example_widget",
 *   module = "field_permission_example",
 *   label = @Translation("Field Note Widget"),
 *   field_types = {
 *     "field_permission_example_fieldnote"
 *   }
 * )
 */
class TextWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textarea',
      '#default_value' => $value,
    ];
    return ['value' => $element];
  }

}
