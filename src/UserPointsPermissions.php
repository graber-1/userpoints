<?php

namespace Drupal\userpoints;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\userpoints\Entity\UserPointsType;

/**
 * Provides dynamic permissions for User points of different types.
 *
 * @ingroup userpoints
 */
class UserPointsPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The UserPoints by bundle permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function generatePermissions() {
    $perms = [];

    foreach (UserPointsType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\userpoints\Entity\UserPointsType $type
   *   The UserPoints type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(UserPointsType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "manage $type_id points" => [
        'title' => $this->t('Manage all %type_name points', $type_params),
      ],
      "view $type_id points" => [
        'title' => $this->t('View %type_name points', $type_params),
      ],
      "view own $type_id points" => [
        'title' => $this->t('View own %type_name points', $type_params),
      ],
    ];
  }

}
