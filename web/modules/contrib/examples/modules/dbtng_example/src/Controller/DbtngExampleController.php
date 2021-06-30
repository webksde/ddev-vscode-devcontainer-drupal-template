<?php

namespace Drupal\dbtng_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dbtng_example\DbtngExampleRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for DBTNG Example.
 *
 * @ingroup dbtng_example
 */
class DbtngExampleController extends ControllerBase {

  /**
   * The repository for our specialized queries.
   *
   * @var \Drupal\dbtng_example\DbtngExampleRepository
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static($container->get('dbtng_example.repository'));
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct a new controller.
   *
   * @param \Drupal\dbtng_example\DbtngExampleRepository $repository
   *   The repository service.
   */
  public function __construct(DbtngExampleRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * Render a list of entries in the database.
   */
  public function entryList() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('Generate a list of all entries in the database. There is no filter in the query.'),
    ];

    $rows = [];
    $headers = [
      $this->t('Id'),
      $this->t('uid'),
      $this->t('Name'),
      $this->t('Surname'),
      $this->t('Age'),
    ];

    $entries = $this->repository->load();

    foreach ($entries as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  /**
   * Render a filtered list of entries in the database.
   */
  public function entryAdvancedList() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('A more complex list of entries in the database. Only the entries with name = "John" and age older than 18 years are shown, the username of the person who created the entry is also shown.'),
    ];

    $headers = [
      $this->t('Id'),
      $this->t('Created by'),
      $this->t('Name'),
      $this->t('Surname'),
      $this->t('Age'),
    ];

    $rows = [];

    $entries = $this->repository->advancedLoad();

    foreach ($entries as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\Html::escape', $entry);
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => ['id' => 'dbtng-example-advanced-list'],
      '#empty' => $this->t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

}
