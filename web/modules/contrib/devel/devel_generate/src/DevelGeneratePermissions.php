<?php

namespace Drupal\devel_generate;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class DevelGeneratePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The plugin manager.
   *
   * @var \Drupal\devel_generate\DevelGeneratePluginManager
   */
  protected $develGeneratePluginManager;

  /**
   * Constructs a new DevelGeneratePermissions instance.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $develGeneratePluginManager
   *   The plugin manager.
   */
  public function __construct(DevelGeneratePluginManager $develGeneratePluginManager) {
    $this->develGeneratePluginManager = $develGeneratePluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.develgenerate'));
  }

  /**
   * A permissions callback.
   *
   * @see devel_generate.permissions.yml
   *
   * @return array
   *   An array of permissions for all plugins.
   */
  public function permissions() {
    $devel_generate_plugins = $this->develGeneratePluginManager->getDefinitions();
    foreach ($devel_generate_plugins as $plugin) {

      $permission = $plugin['permission'];
      $permissions[$permission] = [
        'title' => $this->t('@permission', ['@permission' => $permission]),
      ];
    }

    return $permissions;
  }

}
