<?php

namespace Drupal\Tests\commerce_wishlist\Kernel\Entity;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\WishlistPurchase;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;

/**
 * Tests the wishlist item entity.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\Entity\WishlistItem
 * @group commerce_wishlist
 */
class WishlistItemTest extends CartKernelTestBase {

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

    $this->installEntitySchema('commerce_wishlist');
    $this->installEntitySchema('commerce_wishlist_item');
    $this->installConfig(['commerce_wishlist']);
  }

  /**
   * Tests the wishlist item entity and its methods.
   *
   * @covers ::getWishlistId
   * @covers ::getWishlist
   * @covers ::getPurchasableEntity
   * @covers ::getPurchasableEntityId
   * @covers ::getTitle
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getComment
   * @covers ::setComment
   * @covers ::getPriority
   * @covers ::setPriority
   * @covers ::getPurchases
   * @covers ::setPurchases
   * @covers ::addPurchase
   * @covers ::removePurchase
   * @covers ::getPurchasedQuantity
   * @covers ::getLastPurchasedTime
   * @covers ::getCreatedTime
   * @covers ::getChangedTime
   */
  public function testWishlistItem() {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
    ]);
    $product->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
    ]);
    $variation->save();
    $variation = $this->reloadEntity($variation);
    /** @var \Drupal\commerce_wishlist\WishlistItemStorageInterface $wishlist_item_storage */
    $wishlist_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_wishlist_item');

    $wishlist = Wishlist::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $wishlist_item = $wishlist_item_storage->createFromPurchasableEntity($variation, [
      'quantity' => 2,
    ]);
    $wishlist_item->save();
    $wishlist->setItems([$wishlist_item]);
    $wishlist->save();
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = $this->reloadEntity($wishlist_item);

    $this->assertEquals($wishlist_item->getWishlistId(), $wishlist->id());
    $this->assertEquals($wishlist_item->getWishlist()->id(), $wishlist->id());
    $this->assertEquals($wishlist_item->getPurchasableEntity()->id(), $variation->id());
    $this->assertEquals($wishlist_item->getPurchasableEntityId(), $variation->id());
    $this->assertEquals($wishlist_item->getTitle(), $variation->label());
    $this->assertEquals($wishlist_item->getQuantity(), 2);
    $wishlist_item->setQuantity(3);
    $this->assertEquals($wishlist_item->getQuantity(), 3);

    $wishlist_item->setComment('My comment');
    $this->assertEquals($wishlist_item->getComment(), 'My comment');

    $wishlist_item->setPriority(100);
    $this->assertEquals($wishlist_item->getPriority(), 100);

    $time = 635879700;
    $this->assertNull($wishlist_item->getLastPurchasedTime());
    $purchase = new WishlistPurchase(10, 2, $time + 10);
    $wishlist_item->setPurchases([$purchase]);
    $purchases = $wishlist_item->getPurchases();
    $this->assertEquals([$purchase], $purchases);
    $this->assertEquals(2, $wishlist_item->getPurchasedQuantity());
    $this->assertEquals($time + 10, $wishlist_item->getLastPurchasedTime());

    $another_purchase = new WishlistPurchase(11, 3, $time + 5);
    $wishlist_item->addPurchase($another_purchase);
    $purchases = $wishlist_item->getPurchases();
    $this->assertEquals([$purchase, $another_purchase], $purchases);
    $this->assertEquals(5, $wishlist_item->getPurchasedQuantity());
    $this->assertEquals($time + 10, $wishlist_item->getLastPurchasedTime());

    $wishlist_item->removePurchase($purchase);
    $purchases = $wishlist_item->getPurchases();
    $this->assertEquals([$another_purchase], $purchases);
    $this->assertEquals(3, $wishlist_item->getPurchasedQuantity());
    $this->assertEquals($time + 5, $wishlist_item->getLastPurchasedTime());

    $wishlist_item->setCreatedTime($time);
    $this->assertEquals($wishlist_item->getCreatedTime(), $time);
  }

}
