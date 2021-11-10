<?php

namespace Drupal\Tests\commerce_wishlist\Kernel;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItem;

/**
 * Tests the wishlist item access control.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistItemAccessControlHandler
 * @group commerce_wishlist
 */
class WishlistItemAccessTest extends WishlistKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
    $regular_user = $this->createUser(['uid' => 2]);
    \Drupal::currentUser()->setAccount($regular_user);
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();

    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $variation,
    ]);
    $wishlist_item->save();

    $wishlist = Wishlist::create([
      'type' => 'default',
      'name' => 'My wishlist',
      'wishlist_items' => [$wishlist_item],
    ]);
    $wishlist->save();
    $wishlist_item = $this->reloadEntity($wishlist_item);

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($wishlist_item->access('view', $account));
    $this->assertFalse($wishlist_item->access('update', $account));
    $this->assertFalse($wishlist_item->access('delete', $account));

    $account = $this->createUser([], ['view any commerce_wishlist']);
    $this->assertTrue($wishlist_item->access('view', $account));
    $this->assertFalse($wishlist_item->access('update', $account));
    $this->assertFalse($wishlist_item->access('delete', $account));

    $account = $this->createUser([], ['update any default commerce_wishlist']);
    $this->assertFalse($wishlist_item->access('view', $account));
    $this->assertFalse($wishlist_item->access('update', $account));
    $this->assertFalse($wishlist_item->access('delete', $account));

    $account = $this->createUser([], [
      'manage commerce_product_variation commerce_wishlist_item',
    ]);
    $this->assertFalse($wishlist_item->access('view', $account));
    $this->assertTrue($wishlist_item->access('update', $account));
    $this->assertTrue($wishlist_item->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_wishlist']);
    $this->assertTrue($wishlist_item->access('view', $account));
    $this->assertTrue($wishlist_item->access('update', $account));
    $this->assertTrue($wishlist_item->access('delete', $account));

    // Broken wishlist reference.
    $wishlist_item->set('wishlist_id', '999');
    $account = $this->createUser([], ['manage commerce_product_variation commerce_wishlist_item']);
    $this->assertFalse($wishlist_item->access('view', $account));
    $this->assertFalse($wishlist_item->access('update', $account));
    $this->assertFalse($wishlist_item->access('delete', $account));

    $account = $this->createUser([], ['view own commerce_wishlist']);
    $wishlist->setOwnerId($account->id());
    $wishlist->save();
    $wishlist_item = $this->reloadEntity($wishlist_item);
    $this->assertTrue($wishlist_item->access('view', $account));
    $this->assertFalse($wishlist_item->access('update', $account));
    $this->assertFalse($wishlist_item->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_wishlist_item');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['administer commerce_wishlist']);
    $this->assertTrue($access_control_handler->createAccess('default', $account));
    $this->assertTrue($access_control_handler->createAccess('commerce_product_variation', $account));

    $account = $this->createUser([], ['manage commerce_product_variation commerce_wishlist_item']);
    $this->assertTrue($access_control_handler->createAccess('commerce_product_variation', $account));
  }

}
