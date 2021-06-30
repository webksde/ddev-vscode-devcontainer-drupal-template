<?php

namespace Drupal\admin_toolbar_tools\Plugin\Menu;

use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu link plugins for configuration entities.
 */
class MenuLinkEntity extends MenuLinkDefault {

  /**
   * The entity represented in the menu link.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a new MenuLinkEntity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->entity = $entity_type_manager->getStorage($this->pluginDefinition['metadata']['entity_type'])->load($this->pluginDefinition['metadata']['entity_id']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($this->entity) {
      return (string) $this->entity->label();
    }
    return $this->pluginDefinition['title'] ?: $this->t('Missing');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // @todo Remove node_type special handling.
    if ($this->entity instanceof EntityDescriptionInterface || $this->entity instanceof NodeTypeInterface) {
      return $this->entity->getDescription();
    }
    return parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    if ($this->entity) {
      return $this->entity->getCacheContexts();
    }
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($this->entity) {
      return $this->entity->getCacheTags();
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if ($this->entity) {
      return $this->entity->getCacheMaxAge();
    }
    return parent::getCacheMaxAge();
  }

}
