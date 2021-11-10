<?php

namespace Drupal\commerce_wishlist\Plugin\Field\FieldType;

use Drupal\commerce_wishlist\WishlistPurchase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Represents a list of wishlist purchase field items.
 */
interface WishlistPurchaseItemListInterface extends FieldItemListInterface {

  /**
   * Gets the purchase value objects from the field list.
   *
   * @return \Drupal\commerce_wishlist\WishlistPurchase[]
   *   The purchases.
   */
  public function getPurchases();

  /**
   * Removes the matching purchase.
   *
   * @param \Drupal\commerce_wishlist\WishlistPurchase $purchase
   *   The purchase.
   *
   * @return $this
   */
  public function removePurchase(WishlistPurchase $purchase);

}
