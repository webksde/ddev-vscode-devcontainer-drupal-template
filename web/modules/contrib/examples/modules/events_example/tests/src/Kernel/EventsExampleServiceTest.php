<?php

namespace Drupal\Tests\events_example\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\events_example\EventSubscriber\EventsExampleSubscriber;

/**
 * Test to ensure 'events_example_subscriber' service is reachable.
 *
 * @group events_example
 * @group examples
 *
 * @ingroup events_example
 */
class EventsExampleServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['events_example'];

  /**
   * Test for existence of 'events_example_subscriber' service.
   */
  public function testEventsExampleService() {
    $subscriber = $this->container->get('events_example_subscriber');
    $this->assertInstanceOf(EventsExampleSubscriber::class, $subscriber);
  }

}
