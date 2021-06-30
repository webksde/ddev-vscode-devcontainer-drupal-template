<?php

namespace Drupal\Tests\field_permission_example\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests our sticky-note field type.
 *
 * This class is based off the tests used in Drupal core for field plugins,
 * since we need to use some of the same convenience methods for testing
 * our custom field type. This base class also brings in the new
 * PHPUnit-based kernel test system that replaces the older Simpletest-
 * based classes.
 *
 * @see \Drupal\KernelTests\KernelTestBase
 *
 * @group field_permission_example
 * @group examples
 */
class FieldNoteItemTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * We add the additional modules we need loaded here.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. So, it is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var array
   */
  public static $modules = ['field_permission_example'];

  /**
   * {@inheritdoc}
   *
   * This sets up the entity_test and user types to use our example
   * field plugins.
   */
  protected function setUp() {
    parent::setUp();
    $type_manager = $this->container->get('entity_type.manager');

    // Set up our entity_type and user type for our new field:
    $type_manager
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => 'field_fieldnote',
        'entity_type' => 'entity_test',
        'type' => 'field_permission_example_fieldnote',
      ])->save();

    $type_manager
      ->getStorage('field_config')
      ->create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_fieldnote',
        'bundle' => 'entity_test',
      ])->save();

    // Create a form display for the default form mode, and
    // add our field type.
    $type_manager
      ->getStorage('entity_form_display')
      ->create([
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
        'mode' => 'default',
        'status' => TRUE,
      ])
      ->setComponent('field_fieldnote', [
        'type' => 'field_permission_example_widget',
      ])
      ->save();

    // Now do this for the user type.
    $type_manager
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => 'user_fieldnote',
        'entity_type' => 'user',
        'type' => 'field_permission_example_fieldnote',
      ])->save();

    $type_manager
      ->getStorage('field_config')
      ->create([
        'entity_type' => 'user',
        'field_name' => 'user_fieldnote',
        'bundle' => 'user',
      ])->save();

    // Fetch a form display for a user. This may already exist, so check as
    // Core does.
    // @see https://api.drupal.org/api/drupal/core%21includes%21entity.inc/function/entity_get_form_display/8
    $entity_form_display
      = $type_manager
        ->getStorage('entity_form_display')
        ->load('user.user.default');
    if (empty($entity_form_display)) {
      $entity_form_display
        = $type_manager
          ->getStorage('entity_form_display')
          ->create([
            'targetEntityType' => 'user',
            'bundle' => 'user',
            'mode' => 'default',
            'status' => TRUE,
          ]);
    }
    // And add our fancy field to that display:
    $entity_form_display->setComponent('field_fieldnote', [
      'type' => 'field_permission_example_widget',
    ])->save();

  }

  /**
   * Test entity fields of the field_permission_example_fieldnote field type.
   */
  public function testFieldNoteItem() {
    // Verify entity creation.
    $type_manager = $this->container->get('entity_type.manager');
    $entity
      = $type_manager
        ->getStorage('entity_test')
        ->create([]);
    $value = 'This is an epic entity';
    $entity->field_fieldnote = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity
      = $type_manager
        ->getStorage('entity_test')
        ->load($id);

    $this->assertTrue($entity->field_fieldnote instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_fieldnote[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_fieldnote->value, $value);
    $this->assertEqual($entity->field_fieldnote[0]->value, $value);

    // Verify changing the field's value.
    $new_value = $this->randomMachineName();
    $entity->field_fieldnote->value = $new_value;
    $this->assertEqual($entity->field_fieldnote->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();

    $entity
      = $type_manager
        ->getStorage('entity_test')
        ->load($id);

    $this->assertEqual($entity->field_fieldnote->value, $new_value);

    // Test sample item generation.
    $entity
      = $type_manager
        ->getStorage('entity_test')
        ->create([]);

    $entity->field_fieldnote->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Test multiple access scenarios for the fieldnote field.
   */
  public function testFieldNoteAccess() {

    // Let's set up some scenarios.
    $scenarios = [
      'admin_type' => [
        'perms' => ['administer the fieldnote field'],
        'can_view_any' => TRUE,
        'can_edit_any' => TRUE,
        'can_view_own' => TRUE,
        'can_edit_own' => TRUE,
      ],
      'low_access' => [
        'perms' => ['view test entity'],
        'can_view_any' => FALSE,
        'can_edit_any' => FALSE,
        'can_view_own' => FALSE,
        'can_edit_own' => FALSE,
      ],
      'view_any' => [
        'perms' => [
          'view test entity',
          'view any fieldnote',
        ],
        'can_view_any' => TRUE,
        'can_edit_any' => FALSE,
        'can_view_own' => FALSE,
        'can_edit_own' => FALSE,
      ],
      'edit_any' => [
        'perms' => [
          'view test entity',
          'view any fieldnote',
          'edit any fieldnote',
        ],
        'can_view_any' => TRUE,
        'can_edit_any' => TRUE,
        'can_view_own' => FALSE,
        'can_edit_own' => FALSE,
      ],
      'view_own' => [
        'perms' => [
          'view test entity',
          'view own fieldnote',
        ],
        'can_view_any' => FALSE,
        'can_edit_any' => FALSE,
        'can_view_own' => TRUE,
        'can_edit_own' => FALSE,
      ],
      'edit_own' => [
        'perms' => [
          'view test entity',
          'view own fieldnote',
          'edit own fieldnote',
        ],
        'can_view_any' => FALSE,
        'can_edit_any' => FALSE,
        'can_view_own' => TRUE,
        'can_edit_own' => TRUE,
      ],
    ];

    $value = 'This is an epic entity';
    // We also need to test users as an entity to attach to.  They work
    // a little differently than most content entity types:
    $arbitrary_user = $this->createUser([], 'Some User');
    $arbitrary_user->user_fieldnote = $value;
    $arbitrary_user->save();

    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    foreach ($scenarios as $name => $scenario) {
      $test_user = $this->createUser($scenario['perms'], $name);
      $entity = $storage->create(['entity_test']);
      $entity->field_fieldnote = $value;
      $entity->name->value = $this->randomMachineName();
      $entity->save();

      foreach (['can_view_any', 'can_edit_any'] as $op) {
        $this->doAccessAssertion($entity, 'field_fieldnote', $test_user, $name, $op, $scenario[$op]);
        $this->doAccessAssertion($arbitrary_user, 'user_fieldnote', $test_user, $name, $op, $scenario[$op]);
      }

      if ($scenario['can_view_own'] or $scenario['can_edit_own']) {
        $entity->user_id = $test_user;
        $entity->save();
        $test_user->user_fieldnote = $value;
        $test_user->save();

        foreach (['can_view_own', 'can_edit_own'] as $op) {
          $this->doAccessAssertion($entity, 'field_fieldnote', $test_user, $name, $op, $scenario[$op]);
          $this->doAccessAssertion($test_user, 'user_fieldnote', $test_user, $name, $op, $scenario[$op]);
        }
      }
    }

  }

  /**
   * Helper routine to run the assertions.
   */
  protected function doAccessAssertion($entity, $field_name, $account, $name, $op, $expected) {
    $expect_str = $expected ? "CAN" : "CANNOT";
    $assert_str = "$name $expect_str do $op on field $field_name";
    $operation = preg_match('/edit/', $op) ? "edit" : "view";
    $result = $entity->$field_name->access($operation, $account);
    if ($expected) {
      $this->assertTrue($result, $assert_str);
    }
    else {
      $this->assertFalse($result, $assert_str);
    }
  }

}
