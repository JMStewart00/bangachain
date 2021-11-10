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
 * Tests the wishlist assignment.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistAssignment
 * @group commerce_wishlist
 */
class WishlistAssignmentTest extends WishlistKernelTestBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wishlist assignment.
   *
   * @var \Drupal\commerce_wishlist\WishlistAssignmentInterface
   */
  protected $wishlistAssignment;

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
      'id' => 'test_1',
      'label' => 'Test 1',
    ]);
    $wishlist_type->save();

    $another_wishlist_type = WishlistType::create([
      'id' => 'test_2',
      'label' => 'Test 2',
    ]);
    $another_wishlist_type->save();

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

    $this->anonymousUser = $this->createUser([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ]);
    $this->authenticatedUser = $this->createUser();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->wishlistAssignment = $this->container->get('commerce_wishlist.wishlist_assignment');
    $this->config('commerce_wishlist.settings')->set('allow_multiple', 1)->save();
  }

  /**
   * Tests wishlist assignment.
   *
   * @covers ::assign
   */
  public function testAssignWishlist() {
    $default_wishlist = $this->entityTypeManager->getStorage('commerce_wishlist')->loadDefaultByUser($this->authenticatedUser, 'test_1');
    $this->assertEmpty($default_wishlist);

    $wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationFirst,
    ]);
    $wishlist_item->save();
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item);

    $wishlist = Wishlist::create([
      'type' => 'test_1',
      'name' => 'First wishlist',
      'wishlist_items' => [$wishlist_item],
      'uid' => 0,
    ]);
    $wishlist->save();
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $this->wishlistAssignment->assign($wishlist, $this->authenticatedUser);
    $this->reloadEntity($wishlist);

    $this->assertEqual($wishlist->getOwnerId(), $this->authenticatedUser->id());

  }

  /**
   * Tests multiple wishlist assignment.
   *
   * @covers ::assignMultiple
   */
  public function testAssignMultipleWishlist() {
    $default_wishlist = $this->entityTypeManager->getStorage('commerce_wishlist')->loadDefaultByUser($this->authenticatedUser, 'test_1');
    $this->assertEmpty($default_wishlist);

    $wishlist_item_1 = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationFirst,
    ]);
    $wishlist_item_1->save();
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item_1);

    $wishlist_item_2 = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->productVariationSecond,
    ]);
    $wishlist_item_2->save();
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item_1);

    $wishlist_1 = Wishlist::create([
      'type' => 'test_1',
      'name' => 'First wishlist',
      'wishlist_items' => [$wishlist_item_1],
      'uid' => 0,
    ]);
    $wishlist_1->save();
    $this->assertInstanceOf(WishlistInterface::class, $wishlist_1);

    $wishlist_2 = Wishlist::create([
      'type' => 'test_1',
      'name' => 'First wishlist',
      'wishlist_items' => [$wishlist_item_2, $wishlist_item_1],
      'uid' => 0,
    ]);
    $wishlist_2->save();
    $this->assertInstanceOf(WishlistInterface::class, $wishlist_2);

    $wishlists = [
      $wishlist_1,
      $wishlist_2,
    ];

    $this->wishlistAssignment->assignMultiple($wishlists, $this->authenticatedUser);
    $this->reloadEntity($wishlist_1);
    $this->reloadEntity($wishlist_2);

    $this->assertEqual($wishlist_1->getOwnerId(), $this->authenticatedUser->id());
    $this->assertEqual($wishlist_2->getOwnerId(), $this->authenticatedUser->id());

    $this->config('commerce_wishlist.settings')->set('allow_multiple', 0)->save();
    $another_user = $this->createUser();
    $default_wishlist = $this->entityTypeManager->getStorage('commerce_wishlist')->loadDefaultByUser($another_user, 'test_1');
    $this->assertEmpty($default_wishlist);

    $wishlist = Wishlist::create([
      'type' => 'test_1',
      'name' => 'First wishlist',
      'wishlist_items' => [$wishlist_item_1],
      'uid' => $another_user->id(),
      'is_default' => TRUE,
    ]);
    $wishlist->save();
    $default_wishlist = $this->entityTypeManager->getStorage('commerce_wishlist')->loadDefaultByUser($another_user, 'test_1');
    $this->assertNotEmpty($default_wishlist);

    $anonymous_wishlist = Wishlist::create([
      'type' => 'test_1',
      'name' => 'Anonymous wishlist',
      'wishlist_items' => [$wishlist_item_2],
      'uid' => 0,
    ]);
    $anonymous_wishlist->save();
    $this->wishlistAssignment->assignMultiple([$anonymous_wishlist], $another_user);
    $wishlist = $this->reloadEntity($wishlist);
    $this->assertCount(2, $wishlist->getItems());

    $anonymous_wishlist = $this->reloadEntity($anonymous_wishlist);
    $this->assertNull($anonymous_wishlist);

    $default_wishlist = $this->entityTypeManager->getStorage('commerce_wishlist')->loadDefaultByUser($another_user, 'test_2');
    $this->assertEmpty($default_wishlist);
    $anonymous_wishlist = Wishlist::create([
      'type' => 'test_2',
      'name' => 'Anonymous wishlist',
      'wishlist_items' => [$wishlist_item_2],
      'uid' => 0,
    ]);
    $anonymous_wishlist->save();
    $this->wishlistAssignment->assignMultiple([$anonymous_wishlist], $another_user);
    $anonymous_wishlist = $this->reloadEntity($anonymous_wishlist);
    $this->assertEqual($anonymous_wishlist->getOwnerId(), $another_user->id());
  }

}
