<?php

namespace Drupal\userpoints\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class UserpointsRoutes implements ContainerInjectionInterface {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UserpointsRoutes object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *   The stream wrapper manager service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->config = $configFactory->get('userpoints.settings');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    // Generate image derivatives of publicly available files. If clean URLs are
    // disabled image derivatives will always be served through the menu system.
    // If clean URLs are enabled and the image derivative already exists, PHP
    // will be bypassed.
    $bundle_info = $this->config->get('userpoints_ui_bundles');
    if (empty($bundle_info)) {
      $bundle_info = [];
    }
    foreach ($bundle_info as $entity_type_id => $bundles) {
      $links = $this->entityTypeManager->getDefinition($entity_type_id)->get('links');
      if (isset($links['canonical'])) {
        $classname = '\Userpoints' . lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $entity_type_id)))) . 'TabForm';
        $routes['entity.' . $entity_type_id . '.userpoints'] = new Route(
          $links['canonical'] . '/userpoints',
          [
            '_title' => 'Points',
            '_form' => '\Drupal\userpoints\Form' . $classname,
          ],
          [
            '_access_' . $entity_type_id . '_points_tab' => 'TRUE',
            $entity_type_id => '\d+',
          ],
          [
            'parameters' => [
              $entity_type_id => [
                'type' => 'entity:' . $entity_type_id,
              ],
            ],
          ]
        );
      }
    }
    return $routes;
  }

}
