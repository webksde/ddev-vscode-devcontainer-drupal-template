<?php

namespace Drupal\rest_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Setup form for a Drupal REST client.
 *
 * Here you configure what the other Drupal installation.
 *
 * @ingroup rest_example
 */
class RestExampleClientSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_example_client_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#markup' => t('Set here the remote server options.'),
    ];
    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote server URL'),
      '#description' => $this->t('Format like this: http://www.example.com or http://www.example.com/test-site (no trailing slash)'),
      '#default_value' => $this->config('rest_example.settings')->get('server_url'),
      '#required' => TRUE,
    ];
    $form['server_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote server username'),
      '#default_value' => $this->config('rest_example.settings')->get('server_username'),
      '#description' => $this->t('A user on the remote system that has the proper rights to use the REST service'),
      '#required' => TRUE,
    ];
    $form['server_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote server password'),
      '#default_value' => $this->config('rest_example.settings')->get('server_password'),
      '#description' => $this->t('Remote users password'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();

    $this->config('rest_example.settings')
      ->set('server_url', $form_values['server_url'])
      ->set('server_username', $form_values['server_username'])
      ->set('server_password', $form_values['server_password'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'rest_example.settings',
    ];
  }

}
