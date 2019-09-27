<?php

namespace Drupal\userpoints\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for User points entities.
 */
class UserPointsViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
