<?php

namespace Drupal\sitewide_alert\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Disable access to field UI for Sitewide Alerts.
    // Currently adding and configuring fields has little effect.
    $sitewideAlertFieldUiRoutesNames = [
      'entity.field_config.sitewide_alert_field_edit_form',
      'entity.field_config.sitewide_alert_storage_edit_form',
      'entity.field_config.sitewide_alert_storage_edit_form',
      'entity.sitewide_alert.field_ui_fields',
      'field_ui.field_storage_config_add_sitewide_alert',
      'entity.entity_form_display.sitewide_alert.default',
      'entity.entity_form_display.sitewide_alert.form_mode',
      'entity.entity_view_display.sitewide_alert.default',
      'entity.entity_view_display.sitewide_alert.view_mode',
    ];

    foreach ($sitewideAlertFieldUiRoutesNames as $routeName) {
      if ($route = $collection->get($routeName)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -101];
    return $events;
  }

}
