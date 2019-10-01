<?php

namespace Drupal\userpoints\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\userpoints\Service\UserPointsServiceInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\userpoints\Exception\UserPointsException;
use Exception;

/**
 * Provides a resource to handle bookings.
 *
 * @RestResource(
 *   id = "userpoints_rest_resource",
 *   label = @Translation("Userpoints rest resource"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/userpoints"
 *   }
 * )
 */
class UserpointsRestResource extends ResourceBase {

  // Method-specific required parameters.
  const REQUIRED_PARAMS = [
    'add' => ['quantity'],
    'transfer' => ['quantity', 'target_entity_id'],
  ];

  /**
   * The current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Entity Type Manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The userpoints service.
   *
   * @var \Drupal\userpoints\Service\UserPointsServiceInterface
   */
  protected $userpointsService;

  /**
   * Constructs a new BookingRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\userpoints\Service\UserPointsServiceInterface $userpointsService
   *   The userpoints service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entityTypeManager,
    UserPointsServiceInterface $userpointsService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
    $this->userpointsService = $userpointsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('userpoints'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('userpoints.points')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRouteRequirements($method) {
    // We check access for every operation separately so this route itself
    // can be accessed by all.
    return [
      '_access' => 'TRUE',
    ];
  }

  /**
   * Router function really.
   *
   * @param array $data
   *   The request data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(array $data) {
    if (!isset($data['op'])) {
      throw new BadRequestHttpException('Operation "op" parameter needs to be specified.');
    }

    $method = $data['op'] . 'Operation';
    if (method_exists($this, $method)) {
      // Merge in defaults.
      $data += [
        'entity_type_id' => 'user',
        'target_entity_type_id' => 'user',
      ];

      $this->checkAccess($data);
      $this->validateInput($data);
      $output = $this->{$method}($data);
      if (in_array($data['op'], ['add', 'transfer'], TRUE)) {
        return new ModifiedResourceResponse($output);
      }
      return new ResourceResponse($output);
    }
    else {
      throw new BadRequestHttpException('Invalid operation specified in "op" parameter.');
    }
  }

  /**
   * Input validator for all operations.
   *
   * @param array $data
   *   The request data.
   */
  protected function validateInput(array &$data) {
    $required = ['entity_id', 'type'];
    if (array_key_exists($data['op'], self::REQUIRED_PARAMS)) {
      $required += self::REQUIRED_PARAMS[$data['op']];
    }

    $missing = [];
    foreach ($required as $param) {
      if (!isset($data[$param])) {
        $missing[] = $param;
      }
    }

    if (!empty($missing)) {
      throw new BadRequestHttpException(sprintf('Missing request parameters: %s.', implode(', ', $missing)));
    }

    if (!isset($data['entity']) || !($data['entity'] instanceof EntityInterface)) {
      try {
        $data['entity'] = $this->entityTypeManager->getStorage($data['entity_type_id'])->load($data['entity_id']);
      }
      catch (Exception $e) {
        throw new BadRequestHttpException($e->getMessage());
      }
      if (!$data['entity']) {
        throw new BadRequestHttpException('Entity doesn\'t exist or cannot be loaded.');
      }
    }

    // Additional validation for the transfer method.
    if ($data['op'] === 'transfer') {
      try {
        $data['target_entity'] = $this->entityTypeManager->getStorage($data['target_entity_type_id'])->load($data['target_entity_id']);
      }
      catch (Exception $e) {
        throw new BadRequestHttpException($e->getMessage());
      }
      if (!$data['target_entity']) {
        throw new BadRequestHttpException('Target entity doesn\'t exist or cannot be loaded.');
      }
    }
  }

  /**
   * Access checker function.
   *
   * @param array $data
   *   The request data.
   */
  protected function checkAccess(array &$data) {
    $access = FALSE;

    if (isset($data['type'])) {
      switch ($data['op']) {
        case 'add':
        case 'transfer':
          if ($this->currentUser->hasPermission('manage all points')) {
            $access = TRUE;
          }
          elseif ($this->currentUser->hasPermission("manage {$data['type']} points")) {
            $access = TRUE;
          }
          break;

        case 'getQuantity':
        case 'getLog':
          if ($this->currentUser->hasPermission('view all points')) {
            $access = TRUE;
          }
          elseif ($this->currentUser->hasPermission("view {$data['type']} points")) {
            $access = TRUE;
          }
          else {
            if (isset($data['entity_type_id']) && isset($data['entity_id'])) {
              try {
                $data['entity'] = $this->entityTypeManager->getStorage($data['entity_type_id'])->load($data['entity_id']);
                if ($data['entity']->getEntityTypeId() === 'user' && $data['entity']->id() === $this->currentUser->id() && $this->currentUser->hasPermission("view own {$data['type']} points")) {
                  $access = TRUE;
                }
              }
              catch (Exception $e) {
                // Just do nothing, access will not be granted.
              }
            }
          }
          break;

      }
    }

    if (!$access) {
      throw new AccessDeniedHttpException(sprintf('Access denied.'));
    }
  }

  /**
   * Get current number of points.
   *
   * @param array $data
   *   The request data.
   */
  protected function getQuantityOperation(array $data) {
    return $this->userpointsService->getPoints($data['entity'], $data['type']);
  }

  /**
   * Get log.
   *
   * @param array $data
   *   The request data.
   */
  protected function getLogOperation(array $data) {
    return $this->userpointsService->getLog($data['entity'], $data['type']);
  }

  /**
   * Add / subtract user points.
   */
  protected function addOperation($data) {
    try {
      if (!isset($data['log'])) {
        $data['log'] = '';
      }
      $this->userpointsService->addPoints($data['quantity'], $data['type'], $data['entity'], $data['log']);
    }
    catch (UserPointsException $e) {
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * Transfer user points.
   */
  protected function transferOperation($data) {
    try {
      if (!isset($data['log'])) {
        $data['log'] = '';
      }
      $this->userpointsService->transferPoints($data['quantity'], $data['type'], $data['entity'], $data['target_entity'], $data['log']);
    }
    catch (UserPointsException $e) {
      throw new BadRequestHttpException($e->getMessage());
    }
  }

}
