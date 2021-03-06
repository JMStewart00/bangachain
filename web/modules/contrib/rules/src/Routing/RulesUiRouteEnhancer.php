<?php

namespace Drupal\rules\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\rules\Ui\RulesUiManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Enhances routes with the specified RulesUI.
 *
 * Routes have the plugin ID of the active RulesUI instance set on the _rules_ui
 * option. Based upon that information, this enhances adds the following
 * parameters to the routes:
 * - rules_ui_handler: The RulesUI handler, as specified by the plugin.
 * - rules_component: The rules component being edited, as provided by the
 *   handler.
 */
class RulesUiRouteEnhancer implements EnhancerInterface {

  /**
   * The rules_ui plugin manager.
   *
   * @var \Drupal\rules\Ui\RulesUiManagerInterface
   */
  protected $rulesUiManager;

  /**
   * Constructs the object.
   *
   * @param \Drupal\rules\Ui\RulesUiManagerInterface $rules_ui_manager
   *   The rules_ui plugin manager.
   */
  public function __construct(RulesUiManagerInterface $rules_ui_manager) {
    $this->rulesUiManager = $rules_ui_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if ($plugin_id = $route->getOption('_rules_ui')) {
      $defaults['rules_ui_handler'] = $this->rulesUiManager->createInstance($plugin_id);
    }
    return $defaults;
  }

}
