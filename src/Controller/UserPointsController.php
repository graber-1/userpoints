<?php

namespace Drupal\userpoints\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\userpoints\Entity\UserPointsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserPointsController.
 *
 *  Returns responses for User points routes.
 */
class UserPointsController extends ControllerBase implements ContainerInjectionInterface {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new UserPointsController.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(DateFormatter $date_formatter, Renderer $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a User points revision.
   *
   * @param int $userpoints_revision
   *   The User points revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($userpoints_revision) {
    $userpoints = $this->entityTypeManager()->getStorage('userpoints')
      ->loadRevision($userpoints_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('userpoints');

    return $view_builder->view($userpoints);
  }

  /**
   * Page title callback for a User points revision.
   *
   * @param int $userpoints_revision
   *   The User points revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($userpoints_revision) {
    $userpoints = $this->entityTypeManager()->getStorage('userpoints')
      ->loadRevision($userpoints_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $userpoints->label(),
      '%date' => $this->dateFormatter->format($userpoints->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a User points.
   *
   * @param \Drupal\userpoints\Entity\UserPointsInterface $userpoints
   *   A User points object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(UserPointsInterface $userpoints) {
    $account = $this->currentUser();
    $userpoints_storage = $this->entityTypeManager()->getStorage('userpoints');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $userpoints->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all user points revisions") || $account->hasPermission('administer user points entities')));
    $delete_permission = (($account->hasPermission("delete all user points revisions") || $account->hasPermission('administer user points entities')));

    $rows = [];

    $vids = $userpoints_storage->revisionIds($userpoints);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\userpoints\UserPointsInterface $revision */
      $revision = $userpoints_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $userpoints->getRevisionId()) {
          $link = $this->l($date, new Url('entity.userpoints.revision', [
            'userpoints' => $userpoints->id(),
            'userpoints_revision' => $vid,
          ]));
        }
        else {
          $link = $userpoints->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.userpoints.revision_revert', [
                'userpoints' => $userpoints->id(),
                'userpoints_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.userpoints.revision_delete', [
                'userpoints' => $userpoints->id(),
                'userpoints_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
    }

    $build['userpoints_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
