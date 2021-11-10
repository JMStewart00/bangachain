<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\commerce_wishlist\Event\WishlistEmptyEvent;
use Drupal\commerce_wishlist\Event\WishlistEntityAddEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the wishlist manager.
 *
 * Fires its own events, different from the wishlist entity events by being a
 * result of user interaction (add to wishlist form, wishlist view, etc).
 */
class WishlistManager implements WishlistManagerInterface {

  /**
   * The wishlist item storage.
   *
   * @var \Drupal\commerce_wishlist\WishlistItemStorageInterface
   */
  protected $wishlistItemStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WishlistManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->wishlistItemStorage = $entity_type_manager->getStorage('commerce_wishlist_item');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function emptyWishlist(WishlistInterface $wishlist, $save_wishlist = TRUE) {
    $wishlist_items = $wishlist->getItems();
    foreach ($wishlist_items as $wishlist_item) {
      $wishlist_item->delete();
    }
    $wishlist->setItems([]);

    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_EMPTY, new WishlistEmptyEvent($wishlist, $wishlist_items));
    if ($save_wishlist) {
      $wishlist->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity(WishlistInterface $wishlist, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_wishlist = TRUE) {
    $wishlist_item = $this->wishlistItemStorage->createFromPurchasableEntity($entity, [
      'quantity' => $quantity,
    ]);
    $purchasable_entity = $wishlist_item->getPurchasableEntity();
    $quantity = $wishlist_item->getQuantity();
    $matching_wishlist_item = NULL;
    if ($combine) {
      $matching_wishlist_item = $this->matchWishlistItem($wishlist_item, $wishlist->getItems());
    }
    if ($matching_wishlist_item) {
      $new_quantity = Calculator::add($matching_wishlist_item->getQuantity(), $quantity);
      $matching_wishlist_item->setQuantity($new_quantity);
      $matching_wishlist_item->save();
      $saved_wishlist_item = $matching_wishlist_item;
    }
    else {
      $wishlist_item->save();
      $wishlist->addItem($wishlist_item);
      $saved_wishlist_item = $wishlist_item;
    }

    $event = new WishlistEntityAddEvent($wishlist, $purchasable_entity, $quantity, $wishlist_item);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ENTITY_ADD, $event);
    if ($save_wishlist) {
      $wishlist->save();
    }

    return $saved_wishlist_item;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(WishlistInterface $source, WishlistInterface $target, $save = TRUE) {
    foreach ($source->getItems() as $wishlist_item) {
      $duplicate_wishlist_item = $wishlist_item->createDuplicate();
      $duplicate_wishlist_item->save();
      $target->addItem($duplicate_wishlist_item);
    }

    if ($save) {
      $target->save();
      $source->delete();
    }

    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function removeWishlistItem(WishlistInterface $wishlist, WishlistItemInterface $wishlist_item, $save_wishlist = TRUE) {
    $wishlist->removeItem($wishlist_item);
    if ($save_wishlist) {
      $wishlist->save();
    }
    $wishlist_item->delete();
  }

  /**
   * Finds a matching wishlist item for the given one.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface[] $wishlist_items
   *   The wishlist items to match against.
   *
   * @return \Drupal\commerce_wishlist\Entity\WishlistItemInterface|null
   *   A matching wishlist item, or NULL if none was found.
   */
  protected function matchWishlistItem(WishlistItemInterface $wishlist_item, array $wishlist_items) {
    $matching_wishlist_item = NULL;
    foreach ($wishlist_items as $existing_wishlist_item) {
      if ($existing_wishlist_item->bundle() != $wishlist_item->bundle()) {
        continue;
      }
      if ($existing_wishlist_item->getPurchasableEntityId() != $wishlist_item->getPurchasableEntityId()) {
        continue;
      }
      $matching_wishlist_item = $existing_wishlist_item;
      break;
    }

    return $matching_wishlist_item;
  }

}
