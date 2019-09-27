<?php

namespace Drupal\userpoints\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the User points entity.
 *
 * @ingroup userpoints
 *
 * @ContentEntityType(
 *   id = "userpoints",
 *   label = @Translation("User points"),
 *   bundle_label = @Translation("User points type"),
 *   handlers = {
 *     "storage" = "Drupal\userpoints\UserPointsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\userpoints\Entity\UserPointsViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\userpoints\Form\UserPointsForm",
 *       "add" = "Drupal\userpoints\Form\UserPointsForm",
 *       "edit" = "Drupal\userpoints\Form\UserPointsForm",
 *       "delete" = "Drupal\userpoints\Form\UserPointsDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\userpoints\UserPointsHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\userpoints\UserPointsAccessControlHandler",
 *   },
 *   base_table = "userpoints",
 *   revision_table = "userpoints_revision",
 *   revision_data_table = "userpoints_field_revision",
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer userpoints",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/userpoints/points/{userpoints}",
 *     "add-page" = "/admin/structure/userpoints/points/add",
 *     "add-form" = "/admin/structure/userpoints/points/add/{userpoints_type}",
 *     "edit-form" = "/admin/structure/userpoints/points/{userpoints}/edit",
 *     "delete-form" = "/admin/structure/userpoints/points/{userpoints}/delete",
 *     "version-history" = "/admin/structure/userpoints/points/{userpoints}/revisions",
 *     "revision" = "/admin/structure/userpoints/points/{userpoints}/revisions/{userpoints_revision}/view",
 *     "revision_revert" = "/admin/structure/userpoints/points/{userpoints}/revisions/{userpoints_revision}/revert",
 *     "revision_delete" = "/admin/structure/userpoints/points/{userpoints}/revisions/{userpoints_revision}/delete",
 *   },
 *   bundle_entity_type = "userpoints_type",
 *   field_ui_base_route = "entity.userpoints_type.edit_form"
 * )
 */
class UserPoints extends ContentEntityBase implements UserPointsInterface {

  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('userpoints');

    $entity = \Drupal::service('entity_type.manager')->getStorage($this->entity_type_id->value)->load($this->entity_id->value);

    return t('@type type points for @entity', [
      '@type' => $bundles[$this->bundle()]['label'],
      '@entity' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // Apply initial value of the points entity.
    if (!isset($values['quantity'])) {
      $type = \Drupal::service('entity_type.manager')->getStorage('userpoints_type')->load($values['type']);
      if ($type) {
        $values['quantity'] = $type->initial_value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly,
    // make the current user the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId(\Drupal::service('current_user')->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function addPoints($quantity) {
    $this->quantity->value += $quantity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the revision metadata fields.
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Owner entity type ID'))
      ->setDescription(t('The entity type ID of the entity that holds points.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRevisionable(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'))
      ->setDescription(t('The ID of the entity that holds points.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Points quantity'))
      ->setDescription(t('Number of points.'))
      ->setDefaultValue(0)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_float',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
