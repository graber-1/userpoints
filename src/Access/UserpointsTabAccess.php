<?php

namespace Drupal\userpoints\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\userpoints\UserPointsPermissions;
use Drupal\user\UserInterface;

/**
 * Access check for Points user tab route.
 */
class UserpointsTabAccess implements AccessInterface {

  /**
   * Checks access to the given user's contact page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\user\UserInterface $user
   *   The account being viewed / edited.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, UserInterface $user) {

    // Grant access if the current user has any permission defined by this module
    // except the administration permission that is for settings only.
    if ($account->hasPermission('manage all points') || $account->hasPermission('view all points')) {
      return AccessResult::allowed();
    }
    $userpointsPermissionsBuilder = new UserPointsPermissions();
    $perms = $userpointsPermissionsBuilder->generatePermissions();
    foreach ($perms as $perm => $data) {
      if (substr($perm, 0, 9) !== 'view own ' && $account->hasPermission($perm)) {
        return AccessResult::allowed();
      }
    }

    // Also grant access for view own points purposes.
    if ($account->id() === $user->id()) {
      foreach ($perms as $perm => $data) {
        if (substr($perm, 0, 9) === 'view own ' && $account->hasPermission($perm)) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

}
