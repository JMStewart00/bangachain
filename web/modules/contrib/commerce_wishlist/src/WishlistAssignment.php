<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Event\WishlistAssignEvent;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default wishlist assignment implementation.
 */
class WishlistAssignment implements WishlistAssignmentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * Constructs a new WishlistAssignment object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, WishlistManagerInterface $wishlist_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
    $this->wishlistManager = $wishlist_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function assign(WishlistInterface $wishlist, UserInterface $account) {
    if (!empty($wishlist->getOwnerId())) {
      // Skip wishlists which already have an owner.
      return;
    }

    $wishlist->setOwner($account);
    // Update the referenced shipping profile.
    $shipping_profile = $wishlist->getShippingProfile();
    if ($shipping_profile && empty($shipping_profile->getOwnerId())) {
      $shipping_profile->setOwner($account);
      $shipping_profile->save();
    }
    // Notify other modules.
    $event = new WishlistAssignEvent($wishlist, $account);
    $this->eventDispatcher->dispatch(WishlistEvents::WISHLIST_ASSIGN, $event);

    $wishlist->save();
  }

  /**
   * {@inheritdoc}
   */
  public function assignMultiple(array $wishlists, UserInterface $account) {
    $allow_multiple = (bool) $this->configFactory->get('commerce_wishlist.settings')->get('allow_multiple');
    /** @var \Drupal\commerce_wishlist\WishlistStorageInterface $wishlist_storage */
    $wishlist_storage = $this->entityTypeManager->getStorage('commerce_wishlist');
    foreach ($wishlists as $wishlist) {
      $default_wishlist = $wishlist_storage->loadDefaultByUser($account, $wishlist->bundle());
      // Check if multiple wishlists are allowed, in which case we're assigning
      // the wishlist to the given account.
      if ($allow_multiple || !$default_wishlist) {
        $this->assign($wishlist, $account);
        continue;
      }
      // In case a single wishlist is allowed, we need to merge the wishlist
      // items with the default wishlist.
      $this->wishlistManager->merge($wishlist, $default_wishlist);
    }
  }

}
