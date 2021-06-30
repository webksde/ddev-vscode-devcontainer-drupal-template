<?php

namespace Drupal\rest_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\rest_example\RestExampleClientCalls;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edit or create a new node on a remote Drupal site.
 *
 * @ingroup rest_example
 */
class RestExampleClientEdit extends FormBase {

  /**
   * RestExampleClientCalls service.
   *
   * @var \Drupal\rest_example\RestExampleClientCalls
   */
  private $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(RestExampleClientCalls $restExampleClientCalls) {
    $this->client = $restExampleClientCalls;

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_example_client_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_null($form_state->get('node_id')) && !is_numeric($form_state->get('node_id'))) {
      $form_state->setErrorByName('submit', $this->t('The ID passed in the URL is not an integer'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $config_factory = \Drupal::configFactory();
    if (empty($config_factory->get('rest_example.settings')->get('server_url'))) {
      $this->messenger()->addError($this->t('The remote endpoint service address have not been set. Please go and provide the credentials and the endpoint address on the <a href="@url">config page</a>.', ['@url' => base_path() . 'examples/rest-client-settings']));
      return [
        'error' => [
          '#markup' => 'Unable to establish to the remote site.',
        ],
      ];
    }

    if (!is_null($id) && !is_numeric($id)) {
      return new Response('The ID passed in the URL is not an integer', 500);
    }

    $title = '';
    $form_state->set('node_id', NULL);
    $form_state->set('node_type', 'rest_example_test');

    // If this an existing node, we pull the data from the remote and set the
    // variables that we use as default values later on.
    if (is_numeric($id)) {
      $node = $this->client->index($id);
      if (isset($node[0])) {
        $title = $node[0]['title'];
        $form_state->set('node_id', $id);
      }
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node title'),
      '#default_value' => $title,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->get('node_id');
    $node_type = $form_state->get('node_type');
    $form_values = $form_state->getValues();

    $node = [
      'nid' => $node_id,
      'title' => $form_values['title'],
      'type' => $node_type,
    ];

    if (is_null($node_id)) {
      $this->client->create($node);
    }
    else {
      $this->client->update($node);
    }

    $this->messenger()->addStatus($this->t('Node was successfully created'));

    $form_state->setRedirect('rest_example.client_actions_index');
  }

}
