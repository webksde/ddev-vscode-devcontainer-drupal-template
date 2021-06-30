<?php

namespace Drupal\Tests\phpunit_example\Unit\Subclasses;

use Drupal\phpunit_example\ProtectedPrivates;

/**
 * A class for testing ProtectedPrivate::protectedAdd().
 *
 * We could use reflection to test protected methods, just as with
 * private ones. But in some circumstances it might make more sense
 * to make a subclass and then run the tests against it.
 *
 * This subclass allows us to get access to the protected method.
 *
 * @ingroup phpunit_example
 */
class ProtectedPrivatesSubclass extends ProtectedPrivates {

  /**
   * A stub class so we can access a protected method.
   *
   * We use a naming convention to make it clear that we are using a
   * shimmed method.
   */
  public function subclassProtectedAdd($a, $b) {
    return $this->protectedAdd($a, $b);
  }

}
