<?php

namespace Drupal\commerce_wishlist;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for wishlist items.
 *
 * The "administer commerce_wishlist" permission is also respected.
 */
class WishlistItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $entity */
    $wishlist = $entity->getWishlist();
    if (!$wishlist) {
      // The wishlist item is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation == 'view') {
      $result = $wishlist->access('view', $account, TRUE);
    }
    else {
      $bundle = $entity->bundle();
      $result = AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_wishlist_item");
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent wishlist.
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle commerce_wishlist_item",
    ], 'OR');
  }

}
