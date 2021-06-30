<?php

namespace Drupal\Tests\webprofiler\Unit\DataCollector;

use Drupal\Tests\UnitTestCase;

/**
 * Class DataCollectorBaseTest.
 *
 * @group webprofiler
 */
abstract class DataCollectorBaseTest extends UnitTestCase {

  /**
   * @var
   */
  protected $request;

  /**
   * @var
   */
  protected $response;

  /**
   * @var
   */
  protected $exception;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
    $this->response = $this->createMock('Symfony\Component\HttpFoundation\Response');
    $this->exception = $this->createMock('Exception');
  }

}
