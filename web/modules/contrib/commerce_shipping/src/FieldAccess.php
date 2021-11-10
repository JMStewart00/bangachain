<?php

namespace Drupal\commerce_shipping;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Routing\Routes;

class FieldAccess implements FieldAccessInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new FieldAccess object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL): AccessResultInterface {
    $route = $this->routeMatch->getRouteObject();
    // Only check access if this is running on JSON API routes.
    if (!$route || !$route->hasDefault(Routes::JSON_API_ROUTE_FLAG_KEY)) {
      return AccessResult::neutral();
    }
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    // We currently only support shipments.
    if ($entity_type_id !== 'commerce_shipment') {
      return AccessResult::neutral();
    }

    if ($operation === 'edit') {
      $disallowed = $this->getProtectedEditFieldNames($entity_type_id);
      return AccessResult::forbiddenIf(in_array($field_definition->getName(), $disallowed, TRUE));
    }

    return AccessResult::neutral();
  }

  /**
   * Gets protected fields that cannot be edited for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The array of field names.
   */
  protected function getProtectedEditFieldNames(string $entity_type_id): array {
    $field_names = [
      'commerce_shipment' => [
        'original_amount',
        'amount',
        'adjustments',
        // When commerce_shipping is used in combination with commerce_api,
        // the rate is applied using the order resources.
        'shipping_service',
        'shipping_method',
      ],
    ];
    return $field_names[$entity_type_id] ?? [];
  }

}
