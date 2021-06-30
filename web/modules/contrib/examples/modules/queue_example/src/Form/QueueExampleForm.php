<?php

namespace Drupal\queue_example\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Queue\QueueGarbageCollectionInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use queue.
 */
class QueueExampleForm extends FormBase {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The CronInterface object.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * What kind of queue backend are we using?
   *
   * @var string
   */
  protected $queueType;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory service to get new/existing queues for use.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param Drupal\Core\CronInterface $cron
   *   The cron service.
   * @param Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(QueueFactory $queue_factory, Connection $database, CronInterface $cron, Settings $settings) {
    $this->queueFactory = $queue_factory;
    $this->queueType = $settings->get('queue_default', 'queue.database');
    $this->database = $database;
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('queue'), $container->get('database'), $container->get('cron'), $container->get('settings'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Return a string that is the unique ID of our form. Best practice here is
    // to namespace the form based on your module's name.
    return 'queue_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Simple counter that makes it possible to put auto-incrementing default
    // string into the string to insert.
    if (empty($form_state->get('insert_counter'))) {
      $form_state->set('insert_counter', 1);
    }

    $queue_name = $form_state->getValue('queue_name') ?: 'queue_example_first_queue';
    $items = $this->retrieveQueue($queue_name);

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('This page is an interface on the Drupal queue API. You can add new items to the queue, "claim" one (retrieve the next item and keep a lock on it), and delete one (remove it from the queue). Note that claims are not expired until cron runs, so there is a special button to run cron to perform any necessary expirations.') . '</div>',
    ];

    $form['wrong_queue_warning'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('Note: the example works only with the default queue implementation, which is not currently configured!!') . '</div>',
      '#access' => (!$this->doesQueueUseDB()),
    ];

    $queue_names = ['queue_example_first_queue', 'queue_example_second_queue'];
    $form['queue_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose queue to examine'),
      '#options' => array_combine($queue_names, $queue_names),
      '#default_value' => $queue_name,
    ];

    $form['queue_show'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show queue'),
      '#submit' => ['::submitShowQueue'],
    ];

    $form['status_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Queue status for @name', ['@name' => $queue_name]),
      '#collapsible' => TRUE,
    ];

    if (count($items) > 0) {
      $form['status_fieldset']['status'] = [
        '#theme' => 'table',
        '#header' => [
          $this->t('Item ID'),
          $this->t('Claimed/Expiration'),
          $this->t('Created'),
          $this->t('Content/Data'),
        ],
        '#rows' => array_map([$this, 'processQueueItemForTable'], $items),
      ];
    }
    else {
      $form['status_fieldset']['status'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no items in the queue.'),
      ];
    }

    $form['insert_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Insert into @name', ['@name' => $queue_name]),
    ];

    $form['insert_fieldset']['string_to_add'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->t('item @counter', ['@counter' => $form_state->get('insert_counter')]),
    ];

    $form['insert_fieldset']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert into queue'),
      '#submit' => ['::submitAddQueueItem'],
    ];

    $form['claim_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Claim from queue'),
      '#collapsible' => TRUE,
    ];

    $form['claim_fieldset']['claim_time'] = [
      '#type' => 'radios',
      '#title' => $this->t('Claim time, in seconds'),
      '#options' => [
        0 => $this->t('none'),
        5 => $this->t('5 seconds'),
        60 => $this->t('60 seconds'),
      ],
      '#description' => $this->t('This time is only valid if cron runs during this time period. You can run cron manually below.'),
      '#default_value' => $form_state->getValue('claim_time') ?: 5,
    ];

    $form['claim_fieldset']['claim_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Claim the next item from the queue'),
      '#submit' => ['::submitClaimItem'],
    ];

    $form['claim_fieldset']['claim_and_delete_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Claim the next item and delete it'),
      '#submit' => ['::submitClaimDeleteItem'],
    ];

    $form['claim_fieldset']['run_cron'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run cron manually to expire claims'),
      '#submit' => ['::submitRunCron'],
    ];

    $form['delete_queue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete the queue and items in it'),
      '#submit' => ['::submitDeleteQueue'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Retrieves the queue from the database for display purposes only.
   *
   * It is not recommended to access the database directly, and this is only
   * here so that the user interface can give a good idea of what's going on
   * in the queue.
   *
   * @param string $queue_name
   *   The name of the queue from which to fetch items.
   *
   * @return array
   *   An array of item arrays.
   */
  public function retrieveQueue($queue_name) {
    $items = [];

    // This example requires the default queue implementation to work,
    // so we bail if some other queue implementation has been installed.
    if (!$this->doesQueueUseDb()) {
      return $items;
    }

    // Make sure there are queue items available. The queue will not create our
    // database table if there are no items.
    if ($this->queueFactory->get($queue_name)->numberOfItems() >= 1) {
      $result = $this->database->query('SELECT item_id, data, expire, created FROM {' . DatabaseQueue::TABLE_NAME . '} WHERE name = :name ORDER BY item_id',
        [':name' => $queue_name],
        ['fetch' => \PDO::FETCH_ASSOC]
      );
      foreach ($result as $item) {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * Check if we are using the default database queue.
   *
   * @return bool
   *   TRUE if we are using the default database queue implementation.
   */
  protected function doesQueueUseDb() {
    return $this->queueType == 'queue.database';
  }

  /**
   * Submit function for the show-queue button.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitShowQueue(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    // There is no harm in trying to recreate existing.
    $queue->createQueue();

    // Get the number of items.
    $count = $queue->numberOfItems();

    // Update the form item counter.
    $form_state->set('insert_counter', $count + 1);

    // Unset the string_to_add textbox.
    $form_state->unsetValue('string_to_add');

    $form_state->setRebuild();
  }

  /**
   * Submit function for the insert-into-queue button.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitAddQueueItem(array &$form, FormStateInterface $form_state) {
    // Get a queue (of the default type) called 'queue_example_queue'.
    // If the default queue class is SystemQueue this creates a queue that
    // stores its items in the database.
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    // There is no harm in trying to recreate existing.
    $queue->createQueue();

    // Queue the string.
    $queue->createItem($form_state->getValue('string_to_add'));
    $count = $queue->numberOfItems();
    $this->messenger()->addMessage($this->t('Queued your string (@string_to_add). There are now @count items in the queue.', ['@count' => $count, '@string_to_add' => $form_state->getValue('string_to_add')]));
    // Allows us to keep information in $form_state.
    $form_state->setRebuild();

    // Unsetting the string_to_add allows us to set the incremented default
    // value for the user so they don't have to type anything.
    $form_state->unsetValue('string_to_add');
    $form_state->set('insert_counter', $count + 1);
  }

  /**
   * Submit function for the "claim" button.
   *
   * Claims (retrieves) an item from the queue and reports the results.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitClaimItem(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    // There is no harm in trying to recreate existing.
    $queue->createQueue();
    $item = $queue->claimItem($form_state->getValue('claim_time'));
    $count = $queue->numberOfItems();
    if (!empty($item)) {
      $this->messenger()->addMessage($this->t('Claimed item id=@item_id string=@string for @seconds seconds. There are @count items in the queue.', [
        '@count' => $count,
        '@item_id' => $item->item_id,
        '@string' => $item->data,
        '@seconds' => $form_state->getValue('claim_time'),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('There were no items in the queue available to claim. There are @count items in the queue.', ['@count' => $count]));
    }
    $form_state->setRebuild();
  }

  /**
   * Submit function for "Claim and delete" button.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitClaimDeleteItem(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    // There is no harm in trying to recreate existing.
    $queue->createQueue();
    $count = $queue->numberOfItems();
    $item = $queue->claimItem(60);
    if (!empty($item)) {
      $this->messenger()->addMessage($this->t('Claimed and deleted item id=@item_id string=@string for @seconds seconds. There are @count items in the queue.', [
        '@count' => $count,
        '@item_id' => $item->item_id,
        '@string' => $item->data,
        '@seconds' => $form_state->getValue('claim_time'),
      ]));
      $queue->deleteItem($item);
      $count = $queue->numberOfItems();
      $this->messenger()->addMessage($this->t('There are now @count items in the queue.', ['@count' => $count]));
    }
    else {
      $count = $queue->numberOfItems();
      $this->messenger()->addMessage($this->t('There were no items in the queue available to claim/delete. There are currently @count items in the queue.', ['@count' => $count]));
    }
    $form_state->setRebuild();
  }

  /**
   * Submit function for "run cron" button.
   *
   * Runs cron (to release expired claims) and reports the results.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitRunCron(array &$form, FormStateInterface $form_state) {
    $this->cron->run();
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    // @see https://www.drupal.org/node/2705809
    if ($queue instanceof QueueGarbageCollectionInterface) {
      $queue->garbageCollection();
    }
    // There is no harm in trying to recreate existing.
    $queue->createQueue();
    $count = $queue->numberOfItems();
    $this->messenger()->addMessage($this->t('Ran cron. If claimed items expired, they should be expired now. There are now @count items in the queue', ['@count' => $count]));
    $form_state->setRebuild();
  }

  /**
   * Submit handler for clearing/deleting the queue.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitDeleteQueue(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get($form_state->getValue('queue_name'));
    $queue->deleteQueue();
    $this->messenger()->addMessage($this->t('Deleted the @queue_name queue and all items in it', ['@queue_name' => $form_state->getValue('queue_name')]));
  }

  /**
   * Helper method to format a queue item for display in a summary table.
   *
   * @param array $item
   *   Queue item array with keys for item_id, expire, created, and data.
   *
   * @return array
   *   An array with the queue properties in the right order for display in a
   *   summary table.
   */
  private function processQueueItemForTable(array $item) {
    if ($item['expire'] > 0) {
      $item['expire'] = $this->t('Claimed: expires %expire', ['%expire' => date('r', $item['expire'])]);
    }
    else {
      $item['expire'] = $this->t('Unclaimed');
    }
    $item['created'] = date('r', $item['created']);
    $item['content'] = Html::escape(unserialize($item['data']));
    unset($item['data']);

    return $item;
  }

}
