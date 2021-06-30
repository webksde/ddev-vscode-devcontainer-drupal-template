<?php

namespace Drupal\menu_example\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * The \Drupal\Core\Routing\RouteSubscriberBase class contains an event
 * listener that listens to this event. We alter existing routes by
 * implementing the alterRoutes(RouteCollection $collection) method of
 * this class.
 *
 * @see https://www.drupal.org/docs/8/api/routing-system/altering-existing-routes-and-adding-new-routes-based-on-dynamic-ones
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Get the path from RouteCollection.
    $route = $collection->get('example.menu_example.path_override');
    // Set the new path.
    $route->setPath('/examples/menu-example/menu-altered-path');
    // Change title to indicate changes.
    $route->setDefault('_title', 'Menu item altered by RouteSubscriber::alterRoutes');
  }

}
