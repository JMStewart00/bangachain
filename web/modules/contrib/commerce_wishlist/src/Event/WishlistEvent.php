<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistEvent extends Event {

  /**
   * The wishlist.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * Constructs a new WishlistEvent object.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist.
   */
  public function __construct(WishlistInterface $wishlist) {
    $this->wishlist = $wishlist;
  }

  /**
   * Gets the wishlist.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   Gets the wishlist.
   */
  public function getWishlist() {
    return $this->wishlist;
  }

}
