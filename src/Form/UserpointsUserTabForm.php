<?php

namespace Drupal\userpoints\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * User points edition tab form class.
 */
class UserpointsUserTabForm extends UserpointsEntityTabForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'userpoints_user_points_tab_form';
  }

  /**
   * The form builder function.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param Drupal\user\UserInterface $user
   *   The account for which points are edited.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {

    return $this->doBuildForm($form, $form_state, $user);
  }

}
