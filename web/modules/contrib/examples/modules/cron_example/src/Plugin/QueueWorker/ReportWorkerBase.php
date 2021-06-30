<?php

namespace Drupal\cron_example\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class ReportWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ReportWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service the instance should use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $form = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('logger.factory')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param int $worker
   *   Worker number.
   * @param object $item
   *   The $item which was stored in the cron queue.
   */
  protected function reportWork($worker, $item) {
    if ($this->state->get('cron_example_show_status_message')) {
      $this->messenger()->addMessage(
        $this->t('Queue @worker worker processed item with sequence @sequence created at @time', [
          '@worker' => $worker,
          '@sequence' => $item->sequence,
          '@time' => date('c', $item->created),
        ])
      );
    }
    $this->logger->get('cron_example')->info('Queue @worker worker processed item with sequence @sequence created at @time', [
      '@worker' => $worker,
      '@sequence' => $item->sequence,
      '@time' => date('c', $item->created),
    ]);
  }

}
