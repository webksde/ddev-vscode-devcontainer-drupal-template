<?php

namespace Drupal\plugin_type_example\Plugin\Sandwich;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin_type_example\SandwichBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a meatball sandwich.
 *
 * Because the plugin manager class for our plugins uses annotated class
 * discovery, our meatball sandwich only needs to exist within the
 * Plugin\Sandwich namespace, and provide a Sandwich annotation to be declared
 * as a plugin. This is defined in
 * \Drupal\plugin_type_example\SandwichPluginManager::__construct().
 *
 * The following is the plugin annotation. This is parsed by Doctrine to make
 * the plugin definition. Any values defined here will be available in the
 * plugin definition.
 *
 * This should be used for metadata that is specifically required to instantiate
 * the plugin, or for example data that might be needed to display a list of all
 * available plugins where the user selects one. This means many plugin
 * annotations can be reduced to a plugin ID, a label and perhaps a description.
 *
 * @Sandwich(
 *   id = "meatball_sandwich",
 *   description = @Translation("Italian style meatballs drenched in irresistible marinara sauce, served on freshly baked bread."),
 *   calories = "1200"
 * )
 */
class ExampleMeatballSandwich extends SandwichBase implements ContainerFactoryPluginInterface {

  // Use Drupal\Core\StringTranslation\StringTranslationTrait to define
  // $this->t() for string translations in our plugin.
  use StringTranslationTrait;

  /**
   * The day the sandwich is ordered.
   *
   * Since meatball sandwiches have a special behavior on Sundays, and since we
   * want to test that behavior on days other than Sunday, we have to store the
   * day as a property so we can test it.
   *
   * This is the string representation of the day of the week you get from
   * date('D').
   *
   * @var string
   */
  protected $day;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // This class needs to translate strings, so we need to inject the string
    // translation service from the container. This means our plugin class has
    // to implement ContainerFactoryPluginInterface. This requires that we make
    // this create() method, and use it to inject services from the container.
    // @see https://www.drupal.org/node/2012118
    $sandwich = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
    return $sandwich;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $translation) {
    // Store the translation service.
    $this->setStringTranslation($translation);
    // Store the day so we can generate a special description on Sundays.
    $this->day = date('D');
    // Pass the other parameters up to the parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function order(array $extras) {
    $ingredients = ['meatballs', 'irresistible marinara sauce'];
    $sandwich = array_merge($ingredients, $extras);
    return 'You ordered an ' . implode(', ', $sandwich) . ' sandwich. Enjoy!';
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    // We override the description() method in order to change the description
    // text based on the date. On Sunday we only have day old bread.
    if ($this->day == 'Sun') {
      return $this->t("Italian style meatballs drenched in irresistible marinara sauce, served on day old bread.");
    }
    return parent::description();
  }

}
