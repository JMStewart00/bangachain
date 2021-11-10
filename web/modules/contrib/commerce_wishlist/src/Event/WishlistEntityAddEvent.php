<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist entity add event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistEntityAddEvent extends Event {

  /**
   * The wishlist entity.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * The added entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * The quantity.
   *
   * @var float
   */
  protected $quantity;

  /**
   * The destination wishlist item.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   */
  protected $wishlistItem;

  /**
   * Constructs a new WishlistWishlistItemEvent.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The added entity.
   * @param float $quantity
   *   The quantity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The destination wishlist item.
   */
  public function __construct(WishlistInterface $wishlist, PurchasableEntityInterface $entity, $quantity, WishlistItemInterface $wishlist_item) {
    $this->wishlist = $wishlist;
    $this->entity = $entity;
    $this->quantity = $quantity;
    $this->wishlistItem = $wishlist_item;
  }

  /**
   * Gets the wishlist entity.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   The wishlist entity.
   */
  public function getWishlist() {
    return $this->wishlist;
  }

  /**
   * Gets the added entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface
   *   The added entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the quantity.
   *
   * @return float
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the destination wishlist item.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   The destination wishlist item.
   */
  public function getWishlistItem() {
    return $this->wishlistItem;
  }

}
