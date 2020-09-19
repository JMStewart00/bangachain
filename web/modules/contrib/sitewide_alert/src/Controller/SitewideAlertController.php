<?php

namespace Drupal\sitewide_alert\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\sitewide_alert\Entity\SitewideAlertInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SitewideAlertController.
 *
 *  Returns responses for Sitewide Alert routes.
 */
class SitewideAlertController extends ControllerBase implements ContainerInjectionInterface {


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
   * Constructs a new SitewideAlertController.
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
   * Displays a Sitewide Alert revision.
   *
   * @param int $sitewide_alert_revision
   *   The Sitewide Alert revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($sitewide_alert_revision) {
    $sitewide_alert = $this->entityTypeManager()->getStorage('sitewide_alert')
      ->loadRevision($sitewide_alert_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('sitewide_alert');

    return $view_builder->view($sitewide_alert);
  }

  /**
   * Page title callback for a Sitewide Alert revision.
   *
   * @param int $sitewide_alert_revision
   *   The Sitewide Alert revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($sitewide_alert_revision) {
    $sitewide_alert = $this->entityTypeManager()->getStorage('sitewide_alert')
      ->loadRevision($sitewide_alert_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $sitewide_alert->label(),
      '%date' => $this->dateFormatter->format($sitewide_alert->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Sitewide Alert.
   *
   * @param \Drupal\sitewide_alert\Entity\SitewideAlertInterface $sitewide_alert
   *   A Sitewide Alert object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(SitewideAlertInterface $sitewide_alert) {
    $account = $this->currentUser();
    $sitewide_alert_storage = $this->entityTypeManager()->getStorage('sitewide_alert');

    $langcode = $sitewide_alert->language()->getId();
    $langname = $sitewide_alert->language()->getName();
    $languages = $sitewide_alert->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $sitewide_alert->label()]) : $this->t('Revisions for %title', ['%title' => $sitewide_alert->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all sitewide alert revisions") || $account->hasPermission('administer sitewide alert entities')));
    $delete_permission = (($account->hasPermission("delete all sitewide alert revisions") || $account->hasPermission('administer sitewide alert entities')));

    $rows = [];

    $vids = $sitewide_alert_storage->revisionIds($sitewide_alert);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\sitewide_alert\SitewideAlertInterface $revision */
      $revision = $sitewide_alert_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $sitewide_alert->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.sitewide_alert.revision', [
            'sitewide_alert' => $sitewide_alert->id(),
            'sitewide_alert_revision' => $vid,
          ]));
        }
        else {
          $link = $sitewide_alert->toLink($date)->toString();
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
              'url' => $has_translations ?
              Url::fromRoute('entity.sitewide_alert.translation_revert', [
                'sitewide_alert' => $sitewide_alert->id(),
                'sitewide_alert_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.sitewide_alert.revision_revert', [
                'sitewide_alert' => $sitewide_alert->id(),
                'sitewide_alert_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.sitewide_alert.revision_delete', [
                'sitewide_alert' => $sitewide_alert->id(),
                'sitewide_alert_revision' => $vid,
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
    }

    $build['sitewide_alert_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
