<?php

namespace Drupal\Tests\dbtng_example\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel testing of the DbtngExampleRepository service.
 *
 * @coversDefaultClass \Drupal\dbtng_example\DbtngExampleRepository
 *
 * @group dbtng_example
 * @group examples
 *
 * @ingroup dbtng_example
 */
class DbtngExampleRepositoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dbtng_example'];

  /**
   * {@inheritdoc}
   *
   * Kernel tests do not invoke hook_schema() or hook_install(). Therefore we
   * have to do it if our tests expect them to have been run.
   */
  protected function setUp() {
    parent::setUp();
    // Install the schema we defined in hook_schema().
    $this->installSchema('dbtng_example', 'dbtng_example');
    // Inovke hook_install().
    $this->container->get('module_handler')->invoke('dbtng_example', 'install');
  }

  /**
   * Tests several combinations, adding entries, updating and deleting.
   */
  public function testDbtngExampleStorage() {
    /* @var $repository \Drupal\dbtng_example\DbtngExampleRepository */
    $repository = $this->container->get('dbtng_example.repository');
    // Create a new entry.
    $entry = [
      'name' => 'James',
      'surname' => 'Doe',
      'age' => 23,
    ];
    $repository->insert($entry);

    // Save another entry.
    $entry = [
      'name' => 'Jane',
      'surname' => 'NotDoe',
      'age' => 19,
    ];
    $repository->insert($entry);

    // Verify that 4 records are found in the database.
    $result = $repository->load();
    $this->assertCount(4, $result);

    // Verify 2 of these records have 'Doe' as surname.
    $result = $repository->load(['surname' => 'Doe']);
    $this->assertCount(2, $result, 'Did not find two entries in the table with surname = "Doe".');

    // Now find our not-Doe entry.
    $result = $repository->load(['surname' => 'NotDoe']);
    // Found one entry in the table with surname "NotDoe'.
    $this->assertCount(1, $result, 'Did not find one entry in the table with surname "NotDoe');
    // Our NotDoe will be changed to "NowDoe".
    $entry = $result[0];
    $entry->surname = "NowDoe";
    // update() returns the number of entries updated.
    $this->assertNotEquals(0, $repository->update((array) $entry));

    $result = $repository->load(['surname' => 'NowDoe']);
    $this->assertCount(1, $result, "Did not find renamed 'NowDoe' surname.");

    // Read only John Doe entry.
    $result = $repository->load(['name' => 'John', 'surname' => 'Doe']);
    $this->assertCount(1, $result, 'Did not find one entry for John Doe.');

    // Get the entry.
    $entry = (array) end($result);
    // Change age to 45.
    $entry['age'] = 45;
    // Update entry in database.
    $repository->update((array) $entry);

    // Find entries with age = 45.
    // Read only John Doe entry.
    $result = $repository->load(['surname' => 'NowDoe']);
    // Found one entry with surname = Nowdoe.
    $this->assertCount(1, $result, 'Did not find one entry with surname = Nowdoe.');

    // Verify it is Jane NowDoe.
    $entry = (array) end($result);
    // The name Jane is found in the entry.
    $this->assertEquals('Jane', $entry['name'], 'The name Jane is not found in the entry.');
    // The surname NowDoe is found in the entry.
    $this->assertEquals('NowDoe', $entry['surname'], 'The surname NowDoe is not found in the entry.');

    // Delete the entry.
    $repository->delete($entry);

    // Verify that now there are only 3 records.
    $result = $repository->load();
    // Found only three records, a record was deleted.
    $this->assertCount(3, $result, 'Did not find only three records, a record might not have been deleted.');
  }

}
