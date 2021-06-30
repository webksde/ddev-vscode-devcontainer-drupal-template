<?php

namespace Drupal\Tests\testing_example\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\testing_example\Controller\ContrivedController;

/**
 * The class to test ContrivedController.
 *
 * @group testing_example
 */
class ContrivedControllerTest extends UnitTestCase {

  /**
   * Data provider for testAdd().
   */
  public function provideTestAdd() {
    return [
      [4, 2, 2],
    ];
  }

  /**
   * Test add.
   *
   * @dataProvider provideTestAdd
   */
  public function testAdd($expected, $first, $second) {
    $controller = $this->getMockBuilder(ContrivedController::class)
      ->disableOriginalConstructor()
      ->getMock();
    $ref_add = new \ReflectionMethod($controller, 'add');
    $ref_add->setAccessible(TRUE);
    $this->assertEquals($expected, $ref_add->invokeArgs($controller, [$first, $second]));
  }

  /**
   * Data provider for testHandCount().
   */
  public function provideTestHandCount() {
    return [
      ['I can count these on one hand.', 0, 0],
      ['I can count these on one hand.', 1, 0],
      ['I can count these on one hand.', 0, 1],
      ['I need two hands to count these.', 5, 5],
      ['That\'s just too many numbers to count.', 5, 6],
      ['That\'s just too many numbers to count.', 6, 5],
    ];
  }

  /**
   * Test hand count.
   *
   * @dataProvider provideTestHandCount
   */
  public function testHandCount($expected, $first, $second) {
    // Get a mock translation service.
    $mock_translation = $this->getStringTranslationStub();
    // Create a new controller with our mocked translation service.
    $controller = new ContrivedController($mock_translation);

    // Set up a reflection for handCount().
    $ref_hand_count = new \ReflectionMethod($controller, 'handCount');
    // Set handCount() to be public.
    $ref_hand_count->setAccessible(TRUE);
    // Check out whether handCount() meets our expectation.
    $message = $ref_hand_count->invokeArgs($controller, [$first, $second]);
    $this->assertEquals($expected, (string) $message);
  }

  /**
   * Data provider for testHandCountIsolated().
   */
  public function providerTestHandCountIsolated() {
    $data = [];

    // Add one-hand data.
    foreach (range(0, 5) as $sum) {
      $data[] = ['I can count these on one hand.', $sum];
    }

    // Add two-hand data.
    foreach (range(6, 10) as $sum) {
      $data[] = ['I need two hands to count these.', $sum];
    }

    // Add too-many data.
    foreach (range(11, 15) as $sum) {
      $data[] = ['That\'s just too many numbers to count.', $sum];
    }

    return $data;
  }

  /**
   * Test hand count isolated.
   *
   * @dataProvider providerTestHandCountIsolated
   */
  public function testHandCountIsolated($expected, $sum) {
    // Mock a ContrivedController, using a mocked translation service.
    $controller = $this->getMockBuilder(ContrivedController::class)
      ->setConstructorArgs([$this->getStringTranslationStub()])
      // Specify that we'll also mock add().
      ->setMethods(['add'])
      ->getMock();

    // Mock add() so that it returns our $sum when it's called with (0,0).
    $controller->expects($this->once())
      ->method('add')
      ->with($this->equalTo(0), $this->equalTo(0))
      ->willReturn($sum);

    // Use reflection to make handCount() public.
    $ref_hand_count = new \ReflectionMethod($controller, 'handCount');
    $ref_hand_count->setAccessible(TRUE);

    // Invoke handCount().
    $message = (string) $ref_hand_count->invokeArgs($controller, [0, 0]);

    // Assert our expectations.
    $this->assertEquals($expected, $message);
  }

}
