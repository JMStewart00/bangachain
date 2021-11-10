<?php

namespace Drupal\commerce_wishlist\Access;

use Drupal\commerce_wishlist\WishlistSessionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for the wishlist item details_form route.
 */
class WishlistItemDetailsAccessCheck implements AccessInterface {

  /**
   * The wishlist session.
   *
   * @var \Drupal\commerce_wishlist\WishlistSessionInterface
   */
  protected $wishlistSession;

  /**
   * Constructs a new WishlistItemDetailsAccessCheck object.
   *
   * @param \Drupal\commerce_wishlist\WishlistSessionInterface $wishlist_session
   *   The wishlist session.
   */
  public function __construct(WishlistSessionInterface $wishlist_session) {
    $this->wishlistSession = $wishlist_session;
  }

  /**
   * Checks access to the wishlist item details form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    if ($account->hasPermission('administer commerce_wishlist')) {
      // Administrators can modify anyone's wishlst.
      $access = AccessResult::allowed()->cachePerPermissions();
    }
    else {
      // Users can modify their own wishlists.
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $route_match->getParameter('commerce_wishlist_item');
      $user = $wishlist_item->getWishlist()->getOwner();

      if ($account->isAuthenticated()) {
        $access = AccessResult::allowedIf($user->id() === $account->id())
          ->addCacheableDependency($wishlist_item)
          ->cachePerUser();
      }
      else {
        $access = AccessResult::allowedIf($this->wishlistSession->hasWishlistId($wishlist_item->getWishlistId()))
          ->addCacheableDependency($wishlist_item)
          ->addCacheContexts(['wishlist']);
      }
    }

    return $access;
  }

}
