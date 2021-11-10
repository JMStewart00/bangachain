<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist item event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistItemEvent extends Event {

  /**
   * The wishlist item.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlistItem;

  /**
   * Constructs a new WishlistItemEvent object.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   */
  public function __construct(WishlistItemInterface $wishlist_item) {
    $this->wishlistItem = $wishlist_item;
  }

  /**
   * Gets the wishlist item.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface
   *   Gets the wishlist item.
   */
  public function getWishlistItem() {
    return $this->wishlistItem;
  }

}
