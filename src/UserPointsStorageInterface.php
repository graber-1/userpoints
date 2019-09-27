<?php

namespace Drupal\userpoints;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface UserPointsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of User points revision IDs for a specific User points.
   *
   * @param \Drupal\userpoints\Entity\UserPointsInterface $entity
   *   The User points entity.
   *
   * @return int[]
   *   User points revision IDs (in ascending order).
   */
  public function revisionIds(UserPointsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as User points author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   User points revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
