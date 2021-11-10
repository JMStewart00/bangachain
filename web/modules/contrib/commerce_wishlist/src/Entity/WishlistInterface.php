<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for wishlists.
 */
interface WishlistInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the wishlist code.
   *
   * @return string
   *   The wishlist code.
   */
  public function getCode();

  /**
   * Sets the wishlist code.
   *
   * @param string $code
   *   The wishlist code.
   *
   * @return $this
   */
  public function setCode($code);

  /**
   * Gets the wishlist name.
   *
   * @return string
   *   The wishlist name.
   */
  public function getName();

  /**
   * Sets the wishlist name.
   *
   * @param string $name
   *   The wishlist name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the shipping profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The shipping profile, or null.
   */
  public function getShippingProfile();

  /**
   * Sets the shipping profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The shipping profile.
   *
   * @return $this
   */
  public function setShippingProfile(ProfileInterface $profile);

  /**
   * Gets the wishlist items.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface[]
   *   The wishlist items.
   */
  public function getItems();

  /**
   * Sets the wishlist items.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface[] $wishlist_items
   *   The wishlist items.
   *
   * @return $this
   */
  public function setItems(array $wishlist_items);

  /**
   * Gets whether the wishlist has wishlist items.
   *
   * @return bool
   *   TRUE if the wishlist has wishlist items, FALSE otherwise.
   */
  public function hasItems();

  /**
   * Adds an wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   *
   * @return $this
   */
  public function addItem(WishlistItemInterface $wishlist_item);

  /**
   * Removes an wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   *
   * @return $this
   */
  public function removeItem(WishlistItemInterface $wishlist_item);

  /**
   * Checks whether the wishlist has a given wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   *
   * @return bool
   *   TRUE if the wishlist item was found, FALSE otherwise.
   */
  public function hasItem(WishlistItemInterface $wishlist_item);

  /**
   * Gets whether this is the user's default wishlist.
   *
   * @return bool
   *   TRUE if this is the user's default wishlist, FALSE otherwise.
   */
  public function isDefault();

  /**
   * Sets whether this is the user's default wishlist.
   *
   * @param bool $default
   *   Whether this is the user's default wishlist.
   *
   * @return $this
   */
  public function setDefault($default);

  /**
   * Gets whether the wishlist is public.
   *
   * @return bool
   *   TRUE if the wishlist is public, FALSE otherwise.
   */
  public function isPublic();

  /**
   * Sets whether the wishlist is public.
   *
   * @param bool $public
   *   Whether the wishlist is public.
   *
   * @return $this
   */
  public function setPublic($public);

  /**
   * Gets whether items should remain in the wishlist once purchased.
   *
   * @return bool
   *   TRUE if purchased items should remain in the wishlist, FALSE otherwise.
   */
  public function getKeepPurchasedItems();

  /**
   * Sets whether items should remain in the wishlist once purchased.
   *
   * @param bool $keep_purchased_items
   *   Whether items should remain in the wishlist once purchased.
   *
   * @return $this
   */
  public function setKeepPurchasedItems($keep_purchased_items);

  /**
   * Gets the wishlist creation timestamp.
   *
   * @return int
   *   Creation timestamp of the wishlist.
   */
  public function getCreatedTime();

  /**
   * Sets the wishlist creation timestamp.
   *
   * @param int $timestamp
   *   The wishlist creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
