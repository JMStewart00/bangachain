<?php

namespace Drupal\Tests\commerce_wishlist\Functional;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\Core\Url;

/**
 * Tests the wishlist UI.
 *
 * @group commerce_wishlist
 */
class WishlistTest extends WishlistBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->config('commerce_wishlist.settings')->set('allow_multiple', 1)->save();
  }

  /**
   * Tests adding a wishlist.
   */
  public function testAdd() {
    $this->drupalGet(Url::fromRoute('entity.commerce_wishlist.collection')->toString());
    $this->clickLink('Add wishlist');

    $this->submitForm([
      'name[0][value]' => 'Special desire',
      'is_public[value]' => '1',
      'keep_purchased_items[value]' => '1',
    ], 'Save');
    $this->assertSession()->pageTextContains('The wishlist Special desire has been successfully saved.');

    $wishlist = Wishlist::load(1);
    $this->assertEquals('Special desire', $wishlist->getName());
    $this->assertTrue($wishlist->isPublic());
    $this->assertTrue($wishlist->getKeepPurchasedItems());
    $this->assertEmpty($wishlist->getItems());
    $this->assertNotEmpty($wishlist->getCode());
  }

  /**
   * Tests adding multiple wishlist.
   */
  public function testAddMultiple() {
    $this->drupalGet(Url::fromRoute('entity.commerce_wishlist.collection')->toString());
    $this->clickLink('Add wishlist');

    $this->submitForm([
      'name[0][value]' => 'Special desire',
      'is_public[value]' => '1',
      'keep_purchased_items[value]' => '1',
    ], 'Save');
    $this->assertSession()->pageTextContains('The wishlist Special desire has been successfully saved.');

    $wishlist = Wishlist::load(1);
    $this->assertEquals('Special desire', $wishlist->getName());
    $this->assertTrue($wishlist->isPublic());
    $this->assertTrue($wishlist->getKeepPurchasedItems());
    $this->assertEmpty($wishlist->getItems());
    $this->assertNotEmpty($wishlist->getCode());

    // Add second wishlist.
    $this->drupalGet(Url::fromRoute('entity.commerce_wishlist.collection')->toString());
    $this->clickLink('Add wishlist');
    $this->submitForm([
      'name[0][value]' => 'Special desire 2',
      'is_public[value]' => '1',
      'keep_purchased_items[value]' => '1',
    ], 'Save');

    $this->assertSession()->pageTextContains('The wishlist Special desire 2 has been successfully saved.');

    $wishlist = Wishlist::load(2);
    $this->assertEquals('Special desire 2', $wishlist->getName());
    $this->assertTrue($wishlist->isPublic());
    $this->assertTrue($wishlist->getKeepPurchasedItems());
    $this->assertEmpty($wishlist->getItems());
    $this->assertNotEmpty($wishlist->getCode());

    // Change settings and don't allow multiple wishlists.
    $this->config('commerce_wishlist.settings')->set('allow_multiple', 0)->save();
    $this->drupalGet(Url::fromRoute('entity.commerce_wishlist.collection')->toString());
    $this->clickLink('Add wishlist');
    $this->submitForm([
      'name[0][value]' => 'Special desire 3',
      'is_public[value]' => '1',
      'keep_purchased_items[value]' => '1',
    ], 'Save');

    $this->assertSession()->pageTextContains('Cannot create a new wishlist (Only a single wishlist per customer is allowed).');

  }

  /**
   * Tests editing a wishlist.
   */
  public function testEdit() {
    $wishlist = $this->createEntity('commerce_wishlist', [
      'type' => 'default',
      'name' => $this->randomMachineName(8),
      'is_public' => TRUE,
      'keep_purchased_items' => FALSE,
    ]);
    $code = $wishlist->getCode();
    $this->drupalGet($wishlist->toUrl('edit-form'));
    $wishlist_tab_uri = Url::fromRoute('entity.commerce_wishlist_item.collection', [
      'commerce_wishlist' => $wishlist->id(),
    ])->toString();
    $this->assertSession()->linkByHrefExists($wishlist->toUrl('edit-form')->toString());
    $this->assertSession()->linkByHrefExists($wishlist->toUrl('duplicate-form')->toString());
    $this->assertSession()->linkByHrefExists($wishlist_tab_uri);

    $this->submitForm([
      'name[0][value]' => 'Top secret gifts',
      'is_public[value]' => '0',
      'keep_purchased_items[value]' => '1',
    ], 'Save');
    $this->assertSession()->pageTextContains('The wishlist Top secret gifts has been successfully saved');

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load(1);
    $this->assertEquals('Top secret gifts', $wishlist->getName());
    $this->assertFalse($wishlist->isPublic());
    $this->assertTrue($wishlist->getKeepPurchasedItems());
    $this->assertEmpty($wishlist->hasItems());
    $this->assertEquals($code, $wishlist->getCode());
  }

  /**
   * Tests duplicating a wishlist.
   */
  public function testDuplicate() {
    $wishlist = $this->createEntity('commerce_wishlist', [
      'type' => 'default',
      'name' => 'Secret gifts',
      'is_public' => TRUE,
      'keep_purchased_items' => TRUE,
    ]);
    $this->drupalGet($wishlist->toUrl('duplicate-form'));
    $this->assertSession()->pageTextContains('Duplicate Secret gifts');
    $wishlist_tab_uri = Url::fromRoute('entity.commerce_wishlist_item.collection', [
      'commerce_wishlist' => $wishlist->id(),
    ])->toString();
    $this->assertSession()->linkByHrefExists($wishlist->toUrl('edit-form')->toString());
    $this->assertSession()->linkByHrefExists($wishlist->toUrl('duplicate-form')->toString());
    $this->assertSession()->linkByHrefExists($wishlist_tab_uri);
    $this->submitForm([
      'name[0][value]' => 'Secret gits duplicated',
      'is_public[value]' => '0',
    ], 'Save');
    $this->assertSession()->pageTextContains('The wishlist Secret gits duplicated has been successfully saved.');

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    // Confirm that the original wishlist is unchanged.
    $wishlist_1 = Wishlist::load(1);
    $this->assertEquals('Secret gifts', $wishlist_1->getName());
    $this->assertTrue($wishlist_1->isPublic());

    // Confirm that the new wishlist has the expected data.
    $wishlist_2 = Wishlist::load(2);
    $this->assertEquals('Secret gits duplicated', $wishlist_2->getName());
    $this->assertNotTrue($wishlist_2->isPublic());
    // Check that there is no attached items.
    $this->assertEmpty($wishlist_2->hasItems());

    // Check that codes are different.
    $this->assertNotSame($wishlist_1->getCode(), $wishlist_2->getCode());
  }

  /**
   * Tests deleting a wishlist.
   */
  public function testDelete() {
    $wishlist = $this->createEntity('commerce_wishlist', [
      'type' => 'default',
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($wishlist->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist_exists = (bool) Wishlist::load($wishlist->id());
    $this->assertFalse($wishlist_exists);
  }

}
