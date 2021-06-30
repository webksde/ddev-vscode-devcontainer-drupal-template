<?php

namespace Drupal\devel_generate;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin type manager for DevelGenerate plugins.
 */
class DevelGeneratePluginManager extends DefaultPluginManager {

  /**
   * Constructs a DevelGeneratePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DevelGenerate', $namespaces, $module_handler, NULL, 'Drupal\devel_generate\Annotation\DevelGenerate');
    $this->alterInfo('devel_generate_info');
    $this->setCacheBackend($cache_backend, 'devel_generate_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = [];
    foreach (parent::findDefinitions() as $plugin_id => $plugin_definition) {
      $plugin_available = TRUE;
      foreach ($plugin_definition['dependencies'] as $module_name) {
        // If a plugin defines module dependencies and at least one module is
        // not installed don't make this plugin available.
        if (!$this->moduleHandler->moduleExists($module_name)) {
          $plugin_available = FALSE;
          break;
        }
      }
      if ($plugin_available) {
        $definitions[$plugin_id] = $plugin_definition;
      }
    }
    return $definitions;
  }

}
