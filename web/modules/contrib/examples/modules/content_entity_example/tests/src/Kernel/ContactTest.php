<?php

namespace Drupal\Tests\content_entity_example\Kernel;

use Drupal\content_entity_example\Entity\Contact;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test basic CRUD operations for our Contact entity type.
 *
 * @group content_entity_example
 * @group examples
 *
 * @ingroup content_entity_example
 */
class ContactTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_entity_example', 'options', 'user'];

  /**
   * Basic CRUD operations on a Contact entity.
   */
  public function testEntity() {
    $this->installEntitySchema('content_entity_example_contact');
    $entity = Contact::create([
      'name' => 'Name',
      'first_name' => 'Firstname',
      'user_id' => 0,
      'role' => 'user',
    ]);
    $this->assertNotNull($entity);
    $this->assertEquals(SAVED_NEW, $entity->save());
    $this->assertEquals(SAVED_UPDATED, $entity->set('role', 'administrator')->save());
    $entity_id = $entity->id();
    $this->assertNotEmpty($entity_id);
    $entity->delete();
    $this->assertNull(Contact::load($entity_id));
  }

}
