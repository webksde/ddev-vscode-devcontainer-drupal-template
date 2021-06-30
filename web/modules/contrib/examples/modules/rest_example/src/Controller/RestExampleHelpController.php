<?php

namespace Drupal\rest_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Provides a help page for the REST Examples module.
 *
 * @ingroup rest_example
 */
class RestExampleHelpController extends ControllerBase {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'rest_example';
  }

}
