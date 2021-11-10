<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\user\UserInterface;

/**
 * Handles assigning anonymous wishlists to user accounts.
 *
 * Invoked on login.
 */
interface WishlistAssignmentInterface {

  /**
   * Assigns the anonymous wishlist to the given user account.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist wishlist.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  public function assign(WishlistInterface $wishlist, UserInterface $account);

  /**
   * Assigns multiple anonymous wishlists to the given user account.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface[] $wishlists
   *   The wishlists.
   * @param \Drupal\user\UserInterface $account
   *   The account.
   */
  public function assignMultiple(array $wishlists, UserInterface $account);

}
