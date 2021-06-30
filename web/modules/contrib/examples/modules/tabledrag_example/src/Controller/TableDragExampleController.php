<?php

namespace Drupal\tabledrag_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Controller for the tabledrag example.
 *
 * This controller only deals with the description path.
 */
class TableDragExampleController extends ControllerBase {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'tabledrag_example';
  }

}
