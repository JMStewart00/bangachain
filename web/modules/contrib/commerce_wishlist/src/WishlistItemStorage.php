<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the wishlist item storage.
 */
class WishlistItemStorage extends CommerceContentEntityStorage implements WishlistItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []) {
    $values += [
      'type' => $entity->getEntityTypeId(),
      'purchasable_entity' => $entity,
    ];
    return self::create($values);
  }

}
