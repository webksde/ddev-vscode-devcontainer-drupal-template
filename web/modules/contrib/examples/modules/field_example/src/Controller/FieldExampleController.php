<?php

namespace Drupal\field_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller for field example description page.
 *
 * This class uses the DescriptionTemplateTrait to display text we put in the
 * templates/description.html.twig file.
 */
class FieldExampleController {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'field_example';
  }

}
