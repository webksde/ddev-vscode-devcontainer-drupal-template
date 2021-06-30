<?php

namespace Drupal\Tests\stream_wrapper_example\Traits;

use Drupal\stream_wrapper_example\SessionHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Prophecy\Argument;

/**
 * A trait to expose a mock session type to PHPUnit tests.
 */
trait MockSessionTrait {

  /**
   * We'll use this array to back our mock session.
   *
   * @var array
   */
  protected $sessionStore;

  /**
   * A representation of the HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $requestStack;

  /**
   * Create a mock session object.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   *   A test double, or mock, of a RequestStack object
   *   that can be used to return a mock Session object.
   */
  protected function createSessionMock() {
    $this->sessionStore = [];
    $session = $this->prophesize(SessionInterface::class);
    $test = $this;

    $session
      ->get('stream_wrapper_example', [])
      ->will(function ($args) use ($test) {
        return $test->getSessionStore();
      });

    $session
      ->set('stream_wrapper_example', Argument::any())
      ->will(function ($args) use ($test) {
        $test->setSessionStore($args[1]);
      });

    $session
      ->remove('stream_wrapper_example')
      ->will(function ($args) use ($test) {
        $test->resetSessionStore();
      });

    $request = $this->prophesize(Request::class);
    $request
      ->getSession()
      ->willReturn($session->reveal());

    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack
      ->getCurrentRequest()
      ->willReturn($request->reveal());

    return $this->requestStack = $request_stack->reveal();
  }

  /**
   * Get a session helper.
   */
  public function getSessionHelper() {
    return new SessionHelper($this->requestStack);
  }

  /**
   * Helper for mocks.
   */
  public function getSessionStore() {
    return $this->sessionStore;
  }

  /**
   * Helper for our mocks.
   */
  public function setSessionStore($data) {
    $this->sessionStore = $data;
  }

  /**
   * Helper for our mocks.
   */
  public function resetSessionStore() {
    $this->sessionStore = [];
  }

}
