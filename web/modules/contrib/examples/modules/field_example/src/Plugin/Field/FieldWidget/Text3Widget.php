<?php

namespace Drupal\field_example\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_example_3text' widget.
 *
 * @FieldWidget(
 *   id = "field_example_3text",
 *   module = "field_example",
 *   label = @Translation("RGB text field"),
 *   field_types = {
 *     "field_example_rgb"
 *   }
 * )
 */
class Text3Widget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    // Parse the single hex string into RBG values.
    if (!empty($value)) {
      preg_match_all('@..@', substr($value, 1), $match);
    }
    else {
      $match = [[]];
    }

    // Set up the form element for this widget.
    $element += [
      '#type' => 'details',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    // Add in the RGB textfield elements.
    foreach ([
      'r' => $this->t('Red'),
      'g' => $this->t('Green'),
      'b' => $this->t('Blue'),
    ] as $key => $title) {
      $element[$key] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#size' => 2,
        '#default_value' => array_shift($match[0]),
        '#attributes' => ['class' => ['rgb-entry']],
        '#description' => $this->t('The 2-digit hexadecimal representation of @color saturation, like "a1" or "ff"', ['@color' => $title]),
      ];
      // Since Form API doesn't allow a fieldset to be required, we
      // have to require each field element individually.
      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }
    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as text.
   */
  public function validate($element, FormStateInterface $form_state) {
    // Validate each of the textfield entries.
    $values = [];
    foreach (['r', 'g', 'b'] as $colorfield) {
      $values[$colorfield] = $element[$colorfield]['#value'];
      // If they left any empty, we'll set the value empty and quit.
      if (strlen($values[$colorfield]) == 0) {
        $form_state->setValueForElement($element, '');
        return;
      }
      // If they gave us anything that's not hex, reject it.
      if ((strlen($values[$colorfield]) != 2) || !ctype_xdigit($values[$colorfield])) {
        $form_state->setError($element[$colorfield], $form_state, $this->t("Saturation value must be a 2-digit hexadecimal value between 00 and ff."));
      }
    }

    // Set the value of the entire form element.
    $value = strtolower(sprintf('#%02s%02s%02s', $values['r'], $values['g'], $values['b']));
    $form_state->setValueForElement($element, $value);
  }

}
