<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests Devel controller.
 *
 * @group devel
 */
class DevelControllerTest extends DevelBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'devel',
    'node',
    'entity_test',
    'devel_entity_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Create a test entity.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'entity_test', 'name' => $random_label];
    $this->entity = $entity_type_manager->getStorage('entity_test')->create($data);
    $this->entity->save();

    // Create a test entity with only canonical route.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_canonical', 'name' => $random_label];
    $this->entity_canonical = $entity_type_manager->getStorage('devel_entity_test_canonical')->create($data);
    $this->entity_canonical->save();

    // Create a test entity with only edit route.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_edit', 'name' => $random_label];
    $this->entity_edit = $entity_type_manager->getStorage('devel_entity_test_edit')->create($data);
    $this->entity_edit->save();

    // Create a test entity with no routes.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_no_links', 'name' => $random_label];
    $this->entity_no_links = $entity_type_manager->getStorage('devel_entity_test_no_links')->create($data);
    $this->entity_no_links->save();

    $this->drupalPlaceBlock('local_tasks_block');

    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'access devel information',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests route generation.
   */
  public function testRouteGeneration() {
    // Test Devel load and render routes for entities with both route
    // definitions.
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('View');
    $this->assertSession()->LinkExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    $this->drupalGet('devel/entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkExists('Render');
    $this->assertSession()->LinkExists('Load');
    $this->assertSession()->linkByHrefExists('devel/entity_test/' . $this->entity->id() . '/render');
    $this->drupalGet('devel/entity_test/' . $this->entity->id() . '/render');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('devel/entity_test/' . $this->entity->id() . '/definition');
    $this->drupalGet('devel/entity_test/' . $this->entity->id() . '/definition');
    $this->assertSession()->statusCodeEquals(200);

    // Test Devel load and render routes for entities with only canonical route
    // definitions.
    $this->drupalGet('devel_entity_test_canonical/' . $this->entity_canonical->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('View');
    $this->assertSession()->LinkNotExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    // Use xpath with equality check on @data-drupal-link-system-path because
    // assertNoLinkByHref matches on partial values and finds the other link.
    $this->assertSession()->elementNotExists('xpath',
      '//a[@data-drupal-link-system-path = "devel/devel_entity_test_canonical/' . $this->entity_canonical->id() . '"]');
    $this->assertSession()->elementExists('xpath',
      '//a[@data-drupal-link-system-path = "devel/devel_entity_test_canonical/' . $this->entity_canonical->id() . '/render"]');
    $this->drupalGet('devel/devel_entity_test_canonical/' . $this->entity_canonical->id());
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/devel_entity_test_canonical/' . $this->entity_canonical->id() . '/render');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkExists('Render');
    $this->assertSession()->LinkNotExists('Load');
    $this->assertSession()->linkByHrefExists('devel/devel_entity_test_canonical/' . $this->entity_canonical->id() . '/definition');
    $this->drupalGet('devel/devel_entity_test_canonical/' . $this->entity_canonical->id() . '/definition');
    $this->assertSession()->statusCodeEquals(200);

    // Test Devel load and render routes for entities with only edit route
    // definitions.
    $this->drupalGet('devel_entity_test_edit/manage/' . $this->entity_edit->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkNotExists('View');
    $this->assertSession()->LinkExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    $this->assertSession()->linkByHrefExists('devel/devel_entity_test_edit/' . $this->entity_edit->id());
    $this->drupalGet('devel/devel_entity_test_edit/' . $this->entity_edit->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkNotExists('Render');
    $this->assertSession()->LinkExists('Load');
    $this->assertSession()->linkByHrefExists('devel/devel_entity_test_edit/' . $this->entity_edit->id() . '/definition');
    $this->assertSession()->linkByHrefNotExists('devel/devel_entity_test_edit/' . $this->entity_edit->id() . '/render');
    $this->drupalGet('devel/devel_entity_test_edit/' . $this->entity_edit->id() . '/definition');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('devel/devel_entity_test_edit/' . $this->entity_edit->id() . '/render');
    $this->assertSession()->statusCodeEquals(404);

    // Test Devel load and render routes for entities with no route
    // definitions.
    $this->drupalGet('devel_entity_test_no_links/' . $this->entity_edit->id());
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/devel_entity_test_no_links/' . $this->entity_no_links->id());
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/devel_entity_test_no_links/' . $this->entity_no_links->id() . '/render');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/devel_entity_test_no_links/' . $this->entity_no_links->id() . '/definition');
    $this->assertSession()->statusCodeEquals(404);
  }

}
