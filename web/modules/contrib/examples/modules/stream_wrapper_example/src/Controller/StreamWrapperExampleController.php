<?php

namespace Drupal\stream_wrapper_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for the Stream Wrapper Example.
 */
class StreamWrapperExampleController extends ControllerBase {

  /**
   * Description page for the example.
   */
  public function description() {
    $build = [
      'description' => [
        '#theme' => 'example_description',
      ],
    ];
    return $build;
  }

}
