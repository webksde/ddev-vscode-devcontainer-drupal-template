<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements common submit handler used in form_api_example demo forms.
 *
 * We extend FormBase, which is the simplest form base class used in Drupal, to
 * add a common submitForm method that will display the submitted values via
 * the Messenger service.
 *
 * @see \Drupal\Core\Form\FormBase
 */
abstract class DemoBase extends FormBase {

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Find out what was submitted.
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $label = isset($form[$key]['#title']) ? $form[$key]['#title'] : $key;

      // Many arrays return 0 for unselected values so lets filter that out.
      if (is_array($value)) {
        $value = array_filter($value);
      }
      // Only display for controls that have titles and values.
      if ($value) {
        $display_value = is_array($value) ? print_r($value, 1) : $value;
        $message = $this->t('Value for %title: %value', ['%title' => $label, '%value' => $display_value]);
        $this->messenger()->addMessage($message);
      }
    }
  }

}
