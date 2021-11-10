<?php

namespace Drupal\commerce_wishlist\Event;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the wishlist assign event.
 *
 * @see \Drupal\commerce_wishlist\Event\WishlistEvents
 */
class WishlistAssignEvent extends Event {

  /**
   * The wishlist entity.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Constructs a new WishlistAssignEvent.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist entity.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  public function __construct(WishlistInterface $wishlist, UserInterface $account) {
    $this->wishlist = $wishlist;
    $this->account = $account;
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
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
  }

}
