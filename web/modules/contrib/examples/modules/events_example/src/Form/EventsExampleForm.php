<?php

namespace Drupal\events_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\events_example\Event\IncidentEvents;
use Drupal\events_example\Event\IncidentReportEvent;

/**
 * Implements the SimpleForm form controller.
 *
 * The submitForm() method of this class demonstrates using the event dispatcher
 * service to dispatch an event.
 *
 * @see \Drupal\events_exampl\Event\IncidentEvents
 * @see \Drupal\events_example\Event\IncidentReportEvent
 * @see \Symfony\Component\EventDispatcher\EventDispatcherInterface
 * @see \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
 *
 * @ingroup events_example
 */
class EventsExampleForm extends FormBase {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new UserLoginForm.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    // The event dispatcher service is an implementation of
    // \Symfony\Component\EventDispatcher\EventDispatcherInterface. In Drupal
    // this is generally and instance of the
    // \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher service.
    // This dispatcher improves performance when dispatching events by compiling
    // a list of subscribers into the service container so that they do not need
    // to be looked up every time.
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#markup' => '<p>' . $this->t('This form demonstrates subscribing to, and dispatching, events. When the form is submitted an event is dispatched indicating a new report has been submitted. Event subscribers respond to this event with various messages depending on the incident type. Review the code for the events_example module to see how it works.') . '</p>',
    ];

    $form['incident_type'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('What type of incident do you want to report?'),
      '#options' => [
        'stolen_princess' => $this->t('Missing princess'),
        'cat' => $this->t('Cat stuck in tree'),
        'joker' => $this->t('Something involving the Joker'),
      ],
    ];

    $form['incident'] = [
      '#type' => 'textarea',
      '#required' => FALSE,
      '#title' => $this->t('Incident report'),
      '#description' => $this->t('Describe the incident in detail. This information will be passed along to all crime fighters.'),
      '#cols' => 60,
      '#rows' => 5,
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
   */
  public function getFormId() {
    return 'events_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('incident_type');
    $report = $form_state->getValue('incident');

    // When dispatching, or triggering, an event start by constructing a new
    // event object. Then use the event dispatcher service to notify any event
    // subscribers. Event objects are used to transport relevant data to any
    // subscribers, as well as keep track of the current state of an event. It
    // is best practice to create a unique class wrapping
    // \Symfony\Component\EventDispatcher\Event.
    $event = new IncidentReportEvent($type, $report);

    // Dispatch an event by specifying which event, and providing an event
    // object. Rather than hard code the event name you should use a constant
    // to represent the event being dispatched. The constant serves as a
    // location for documentation of the event, and ensures your code is future
    // proofed against event name changes.
    $this->eventDispatcher->dispatch(IncidentEvents::NEW_REPORT, $event);
  }

}
