<?php

namespace Drupal\webprofiler\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\webprofiler\Entity\Decorators\Config\ConfigEntityStorageDecorator;
use Drupal\webprofiler\Entity\Decorators\Config\ImageStyleStorageDecorator;
use Drupal\webprofiler\Entity\Decorators\Config\DomainStorageDecorator;
use Drupal\webprofiler\Entity\Decorators\Config\RoleStorageDecorator;
use Drupal\webprofiler\Entity\Decorators\Config\ShortcutSetStorageDecorator;
use Drupal\webprofiler\Entity\Decorators\Config\VocabularyStorageDecorator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityManagerWrapper.
 */
class EntityManagerWrapper extends DefaultPluginManager implements EntityTypeManagerInterface, ContainerAwareInterface {

  /**
   * @var array
   */
  private $loaded;

  /**
   * @var array
   */
  private $rendered;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $handler */
    $handler = $this->getHandler($entity_type, 'storage');
    $type = ($handler instanceof ConfigEntityStorageInterface) ? 'config' : 'content';

    if (!isset($this->loaded[$type][$entity_type])) {
      $handler = $this->getStorageDecorator($entity_type, $handler);
      $this->loaded[$type][$entity_type] = $handler;
    }
    else {
      $handler = $this->loaded[$type][$entity_type];
    }

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type) {
    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $handler */
    $handler = $this->getHandler($entity_type, 'view_builder');

    if ($handler instanceof EntityViewBuilderInterface) {
      if (!isset($this->rendered[$entity_type])) {
        $handler = new EntityViewBuilderDecorator($handler);
        $this->rendered[$entity_type] = $handler;
      }
      else {
        $handler = $this->rendered[$entity_type];
      }
    }

    return $handler;
  }

  /**
   * @param $entity_type
   * @param $handler
   *
   * @return \Drupal\webprofiler\Entity\EntityDecorator
   */
  private function getStorageDecorator($entity_type, $handler) {
    if ($handler instanceof ConfigEntityStorageInterface) {
      switch ($entity_type) {
        // Do not need a 'break' statement after each case-breaking 'return'.
        case 'taxonomy_vocabulary':
          return new VocabularyStorageDecorator($handler);

        case 'user_role':
          return new RoleStorageDecorator($handler);

        case 'shortcut_set':
          return new ShortcutSetStorageDecorator($handler);

        case 'image_style':
          return new ImageStyleStorageDecorator($handler);

        case 'domain':
          return new DomainStorageDecorator($handler);

        default:
          return new ConfigEntityStorageDecorator($handler);
      }
    }
    return $handler;
  }

  /**
   * @param $type
   * @param $entity_type
   *
   * @return array
   */
  public function getLoaded($type, $entity_type) {
    return isset($this->loaded[$type][$entity_type]) ? $this->loaded[$type][$entity_type] : NULL;
  }

  /**
   * @param $entity_type
   *
   * @return array
   */
  public function getRendered($entity_type) {
    return isset($this->rendered[$entity_type]) ? $this->rendered[$entity_type] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->entityTypeManager->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->entityTypeManager->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($entity_type) {
    return $this->entityTypeManager->getAccessControlHandler($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions();
    $this->loaded = NULL;
    $this->rendered = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type) {
    return $this->entityTypeManager->getListBuilder($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type, $operation) {
    return $this->entityTypeManager->getFormObject($entity_type, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviders($entity_type) {
    return $this->entityTypeManager->getRouteProviders($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandler($entity_type, $handler_type) {
    return $this->entityTypeManager->hasHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type, $handler_type) {
    return $this->entityTypeManager->getHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance(
    $class,
    EntityTypeInterface $definition = NULL
  ) {
    return $this->entityTypeManager->createHandlerInstance($class, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    return $this->entityTypeManager->getDefinition(
      $entity_type_id,
      $exception_on_invalid
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->entityTypeManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->entityTypeManager->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->entityTypeManager->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->entityTypeManager->setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDefinition($entity_type_id) {
    return $this->entityTypeManager->getActiveDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveFieldStorageDefinitions($entity_type_id) {
    return $this->entityTypeManager->getActiveFieldStorageDefinitions($entity_type_id);
  }

}
