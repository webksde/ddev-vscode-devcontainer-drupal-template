<?php

namespace Drupal\admin_toolbar_search\Controller;

use Drupal\admin_toolbar_search\SearchLinks;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AdminToolbarSearchController.
 *
 * @package Drupal\admin_toolbar_tools\Controller
 */
class AdminToolbarSearchController extends ControllerBase {

  /**
   * The search links service.
   *
   * @var \Drupal\admin_toolbar_search\SearchLinks
   */
  protected $links;

  /**
   * Constructs an AdminToolbarSearchController object.
   *
   * @param \Drupal\admin_toolbar_search\SearchLinks $links
   *   The search links service.
   */
  public function __construct(SearchLinks $links) {
    $this->links = $links;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('admin_toolbar_search.search_links')
    );
  }

  /**
   * Return additional search links.
   */
  public function search() {
    return new JsonResponse($this->links->getLinks());
  }

}
