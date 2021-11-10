<?php

namespace Drupal\commerce_wishlist\Cache\Context;

use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the WishlistCacheContext service, for "per wishlist" caching.
 *
 * Cache context ID: 'wishlist'.
 */
class WishlistCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The wishlist provider service.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * Constructs a new WishlistCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider service.
   */
  public function __construct(AccountInterface $account, WishlistProviderInterface $wishlist_provider) {
    $this->account = $account;
    $this->wishlistProvider = $wishlist_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Current wishlist IDs');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return implode(':', $this->wishlistProvider->getWishlistIds($this->account));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    foreach ($this->wishlistProvider->getWishlists($this->account) as $wishlist) {
      $metadata->addCacheableDependency($wishlist);
    }
    return $metadata;
  }

}
