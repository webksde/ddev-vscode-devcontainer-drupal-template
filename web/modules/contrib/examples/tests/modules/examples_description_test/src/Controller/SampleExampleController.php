<?php

namespace Drupal\examples_description_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Simple controller class used to test the DescriptionTemplateTrait.
 */
class SampleExampleController extends ControllerBase {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'examples_description_test';
  }

  /**
   * {@inheritdoc}
   *
   * We override this so we can see some substitutions.
   */
  protected function getDescriptionVariables() {
    $variables = [
      'module' => $this->getModuleName(),
      'slogan' => $this->t('We aim to please'),
    ];
    return $variables;
  }

}
