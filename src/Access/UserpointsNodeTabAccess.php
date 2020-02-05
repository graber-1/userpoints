<?php

namespace Drupal\userpoints\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Access check for Points user tab route.
 */
class UserpointsNodeTabAccess extends UserpointsTabAccess {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    return $this->checkAccess($account, $node);
  }

}
