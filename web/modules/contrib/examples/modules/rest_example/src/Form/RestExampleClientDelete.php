<?php

namespace Drupal\rest_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\rest_example\RestExampleClientCalls;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete a new node on a remote Drupal site.
 *
 * @ingroup rest_example
 */
class RestExampleClientDelete extends ConfirmFormBase {
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_example_client_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure that you want to delete this content.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('rest_example.client_actions_index');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (is_null($form_state->get('node_id')) || !is_numeric($form_state->get('node_id'))) {
      $form_state->setErrorByName('delete', $this->t('The ID passed in the URL is not an integer'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $form = parent::buildForm($form, $form_state);

    $form_state->set('node_id', $id);

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->get('node_id');

    $node = [
      'nid' => $node_id,
    ];

    $this->restExampleClientCalls->delete($node);
    $this->messenger()->addStatus($this->t('Node was successfully deleted'));
    $form_state->setRedirect('rest_example.client_actions_index');
  }

}
