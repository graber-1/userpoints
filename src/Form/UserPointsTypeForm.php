<?php

namespace Drupal\userpoints\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserPointsTypeForm.
 */
class UserPointsTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $userpoints_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $userpoints_type->label(),
      '#description' => $this->t("Label for the User points type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $userpoints_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\userpoints\Entity\UserPointsType::load',
      ],
      '#disabled' => !$userpoints_type->isNew(),
    ];

    $form['initial_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial value'),
      '#default_value' => isset($userpoints_type->initial_value) ? $userpoints_type->initial_value : 0,
      '#description' => $this->t("Initial value for the user points type."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $userpoints_type = $this->entity;
    $status = $userpoints_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label User points type.', [
          '%label' => $userpoints_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label User points type.', [
          '%label' => $userpoints_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($userpoints_type->toUrl('collection'));
  }

}
