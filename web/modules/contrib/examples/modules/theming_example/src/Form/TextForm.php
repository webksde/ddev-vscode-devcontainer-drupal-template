<?php


namespace Drupal\theming_example\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A simple form that displays a textfield and submit button.
 *
 * This form will be rendered by theme('form') (theme_form() by default)
 * because we do not provide a theme function for it here.
 */
class TextForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'theming_example_form_text';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Please input something!'),
      '#required' => TRUE,
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
      ['%input' => $form_state->getValue('text')]));
  }

}
