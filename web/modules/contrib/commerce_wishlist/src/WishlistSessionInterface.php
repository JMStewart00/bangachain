<?php

namespace Drupal\commerce_wishlist;

/**
 * Stores wishlist ids in the anonymous user's session.
 *
 * Allows the system to keep track of which wishlist entities belong to the
 * anonymous user. The session is the only available storage in this case, since
 * all anonymous users share the same user id (0).
 *
 * @see \Drupal\commerce_wishlist\WishlistProviderInterface
 */
interface WishlistSessionInterface {

  /**
   * Gets all wishlist ids from the session.
   *
   * @return int[]
   *   A list of wishlist ids.
   */
  public function getWishlistIds();

  /**
   * Adds the given wishlist ID to the session.
   *
   * @param int $wishlist_id
   *   The wishlist ID.
   */
  public function addWishlistId($wishlist_id);

  /**
   * Checks whether the given wishlist ID exists in the session.
   *
   * @param int $wishlist_id
   *   The wishlist ID.
   *
   * @return bool
   *   TRUE if the given wishlist ID exists in the session, FALSE otherwise.
   */
  public function hasWishlistId($wishlist_id);

  /**
   * Deletes the given wishlist ID from the session.
   *
   * @param int $wishlist_id
   *   The wishlist ID.
   */
  public function deleteWishlistId($wishlist_id);

}
