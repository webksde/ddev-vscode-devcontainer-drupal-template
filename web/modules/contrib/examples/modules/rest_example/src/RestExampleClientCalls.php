<?php

namespace Drupal\rest_example;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Here we interact with the remote service.
 *
 * We use Guzzle (what else ;-) ).
 */
class RestExampleClientCalls {

  /**
   * The client used to send HTTP requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The header uses when sending HTTP request.
   *
   * The headers are very important when communicating with the REST server.
   * It's used by the server the verify that it supports the sent data
   * (Content-Type) and that it supports the type of response that the client
   * wants.
   *
   * @var array
   */

  protected $clientHeaders = [
    'Accept' => 'application/haljson',
    'Content-Type' => 'application/haljson',
  ];

  /**
   * The authentication parameters used when calling the remote REST server.
   *
   * @var array
   */

  protected $clientAuth;

  /**
   * The URL of the remote REST server.
   *
   * @var string
   */

  protected $remoteUrl;

  /**
   * The constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory) {
    $this->client = $client;

    // Retrieve the config from the configuration page set at
    // examples/rest_client_settings.
    $rest_config = $config_factory->get('rest_example.settings');

    $this->clientAuth = [
      $rest_config->get('server_password'),
      $rest_config->get('server_username'),
    ];

    $this->remoteUrl = $rest_config->get('server_url');
  }

  /**
   * Retrieve a list of nodes from the remote server.
   *
   * When we retrieve entities we use GET.
   *
   * @param int $node_id
   *   The ID of the remote node, if needed. If the ID is NULL, all nodes will
   *   be fetched.
   *
   * @return mixed
   *   JSON formatted string with the nodes from the remote server.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function index($node_id = NULL) {

    // If the configurated URL is an empty string, return nothing.
    if (empty($this->remoteUrl)) {
      return '';
    }

    $id = '';
    if (!empty($node_id) && is_numeric($node_id)) {
      $id = '/' . $node_id;
    }

    $response = $this->client->request('GET',
    $this->remoteUrl . '/rest/node' . $id, [
      'headers' => $this->clientHeaders,
    ]
    );

    return Json::decode($response->getBody()->getContents());
  }

  /**
   * Create a node on the remote server, using POST.
   *
   * @param array $node
   *   Contains the data of the node we want to create.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A HTTP response.
   *
   * @throws \InvalidArgumentException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function create(array $node) {

    if (empty($this->remoteUrl)) {
      return new Response('The remote endpoint URL has not been setup.', 500);
    }

    // Build an array of options telling the remote server what type of content
    // we want to create, and give it a title. After that, we encode it all
    // to JSON.
    $request_body = json_encode([
      '_links' => [
        'type' => [
          'href' => $this->remoteUrl . '/rest/type/node/' . $node['type'],
        ],
      ],
      'title' => [0 => ['value' => $node['title']]],
    ]);

    $response = $this->client->request('POST',
        $this->remoteUrl . '/entity/node',
    [
      'headers' => $this->clientHeaders,
      'auth' => $this->clientAuth,
      'body' => $request_body,
    ]
    );

    // Validate the response from the remote server.
    if ($response->getStatusCode() != 201) {
      return new Response('An error occured while creating the node.', 500);
    }
  }

  /**
   * Update (PATCH) a node on the remote server.
   *
   * You are encuraged to read the code and the comments in
   * RestExampleClientCalls::create() first.
   *
   * @param array $node
   *   Contains the data of the node we want to create.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A HTTP response.
   *
   * @throws \InvalidArgumentException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function update(array $node) {
    if (empty($this->remoteUrl)) {
      return new Response('The remote URL has not been setup.', 500);
    }

    $request_body = json_encode([
      '_links' => [
        'type' => [
          'href' => $this->remoteUrl . '/rest/type/node/' . $node['type'],
        ],
      ],
      'title' => [0 => ['value' => $node['title']]],
    ]);
    $response = $this->client->request('POST',
        $this->remoteUrl . '/node/' . $node['nid'],
    [
      'headers' => $this->clientHeaders,
      'auth' => $this->clientAuth,
      'body' => $request_body,
    ]
    );

    if ($response->getStatusCode() != 204) {
      return new Response('An error occured while patching the node.', 500);
    }
  }

  /**
   * Delete a node on the remote server, using DELETE.
   *
   * You are encouraged to read the code and the comments in
   * RestExampleClientCalls::create() first.
   *
   * @param array $node
   *   Contains the data of the node we want to create.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response.
   *
   * @throws \InvalidArgumentException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function delete(array $node) {

    if (empty($this->remoteUrl)) {
      return new Response('The remote URL has not been setup.', 500);
    }

    $response = $this->client->request('DELETE',
        $this->remoteUrl . '/node/' . $node['nid'],
    [
      'headers' => $this->clientHeaders,
      'auth' => $this->clientAuth,
    ]
    );

    if ($response->getStatusCode() != 204) {
      return new Response('An error occured while patching the node.', 500);
    }
  }

}
