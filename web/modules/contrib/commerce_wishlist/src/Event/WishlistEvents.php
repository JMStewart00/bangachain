<?php

namespace Drupal\commerce_wishlist\Event;

/**
 * Defines events for the wishlist module.
 */
final class WishlistEvents {

  /**
   * Name of the event fired after assigning the anonymous wishlist to a user.
   *
   * Fired before the wishlist is saved.
   *
   * Use this event to implement logic such as canceling any existing wishlists
   * the user might already have prior to the anonymous wishlist assignment.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistAssignEvent
   */
  const WISHLIST_ASSIGN = 'commerce_wishlist.wishlist.assign';

  /**
   * Name of the event fired after emptying the wishlist.
   *
   * Fired before the wishlist is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEmptyEvent
   */
  const WISHLIST_EMPTY = 'commerce_wishlist.wishlist.empty';

  /**
   * Name of the event fired after adding a purchasable entity to the wishlist.
   *
   * Fired before the wishlist is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEntityAddEvent
   */
  const WISHLIST_ENTITY_ADD = 'commerce_wishlist.entity.add';

  /**
   * Name of the event fired after loading a wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_LOAD = 'commerce_wishlist.commerce_wishlist.load';

  /**
   * Name of the event fired after creating a new wishlist.
   *
   * Fired before the wishlist is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_CREATE = 'commerce_wishlist.commerce_wishlist.create';

  /**
   * Name of the event fired before saving a wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_PRESAVE = 'commerce_wishlist.commerce_wishlist.presave';

  /**
   * Name of the event fired after saving a new wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_INSERT = 'commerce_wishlist.commerce_wishlist.insert';

  /**
   * Name of the event fired after saving an existing wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_UPDATE = 'commerce_wishlist.commerce_wishlist.update';

  /**
   * Name of the event fired before deleting a wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_PREDELETE = 'commerce_wishlist.commerce_wishlist.predelete';

  /**
   * Name of the event fired after deleting a wishlist.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistEvent
   */
  const WISHLIST_DELETE = 'commerce_wishlist.commerce_wishlist.delete';

  /**
   * Name of the event fired after loading a wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_LOAD = 'commerce_wishlist.commerce_wishlist_item.load';

  /**
   * Name of the event fired after creating a wishlist item.
   *
   * Fired before the wishlist item is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_CREATE = 'commerce_wishlist.commerce_wishlist_item.create';

  /**
   * Name of the event fired before saving a wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_PRESAVE = 'commerce_wishlist.commerce_wishlist_item.presave';

  /**
   * Name of the event fired after saving a new wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_INSERT = 'commerce_wishlist.commerce_wishlist_item.insert';

  /**
   * Name of the event fired after saving an existing wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_UPDATE = 'commerce_wishlist.commerce_wishlist_item.update';

  /**
   * Name of the event fired before deleting a wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_PREDELETE = 'commerce_wishlist.commerce_wishlist_item.predelete';

  /**
   * Name of the event fired after deleting a wishlist item.
   *
   * @Event
   *
   * @see \Drupal\commerce_wishlist\Event\WishlistItemEvent
   */
  const WISHLIST_ITEM_DELETE = 'commerce_wishlist.commerce_wishlist_item.delete';

}
