<?php

namespace Drupal\webprofiler\Entity\Decorators\Config;

use Drupal\taxonomy\VocabularyStorageInterface;

/**
 * Class EntityStorageDecorator.
 */
class VocabularyStorageDecorator extends ConfigEntityStorageDecorator implements VocabularyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids($vids) {
    return $this->getOriginalObject()->getToplevelTids($vids);
  }

}
