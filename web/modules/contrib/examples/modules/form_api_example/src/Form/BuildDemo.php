<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the build demo form controller.
 *
 * This example uses the Messenger service to demonstrate the order of
 * controller method invocations by the form api.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class BuildDemo extends FormBase {

  /**
   * Counter keeping track of the sequence of method invocation.
   *
   * @var int
   */
  protected static $sequenceCounter = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->displayMethodInvocation('__construct');
  }

  /**
   * Update form processing information.
   *
   * Display the method being called and it's sequence in the form
   * processing.
   *
   * @param string $method_name
   *   The method being invoked.
   */
  private function displayMethodInvocation($method_name) {
    self::$sequenceCounter++;
    $this->messenger()->addMessage(self::$sequenceCounter . ". $method_name");
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Demonstrates how submit, rebuild, form-rebuild and #ajax submit work.'),
    ];

    // Simple checkbox for ajax orders.
    $form['change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Change Me'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'message-wrapper',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    // Add button handlers.
    $form['actions']['button'] = [
      '#type' => 'button',
      '#value' => 'Rebuild',
    ];

    $form['actions']['rebuild'] = [
      '#type' => 'submit',
      '#value' => 'Submit Rebuild',
      '#submit' => ['::rebuildFormSubmit'],
    ];

    $form['actions']['ajaxsubmit'] = [
      '#type' => 'submit',
      '#value' => 'Ajax Submit',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'message-wrapper',
      ],
    ];

    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'message-wrapper'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $this->displayMethodInvocation('getFormId');
    return 'form_api_example_build_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->displayMethodInvocation('validateForm');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->displayMethodInvocation('submitForm');
  }

  /**
   * Implements ajax submit callback.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $this->displayMethodInvocation('ajaxSubmit');
    $form['messages']['status'] = [
      '#type' => 'status_messages',
    ];

    return $form['messages'];
  }

  /**
   * Implements submit callback for Rebuild button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   */
  public function rebuildFormSubmit(array &$form, FormStateInterface $form_state) {
    $this->displayMethodInvocation('rebuildFormSubmit');
    $form_state->setRebuild(TRUE);
  }

}
