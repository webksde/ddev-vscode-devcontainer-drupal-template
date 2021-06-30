<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Submit a form without a page reload.
 */
class SubmitDriven extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_autotextfields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This container wil be replaced by AJAX.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
    ];
    // The box contains some markup that we can change on a submit request.
    $form['container']['box'] = [
      '#type' => 'markup',
      '#markup' => '<h1>Initial markup for box</h1>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      // The AJAX handler will call our callback, and will replace whatever page
      // element has id box-container.
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'box-container',
      ],
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Callback for submit_driven example.
   *
   * Select the 'box' element, change the markup in it, and return it as a
   * renderable array.
   *
   * @return array
   *   Renderable array (the box element)
   */
  public function promptCallback(array &$form, FormStateInterface $form_state) {
    // In most cases, it is recommended that you put this logic in form
    // generation rather than the callback. Submit driven forms are an
    // exception, because you may not want to return the form at all.
    $element = $form['container'];
    $element['box']['#markup'] = "Clicked submit ({$form_state->getValue('op')}): " . date('c');
    return $element;
  }

}
