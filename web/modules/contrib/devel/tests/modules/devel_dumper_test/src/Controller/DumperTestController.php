<?php

namespace Drupal\devel_dumper_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DumperTestController.
 *
 * @package Drupal\devel_dumper_test\Controller
 */
class DumperTestController extends ControllerBase {

  /**
   * The dumper manager.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * Constructs a new DumperTestController object.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $devel_dumper_manager
   *   The dumper manager.
   */
  public function __construct(DevelDumperManagerInterface $devel_dumper_manager) {
    $this->dumper = $devel_dumper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('devel.dumper')
    );
  }

  /**
   * Returns the dump output to test.
   *
   * @return array
   *   The render array output.
   */
  public function dump() {
    $this->dumper->dump('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the message output to test.
   *
   * @return array
   *   The render array output.
   */
  public function message() {
    $this->dumper->message('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the debug output to test.
   *
   * @return array
   *   The render array output.
   */
  public function debug() {
    $this->dumper->debug('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function export() {
    return [
      '#markup' => $this->dumper->export('Test output'),
    ];
  }

  /**
   * Returns the renderable export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function exportRenderable() {
    return $this->dumper->exportAsRenderable('Test output');
  }

}
