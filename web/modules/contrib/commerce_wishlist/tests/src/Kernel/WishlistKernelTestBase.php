<?php

namespace Drupal\Tests\commerce_wishlist\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Provides a base class for Wishlist kernel tests.
 */
abstract class WishlistKernelTestBase extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_wishlist',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_wishlist_item');
    $this->installEntitySchema('commerce_wishlist');
    $this->installConfig(['commerce_wishlist']);
  }

}
