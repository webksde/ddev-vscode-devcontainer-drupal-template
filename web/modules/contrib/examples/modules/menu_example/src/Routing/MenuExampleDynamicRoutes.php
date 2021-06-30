<?php

namespace Drupal\menu_example\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes for our tab menu items.
 *
 * These routes support the links created in menu_example.links.task.yml.
 *
 * @see menu_example.links.task.yml
 * @see https://www.drupal.org/docs/8/api/routing-system/providing-dynamic-routes
 */
class MenuExampleDynamicRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    $tabs = [
      'tabs' => 'Default primary tab',
      'tabs/second' => 'Second',
      'tabs/third' => 'Third',
      'tabs/fourth' => 'Fourth',
      'tabs/default/second' => 'Second',
      'tabs/default/third' => 'Third',
    ];

    foreach ($tabs as $path => $title) {
      $machine_name = 'examples.menu_example.' . str_replace('/', '_', $path);
      $routes[$machine_name] = new Route(
        // Path to attach this route to:
        '/examples/menu-example/' . $path,
        // Route defaults:
        [
          '_controller' => '\Drupal\menu_example\Controller\MenuExampleController::tabsPage',
          '_title' => $title,
          'path' => $path,
          'title' => $title,
        ],
        // Route requirements:
        [
          '_access' => 'TRUE',
        ]
      );
    }

    return $routes;
  }

}
