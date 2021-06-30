<?php

namespace Drupal\hooks_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller for Hooks example description page.
 *
 * This class uses the DescriptionTemplateTrait to display text we put in the
 * templates/description.html.twig file.
 */
class HooksExampleController {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'hooks_example';
  }

}
