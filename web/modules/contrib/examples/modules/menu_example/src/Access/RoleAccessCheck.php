<?php

namespace Drupal\menu_example\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to routes based on roles.
 *
 * To achieve this, we implement a class with AccessInterface and use that to
 * check access.
 *
 * Our module is called menu_example, this file will be placed under
 * menu_example/src/Access/CustomAccessCheck.php.
 *
 * The @link menu_example_services.yml @endlink contains entry for this service
 * class.
 *
 * @see https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes
 */
class RoleAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    // If the user is authenticated, return TRUE.
    return AccessResult::allowedIf($account->isAuthenticated());
  }

}
