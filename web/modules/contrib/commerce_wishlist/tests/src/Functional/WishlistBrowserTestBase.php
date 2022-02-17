<?php

namespace Drupal\Tests\commerce_wishlist\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Defines base class for commerce_wishlist test cases.
 */
abstract class WishlistBrowserTestBase extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_product',
    'commerce_wishlist',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access commerce_wishlist overview',
      'administer commerce_wishlist',
      'administer commerce_product',
    ], parent::getAdministratorPermissions());
  }

}
