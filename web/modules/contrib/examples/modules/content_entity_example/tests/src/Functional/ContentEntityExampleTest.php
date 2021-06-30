<?php

namespace Drupal\Tests\content_entity_example\Functional;

use Drupal\content_entity_example\Entity\Contact;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests the basic functions of the Content Entity Example module.
 *
 * @ingroup content_entity_example
 *
 * @group content_entity_example
 * @group examples
 */
class ContentEntityExampleTest extends ExamplesBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_entity_example', 'block', 'field_ui'];

  /**
   * Basic tests for Content Entity Example.
   */
  public function testContentEntityExample() {
    $assert = $this->assertSession();

    $web_user = $this->drupalCreateUser([
      'add contact entity',
      'edit contact entity',
      'view contact entity',
      'delete contact entity',
      'administer contact entity',
      'administer content_entity_example_contact display',
      'administer content_entity_example_contact fields',
      'administer content_entity_example_contact form display',
    ]);

    // Anonymous User should not see the link to the listing.
    $assert->pageTextNotContains('Content Entity Example');

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $assert->linkExists('Content Entity Example');

    $this->clickLink('Content Entity Example');

    // WebUser can add entity content.
    $assert->linkExists('Add contact');

    $this->clickLink($this->t('Add contact'));

    $assert->fieldValueEquals('name[0][value]', '');
    $assert->fieldValueEquals('name[0][value]', '');
    $assert->fieldValueEquals('name[0][value]', '');
    $assert->fieldValueEquals('name[0][value]', '');

    $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
    $assert->fieldValueEquals('user_id[0][target_id]', $user_ref);

    // Post content, save an instance. Go back to list after saving.
    $edit = [
      'name[0][value]' => 'test name',
      'first_name[0][value]' => 'test first name',
      'role' => 'administrator',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Entity listed.
    $assert->linkExists('Edit');
    $assert->linkExists('Delete');

    $this->clickLink('test name');

    // Entity shown.
    $assert->pageTextContains('test name');
    $assert->pageTextContains('test first name');
    $assert->pageTextContains('administrator');
    $assert->linkExists('Add contact');
    $assert->linkExists('Edit');
    $assert->linkExists('Delete');

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $assert->linkExists('Cancel');
    $this->drupalPostForm(NULL, [], 'Delete');

    // Back to list, must be empty.
    $assert->pageTextNotContains('test name');

    // Settings page.
    $this->drupalGet('admin/structure/content_entity_example_contact_settings');
    $assert->pageTextContains('Contact Settings');

    // Make sure the field manipulation links are available.
    $assert->linkExists('Settings');
    $assert->linkExists('Manage fields');
    $assert->linkExists('Manage form display');
    $assert->linkExists('Manage display');
  }

  /**
   * Test all paths exposed by the module, by permission.
   */
  public function testPaths() {
    $assert = $this->assertSession();

    // Generate a contact so that we can test the paths against it.
    $contact = Contact::create([
      'name' => 'somename',
      'first_name' => 'Joe',
      'role' => 'administrator',
    ]);
    $contact->save();

    // Gather the test data.
    $data = $this->providerTestPaths($contact->id());

    // Run the tests.
    foreach ($data as $datum) {
      // drupalCreateUser() doesn't know what to do with an empty permission
      // array, so we help it out.
      if ($datum[2]) {
        $user = $this->drupalCreateUser([$datum[2]]);
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($datum[1]);
      $assert->statusCodeEquals($datum[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $contact_id
   *   The id of an existing Contact entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($contact_id) {
    return [
      [
        200,
        '/content_entity_example_contact/' . $contact_id,
        'view contact entity',
      ],
      [
        403,
        '/content_entity_example_contact/' . $contact_id,
        '',
      ],
      [
        200,
        '/content_entity_example_contact/list',
        'view contact entity',
      ],
      [
        403,
        '/content_entity_example_contact/list',
        '',
      ],
      [
        200,
        '/content_entity_example_contact/add',
        'add contact entity',
      ],
      [
        403,
        '/content_entity_example_contact/add',
        '',
      ],
      [
        200,
        '/content_entity_example_contact/' . $contact_id . '/edit',
        'edit contact entity',
      ],
      [
        403,
        '/content_entity_example_contact/' . $contact_id . '/edit',
        '',
      ],
      [
        200,
        '/contact/' . $contact_id . '/delete',
        'delete contact entity',
      ],
      [
        403,
        '/contact/' . $contact_id . '/delete',
        '',
      ],
      [
        200,
        'admin/structure/content_entity_example_contact_settings',
        'administer contact entity',
      ],
      [
        403,
        'admin/structure/content_entity_example_contact_settings',
        '',
      ],
    ];
  }

  /**
   * Test add new fields to the contact entity.
   */
  public function testAddFields() {
    $web_user = $this->drupalCreateUser([
      'administer contact entity',
      'administer content_entity_example_contact display',
      'administer content_entity_example_contact fields',
      'administer content_entity_example_contact form display',
    ]);

    $this->drupalLogin($web_user);
    $entity_name = 'content_entity_example_contact';
    $add_field_url = 'admin/structure/' . $entity_name . '_settings/fields/add-field';
    $this->drupalGet($add_field_url);
    $field_name = 'test_name';
    $edit = [
      'new_storage_type' => 'list_string',
      'label' => 'test name',
      'field_name' => $field_name,
    ];

    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $expected_path = $this->buildUrl('admin/structure/' . $entity_name . '_settings/fields/' . $entity_name . '.' . $entity_name . '.field_' . $field_name . '/storage');

    // Fetch url without query parameters.
    $current_path = strtok($this->getUrl(), '?');
    $this->assertEquals($expected_path, $current_path);
  }

  /**
   * Ensure admin and permissioned users can create contacts.
   */
  public function testCreateAdminPermission() {
    $assert = $this->assertSession();
    $add_url = Url::fromRoute('content_entity_example.contact_add');

    // Create a Contact entity object so that we can query it for it's annotated
    // properties. We don't need to save it.
    /* @var $contact \Drupal\content_entity_example\Entity\Contact */
    $contact = Contact::create();

    // Create an admin user and log them in. We use the entity annotation for
    // admin_permission in order to validate it. We also have to add the view
    // list permission because the add form redirects to the list on success.
    $this->drupalLogin($this->drupalCreateUser([
      $contact->getEntityType()->getAdminPermission(),
      'view contact entity',
    ]));

    // Post a contact.
    $edit = [
      'name[0][value]' => 'Test Admin Name',
      'first_name[0][value]' => 'Admin First Name',
      'role' => 'administrator',
    ];
    $this->drupalPostForm($add_url, $edit, 'Save');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Test Admin Name');

    // Create a user with 'add contact entity' permission. We also have to add
    // the view list permission because the add form redirects to the list on
    // success.
    $this->drupalLogin($this->drupalCreateUser([
      'add contact entity',
      'view contact entity',
    ]));

    // Post a contact.
    $edit = [
      'name[0][value]' => 'Mere Mortal Name',
      'first_name[0][value]' => 'Mortal First Name',
      'role' => 'user',
    ];
    $this->drupalPostForm($add_url, $edit, 'Save');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Mere Mortal Name');

    // Finally, a user who can only view should not be able to get to the add
    // form.
    $this->drupalLogin($this->drupalCreateUser([
      'view contact entity',
    ]));
    $this->drupalGet($add_url);
    $assert->statusCodeEquals(403);
  }

}
