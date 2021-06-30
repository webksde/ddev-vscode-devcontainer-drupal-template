<?php

namespace Drupal\block_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller routines for block example routes.
 */
class BlockExampleController {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'block_example';
  }

}
