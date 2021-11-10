<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\commerce_wishlist\WishlistPurchase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for wishlist items.
 */
interface WishlistItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the parent wishlist.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface|null
   *   The wishlist, or NULL.
   */
  public function getWishlist();

  /**
   * Gets the parent wishlist ID.
   *
   * @return int|null
   *   The wishlist ID, or NULL.
   */
  public function getWishlistId();

  /**
   * Gets the purchasable entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity, or NULL.
   */
  public function getPurchasableEntity();

  /**
   * Gets the purchasable entity ID.
   *
   * @return int
   *   The purchasable entity ID.
   */
  public function getPurchasableEntityId();

  /**
   * Gets the wishlist item title.
   *
   * @return string
   *   The wishlist item title
   */
  public function getTitle();

  /**
   * Gets the wishlist item quantity.
   *
   * @return string
   *   The wishlist item quantity
   */
  public function getQuantity();

  /**
   * Sets the wishlist item quantity.
   *
   * @param string $quantity
   *   The wishlist item quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the wishlist item comment.
   *
   * @return string
   *   The wishlist item comment.
   */
  public function getComment();

  /**
   * Sets the wishlist item comment.
   *
   * @param string $comment
   *   The wishlist item comment.
   *
   * @return $this
   */
  public function setComment($comment);

  /**
   * Gets the wishlist item priority.
   *
   * @return int
   *   The wishlist item priority.
   */
  public function getPriority();

  /**
   * Sets the wishlist item priority.
   *
   * @param int $priority
   *   The wishlist item priority.
   *
   * @return $this
   */
  public function setPriority($priority);

  /**
   * Gets the purchases.
   *
   * Each object contains the order ID, quantity, and timestamp of a purchase.
   *
   * @return \Drupal\commerce_wishlist\WishlistPurchase[]
   *   The purchases.
   */
  public function getPurchases();

  /**
   * Sets the purchases.
   *
   * @param \Drupal\commerce_wishlist\WishlistPurchase[] $purchases
   *   The purchases.
   *
   * @return $this
   */
  public function setPurchases(array $purchases);

  /**
   * Adds a new purchase.
   *
   * @param \Drupal\commerce_wishlist\WishlistPurchase $purchase
   *   The purchase.
   */
  public function addPurchase(WishlistPurchase $purchase);

  /**
   * Removes a purchase.
   *
   * @param \Drupal\commerce_wishlist\WishlistPurchase $purchase
   *   The purchase.
   *
   * @return $this
   */
  public function removePurchase(WishlistPurchase $purchase);

  /**
   * Gets the purchased quantity.
   *
   * Represents the sum of all purchase quantities.
   *
   * @return string
   *   The purchased quantity.
   */
  public function getPurchasedQuantity();

  /**
   * Gets the timestamp of the last purchase.
   *
   * @return int|null
   *   The timestamp of the last purchase, or NULL if the wishlist item
   *   hasn't been purchased yet.
   */
  public function getLastPurchasedTime();

  /**
   * Gets the wishlist item creation timestamp.
   *
   * @return int
   *   The wishlist item creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the wishlist item creation timestamp.
   *
   * @param int $timestamp
   *   The wishlist item creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
