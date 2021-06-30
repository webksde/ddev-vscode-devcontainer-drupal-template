<?php

namespace Drupal\plugin_type_example\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Sandwich annotation object.
 *
 * Provides an example of how to define a new annotation type for use in
 * defining a plugin type. Demonstrates documenting the various properties that
 * can be used in annotations for plugins of this type.
 *
 * Note that the "@ Annotation" line below is required and should be the last
 * line in the docblock. It's used for discovery of Annotation definitions.
 *
 * @see \Drupal\plugin_type_example\SandwichPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Sandwich extends Plugin {
  /**
   * A brief, human readable, description of the sandwich type.
   *
   * This property is designated as being translatable because it will appear
   * in the user interface. This provides a hint to other developers that they
   * should use the Translation() construct in their annotation when declaring
   * this property.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The number of calories per serving of this sandwich type.
   *
   * This property is a float value, so we indicate that to other developers
   * who are writing annotations for a Sandwich plugin.
   *
   * @var int
   */
  public $calories;

}
