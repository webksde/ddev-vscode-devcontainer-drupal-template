<?php

namespace Drupal\session_example\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Form to allow the user to store information in their session.
 *
 * In this object we'll inject the session service. In the submission form we
 * got the session from a Request object supplied by the routing system. Either
 * of these work, because they're the same object. But we use injection here
 * because the buildForm() method does not have an easy way to derive the
 * Request object or the session.
 *
 * @ingroup session_example
 */
class SessionExampleForm extends FormBase {

  /**
   * The session object.
   *
   * We will use this to store information that the user submits, so that it
   * persists across requests.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * Constructs a new SessionExampleForm object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The cache tag invalidator service.
   */
  public function __construct(SessionInterface $session, CacheTagsInvalidatorInterface $invalidator) {
    $this->session = $session;
    $this->cacheTagInvalidator = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Session Data Form'),
      '#markup' => $this->t('In this example form, data that you enter into the form will be saved into your session data, which persists until you log out of Drupal.'),
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => $this->t('Your name.'),
      '#default_value' => $this->session->get('session_example.name', ''),
    ];
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#placeholder' => $this->t('Your email address.'),
      '#default_value' => $this->session->get('session_example.email', ''),
    ];
    $form['quest'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quest'),
      '#placeholder' => $this->t('What is your quest?'),
      '#default_value' => $this->session->get('session_example.quest', ''),
    ];
    $form['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Favorite Color'),
      '#options' => [
        '' => $this->t('--'),
        'red' => $this->t('Red'),
        'blue' => $this->t('Blue'),
        'yellow' => $this->t('Yellow'),
        'argggh' => $this->t('Argggghhh!!'),
      ],
      '#default_value' => $this->session->get('session_example.color', ''),
      '#description' => $this->t('What is your favorite color?'),
    ];
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Session'),
      '#submit' => ['::submitClearSession'],
    ];
    return $form;
  }

  /**
   * Store a form value in the session.
   *
   * Form values are always a string. This means an empty string is a valid
   * value for when a user wants to remove a value from the session. We have to
   * handle this special case for the session object.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   */
  protected function setSessionValue($key, $value) {
    if (empty($value)) {
      // If the value is an empty string, remove the key from the session.
      $this->session->remove($key);
    }
    else {
      $this->session->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We get the submitted form information and store it in the session. We use
    // key names which include our module name in order to avoid namespace
    // collision.
    $this->setSessionValue('session_example.name', $form_state->getValue('name'));
    $this->setSessionValue('session_example.email', $form_state->getValue('email'));
    $this->setSessionValue('session_example.quest', $form_state->getValue('quest'));
    $this->setSessionValue('session_example.color', $form_state->getValue('color'));
    // Tell the user what happened here, and that they can look at another page
    // to see the result.
    $this->messenger()->addMessage($this->t('The session has been saved successfully. @link', [
      '@link' => Link::createFromRoute('Check here.', 'session_example.view')->toString(),
    ]));
    // Since we might have changed the session information, we will invalidate
    // the cache tag for this session.
    $this->invalidateCacheTag();
  }

  /**
   * Remove all the session information.
   */
  public function submitClearSession(array &$form, FormStateInterface $form_state) {
    $items = [
      'session_example.name',
      'session_example.email',
      'session_example.quest',
      'session_example.color',
    ];
    foreach ($items as $item) {
      $this->session->remove($item);
    }
    $this->messenger()->addMessage($this->t('Session is cleared.'));
    // Since we might have changed the session information, we will invalidate
    // the cache tag for this session.
    $this->invalidateCacheTag();
  }

  /**
   * Invalidate the cache tag for this session.
   *
   * The form will use this method to invalidate the cache tag when the user
   * updates their information in the submit handlers.
   */
  protected function invalidateCacheTag() {
    $this->cacheTagInvalidator->invalidateTags(['session_example:' . $this->session->getId()]);
  }

}
