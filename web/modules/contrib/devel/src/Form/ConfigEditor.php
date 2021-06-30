<?php

namespace Drupal\devel\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Edit config variable form.
 */
class ConfigEditor extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_config_system_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = '') {
    $config = $this->config($config_name);

    if ($config === FALSE || $config->isNew()) {
      $this->messenger()->addError($this->t('Config @name does not exist in the system.', ['@name' => $config_name]));
      return;
    }

    $data = $config->getOriginal();

    if (empty($data)) {
      $this->messenger()->addWarning($this->t('Config @name exists but has no data.', ['@name' => $config_name]));
      return;
    }

    try {
      $output = Yaml::encode($data);
    }
    catch (InvalidDataTypeException $e) {
      $this->messenger()->addError($this->t('Invalid data detected for @name : %error', ['@name' => $config_name, '%error' => $e->getMessage()]));
      return;
    }

    $form['current'] = [
      '#type' => 'details',
      '#title' => $this->t('Current value for %variable', ['%variable' => $config_name]),
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['current']['value'] = [
      '#type' => 'item',
      // phpcs:ignore Drupal.Functions.DiscouragedFunctions
      '#markup' => dpr($output, TRUE),
    ];

    $form['name'] = [
      '#type' => 'value',
      '#value' => $config_name,
    ];
    $form['new'] = [
      '#type' => 'textarea',
      '#title' => $this->t('New value'),
      '#default_value' => $output,
      '#rows' => 24,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->buildCancelLinkUrl(),
    ];
    $form['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => Url::fromRoute('devel.config_delete', ['config_name' => $config_name]),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('new');
    // Try to parse the new provided value.
    try {
      $parsed_value = Yaml::decode($value);
      // Config::setData needs array for the new configuration and
      // a simple string is valid YAML for any reason.
      if (is_array($parsed_value)) {
        $form_state->setValue('parsed_value', $parsed_value);
      }
      else {
        $form_state->setErrorByName('new', $this->t('Invalid input'));
      }
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('new', $this->t('Invalid input: %error', ['%error' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    try {
      $this->configFactory()->getEditable($values['name'])->setData($values['parsed_value'])->save();
      $this->messenger()->addMessage($this->t('Configuration variable %variable was successfully saved.', ['%variable' => $values['name']]));
      $this->logger('devel')->info('Configuration variable %variable was successfully saved.', ['%variable' => $values['name']]);

      $form_state->setRedirectUrl(Url::fromRoute('devel.configs_list'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      $this->logger('devel')->error('Error saving configuration variable %variable : %error.', ['%variable' => $values['name'], '%error' => $e->getMessage()]);
    }
  }

  /**
   * Builds the cancel link url for the form.
   *
   * @return \Drupal\Core\Url
   *   Cancel url
   */
  private function buildCancelLinkUrl() {
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
    }
    else {
      $url = Url::fromRoute('devel.configs_list');
    }

    return $url;
  }

}
