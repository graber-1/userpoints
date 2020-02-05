<?php

namespace Drupal\userpoints\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserPointsSettingsForm.
 *
 * @ingroup userpoints
 */
class UserPointsSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'userpoints.settings';

  const SUPPORTED_ENTITY_TYPES = [
    'user',
    'node',
  ];

  /**
   * The entity type bundle info service.
   *
   * @var array
   */
  protected $bundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new UserPointsSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configFactory);
    $this->bundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'userpoints_settings';
  }

  /**
   * Defines the settings form for User points entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::CONFIG_NAME);

    $form['userpoints_ui_bundles'] = ['#tree' => TRUE];
    $bundle_defaults = $config->get('userpoints_ui_bundles', []);
    foreach (static::SUPPORTED_ENTITY_TYPES as $entity_type_id) {
      $bundle_info = $this->bundleInfo->getBundleInfo($entity_type_id);

      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $form['userpoints_ui_bundles'][$entity_type_id] = [
        '#type' => 'details',
        '#open' => 'FALSE',
        '#title' => $definition->getLabel(),
      ];

      foreach ($bundle_info as $bundle_id => $data) {
        $form['userpoints_ui_bundles'][$entity_type_id][$bundle_id] = [
          '#type' => 'checkbox',
          '#title' => $data['label'],
          '#default_value' => !empty($bundle_defaults[$entity_type_id][$bundle_id]),
        ];
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);
    $userpoints_ui_bundles = $form_state->getValue('userpoints_ui_bundles');
    foreach ($userpoints_ui_bundles as $entity_type_id => &$bundles) {
      $bundles = array_filter($bundles);
      if (empty($bundles)) {
        unset($userpoints_ui_bundles[$entity_type_id]);
      }
    }

    $config->set('userpoints_ui_bundles', $userpoints_ui_bundles);

    $config->save();

    $this->messenger()->addStatus($this->t('Please rebuild caches in order for those settings to take effect.'));
    parent::submitForm($form, $form_state);
  }

}
