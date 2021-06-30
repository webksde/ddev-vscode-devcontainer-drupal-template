<?php

namespace Drupal\tabledrag_example\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tabledrag_example\Fixtures;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Table drag example reset form.
 *
 * @package Drupal\tabledrag_example\Form
 */
class TableDragExampleResetForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Construct a form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tabledrag_example_reset';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Reset demo data for TableDrag Example');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('tabledrag_example.description');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to reset demo data?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, Reset It!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = Fixtures::getSampleItems();
    foreach ($data as $id => $item) {
      // Add 1 to each array key to match ID.
      $id++;
      $this->database->update('tabledrag_example')
        ->fields([
          'weight' => 0,
          'pid' => 0,
          'description' => $item['description'],
          'itemgroup' => $item['itemgroup'],
        ])
        ->condition('id', $id, '=')
        ->execute();
    }
    $this->messenger()->addMessage($this->t('Data for TableDrag Example has been reset.'), 'status');
    $form_state->setRedirect('tabledrag_example.description');
  }

}
