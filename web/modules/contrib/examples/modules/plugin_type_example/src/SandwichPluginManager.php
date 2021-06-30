<?php

namespace Drupal\plugin_type_example;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plugin_type_example\Annotation\Sandwich;

/**
 * A plugin manager for sandwich plugins.
 *
 * The SandwichPluginManager class extends the DefaultPluginManager to provide
 * a way to manage sandwich plugins. A plugin manager defines a new plugin type
 * and how instances of any plugin of that type will be discovered, instantiated
 * and more.
 *
 * Using the DefaultPluginManager as a starting point sets up our sandwich
 * plugin type to use annotated discovery.
 *
 * The plugin manager is also declared as a service in
 * plugin_type_example.services.yml so that it can be easily accessed and used
 * anytime we need to work with sandwich plugins.
 */
class SandwichPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
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
    // We replace the $subdir parameter with our own value.
    // This tells the plugin manager to look for Sandwich plugins in the
    // 'src/Plugin/Sandwich' subdirectory of any enabled modules. This also
    // serves to define the PSR-4 subnamespace in which sandwich plugins will
    // live. Modules can put a plugin class in their own namespace such as
    // Drupal\{module_name}\Plugin\Sandwich\MySandwichPlugin.
    $subdir = 'Plugin/Sandwich';

    // The name of the interface that plugins should adhere to. Drupal will
    // enforce this as a requirement. If a plugin does not implement this
    // interface, Drupal will throw an error.
    $plugin_interface = SandwichInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = Sandwich::class;

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // This allows the plugin definitions to be altered by an alter hook. The
    // parameter defines the name of the hook, thus: hook_sandwich_info_alter().
    // In this example, we implement this hook to change the plugin definitions:
    // see plugin_type_example_sandwich_info_alter().
    $this->alterInfo('sandwich_info');

    // This sets the caching method for our plugin definitions. Plugin
    // definitions are discovered by examining the $subdir defined above, for
    // any classes with an $plugin_definition_annotation_name. The annotations
    // are read, and then the resulting data is cached using the provided cache
    // backend. For our Sandwich plugin type, we've specified the @cache.default
    // service be used in the plugin_type_example.services.yml file. The second
    // argument is a cache key prefix. Out of the box Drupal with the default
    // cache backend setup will store our plugin definition in the cache_default
    // table using the sandwich_info key. All that is implementation details
    // however, all we care about it that caching for our plugin definition is
    // taken care of by this call.
    $this->setCacheBackend($cache_backend, 'sandwich_info', ['sandwich_info']);
  }

}
