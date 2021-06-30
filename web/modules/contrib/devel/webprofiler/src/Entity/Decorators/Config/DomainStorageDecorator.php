<?php

namespace Drupal\webprofiler\Entity\Decorators\Config;

use Drupal\domain\DomainStorageInterface;
use Drupal\domain\DomainInterface;

/**
 * Class DomainStorageDecorator.
 */
class DomainStorageDecorator extends ConfigEntityStorageDecorator implements DomainStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadDefaultDomain() {
    return $this->getOriginalObject()->loadDefaultDomain();
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultId() {
    return $this->getOriginalObject()->loadDefaultId();
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleSorted(array $ids = NULL) {
    return $this->getOriginalObject()->loadMultipleSorted($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    return $this->getOriginalObject()->loadByHostname($hostname);
  }

  /**
   * {@inheritdoc}
   */
  public function loadOptionsList() {
    return $this->getOriginalObject()->loadOptionsList();
  }

  /**
   * {@inheritdoc}
   */
  public function sort(DomainInterface $a, DomainInterface $b) {
    return $this->getOriginalObject()->sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function loadSchema() {
    return $this->getOriginalObject()->loadSchema();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareHostname($hostname) {
    return $this->getOriginalObject()->prepareHostname($hostname);
  }

  /**
   * {@inheritdoc}
   */
  public function createHostname() {
    return $this->getOriginalObject()->createHostname();
  }

  /**
   * {@inheritdoc}
   */
  public function createMachineName($hostname = NULL) {
    return $this->getOriginalObject()->createMachineName($hostname);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultScheme() {
    return $this->getOriginalObject()->getDefaultScheme();
  }

}
