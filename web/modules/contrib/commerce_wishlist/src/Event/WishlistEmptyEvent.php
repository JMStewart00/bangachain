<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist empty event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistEmptyEvent extends Event {

  /**
   * The wishlist entity.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * The removed wishlist items.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface[]
   */
  protected $wishlistItems;

  /**
   * Constructs a new WishlistEmptyEvent.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface[] $wishlist_items
   *   The removed wishlist items.
   */
  public function __construct(WishlistInterface $wishlist, array $wishlist_items) {
    $this->wishlist = $wishlist;
    $this->wishlistItems = $wishlist_items;
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
   * Gets the removed wishlist items.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface[]
   *   The removed wishlist items.
   */
  public function getItems() {
    return $this->wishlistItems;
  }

}
