<?php

/**
 * @file
 * Install, update and uninstall functions for the dbtng_example module.
 */

/**
 * Implements hook_install().
 *
 * Creates some default entries on this module custom table.
 *
 * @see hook_install()
 *
 * @ingroup dbtng_example
 */
function dbtng_example_install() {
  // Insert some example data into our schema.
  $entries = [
    [
      'name' => 'John',
      'surname' => 'Doe',
      'age' => 0,
    ],
    [
      'name' => 'John',
      'surname' => 'Roe',
      'age' => 100,
      'uid' => 1,
    ],
  ];

  $connection = \Drupal::database();
  foreach ($entries as $entry) {
    $connection->insert('dbtng_example')->fields($entry)->execute();
  }
}

/**
 * Implements hook_schema().
 *
 * Defines the database tables used by this module.
 *
 * @see hook_schema()
 *
 * @ingroup dbtng_example
 */
function dbtng_example_schema() {
  $schema['dbtng_example'] = [
    'description' => 'Stores example person entries for demonstration purposes.',
    'fields' => [
      'pid' => [
        'type' => 'serial',
        'not null' => TRUE,
        'description' => 'Primary Key: Unique person ID.',
      ],
      'uid' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
        'description' => "Creator user's {users}.uid",
      ],
      'name' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
        'description' => 'Name of the person.',
      ],
      'surname' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
        'description' => 'Surname of the person.',
      ],
      'age' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
        'size' => 'tiny',
        'description' => 'The age of the person in years.',
      ],
    ],
    'primary key' => ['pid'],
    'indexes' => [
      'name' => ['name'],
      'surname' => ['surname'],
      'age' => ['age'],
    ],
  ];

  return $schema;
}
