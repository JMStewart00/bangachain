<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for shipments.
 *
 * Shipments are always managed in the scope of their parent (the order),
 * so they have a simplified permission set, and rely on parent access
 * when possible:
 * - An shipment can be viewed if the parent order can be viewed.
 * - An shipment can be created, updated or deleted if the user has the
 *   "manage $bundle shipments" permission.
 *
 * The "administer commerce_shipment" permission is also respected.
 */
class ShipmentAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    assert($entity instanceof ShipmentInterface);
    $order = $entity->getOrder();
    if (!$order) {
      // The shipment is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    $bundle = $entity->bundle();
    $result = AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_shipment");
    if ($result->isNeutral() && ($operation === 'view' || $operation === 'update')) {
      $result = $order->access($operation, $account, TRUE);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent order.
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle commerce_shipment",
    ], 'OR');
  }

}
