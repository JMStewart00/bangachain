<?php

namespace Drupal\commerce_wishlist;

use Drupal\Core\Session\AccountInterface;

/**
 * Creates and loads wishlists for anonymous and authenticated users.
 *
 * @see \Drupal\commerce_wishlist\WishlistSessionInterface
 */
interface WishlistProviderInterface {

  /**
   * Creates a wishlist entity for the given user.
   *
   * @param string $wishlist_type
   *   The wishlist type ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   * @param string $name
   *   The wishlist name. Defaults to t('Wishlist').
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface
   *   The created wishlist entity.
   *
   * @throws \Drupal\commerce_wishlist\Exception\DuplicateWishlistException
   *   When a wishlist with the given criteria already exists.
   */
  public function createWishlist($wishlist_type, AccountInterface $account = NULL, $name = NULL);

  /**
   * Gets the wishlist entity for the given user.
   *
   * @param string $wishlist_type
   *   The wishlist type ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface|null
   *   The wishlist entity, or NULL if none found.
   */
  public function getWishlist($wishlist_type, AccountInterface $account = NULL);

  /**
   * Gets the wishlist entity ID for the given user.
   *
   * @param string $wishlist_type
   *   The wishlist type ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return int|null
   *   The wishlist entity ID, or NULL if none found.
   */
  public function getWishlistId($wishlist_type, AccountInterface $account = NULL);

  /**
   * Gets all wishlist entities for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface[]
   *   A list of wishlist entities.
   */
  public function getWishlists(AccountInterface $account = NULL);

  /**
   * Gets all wishlist entity ids for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return int[]
   *   A list of wishlist entity ids.
   */
  public function getWishlistIds(AccountInterface $account = NULL);

}
