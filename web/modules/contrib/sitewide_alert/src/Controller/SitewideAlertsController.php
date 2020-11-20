<?php

namespace Drupal\sitewide_alert\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sitewide_alert\SitewideAlertManager;
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
   * @var \Drupal\sitewide_alert\SitewideAlertManager
   */
  private $sitewideAlertManager;

  /**
   * Constructs a new SitewideAlertsController.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\sitewide_alert\SitewideAlertManager $sitewideAlertManager
   *   The sitewide alert manager.
   */
  public function __construct(Renderer $renderer, SitewideAlertManager $sitewideAlertManager) {
    $this->renderer = $renderer;
    $this->sitewideAlertManager = $sitewideAlertManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('sitewide_alert.sitewide_alert_manager')
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

    $sitewideAlerts = $this->sitewideAlertManager->activeVisibleSitewideAlerts();

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

    // Set the date this response expires so that Drupal's Page Cache will
    // expire this response when the next scheduled alert will be removed.
    // This is needed because Page Cache ignores max age as it does not respect
    // the cache max age. Note that the cache tags will still invalidate this
    // response in the case that new sitewide alerts are added or changed.
    // See Drupal\page_cache\StackMiddleware:storeResponse().
    if ($expireDate = $this->sitewideAlertManager->nextScheduledChange()) {
      $response->setExpires($expireDate->getPhpDateTime());
    }

    // Prevent the browser and downstream caches from caching for more than 15 seconds.
    $response->setMaxAge(15);
    $response->setSharedMaxAge(15);

    return $response;
  }

}
