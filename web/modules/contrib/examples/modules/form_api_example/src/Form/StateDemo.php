<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the state demo form controller.
 *
 * This example demonstrates using the #states property to bind the visibility
 * of a form element to the value of another element in the form. In the
 * example, when the user checks the "Need Special Accommodation" checkbox,
 * additional form elements are made visible.
 *
 * The submit handler for this form is implemented by the
 * \Drupal\form_api_example\Form\DemoBase class.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\form_api_example\Form\DemoBase
 * @see drupal_process_states()
 */
class StateDemo extends DemoBase {

  /**
   * Build the simple form.
   *
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This example demonstrates the #states property. #states makes an element visibility dependent on another.'),
    ];

    $form['needs_accommodation'] = [
      '#type' => 'checkbox',
      '#title' => 'Need Special Accommodations?',
    ];

    // The #states property used here binds the visibility of the
    // container element to the value of the needs_accommodation checkbox above.
    $form['accommodation'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'accommodation',
      ],
      // #states is an associative array. Each key is the name of a state to
      // apply to the element, such as 'visible'. Each value is another array,
      // making a list of conditions that denote when the state should be
      // applied. Every condition is a key/value pair, whose key is a jQuery
      // selector that denotes another element on the page, and whose value is
      // an array of conditions, which must be met on in order for the state to
      // be applied.
      //
      // For additional documentation on the #states property including a list
      // of valid states and conditions see drupal_process_states().
      '#states' => [
        // The state being affected is "invisible".
        'invisible' => [
          // Drupal will only apply this state when the element that satisfies
          // the selector input[name="needs_accommodation"] is un-checked.
          ':input[name="needs_accommodation"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['accommodation']['diet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dietary Restrictions'),
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * @inheritdoc
   */
  public function getFormId() {
    return 'form_api_example_state_demo';
  }

  /**
   * Implements submitForm callback.
   *
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Find out what was submitted.
    $values = $form_state->getValues();
    if ($values['needs_accommodation']) {
      $this->messenger()->addMessage($this->t('Dietary Restriction Requested: %diet', ['%diet' => $values['diet']]));
    }
  }

}
