<?php

namespace Drupal\devel_debug_log\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DevelDebugLogDeleteForm extends FormBase {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * DevelDebugLogDeleteForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }


  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ddl_delete_form';
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ddl_clear'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Clear debug log messages'),
      '#description' => $this->t('This will permanently remove the log messages from the database.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['ddl_clear']['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear log messages'),
    );

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Database::getConnection()->delete('devel_debug_log')
      ->execute();
    $this->messenger->addMessage($this->t('All debug messages have been cleared.'));
  }
}
