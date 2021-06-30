<?php

namespace Drupal\rest_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\rest_example\RestExampleClientCalls;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for rest example routes.
 *
 * @ingroup rest_example
 */
class RestExampleClientController extends ControllerBase {

  /**
   * RestExampleClientCalls object.
   *
   * @var \Drupal\rest_example\RestExampleClientCalls
   */
  private $restExampleClientCalls;

  /**
   * RestExampleClientController constructor.
   *
   * @param \Drupal\rest_example\RestExampleClientCalls $rest_example_client_calls
   *   RestExampleClientCalls service.
   */
  public function __construct(RestExampleClientCalls $rest_example_client_calls) {
    $this->restExampleClientCalls = $rest_example_client_calls;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rest_example_client_calls')
    );
  }

  /**
   * Retrieve a list of all nodes available on the remote site.
   *
   * Building the list as a table by calling the RestExampleClientCalls::index()
   * and builds the list from the response of that.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function indexAction() {

    if (NULL === $this->configFactory->get('rest_example.settings')->get('server_url')) {
      $this->messenger()->addWarning($this->t('The remote endpoint service address have not been set. Please go and provide the credentials and the endpoint address on the <a href="@url">config page</a>.', ['@url' => base_path() . 'examples/rest-client-settings']));
    }
    $build = [];

    $nodes = $this->restExampleClientCalls->index();

    $build['intro'] = [
      '#markup' => $this->t('This is a list of nodes, of type <em>Rest Example Test</em>, on the remote server. From here you can create new node, edit and delete existing ones.'),
    ];

    $build['node_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Type'),
        $this->t('Created'),
        $this->t('Edit'),
        $this->t('Delete'),
      ],
      '#empty' => t('There are no items on the remote system yet'),
    ];

    if (!empty($nodes)) {
      foreach ($nodes as $delta => $node) {
        $build['node_table'][$delta]['title']['#plain_text'] = $node['title'];
        $build['node_table'][$delta]['type']['#plain_text'] = $node['type'];
        $build['node_table'][$delta]['created']['#plain_text'] = $node['created'];
        $build['node_table'][$delta]['edit']['#plain_text'] = Link::createFromRoute($this->t('Edit'), 'rest_example.client_actions_edit', ['id' => $node['nid']])->toString();
        $build['node_table'][$delta]['delete']['#plain_tex'] = Link::createFromRoute($this->t('Delete'), 'rest_example.client_actions_delete', ['id' => $node['nid']])->toString();
      }
    }

    return $build;
  }

}
