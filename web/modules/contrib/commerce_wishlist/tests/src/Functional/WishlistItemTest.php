<?php

namespace Drupal\Tests\commerce_wishlist\Functional;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\Core\Url;

/**
 * Tests the wishlist item UI.
 *
 * @group commerce_wishlist
 */
class WishlistItemTest extends WishlistBrowserTestBase {

  /**
   * A test wishlist.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishList;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $firstVariation;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $secondVariation;

  /**
   * The wishlist item collection uri.
   *
   * @var string
   */
  protected $wishListItemCollectionUri;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $this->wishList = $this->createEntity('commerce_wishlist', [
      'type' => 'default',
      'name' => 'Secret gifts',
      'public' => TRUE,
      'keep_purchased_items' => TRUE,
    ]);

    $this->firstVariation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'S-DESK',
      'title' => 'Standing desk',
      'price' => new Price('120.00', 'USD'),
    ]);
    $this->secondVariation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'R-DESK',
      'title' => 'Regular desk',
      'price' => new Price('70.00', 'USD'),
    ]);

    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->firstVariation, $this->secondVariation],
    ]);
    $this->wishListItemCollectionUri = Url::fromRoute('entity.commerce_wishlist_item.collection', [
      'commerce_wishlist' => $this->wishList->id(),
    ])->toString();
  }

  /**
   * Tests adding a wishlist item.
   */
  public function testAdd() {
    $this->drupalGet($this->wishListItemCollectionUri);
    $this->clickLink('Add item');

    $this->submitForm([
      'purchasable_entity[0][target_id]' => 'Standing desk (1)',
      'quantity[0][value]' => '1',
      'comment[0][value]' => 'Love this desk',
    ], 'Save');
    $this->assertSession()->pageTextContains('The item Standing desk has been successfully saved.');

    $wishlist_item = WishlistItem::load(1);
    $this->assertEquals($this->wishList->id(), $wishlist_item->getWishListId());
    $this->assertEquals($this->firstVariation->id(), $wishlist_item->getPurchasableEntityId());
    $this->assertEquals('1', $wishlist_item->getQuantity());
    $this->assertNotEmpty($wishlist_item->getComment());
  }

  /**
   * Tests editing a wishlist item.
   */
  public function testEdit() {
    $wishlist_item = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'wishlist_id' => $this->wishList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '1',
    ]);
    $this->drupalGet($wishlist_item->toUrl('edit-form'));
    $this->submitForm([
      'purchasable_entity[0][target_id]' => 'Regular desk (2)',
      'quantity[0][value]' => '5',
      'comment[0][value]' => 'My updated comment',
    ], 'Save');
    $this->assertSession()->pageTextNotContains('The item Regular desk has been successfully saved.');

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist_item')->resetCache([$wishlist_item->id()]);
    $wishlist_item = WishlistItem::load(1);

    // It is not possible to change product variation on edit.
    $this->assertNotEquals($this->secondVariation->id(), $wishlist_item->getPurchasableEntityId());
    $this->assertEqual($wishlist_item->getComment(), 'My updated comment');
    $this->assertEquals('5', $wishlist_item->getQuantity());
  }

  /**
   * Tests duplicating a wishlist item.
   */
  public function testDuplicate() {
    $wishlist_item = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'wishlist_id' => $this->wishList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '2',
    ]);
    $this->drupalGet($wishlist_item->toUrl('duplicate-form'));
    $this->assertSession()->pageTextContains('Duplicate Standing desk');
    $this->submitForm([
      'purchasable_entity[0][target_id]' => 'Regular desk (2)',
      'quantity[0][value]' => '5',
      'comment[0][value]' => 'Still regular is cheaper',
    ], 'Save');
    $this->assertSession()->pageTextContains('The item Regular desk has been successfully saved.');

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist_item')->resetCache([$wishlist_item->id()]);
    // Confirm that the original wishlist item is unchanged.
    $wishlist_item_1 = WishlistItem::load(1);
    $this->assertEquals($this->wishList->id(), $wishlist_item_1->getWishListId());
    $this->assertEquals($this->firstVariation->id(), $wishlist_item_1->getPurchasableEntityId());
    $this->assertEquals('2', $wishlist_item_1->getQuantity());
    $this->assertEmpty($wishlist_item_1->getComment());

    // Confirm that the new wishlist item has the expected data.
    $wishlist_item_2 = WishlistItem::load(2);
    $this->assertEquals($this->wishList->id(), $wishlist_item_2->getWishListId());
    $this->assertEquals($this->secondVariation->id(), $wishlist_item_2->getPurchasableEntityId());
    $this->assertEquals('5', $wishlist_item_2->getQuantity());
    $this->assertNotEmpty($wishlist_item_2->getComment());
    $this->assertEqual($wishlist_item_2->getComment(), 'Still regular is cheaper');
  }

  /**
   * Tests deleting a wishlist item.
   */
  public function testDelete() {
    $wishlist_item = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'wishlist_id' => $this->wishList->id(),
      'purchasable_entity' => $this->firstVariation->id(),
      'quantity' => '1',
      'comment' => 'You are gonna delete me',
    ]);
    $this->drupalGet($wishlist_item->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist_item')->resetCache([$wishlist_item->id()]);
    $wishlist_item_exists = (bool) WishlistItem::load($wishlist_item->id());
    $this->assertFalse($wishlist_item_exists);
  }

}
