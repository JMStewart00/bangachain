<?php

namespace Drupal\Tests\commerce_wishlist\Kernel;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\Entity\WishlistType;

/**
 * Tests the wishlist manager.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistManager
 * @group commerce_wishlist
 */
class WishlistManagerTest extends WishlistKernelTestBase {

  /**
   * Anonymous user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * Registered user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $purchasableEntity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * First product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariationFirst;

  /**
   * Second product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariationSecond;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $wishlist_type = WishlistType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $wishlist_type->save();

    $this->anonymousUser = $this->createUser([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ]);
    $this->authenticatedUser = $this->createUser();

    $this->productVariationFirst = ProductVariation::create([
      'type' => 'default',
      'sku' => 'product_1',
      'title' => 'Product 1',
      'status' => 1,
    ]);
    $this->productVariationFirst->save();

    $this->productVariationSecond = ProductVariation::create([
      'type' => 'default',
      'sku' => 'product_2',
      'title' => 'Product 2',
      'status' => 1,
    ]);
    $this->productVariationSecond->save();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->wishlistManager = $this->container->get('commerce_wishlist.wishlist_manager');
  }

  /**
   * Tests adding a purchasable entity to a wishlist.
   *
   * @covers ::addEntity
   */
  public function testAddEntity() {
    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();

    $wishlist_item = $this->wishlistManager->addEntity($wishlist, $this->productVariationFirst, 3);
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item);
    $this->assertEquals(3, $wishlist_item->getQuantity());
    $this->assertEquals('Product 1', $wishlist_item->getTitle());
    $this->assertTrue($wishlist->hasItem($wishlist_item));
  }

  /**
   * Tests emptying a wishlist.
   *
   * @covers ::emptyWishlist
   */
  public function testEmptyWishlist() {
    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();
    $this->wishlistManager->addEntity($wishlist, $this->productVariationSecond);

    $this->assertTrue($wishlist->hasItems());
    $this->wishlistManager->emptyWishlist($wishlist);
    $this->assertFalse($wishlist->hasItems());
  }

  /**
   * Tests wishlist merge.
   *
   * @covers ::merge
   */
  public function testMergeWishlist() {
    $wishlist_item_1 = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationFirst,
    ]);

    $source = Wishlist::create([
      'type' => 'test_1',
      'name' => 'Source wishlist',
      'wishlist_items' => [$wishlist_item_1],
      'uid' => 1,
    ]);
    $source->save();
    $this->reloadEntity($source);
    $this->assertEqual($source->id(), $this->authenticatedUser->id());

    $wishlist_item_2 = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationFirst,
    ]);
    $wishlist_item_2->save();
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item_2);

    $target = Wishlist::create([
      'type' => 'test_1',
      'name' => 'Target wishlist',
      'wishlist_items' => [$wishlist_item_2],
      'uid' => 1,
    ]);
    $target->save();
    $this->assertEqual(count($target->getItems()), 1);
    $this->assertInstanceOf(WishlistInterface::class, $target);

    $this->wishlistManager->merge($source, $target);
    $this->reloadEntity($source);
    $this->reloadEntity($target);
    $source = $this->entityTypeManager->getStorage('commerce_wishlist')->load($source->id());
    $target = $this->entityTypeManager->getStorage('commerce_wishlist')->load($target->id());
    $this->assertInstanceOf(WishlistInterface::class, $target);
    $this->assertNotInstanceOf(WishlistInterface::class, $source);

    $this->assertEqual(count($target->getItems()), 2);
  }

  /**
   * Tests removing a wishlist item from a wishlist.
   *
   * @covers ::removeWishlistItem
   */
  public function testRemoveWishlistItem() {
    $wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationFirst,
    ]);
    $wishlist_item->save();
    $wishlist = Wishlist::create([
      'type' => 'test',
      'wishlist_items' => [$wishlist_item],
      'name' => 'My wishlist',
    ]);
    $wishlist->save();
    $this->assertTrue($wishlist->hasItems());

    $this->wishlistManager->removeWishlistItem($wishlist, $wishlist_item);
    $this->assertFalse($wishlist->hasItems());
  }

}
