<?php

namespace Drupal\userpoints\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Access check for Points user tab route.
 */
class UserpointsUserTabAccess extends UserpointsTabAccess {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, UserInterface $user) {
    return $this->checkAccess($account, $user);
  }

}
