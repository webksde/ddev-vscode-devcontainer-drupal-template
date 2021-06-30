<?php

namespace Drupal\pager_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for pager_example.page route.
 *
 * This is an example describing how a module can implement a pager in order to
 * reduce the number of output rows to the screen and allow a user to scroll
 * through multiple screens of output.
 */
class PagerExamplePage extends ControllerBase {

  /**
   * Entity storage for node entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PagerExamplePage constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   Entity storage for node entities.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityStorageInterface $node_storage, AccountInterface $current_user) {
    $this->nodeStorage = $node_storage;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('current_user')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Content callback for the pager_example.page route.
   */
  public function getContent() {
    // First we'll tell the user what's going on. This content can be found
    // in the twig template file: templates/description.html.twig. It will be
    // inserted by the theming function pager_example_description().
    $build = [
      'description' => [
        '#theme' => 'pager_example_description',
        '#description' => $this->t('description'),
        '#attributes' => [],
      ],
    ];

    // Ensure that this page's cache is invalidated when nodes have been
    // published, unpublished, added or deleted; and when user permissions
    // change.
    $build['#cache']['tags'][] = 'node_list';
    $build['#cache']['contexts'][] = 'user.permissions';

    // Now we want to get our tabular data. We select nodes from node storage
    // limited by 2 per page and sort by nid DESC because we want to show newest
    // node first. Additionally, we check that the user has permission to
    // view the node.
    $query = $this->nodeStorage->getQuery()
      ->sort('nid', 'DESC')
      ->addTag('node_access')
      ->pager(2);

    // The node_access tag does not trigger a check on whether a user has the
    // ability to view unpublished content. The 'bypass node access' permission
    // is really more than we need. But, there is no separate permission for
    // viewing unpublished content. There is a permission to 'view own
    // unpublished content', but we don't have a good way of using that in this
    // query. So, unfortunately this query will incorrectly eliminate even those
    // unpublished nodes that the user may, in fact, be allowed to view.
    if (!$this->currentUser->hasPermission('bypass node access')) {
      $query->condition('status', 1);
    }
    $entity_ids = $query->execute();

    $nodes = $this->nodeStorage->loadMultiple($entity_ids);

    // We are going to output the results in a table so we set up the rows.
    $rows = [];
    foreach ($nodes as $node) {
      // There are certain things (besides unpublished nodes) that the
      // node_access tag won't prevent from being seen. The only way to get at
      // those is by explicitly checking for (view) access on a node-by-node
      // basis. In order to prevent the pager from looking strange, we will
      // "mask" these nodes that should not be accessible. If we don't do this
      // masking, it's possible that we'd have lots of pages that don't show any
      // content.
      $rows[] = [
        'nid' => $node->access('view') ? $node->id() : $this->t('XXXXXX'),
        'title' => $node->access('view') ? $node->getTitle() : $this->t('Redacted'),
      ];
    }

    // Build a render array which will be themed as a table with a pager.
    $build['pager_example'] = [
      '#type' => 'table',
      '#header' => [$this->t('NID'), $this->t('Title')],
      '#rows' => $rows,
      '#empty' => $this->t('There are no nodes to display. Please <a href=":url">create a node</a>.', [
        ':url' => Url::fromRoute('node.add', ['node_type' => 'page'])->toString(),
      ]),
    ];
    // Add our pager element so the user can choose which pagination to see.
    // This will add a '?page=1' fragment to the links to subsequent pages.
    $build['pager'] = [
      '#type' => 'pager',
      '#weight' => 10,
    ];

    return $build;
  }

}
