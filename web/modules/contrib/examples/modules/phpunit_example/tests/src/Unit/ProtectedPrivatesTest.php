<?php

namespace Drupal\Tests\phpunit_example\Unit;

use Drupal\Tests\UnitTestCase;

use Drupal\phpunit_example\ProtectedPrivates;

use Drupal\Tests\phpunit_example\Unit\Subclasses\ProtectedPrivatesSubclass;

/**
 * ProtectedPrivates unit testing of restricted methods.
 *
 * This test case demonstrates the following unit testing patterns and topics:
 * - Using reflection to test private class methods.
 * - Using subclassing to test protected class methods.
 *
 * If you are reading this and don't understand the basics of unit testing,
 * start reading AddClassTest instead.
 *
 * This test class uses reflection and subclassing to work around method
 * access problems. Since, by design, a private method is inaccessible,
 * we have to use reflection to gain access to the method for our own
 * purposes.
 *
 * The getAccessibleMethod() method demonstrates a way to do this.
 *
 * Once we've set the method to be accessible, we can use it as if
 * it were public.
 *
 * The same technique can be used for protected methods. However, there
 * might be times when it makes more sense to subclass the class under
 * test, and just make a public accessor method that way. So we
 * demonstrate that here in testProtectedAdd().
 *
 * @ingroup phpunit_example
 *
 * @group phpunit_example
 * @group examples
 */
class ProtectedPrivatesTest extends UnitTestCase {

  /**
   * Get an accessible method using reflection.
   */
  public function getAccessibleMethod($class_name, $method_name) {
    $class = new \ReflectionClass($class_name);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Good data provider.
   */
  public function addDataProvider() {
    return [
      [5, 2, 3],
    ];
  }

  /**
   * Test ProtectedPrivate::privateAdd().
   *
   * We want to test a private method on a class. This is problematic
   * because, by design, we don't have access to this method. However,
   * we do have a tool available to help us out with this problem:
   * We can override the accessibility of a method using reflection.
   *
   * @dataProvider addDataProvider
   */
  public function testPrivateAdd($expected, $a, $b) {
    // Get a reflected, accessible version of the privateAdd() method.
    $private_method = $this->getAccessibleMethod(
      'Drupal\phpunit_example\ProtectedPrivates',
      'privateAdd'
    );
    // Create a new ProtectedPrivates object.
    $pp = new ProtectedPrivates();
    // Use the reflection to invoke on the object.
    $sum = $private_method->invokeArgs($pp, [$a, $b]);
    // Make an assertion.
    $this->assertEquals($expected, $sum);
  }

  /**
   * Bad data provider.
   */
  public function addBadDataProvider() {
    return [
      ['string', []],
    ];
  }

  /**
   * Test ProtectedPrivate::privateAdd() with bad data.
   *
   * This is essentially the same test as testPrivateAdd(), but using
   * non-numeric data. This lets us test the exception-throwing ability
   * of this private method.
   *
   * @dataProvider addBadDataProvider
   */
  public function testPrivateAddBadData($a, $b) {
    // Get a reflected, accessible version of the privateAdd() method.
    $private_method = $this->getAccessibleMethod(
      'Drupal\phpunit_example\ProtectedPrivates',
      'privateAdd');
    // Create a new ProtectedPrivates object.
    $pp = new ProtectedPrivates();
    // Use the reflection to invoke on the object.
    // This should throw an exception.
    $this->expectException(\InvalidArgumentException::class);
    $private_method->invokeArgs($pp, [$a, $b]);
  }

  /**
   * Test ProtectedPrivates::protectedAdd() using a stub class.
   *
   * We could use the same reflection technique to test protected
   * methods, just like we did with private ones.
   *
   * But sometimes it might make more sense to use a stub class
   * which will have access to the protected method. That's what
   * we'll demonstrate here.
   *
   * @dataProvider addDataProvider
   */
  public function testProtectedAdd($expected, $a, $b) {
    $stub = new ProtectedPrivatesSubclass();
    $this->assertEquals($expected, $stub->subclassProtectedAdd($a, $b));
  }

  /**
   * Test ProtectedPrivates::protectedAdd() with bad data using a stub class.
   *
   * This test is similar to testProtectedAdd(), but expects an exception.
   *
   * @dataProvider addBadDataProvider
   */
  public function testProtectedAddBadData($a, $b) {
    $stub = new ProtectedPrivatesSubclass();
    $this->expectException(\InvalidArgumentException::class);
    $stub->subclassProtectedAdd($a, $b);
  }

}
