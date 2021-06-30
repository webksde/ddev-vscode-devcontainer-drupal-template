<?php

namespace Drupal\tablesort_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for tablesort example routes.
 */
class TableSortExampleController extends ControllerBase {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static(
      $container->get('database')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * TableSortExampleController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * A simple controller method to explain what the tablesort example is about.
   */
  public function description() {
    // We are going to output the results in a table with a nice header.
    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      ['data' => $this->t('Numbers'), 'field' => 't.numbers'],
      ['data' => $this->t('Letters'), 'field' => 't.alpha'],
      ['data' => $this->t('Mixture'), 'field' => 't.random'],
    ];

    // Using the TableSort Extender is what tells  the query object that we
    // are sorting.
    $query = $this->database->select('tablesort_example', 't')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('t');

    // Don't forget to tell the query object how to find the header information.
    $result = $query
      ->orderByHeader($header)
      ->execute();

    $rows = [];
    foreach ($result as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = ['data' => (array) $row];
    }

    // Build the table for the nice output.
    $build = [
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    ];
    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
