<?php

namespace Drupal\tour_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller routines for tour example routes.
 *
 * This is where our tour page is defined.
 *
 * @ingroup tour_example
 */
class TourExampleController {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'tour_example';
  }

}
