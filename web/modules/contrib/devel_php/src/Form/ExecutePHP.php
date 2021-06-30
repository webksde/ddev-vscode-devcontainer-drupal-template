<?php

declare(strict_types = 1);

namespace Drupal\devel_php\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that allows privileged users to execute arbitrary PHP code.
 */
class ExecutePHP extends FormBase {

  /**
   * The devel dumper manager service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $develDumper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->develDumper = $container->get('devel.dumper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_execute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $details_open = TRUE) {
    $form['#redirect'] = FALSE;
    $code = (isset($_SESSION['devel_execute_code']) ? $_SESSION['devel_execute_code'] : '');

    $form['execute'] = [
      '#type' => 'details',
      '#title' => $this->t('PHP code to execute'),
      '#open' => (!empty($code) || $details_open),
    ];

    $form['execute']['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('PHP code to execute'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter some code. Do not use <code>&lt;?php ?&gt;</code> tags.'),
      '#default_value' => $code,
      '#rows' => 20,
      '#attributes' => [
        'style' => 'font-family: monospace; font-size: 1.25em;',
      ],
    ];
    $form['execute']['op'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
    ];

    if (isset($_SESSION['devel_execute_code'])) {
      unset($_SESSION['devel_execute_code']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('code');

    try {
      ob_start();
      // phpcs:disable Drupal.Functions.DiscouragedFunctions
      print eval($code);
      // phpcs:enable Drupal.Functions.DiscouragedFunctions
      $this->develDumper->message(ob_get_clean());
    }
    catch (\Throwable $error) {
      $this->messenger()->addError($error->getMessage());
    }

    $_SESSION['devel_execute_code'] = $code;
  }

}
