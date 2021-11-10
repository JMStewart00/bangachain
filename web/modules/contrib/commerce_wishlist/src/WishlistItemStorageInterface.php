<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for wishlist item storage.
 */
interface WishlistItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Constructs a new wishlist item using the given purchasable entity.
   *
   * The new wishlist item isn't saved.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The created wishlist item.
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []);

}
