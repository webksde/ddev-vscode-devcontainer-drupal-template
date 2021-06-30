<?php

namespace Drupal\field_permission_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

/**
 * Controller routines for field permission example routes.
 */
class FieldPermissionExampleController extends ControllerBase {

  /**
   * A simple controller method to explain what this example is about.
   */
  public function description() {
    // Make a link from a route to the permissions admin page.
    $permissions_admin_link = Link::createFromRoute($this->t('the permissions admin page'), 'user.admin_permissions')->toString();

    $build = [
      'description' => [
        '#theme' => 'field_permission_description',
        '#admin_link' => $permissions_admin_link,
      ],
    ];
    return $build;
  }

}
