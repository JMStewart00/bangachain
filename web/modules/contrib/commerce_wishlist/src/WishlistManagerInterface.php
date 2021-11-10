<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;

/**
 * Manages the wishlist and its wishlist items.
 */
interface WishlistManagerInterface {

  /**
   * Empties the given wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   */
  public function emptyWishlist(WishlistInterface $wishlist, $save_wishlist = TRUE);

  /**
   * Adds the given purchasable entity to the given wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param bool $combine
   *   Whether the wishlist item should be combined with an existing matching
   *   one.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The saved wishlist item.
   */
  public function addEntity(WishlistInterface $wishlist, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_wishlist = TRUE);

  /**
   * Merges the source wishlist into the target wishlist.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $source
   *   The source wishlist to merge.
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $target
   *   The target wishlist.
   * @param bool $save
   *   Save wishlist.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   The saved or modified wishlist.
   */
  public function merge(WishlistInterface $source, WishlistInterface $target, $save = TRUE);

  /**
   * Removes the given wishlist item from the wishlist entity.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param bool $save_wishlist
   *   Whether the wishlist should be saved after the operation.
   */
  public function removeWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE);

}
