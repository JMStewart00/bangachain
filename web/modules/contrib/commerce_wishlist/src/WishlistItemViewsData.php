<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Provides views data for wishlist items.
 */
class WishlistItemViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['commerce_wishlist_item']['edit_quantity']['field'] = [
      'title' => t('Wishlist quantity text field'),
      'help' => t('Adds a text field for editing the quantity.'),
      'id' => 'commerce_wishlist_item_edit_quantity',
    ];

    $data['commerce_wishlist_item']['remove_button']['field'] = [
      'title' => t('Remove button'),
      'help' => t('Adds a button for removing the wishlist item.'),
      'id' => 'commerce_wishlist_item_remove_button',
    ];

    $data['commerce_wishlist_item']['move_to_cart']['field'] = [
      'title' => t('Move/copy to cart button'),
      'help' => t('Adds a button for moving or copying the wishlist item to the shopping cart.'),
      'id' => 'commerce_wishlist_item_move_to_cart',
    ];

    return $data;
  }

}
