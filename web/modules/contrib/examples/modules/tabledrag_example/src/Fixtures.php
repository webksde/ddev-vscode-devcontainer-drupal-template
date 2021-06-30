<?php

namespace Drupal\tabledrag_example;

/**
 * Provides sample data for module's examples.
 */
class Fixtures {

  /**
   * Returns array of sample records for demo purposes.
   *
   * @return array
   *   Array of sample records.
   *
   * @see \Drupal\tabledrag_example\Form\TableDragExampleResetForm::submitForm()
   * @see tabledrag_example_install()
   */
  public static function getSampleItems() {
    return [
      [
        'name' => 'Item One',
        'description' => 'The first item',
        'itemgroup' => 'Group1',
      ],
      [
        'name' => 'Item Two',
        'description' => 'The second item',
        'itemgroup' => 'Group1',
      ],
      [
        'name' => 'Item Three',
        'description' => 'The third item',
        'itemgroup' => 'Group1',
      ],
      [
        'name' => 'Item Four',
        'description' => 'The fourth item',
        'itemgroup' => 'Group2',
      ],
      [
        'name' => 'Item Five',
        'description' => 'The fifth item',
        'itemgroup' => 'Group2',
      ],
      [
        'name' => 'Item Six',
        'description' => 'The sixth item',
        'itemgroup' => 'Group2',
      ],
      [
        'name' => 'Item Seven',
        'description' => 'The seventh item',
        'itemgroup' => 'Group3',
      ],
      [
        'name' => 'Item Eight',
        'description' => 'The eighth item',
        'itemgroup' => 'Group3',
      ],
      [
        'name' => 'Item Nine',
        'description' => 'The ninth item',
        'itemgroup' => 'Group3',
      ],
      [
        'name' => 'Item Ten',
        'description' => 'The tenth item',
        'itemgroup' => 'Group4',
      ],
      [
        'name' => 'Item Eleven — A Root Node',
        'description' => 'This item cannot be nested under a parent item',
        'itemgroup' => 'Group4',
      ],
      [
        'name' => 'Item Twelve — A Leaf Item',
        'description' => 'This item cannot have child items',
        'itemgroup' => 'Group4',
      ],
    ];
  }

}
