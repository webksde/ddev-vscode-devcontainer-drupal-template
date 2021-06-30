<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Show textfields based on AJAX-enabled checkbox clicks.
 *
 * @ingroup ajax_example
 */
class Autotextfields extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_autotextfields';
  }

  /**
   * {@inheritdoc}
   *
   * This form has two checkboxes which the user can check in order to then
   * reveal the first and/or last name text fields.
   *
   * We could perform this behavior with #states. We might not want to if, for
   * instance, we wanted to require a name, but let the user choose whether
   * to enter first or last or both.
   *
   * For all the requests this class gets, the buildForm() method will always be
   * called. If an AJAX request comes in, the form state will be set to the
   * state the user changed that caused the AJAX request. So if the user enabled
   * one of our checkboxes, it will be checked in $form_state.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This form demonstrates changing the status of form elements through AJAX requests.'),
    ];
    $form['ask_first_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ask me my first name'),
      '#ajax' => [
        'callback' => '::textfieldsCallback',
        'wrapper' => 'textfields-container',
        'effect' => 'fade',
      ],
    ];
    $form['ask_last_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ask me my last name'),
      '#ajax' => [
        'callback' => '::textfieldsCallback',
        'wrapper' => 'textfields-container',
        'effect' => 'fade',
      ],
    ];

    // Wrap textfields in a container. This container will be replaced through
    // AJAX.
    $form['textfields_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'textfields-container'],
    ];
    $form['textfields_container']['textfields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Generated text fields for first and last name"),
      '#description' => $this->t('This is where we put automatically generated textfields'),
    ];

    // This form is rebuilt on all requests, so whether or not the request comes
    // from AJAX, we should rebuild everything based on the form state.
    // Checkbox values are expressed as 1 or 0, so we have to be sure to compare
    // type as well as value.
    if ($form_state->getValue('ask_first_name', NULL) === 1) {
      $form['textfields_container']['textfields']['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#required' => TRUE,
      ];
    }
    if ($form_state->getValue('ask_last_name', NULL) === 1) {
      $form['textfields_container']['textfields']['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#required' => TRUE,
      ];
    }

    $form['textfields_container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Click Me'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage(
      $this->t('Submit handler: First name: @first_name Last name: @last_name',
        [
          '@first_name' => $form_state->getValue('first_name', 'n/a'),
          '@last_name' => $form_state->getValue('last_name', 'n/a'),
        ]
      )
    );
  }

  /**
   * Callback for ajax_example_autotextfields.
   *
   * Selects the piece of the form we want to use as replacement markup and
   * returns it as a form (renderable array).
   */
  public function textfieldsCallback($form, FormStateInterface $form_state) {
    return $form['textfields_container'];
  }

}
