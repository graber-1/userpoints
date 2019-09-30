<?php

namespace Drupal\userpoints\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\userpoints\Entity\UserPoints;

/**
 * Defines the User Points service.
 */
interface UserPointsServiceInterface {

  /**
   * Get points entity for a referencing (owner) entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $points_type
   *   Type of points.
   *
   * @return \Drupal\userpoints\Entity\UserPoints|null
   *   User points entity.
   */
  public function getPointsEntity(EntityInterface $entity, $points_type);

  /**
   * Get points for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $points_type
   *   Type of points to get.
   *
   * @return float
   *   Number of points.
   */
  public function getPoints(EntityInterface $entity, $points_type);

  /**
   * Gets points revisions data for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $points_type
   *   Type of points to get log for.
   *
   * @return array
   *   Array of revision fields.
   */
  public function getLog(EntityInterface $entity, $points_type);

  /**
   * Add to or subtract points from an entity.
   *
   * @param int|float $quantity
   *   The number of points (can be negative to subtract).
   * @param string $points_type
   *   Type of points to add.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $log
   *   Revision log messsage for the operation.
   */
  public function addPoints($quantity, $points_type, EntityInterface $entity, $log = '');

  /**
   * Transfer points from one entity to the other.
   *
   * @param int|float $quantity
   *   The number of points to transfer (must be positive).
   * @param string $points_type
   *   Type of points to get.
   * @param \Drupal\Core\Entity\EntityInterface $source
   *   The source entity.
   * @param \Drupal\Core\Entity\EntityInterface $target
   *   The target entity.
   * @param string $log
   *   Revision log messsage for the operation.
   */
  public function transferPoints($quantity, $points_type, EntityInterface $source, EntityInterface $target, $log = '');

}
