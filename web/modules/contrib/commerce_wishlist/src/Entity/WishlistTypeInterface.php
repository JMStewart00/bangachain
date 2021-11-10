<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for wishlist types.
 */
interface WishlistTypeInterface extends ConfigEntityInterface {

  /**
   * Gets whether the wishlist item type allows anonymous wishlists.
   *
   * @return bool
   *   TRUE if anonymous wishlists are allowed, FALSE otherwise.
   */
  public function isAllowAnonymous();

  /**
   * Sets whether the wishlist item type allows anonymous wishlists.
   *
   * @param bool $allow_anonymous
   *   Whether the wishlist item type allows anonymous wishlists.
   *
   * @return $this
   */
  public function setAllowAnonymous($allow_anonymous);

}
