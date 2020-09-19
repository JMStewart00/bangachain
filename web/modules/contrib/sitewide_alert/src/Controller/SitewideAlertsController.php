<?php

namespace Drupal\sitewide_alert\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;

/**
 * Class SitewideAlertsController.
 */
class SitewideAlertsController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new SitewideAlertsController.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Load.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Hello string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load() {
    $response = new CacheableJsonResponse([]);

    $sitewideAlertsJson = ['sitewideAlerts' => []];

    $sitewideAlerts = $this->getSitewideAlerts();

    $viewBuilder = $this->entityTypeManager()->getViewBuilder('sitewide_alert');

    foreach ($sitewideAlerts as $sitewideAlert) {
      $message = $viewBuilder->view($sitewideAlert);

      $sitewideAlertsJson['sitewideAlerts'][] = [
        'uuid' => $sitewideAlert->uuid(),
        'message' => $this->renderer->renderPlain($message),
        'dismissible' => $sitewideAlert->isDismissible(),
        'dismissalIgnoreBefore' => $sitewideAlert->getDismissibleIgnoreBeforeTime(),
        'styleClass' => $sitewideAlert->getStyleClass(),
        'showOnPages' => $sitewideAlert->getPagesToShowOn(),
        'negateShowOnPages' => $sitewideAlert->shouldNegatePagesToShowOn(),
      ];
    }

    // Set the response cache to be dependent on whenever sitewide alerts get updated.
    $cacheableMetadata = (new CacheableMetadata())
      ->setCacheMaxAge(30)
      ->addCacheContexts(['languages'])
      ->setCacheTags(['sitewide_alert_list']);

    $response->addCacheableDependency($cacheableMetadata);
    $response->setData($sitewideAlertsJson);

    // Prevent the browser and downstream caches from caching for more than 15 seconds.
    $response->setMaxAge(15);
    $response->setSharedMaxAge(15);

    return $response;
  }

  /**
   * Returns all active sitewide alerts.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   Array of active sitewide alerts indexed by their ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSitewideAlerts() {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface[] $sitewideAlerts */
    $sitewideAlerts = $this->entityTypeManager()->getStorage('sitewide_alert')->loadByProperties(['status' => 1]);

    // Remove any sitewide alerts that are scheduled and it is not time to show them.
    foreach ($sitewideAlerts as $id => $sitewideAlert) {
      if ($sitewideAlert->isScheduled() &&
        !$sitewideAlert->isScheduledToShowAt(new \DateTime('now'))) {
        unset($sitewideAlerts[$id]);
      }
    }

    return $sitewideAlerts;
  }

}
