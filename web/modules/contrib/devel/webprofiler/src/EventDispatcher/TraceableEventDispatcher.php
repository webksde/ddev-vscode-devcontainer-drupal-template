<?php

namespace Drupal\webprofiler\EventDispatcher;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\webprofiler\Stopwatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class TraceableEventDispatcher.
 */
class TraceableEventDispatcher extends ContainerAwareEventDispatcher implements EventDispatcherTraceableInterface {

  /**
   * @var \Drupal\webprofiler\Stopwatch
   *   The stopwatch service.
   */
  protected $stopwatch;

  /**
   * @var array
   */
  protected $calledListeners;

  /**
   * @var array
   */
  protected $notCalledListeners;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, array $listeners = []) {
    parent::__construct($container, $listeners);
    $this->notCalledListeners = $listeners;
  }

  /**
   * {@inheritdoc}
   */
  public function addListener($event_name, $listener, $priority = 0) {
    parent::addListener($event_name, $listener, $priority);
    $this->notCalledListeners[$event_name][$priority][] = ['callable' => $listener];
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch($event, $event_name = NULL) {
    // Temporary hack for 9.0 and 9.1 compat. See https://gitlab.com/drupalspoons/devel/-/issues/344.
    if (is_string($event)) {
      $event_obj = $event_name ?? new Event();
      $event_name = $event;
      $event = $event_obj;
    }

    $this->preDispatch($event_name, $event);
    $e = $this->stopwatch->start($event_name, 'section');

    if (isset($this->listeners[$event_name])) {
      // Sort listeners if necessary.
      if (isset($this->unsorted[$event_name])) {
        krsort($this->listeners[$event_name]);
        unset($this->unsorted[$event_name]);
      }

      // Invoke listeners and resolve callables if necessary.
      foreach ($this->listeners[$event_name] as $priority => &$definitions) {
        foreach ($definitions as &$definition) {
          if (!isset($definition['callable'])) {
            $definition['callable'] = [
              $this->container->get($definition['service'][0]),
              $definition['service'][1],
            ];
          }

          $definition['callable']($event, $event_name, $this);

          $this->addCalledListener($definition, $event_name, $priority);

          if ($event->isPropagationStopped()) {
            return $event;
          }
        }
      }
    }

    if ($e->isStarted()) {
      $e->stop();
    }

    $this->postDispatch($event_name, $event);

    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalledListeners() {
    return $this->calledListeners;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotCalledListeners() {
    return $this->notCalledListeners;
  }

  /**
   * @param \Drupal\webprofiler\Stopwatch $stopwatch
   */
  public function setStopwatch(Stopwatch $stopwatch) {
    $this->stopwatch = $stopwatch;
  }

  /**
   * Called before dispatching the event.
   *
   * @param string $eventName
   *   The event name.
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  protected function preDispatch($eventName, Event $event) {
    switch ($eventName) {
      case KernelEvents::VIEW:
      case KernelEvents::RESPONSE:
        // Stop only if a controller has been executed.
        if ($this->stopwatch->isStarted('controller')) {
          $this->stopwatch->stop('controller');
        }
        break;
    }
  }

  /**
   * Called after dispatching the event.
   *
   * @param string $eventName
   *   The event name.
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  protected function postDispatch($eventName, Event $event) {
    switch ($eventName) {
      case KernelEvents::CONTROLLER:
        $this->stopwatch->start('controller', 'section');
        break;

      case KernelEvents::RESPONSE:
        $token = $event->getResponse()->headers->get('X-Debug-Token');
        try {
          $this->stopwatch->stopSection($token);
        }
        catch (\LogicException $e) {
        }
        break;

      case KernelEvents::TERMINATE:
        // In the special case described in the `preDispatch` method above, the
        // `$token` section does not exist, then closing it throws an exception
        // which must be caught.
        $token = $event->getResponse()->headers->get('X-Debug-Token');
        try {
          $this->stopwatch->stopSection($token);
        }
        catch (\LogicException $e) {
        }
        break;
    }
  }

  /**
   * @param $definition
   * @param $event_name
   * @param $priority
   */
  private function addCalledListener($definition, $event_name, $priority) {
    if ($this->isClosure($definition['callable'])) {
      $this->calledListeners[$event_name][$priority][] = [
        'class' => 'Closure',
        'method' => '',
      ];
    }
    else {
      $this->calledListeners[$event_name][$priority][] = [
        'class' => get_class($definition['callable'][0]),
        'method' => $definition['callable'][1],
      ];
    }

    foreach ($this->notCalledListeners[$event_name][$priority] as $key => $listener) {
      if (isset($listener['service'])) {
        if ($listener['service'][0] == $definition['service'][0] && $listener['service'][1] == $definition['service'][1]) {
          unset($this->notCalledListeners[$event_name][$priority][$key]);
        }
      }
      else {
        if ($this->isClosure($listener['callable'])) {
          if (is_callable($listener['callable'], TRUE, $listenerCallableName) && is_callable($definition['callable'], TRUE, $definitionCallableName)) {
            if ($listenerCallableName == $definitionCallableName) {
              unset($this->notCalledListeners[$event_name][$priority][$key]);
            }
          }
        }
        else {
          if (get_class($listener['callable'][0]) == get_class($definition['callable'][0]) && $listener['callable'][1] == $definition['callable'][1]) {
            unset($this->notCalledListeners[$event_name][$priority][$key]);
          }
        }
      }

    }
  }

  /**
   *
   */
  private function isClosure($t) {
    return is_object($t) && ($t instanceof \Closure);
  }

}
