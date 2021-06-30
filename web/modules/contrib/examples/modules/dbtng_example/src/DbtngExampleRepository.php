<?php

namespace Drupal\dbtng_example;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database-related helper methods for our example.
 *
 * This repository is a service named 'dbtng_example.repository'. You can see
 * how the service is defined in dbtng_example/dbtng_example.services.yml.
 *
 * For projects where there are many specialized queries, it can be useful to
 * group them into 'repositories' of queries. We can also architect this
 * repository to be a service, so that it gathers the database connections it
 * needs. This way other classes which use the repository don't need to concern
 * themselves with database connections, only with business logic.
 *
 * This repository demonstrates basic CRUD behaviors, and also has an advanced
 * query which performs a join with the user table.
 *
 * @ingroup dbtng_example
 */
class DbtngExampleRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Save an entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('dbtng_example')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('dbtng_example')
        ->fields($entry)
        ->condition('pid', $entry['pid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]
      ), 'error');
    }
    return $count ?? 0;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
    $this->connection->delete('dbtng_example')
      ->condition('pid', $entry['pid'])
      ->execute();
  }

  /**
   * Read from the database using a filter array.
   *
   * The standard function to perform reads for static queries is
   * Connection::query().
   *
   * Connection::query() uses an SQL query with placeholders and arguments as
   * parameters.
   *
   * Drupal DBTNG provides an abstracted interface that will work with a wide
   * variety of database engines.
   *
   * The following is a query which uses a string literal SQL query. The
   * placeholders will be substituted with the values in the array. Placeholders
   * are marked with a colon ':'. Table names are marked with braces, so that
   * Drupal's' multisite feature can add prefixes as needed.
   *
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   \Drupal::database()->query(
   *     "SELECT * FROM {dbtng_example} WHERE uid = :uid and name = :name",
   *     [':uid' => 0, ':name' => 'John']
   *   )->execute();
   * @endcode
   *
   * For more dynamic queries, Drupal provides Connection::select() API method,
   * so there are several ways to perform the same SQL query. See the
   * @link http://drupal.org/node/310075 handbook page on dynamic queries. @endlink
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->condition('uid', 0)
   *     ->condition('name', 'John')
   *     ->execute();
   * @endcode
   *
   * Here is select() with named placeholders:
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   $arguments = array(':name' => 'John', ':uid' => 0);
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->where('uid = :uid AND name = :name', $arguments)
   *     ->execute();
   * @endcode
   *
   * Conditions are stacked and evaluated as AND and OR depending on the type of
   * query. For more information, read the conditional queries handbook page at:
   * http://drupal.org/node/310086
   *
   * The condition argument is an 'equal' evaluation by default, but this can be
   * altered:
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE age > 18
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->condition('age', 18, '>')
   *     ->execute();
   * @endcode
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function load(array $entry = []) {
    // Read all the fields from the dbtng_example table.
    $select = $this->connection
      ->select('dbtng_example')
      // Add all the fields into our select query.
      ->fields('dbtng_example');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Load dbtng_example records joined with user records.
   *
   * DBTNG also helps processing queries that return several rows, providing the
   * found objects in the same query execution call.
   *
   * This function queries the database using a JOIN between users table and the
   * example entries, to provide the username that created the entry, and
   * creates a table with the results, processing each row.
   *
   * SELECT
   *  e.pid as pid, e.name as name, e.surname as surname, e.age as age
   *  u.name as username
   * FROM
   *  {dbtng_example} e
   * JOIN
   *  users u ON e.uid = u.uid
   * WHERE
   *  e.name = 'John' AND e.age > 18
   *
   * @see Drupal\Core\Database\Connection::select()
   * @see http://drupal.org/node/310075
   */
  public function advancedLoad() {
    // Get a select query for our dbtng_example table. We supply an alias of e
    // (for 'example').
    $select = $this->connection->select('dbtng_example', 'e');
    // Join the users table, so we can get the entry creator's username.
    $select->join('users_field_data', 'u', 'e.uid = u.uid');
    // Select these specific fields for the output.
    $select->addField('e', 'pid');
    $select->addField('u', 'name', 'username');
    $select->addField('e', 'name');
    $select->addField('e', 'surname');
    $select->addField('e', 'age');
    // Filter only persons named "John".
    $select->condition('e.name', 'John');
    // Filter only persons older than 18 years.
    $select->condition('e.age', 18, '>');
    // Make sure we only get items 0-49, for scalability reasons.
    $select->range(0, 50);

    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $entries;
  }

}
