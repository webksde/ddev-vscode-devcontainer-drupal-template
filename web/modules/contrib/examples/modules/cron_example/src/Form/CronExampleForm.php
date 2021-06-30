<?php

namespace Drupal\cron_example\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use cron.
 */
class CronExampleForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, QueueFactory $queue, StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('queue'),
      $container->get('state')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cron_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cron_example.settings');

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron status information'),
      '#open' => TRUE,
    ];
    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('The cron example demonstrates hook_cron() and hook_queue_info() processing. If you have administrative privileges you can run cron from this page and see the results.'),
    ];

    $next_execution = $this->state->get('cron_example.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : REQUEST_TIME;

    $args = [
      '%time' => date('c', $this->state->get('cron_example.next_execution')),
      '%seconds' => $next_execution - REQUEST_TIME,
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('cron_example_cron() will next execute the first time cron runs after %time (%seconds seconds from now)', $args),
    ];

    if ($this->currentUser->hasPermission('administer site configuration')) {
      $form['cron_run'] = [
        '#type' => 'details',
        '#title' => $this->t('Run cron manually'),
        '#open' => TRUE,
      ];
      $form['cron_run']['cron_reset'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Run cron_example's cron regardless of whether interval has expired."),
        '#default_value' => FALSE,
      ];
      $form['cron_run']['cron_trigger']['actions'] = ['#type' => 'actions'];
      $form['cron_run']['cron_trigger']['actions']['sumbit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      ];
    }

    $form['cron_queue_setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron queue setup (for hook_cron_queue_info(), etc.)'),
      '#open' => TRUE,
    ];

    $queue_1 = $this->queue->get('cron_example_queue_1');
    $queue_2 = $this->queue->get('cron_example_queue_2');

    $args = [
      '%queue_1' => $queue_1->numberOfItems(),
      '%queue_2' => $queue_2->numberOfItems(),
    ];
    $form['cron_queue_setup']['current_cron_queue_status'] = [
      '#type' => 'item',
      '#markup' => $this->t('There are currently %queue_1 items in queue 1 and %queue_2 items in queue 2', $args),
    ];
    $form['cron_queue_setup']['num_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items to add to queue'),
      '#options' => array_combine([1, 5, 10, 100, 1000], [1, 5, 10, 100, 1000]),
      '#default_value' => 5,
    ];
    $form['cron_queue_setup']['queue'] = [
      '#type' => 'radios',
      '#title' => $this->t('Queue to add items to'),
      '#options' => [
        'cron_example_queue_1' => $this->t('Queue 1'),
        'cron_example_queue_2' => $this->t('Queue 2'),
      ],
      '#default_value' => 'cron_example_queue_1',
    ];
    $form['cron_queue_setup']['actions'] = ['#type' => 'actions'];
    $form['cron_queue_setup']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add jobs to queue'),
      '#submit' => [[$this, 'addItems']],
    ];

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration of cron_example_cron()'),
      '#open' => TRUE,
    ];
    $form['configuration']['cron_example_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron interval'),
      '#description' => $this->t('Time after which cron_example_cron will respond to a processing request.'),
      '#default_value' => $config->get('interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        3600 => $this->t('1 hour'),
        86400 => $this->t('1 day'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {
    $cron_reset = $form_state->getValue('cron_reset');
    if (!empty($cron_reset)) {
      $this->state->set('cron_example.next_execution', 0);
    }

    // Use a state variable to signal that cron was run manually from this form.
    $this->state->set('cron_example_show_status_message', TRUE);
    if ($this->cron->run()) {
      $this->messenger()->addMessage($this->t('Cron ran successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Cron run failed.'));
    }
  }

  /**
   * Add the items to the queue when signaled by the form.
   */
  public function addItems(array &$form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $queue_name = $form['cron_queue_setup']['queue'][$values['queue']]['#title'];
    $num_items = $form_state->getValue('num_items');
    // Queues are defined by a QueueWorker Plugin which are selected by their
    // id attritbute.
    // @see \Drupal\cron_example\Plugin\QueueWorker\ReportWorkerOne
    $queue = $this->queue->get($values['queue']);

    for ($i = 1; $i <= $num_items; $i++) {
      // Create a new item, a new data object, which is passed to the
      // QueueWorker's processItem() method.
      $item = new \stdClass();
      $item->created = REQUEST_TIME;
      $item->sequence = $i;
      $queue->createItem($item);
    }

    $args = [
      '%num' => $num_items,
      '%queue' => $queue_name,
    ];
    $this->messenger()->addMessage($this->t('Added %num items to %queue', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the interval as stored in configuration. This will be read when
    // this modules hook_cron function fires and will be used to ensure that
    // action is taken only after the appropiate time has elapsed.
    $this->config('cron_example.settings')
      ->set('interval', $form_state->getValue('cron_example_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cron_example.settings'];
  }

}
