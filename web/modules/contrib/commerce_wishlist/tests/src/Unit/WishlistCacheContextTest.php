<?php

namespace Drupal\Tests\commerce_wishlist\Unit;

use Drupal\commerce_wishlist\Cache\Context\WishlistCacheContext;
use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\Core\Render\TestCacheableDependency;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_wishlist\Cache\Context\WishlistCacheContext
 * @group commerce
 */
class WishlistCacheContextTest extends UnitTestCase {

  /**
   * Tests commerce 'wishlist' cache context.
   */
  public function testWishlistCacheContext() {
    $account = $this->createMock(AccountInterface::class);
    $wishlistProvider = $this->createMock(WishlistProviderInterface::class);
    $wishlistProvider->expects($this->once())->method('getWishlistIds')->willReturn([
      '19',
      '12',
    ]);
    $wishlistProvider->expects($this->once())->method('getWishlists')->willReturn([
      new TestCacheableDependency([], ['commerce_wishlist:19'], 0),
      new TestCacheableDependency([], ['commerce_wishlist:24'], 0),
    ]);

    $wishlistCache = new WishlistCacheContext($account, $wishlistProvider);
    $this->assertEquals('19:12', $wishlistCache->getContext());
    $this->assertEquals(['commerce_wishlist:19', 'commerce_wishlist:24'], $wishlistCache->getCacheableMetadata()->getCacheTags());
  }

}
