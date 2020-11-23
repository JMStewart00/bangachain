<?php

namespace Drupal\bangachain_commerce_update\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class BangachainRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Define the routes we want to use the admin theme.
    $routes = [
      'view.all_variations.page_1',
    ];

    foreach ($collection->all() as $name => $route) {
      if (in_array($name, $routes)) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
