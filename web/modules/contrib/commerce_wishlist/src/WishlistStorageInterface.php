<?php

namespace Drupal\commerce_wishlist;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for wishlist storage.
 */
interface WishlistStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the wishlist for the given code.
   *
   * @param string $code
   *   The code.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface|null
   *   The wishlist, or NULL if none found.
   */
  public function loadByCode($code);

  /**
   * Loads the default wishlist for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   * @param string $wishlist_type_id
   *   The wishlist type ID.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistInterface|null
   *   The default wishlist for the given, if known.
   */
  public function loadDefaultByUser(AccountInterface $account, $wishlist_type_id);

  /**
   * Loads the given user's wishlists.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   * @param string $wishlist_type_id
   *   The wishlist type ID.
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *   The wishlists, ordered by ID, descending.
   */
  public function loadMultipleByUser(AccountInterface $account, $wishlist_type_id);

}
