<?php

namespace Drupal\admin_toolbar_tools;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Admin Toolbar Tools helper service.
 */
class AdminToolbarToolsHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create an AdminToolbarToolsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets a list of content entities.
   *
   * @return array
   *   An array of metadata about content entities.
   */
  public function getBundleableEntitiesList() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entities = [];
    foreach ($entity_types as $key => $entity_type) {
      if ($entity_type->getBundleEntityType() && ($entity_type->get('field_ui_base_route') != '')) {
        $content_entities[$key] = [
          'content_entity' => $key,
          'content_entity_bundle' => $entity_type->getBundleEntityType(),
        ];
      }
    }
    return $content_entities;
  }

  /**
   * Gets an array of entity types that should trigger a menu rebuild.
   *
   * @return array
   *   An array of entity machine names.
   */
  public function getRebuildEntityTypes() {
    $types = ['menu'];
    $content_entities = $this->getBundleableEntitiesList();
    $types = array_merge($types, array_column($content_entities, 'content_entity_bundle'));
    return $types;
  }

}
