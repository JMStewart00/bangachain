<?php

namespace Drupal\commerce_wishlist\Plugin\Field\FieldType;

use Drupal\commerce_wishlist\WishlistPurchase;
use Drupal\Core\Field\FieldItemList;

/**
 * Provides the item list class for the wishlist purchase field type.
 */
class WishlistPurchaseItemList extends FieldItemList implements WishlistPurchaseItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function getPurchases() {
    $purchases = [];
    /** @var \Drupal\commerce_wishlist\Plugin\Field\FieldType\WishlistPurchaseItem $field_item */
    foreach ($this->list as $key => $field_item) {
      if (!$field_item->isEmpty()) {
        $purchases[$key] = $field_item->toPurchase();
      }
    }
    return $purchases;
  }

  /**
   * {@inheritdoc}
   */
  public function removePurchase(WishlistPurchase $purchase) {
    /** @var \Drupal\commerce_wishlist\Plugin\Field\FieldType\WishlistPurchaseItem $field_item */
    foreach ($this->list as $key => $field_item) {
      if ($purchase == $field_item->toPurchase()) {
        $this->removeItem($key);
      }
    }
  }

}
