<?php

namespace Drupal\userpoints\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\userpoints\Entity\UserPointsInterface;
use Drupal\userpoints\Exception\UserPointsException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the User Points service.
 */
class UserPointsService implements UserPointsServiceInterface {

  use StringTranslationTrait;

  /**
   * Userpoints storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $userPointsStorage;

  /**
   * The Event Dispatcher service.
   *
   * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The config factory service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Event Dispatcher service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user instance.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EventDispatcherInterface $eventDispatcher,
    AccountInterface $currentUser
  ) {
    $this->userPointsStorage = $entityTypeManager->getStorage('userpoints');
    $this->eventDispatcher = $eventDispatcher;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getPointsEntity(EntityInterface $entity, $points_type) {
    $query = $this->userPointsStorage->getQuery()
      ->condition('type', $points_type)
      ->condition('entity_type_id', $entity->getEntityTypeId(), '=')
      ->condition('entity_id', $entity->id(), '=');
    $point_ids = $query->execute();

    if (count($point_ids)) {
      $points = $this->userPointsStorage->load(reset($point_ids));
    }
    else {
      $points = $this->createPoints($points_type, $entity);
    }
    return $points;
  }

  /**
   * {@inheritdoc}
   */
  public function getPoints(EntityInterface $entity, $points_type) {
    $points = $this->getPointsEntity($entity, $points_type);
    return $points->getQuantity();
  }

  /**
   * {@inheritdoc}
   */
  public function getLog(EntityInterface $entity, $points_type) {
    $points = $this->getPointsEntity($entity, $points_type);

    $vids = $this->userPointsStorage->revisionIds($points);
    $revisions = $this->userPointsStorage->loadMultipleRevisions($vids);

    $output = [];
    foreach ($revisions as $vid => $revision) {
      $output[$vid] = $revision->toArray();
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function addPoints($quantity, EntityInterface $entity, $points_type, $log = '') {
    $points = $this->getPointsEntity($entity, $points_type);

    if (empty($log)) {
      if ($quantity > 0) {
        $log = $this->formatPlural(
          $quantity,
          '@user added 1 point.',
          '@user added @quantity points.',
          [
            '@quantity' => $quantity,
            '@user' => $this->currentUser->getDisplayName(),
          ]
        );
      }
      elseif ($quantity < 0) {
        $log = $this->formatPlural(
          $quantity,
          '@user subtracted 1 point.',
          '@user subtracted @quantity points.',
          [
            '@quantity' => -$quantity,
            '@user' => $this->currentUser->getDisplayName(),
          ]
        );
      }
      else {
        $log = $this->t('@user initialized points.', [
          '@user' => $this->currentUser->getDisplayName(),
        ]);
      }
    }

    if ($quantity) {
      $points->addPoints($quantity);
      $points->setNewRevision();
      $points->setRevisionLogMessage($log);
      $points->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transferPoints($quantity, $points_type, EntityInterface $source, EntityInterface $target, $log = '') {
    if ($quantity <= 0) {
      throw new UserPointsException("Only positive quantity of points can be transferred.");
    }

    $source_points = $this->getPointsEntity($source, $points_type);

    if ($source_points->getQuantity() < $quantity) {
      throw new UserPointsException("The source entity doesn't have enough points to transfer.");
    }
    if (empty($log)) {
      $log = $this->formatPlural(
        $quantity,
        '@user transfered 1 point from @source to @target.',
        '@user transfered @quantity points from @source to @target.',
        [
          '@quantity' => $quantity,
          '@source' => $source->label(),
          '@target' => $target->label(),
          '@user' => $this->currentUser->getDisplayName(),
        ]
      );
    }

    $target_points = $this->getPointsEntity($target, $points_type);
    $target_points->setNewRevision();

    $target_points->addPoints($quantity);
    $target_points->setRevisionLogMessage($log);

    $source_points->setNewRevision();
    $source_points->setRevisionLogMessage($log);
    $source_points->addPoints(-$quantity);

    // Allow other modules to interact.
    $event = new Event();
    $event->sourcePoints = $source;
    $event->targetPoints = $target;
    $event->quantity = $quantity;
    $this->eventDispatcher->dispatch('userpoints.transfer', $event);

    $source_points->save();
    $target_points->save();
  }

  /**
   * Helper function to create a User points entity.
   */
  protected function createPoints($type, EntityInterface $entity, $log = '') {
    $points = $this->userPointsStorage->create([
      'type' => $type,
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);

    if (!$log) {
      $log = $this->t('Initial value.');
    }
    $points->setRevisionLogMessage($log);

    $points->save();

    return $points;
  }

}
