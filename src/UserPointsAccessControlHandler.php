<?php

namespace Drupal\userpoints;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User points entity.
 *
 * @see \Drupal\userpoints\Entity\UserPoints.
 */
class UserPointsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    $entity_bundle = $entity->bundle();

    // Allow all operations for management users.
    $permission = strtr('manage %bundle points', ['%bundle' => $entity_bundle]);
    if ($account->hasPermission($permission) || $account->hasPermission('manage all points')) {
      return AccessResult::allowed();
    }

    if ($operation === 'view') {

      // View all points of this bundle.
      $permission = strtr('view %bundle points', ['%bundle' => $entity_bundle]);
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed();
      }

      // View own points of this bundle.
      $uid = $entity->getOwnerId();
      $is_own = $account->isAuthenticated() && $account->id() == $uid;
      $permission = strtr('view own %bundle points', ['%bundle' => $entity_bundle]);
      if ($is_own && $account->hasPermission($permission)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permission = strtr('manage %bundle points', ['%bundle' => $entity_bundle]);
    if ($account->hasPermission($permission) || $account->hasPermission('manage all points')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
