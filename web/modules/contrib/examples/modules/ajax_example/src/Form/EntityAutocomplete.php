<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple autocomplete form which looks up usernames.
 *
 * @ingroup ajax_example
 */
class EntityAutocomplete implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The entity type manager service.
   *
   * We need this for the submit handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Container injection factory.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service discovery container.
   *
   * @return self
   *   The form object.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('entity_type.manager')
    );
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_autocomplete_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#markup' => '<div>' . $this->t("This example uses the <code>entity_autocomplete</code> form element to select users. You'll need a few users on your system for it to make sense.") . '</div>',
    ];

    // Here we use the delightful entity_autocomplete form element. It allows us
    // to consistently select entities. See https://www.drupal.org/node/2418529.
    $form['users'] = [
      // A type of entity_autocomplete lets Drupal know it should autocomplete
      // entities.
      '#type' => 'entity_autocomplete',
      // We can specify entity types to autocomplete.
      '#target_type' => 'user',
      // Specifying #tags as TRUE allows for multiple selections, separated by
      // commas.
      '#tags' => TRUE,
      '#title' => $this->t('Choose a user (Separate with commas)'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Here we validate and signal an error if there are no users selected.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $state_users = $form_state->getValue('users');
    if (empty($state_users)) {
      $form_state->setErrorByName('users', 'There were no users selected.');
    }
  }

  /**
   * {@inheritdoc}
   *
   * On submit, show the user the names of the users they selected.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state_users = $form_state->getValue('users');
    $users = [];
    foreach ($state_users as $state_user) {
      $uid = $state_user['target_id'];
      $users[] = $this->entityTypeManager->getStorage('user')->load($uid)->getDisplayName();
    }
    $this->messenger()->addMessage('These are your users: ' . implode(' ', $users));
  }

}
