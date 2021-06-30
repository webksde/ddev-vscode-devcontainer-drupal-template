<?php


namespace Drupal\theming_example\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A simple form that displays a select box and submit button.
 *
 * This form will be be themed by the 'theming_example_select_form' theme
 * handler.
 */
class SelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'theming_example_form_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [
      'newest_first' => $this->t('Newest first'),
      'newest_last' => $this->t('Newest last'),
      'edited_first' => $this->t('Edited first'),
      'edited_last' => $this->t('Edited last'),
      'by_name' => $this->t('By name'),
    ];
    $form['choice'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Choose which ordering you want'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('You chose %input',
      ['%input' => $form_state->getValue('choice')]));
  }

}
