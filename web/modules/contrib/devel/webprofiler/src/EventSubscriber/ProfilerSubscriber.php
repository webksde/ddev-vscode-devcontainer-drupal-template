<?php

namespace Drupal\webprofiler\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 */
class ProfilerSubscriber implements EventSubscriberInterface {

  protected $profiler;

  protected $matcher;

  protected $onlyException;

  protected $onlyMasterRequests;

  protected $exception;

  protected $profiles;

  protected $requestStack;

  protected $parents;

  /**
   * @param \Symfony\Component\HttpKernel\Profiler\Profiler $profiler
   *   A Profiler instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   A RequestStack instance.
   * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface|null $matcher
   *   A RequestMatcher instance.
   * @param bool $onlyException
   *   True if the profiler only collects data when an
   *   exception occurs, false otherwise.
   * @param bool $onlyMasterRequests
   *   True if the profiler only collects data
   *   when the request is a master request, false otherwise.
   */
  public function __construct(Profiler $profiler, RequestStack $requestStack, RequestMatcherInterface $matcher = NULL, $onlyException = FALSE, $onlyMasterRequests = FALSE) {
    $this->profiler = $profiler;
    $this->matcher = $matcher;
    $this->onlyException = (bool) $onlyException;
    $this->onlyMasterRequests = (bool) $onlyMasterRequests;
    $this->profiles = new \SplObjectStorage();
    $this->parents = new \SplObjectStorage();
    $this->requestStack = $requestStack;
  }

  /**
   * Handles the onKernelException event.
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    if ($this->onlyMasterRequests && !$event->isMasterRequest()) {
      return;
    }

    $this->exception = $event->getException();
  }

  /**
   * Handles the onKernelResponse event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $master = $event->isMasterRequest();
    if ($this->onlyMasterRequests && !$master) {
      return;
    }

    if ($this->onlyException && NULL === $this->exception) {
      return;
    }

    $request = $event->getRequest();
    $exception = $this->exception;
    $this->exception = NULL;

    if (NULL !== $this->matcher && !$this->matcher->matches($request)) {
      return;
    }

    if (!$profile = $this->profiler->collect($request, $event->getResponse(), $exception)) {
      return;
    }

    $this->profiles[$request] = $profile;

    $this->parents[$request] = $this->requestStack->getParentRequest();
  }

  /**
   *
   */
  public function onKernelFinishRequest(FinishRequestEvent $event) {
    // Attach children to parents.
    foreach ($this->profiles as $request) {
      if (NULL !== $parentRequest = $this->parents[$request]) {
        if (isset($this->profiles[$parentRequest])) {
          $this->profiles[$parentRequest]->addChild($this->profiles[$request]);
        }
      }
    }

    // Save profiles.
    foreach ($this->profiles as $request) {
      $this->profiler->saveProfile($this->profiles[$request]);
    }

    $this->profiles = new \SplObjectStorage();
    $this->parents = new \SplObjectStorage();
  }

  /**
   *
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse', -100],
      KernelEvents::EXCEPTION => 'onKernelException',
      KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest', -1024],
    ];
  }

}
