<?php

/**
 * @file
 * Hooks specific to the plugin_type_example module.
 */

/**
 * Alter the definitions of all the Sandwich plugins.
 *
 * You can implement this hook to do things like change the properties for each
 * plugin or change the implementing class for a plugin.
 *
 * This hook is invoked by SandwichPluginManager::__construct().
 *
 * @param array $sandwich_plugin_info
 *   This is the array of plugin definitions.
 */
function hook_sandwich_info_alter(array &$sandwich_plugin_info) {
  // Let's change the 'foobar' property for all sandwiches.
  foreach ($sandwich_plugin_info as $plugin_id => $plugin_definition) {
    $sandwich_plugin_info[$plugin_id]['foobar'] = t('We have altered this in the alter hook');
  }
}
