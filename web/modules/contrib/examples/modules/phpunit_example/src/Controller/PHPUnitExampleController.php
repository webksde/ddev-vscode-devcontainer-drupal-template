<?php

namespace Drupal\phpunit_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller for PHPUnit description page.
 *
 * This class uses the DescriptionTemplateTrait to display text we put in the
 * templates/description.html.twig file.  We render out the text via its
 * description() method, and set up our routing to point to
 * PHPUnitExampleController::description().
 */
class PHPUnitExampleController {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'phpunit_example';
  }

}
