<?php

namespace Drupal\node_type_example\Controller;

use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller routines for node_type_example.
 *
 * @ingroup node_type_example
 */
class NodeTypeExampleController {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'node_type_example';
  }

}
