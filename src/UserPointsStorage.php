<?php

namespace Drupal\userpoints;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\userpoints\Entity\UserPointsInterface;

/**
 * Defines the storage handler class for User points entities.
 *
 * This extends the base storage class, adding required special handling for
 * User points entities.
 *
 * @ingroup userpoints
 */
class UserPointsStorage extends SqlContentEntityStorage implements UserPointsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(UserPointsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {userpoints_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {userpoints_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
