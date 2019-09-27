<?php

namespace Drupal\userpoints\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining User points entities.
 *
 * @ingroup userpoints
 */
interface UserPointsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the User points creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User points.
   */
  public function getCreatedTime();

  /**
   * Gets the User points revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the User points revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\userpoints\Entity\UserPointsInterface
   *   The called User points entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the User points revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the User points revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\userpoints\Entity\UserPointsInterface
   *   The called User points entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Get the number of points this entity has.
   *
   * @return float
   *   The number of points.
   */
  public function getQuantity();

  /**
   * Add (or remove) points.
   *
   * @param float|int $quantity
   *   The number of points to add (or remove if negative).
   */
  public function addPoints($quantity);

}
