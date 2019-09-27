<?php

namespace Drupal\userpoints\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\userpoints\Service\UserPointsServiceInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * User points edition tab form class.
 */
class UserpointsUserTabForm extends FormBase {

  /**
   * The userpoints service.
   *
   * @var \Drupal\userpoints\Service\UserPointsServiceInterface
   */
  protected $userpointsService;

  /**
   * Userpoints bundles.
   *
   * @var array
   */
  protected $bundles;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new UserpointsUserEditTabForm object.
   *
   * @param \Drupal\userpoints\Service\UserPointsServiceInterface $userpointsService
   *   The userpoints service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle info service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user instance.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    UserPointsServiceInterface $userpointsService,
    EntityTypeBundleInfoInterface $bundleInfo,
    AccountInterface $currentUser,
    DateFormatter $dateFormatter
  ) {
    $this->userpointsService = $userpointsService;
    $this->currentUser = $currentUser;
    $this->bundles = $bundleInfo->getBundleInfo('userpoints');
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('userpoints.points'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'userpoints_user_points_tab_form';
  }

  /**
   * Helper function to get view and edit access for different point types.
   *
   * @param Drupal\user\UserInterface $user
   *   The account for which points are edited.
   */
  protected function getTypesAccess(UserInterface $user) {
    $output = [
      'type_options' => [],
      'edit_access' => [],
      'view_access' => [],
    ];

    foreach ($this->bundles as $bundle_name => $bundle_info) {
      $output['edit_access'][$bundle_name] = FALSE;
      $output['view_access'][$bundle_name] = FALSE;

      // Check for edit access.
      if ($this->currentUser->hasPermission('manage $bundle_name points') || $this->currentUser->hasPermission('manage all points')) {
        $output['edit_access'][$bundle_name] = TRUE;
      }

      // Check for view access.
      if ($this->currentUser->hasPermission('view $bundle_name points') || $this->currentUser->hasPermission('view all points')) {
        $output['view_access'][$bundle_name] = TRUE;
      }
      elseif ($this->currentUser->id() === $user->id() && $this->currentUser->hasPermission('view own $bundle_name points')) {
        $output['view_access'][$bundle_name] = TRUE;
      }

      // Type option is added when user has at least one of edit and view access.
      if ($output['edit_access'][$bundle_name] || $output['view_access'][$bundle_name]) {
        $output['type_options'][$bundle_name] = $bundle_info['label'];
      }
    }

    return $output;
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

    $access_data = $this->getTypesAccess($user);

    if (count($access_data['type_options']) === 1) {
      $keys = array_keys($access_data['type_options']);
      $selected_type = $keys[1];
      $form['points_form_container'] = [];
    }
    else {
      $ajax_id = 'points-form-container-wrapper';

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Points type'),
        '#options' => ['' => $this->t('-- Select points type --')] + $access_data['type_options'],
        '#ajax' => [
          'wrapper' => $ajax_id,
          'callback' => [get_called_class(), 'ajaxForm'],
        ],
      ];
      $selected_type = $form_state->getValue('type', '');

      $form['points_form_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => $ajax_id,
        ],
      ];
    }

    if (!empty($selected_type)) {
      $element = &$form['points_form_container'];

      if ($access_data['edit_access'][$selected_type]) {
        $element['add_points'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Add / remove @type points', [
            '@type' => $access_data['type_options'][$selected_type],
          ]),
        ];
        $element['add_points']['quantity'] = [
          '#type' => 'number',
          '#title' => $this->t('Quantity'),
          '#description' => $this->t('The amount of points to add (enter negative value to subtract)'),
          '#default_value' => 0,
        ];
        $element['add_points']['log'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Log message'),
          '#description' => $this->t('Leave empty for a default log message.'),
        ];
        $element['add_points']['add'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add / subtract'),
          '#ajax' => [
            'wrapper' => $ajax_id,
            'callback' => [get_called_class(), 'ajaxForm'],
          ],
        ];
      }

      if ($access_data['view_access'][$selected_type]) {
        $element['log'] = [
          '#theme' => 'table',
          '#header' => [
            'quantity' => $this->t('Quantity'),
            'log' => $this->t('Log'),
            'user' => $this->t('Changed by user ID'),
            'created' => $this->t('Created'),
          ],
          '#rows' => [],
          '#empty' => $this->t('There are no points operations for this entity yet.')
        ];
        $log = $this->userpointsService->getLog($user, $selected_type);
        foreach (array_reverse($log) as $vid => $item) {
          // Create flat structure for convenience.
          foreach ([
            'quantity' => 'value',
            'revision_log_message' => 'value',
            'revision_user' => 'target_id',
            'revision_created' => 'value',
          ] as $field_name => $column) {
            $item[$field_name] = $item[$field_name][0][$column];
          }

          $element['log']['#rows'][$vid] = [
            'quantity' => $item['quantity'],
            'log' => $item['revision_log_message'],
            'user' => $item['revision_user'],
            'created' => $this->dateFormatter->format($item['revision_created'], 'short'),
          ];
        }
      }
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function ajaxForm(array $form, FormStateInterface $form_state) {
    return $form['points_form_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#parents'][0] === 'add') {
      if (!$form_state->getValue('quantity')) {
        $form_state->setError($form['points_form_container']['add_points']['quantity'], $this->t('Please enter a number other than zero.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $values = $form_state->getValues();
    $user = $form_state->getBuildInfo()['args'][0];
    $this->userpointsService->addPoints(
      $values['quantity'],
      $user,
      $values['type'],
      $values['log']
    );

    if ($values['quantity'] > 0) {
      $this->messenger()->addStatus($this->t('@count points added.', [
        '@count' => $values['quantity'],
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('@count points subtracted.', [
        '@count' => $values['quantity'],
      ]));
    }
  }

}
