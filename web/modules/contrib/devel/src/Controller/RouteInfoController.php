<?php

namespace Drupal\devel\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides route responses for the route info pages.
 */
class RouteInfoController extends ControllerBase {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The router service.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * The dumper service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * RouterInfoController constructor.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $provider
   *   The route provider.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router service.
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The dumper service.
   */
  public function __construct(RouteProviderInterface $provider, RouterInterface $router, DevelDumperManagerInterface $dumper) {
    $this->routeProvider = $provider;
    $this->router = $router;
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('router.no_access_checks'),
      $container->get('devel.dumper')
    );
  }

  /**
   * Builds the routes overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function routeList() {
    $headers = [
      $this->t('Route Name'),
      $this->t('Path'),
      $this->t('Allowed Methods'),
      $this->t('Operations'),
    ];

    $rows = [];

    foreach ($this->routeProvider->getAllRoutes() as $route_name => $route) {
      $row['name'] = [
        'data' => $route_name,
        'filter' => TRUE,
      ];
      $row['path'] = [
        'data' => $route->getPath(),
        'filter' => TRUE,
      ];
      $row['methods']['data'] = [
        '#theme' => 'item_list',
        '#items' => $route->getMethods(),
        '#empty' => $this->t('ANY'),
        '#context' => ['list_style' => 'comma-list'],
      ];

      // We cannot resolve routes with dynamic parameters from route path. For
      // these routes we pass the route name.
      // @see ::routeItem()
      if (strpos($route->getPath(), '{') !== FALSE) {
        $parameters = ['query' => ['route_name' => $route_name]];
      }
      else {
        $parameters = ['query' => ['path' => $route->getPath()]];
      }

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'devel' => [
            'title' => $this->t('Devel'),
            'url' => Url::fromRoute('devel.route_info.item', [], $parameters),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
                'minHeight' => 500,
              ]),
            ],
          ],
        ],
      ];

      $rows[] = $row;
    }

    $output['routes'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter route name or path'),
      '#filter_description' => $this->t('Enter a part of the route name or path to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No routes found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-route-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the route object.
   *
   * The method tries to resolve the route from the 'path' or the 'route_name'
   * query string value if available. If no route is retrieved from the query
   * string parameters it fallbacks to the current route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function routeDetail(Request $request, RouteMatchInterface $route_match) {
    $route = NULL;

    // Get the route object from the path query string if available.
    if ($path = $request->query->get('path')) {
      try {
        $route = $this->router->match($path);
      }
      catch (\Exception $e) {
        $this->messenger()->addWarning($this->t("Unable to load route for url '%url'", ['%url' => $path]));
      }
    }

    // Get the route object from the route name query string if available and
    // the route is not retrieved by path.
    if ($route === NULL && $route_name = $request->query->get('route_name')) {
      try {
        $route = $this->routeProvider->getRouteByName($route_name);
      }
      catch (\Exception $e) {
        $this->messenger()->addWarning($this->t("Unable to load route '%name'", ['%name' => $route_name]));
      }
    }

    // No route retrieved from path or name specified, get the current route.
    if ($route === NULL) {
      $route = $route_match->getRouteObject();
    }

    return $this->dumper->exportAsRenderable($route);
  }

}
