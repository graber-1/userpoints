<?php

namespace Drupal\userpoints\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the User points type entity.
 *
 * @ConfigEntityType(
 *   id = "userpoints_type",
 *   label = @Translation("User points type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\userpoints\UserPointsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\userpoints\Form\UserPointsTypeForm",
 *       "edit" = "Drupal\userpoints\Form\UserPointsTypeForm",
 *       "delete" = "Drupal\userpoints\Form\UserPointsTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\userpoints\UserPointsTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "userpoints_type",
 *   admin_permission = "administer userpoints",
 *   bundle_of = "userpoints",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/userpoints/{userpoints_type}",
 *     "add-form" = "/admin/structure/userpoints/add",
 *     "edit-form" = "/admin/structure/userpoints/{userpoints_type}/edit",
 *     "delete-form" = "/admin/structure/userpoints/{userpoints_type}/delete",
 *     "collection" = "/admin/structure/userpoints"
 *   }
 * )
 */
class UserPointsType extends ConfigEntityBundleBase implements UserPointsTypeInterface {

  /**
   * The User points type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User points type label.
   *
   * @var string
   */
  protected $label;

}
