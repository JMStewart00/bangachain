<?php

namespace Drupal\Tests\commerce_wishlist\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the wishlist user pages.
 *
 * @group commerce_wishlist
 */
class WishlistUserTest extends CommerceWebDriverTestBase {

  /**
   * The wishlist.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation1;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_cart',
    'commerce_wishlist',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createEntity('commerce_product_variation_type', [
      'id' => 'test',
      'label' => 'Test',
      'orderItemType' => 'default',
      'generateTitle' => FALSE,
    ]);
    $entity_display = commerce_get_entity_display('commerce_product_variation', 'test', 'view');
    $entity_display->setComponent('title', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $entity_display->save();

    $this->variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'test',
      'title' => 'First variation',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);
    $this->variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'test',
      'title' => 'Second variation',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 20.99,
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$this->variation1, $this->variation2],
      'stores' => [$this->store],
    ]);

    $this->wishlist = $this->createEntity('commerce_wishlist', [
      'type' => 'default',
      'title' => 'My wishlist',
      'uid' => $this->adminUser->id(),
    ]);
  }

  /**
   * Tests the behavior of wishlist pages when the wishlist is empty.
   */
  public function testEmptyPage() {
    $this->drupalGet(Url::fromRoute('commerce_wishlist.page'));
    $this->assertSession()->pageTextContains('Your wishlist is empty');

    $this->drupalGet(Url::fromRoute('commerce_wishlist.user_page', [
      'user' => $this->adminUser->id(),
    ]));
    $this->assertSession()->pageTextContains('Your wishlist is empty');
  }

  /**
   * Tests the redirection to canonical/user_form pages.
   */
  public function testRedirects() {
    $wishlist_item = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->variation1,
      'quantity' => 1,
    ]);
    $this->wishlist->addItem($wishlist_item);
    $this->wishlist->save();
    $user_form_url = $this->wishlist->toUrl('user-form')->setAbsolute();

    $this->drupalGet(Url::fromRoute('commerce_wishlist.page'));
    $this->assertEquals($user_form_url->toString(), $this->getSession()->getCurrentUrl());

    $this->drupalGet(Url::fromRoute('commerce_wishlist.user_page', [
      'user' => $this->adminUser->id(),
    ]));
    $this->assertEquals($user_form_url->toString(), $this->getSession()->getCurrentUrl());
  }

  /**
   * Tests the canonical page.
   */
  public function testCanonicalPage() {
    $wishlist_item1 = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->variation1,
      'quantity' => 1,
    ]);
    $wishlist_item2 = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->variation2,
      'quantity' => 2,
    ]);
    $this->wishlist->setItems([$wishlist_item1, $wishlist_item2]);
    $this->wishlist->save();

    $this->drupalGet($this->wishlist->toUrl('canonical'));
    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-header-add-all-to-cart"]');
    $this->assertSession()->elementNotExists('css', 'a[data-drupal-selector="edit-header-share"]');

    $this->assertSession()->pageTextContains('First variation');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-1"]');
    $this->assertSession()->elementNotExists('css', 'input[name="remove-1"]');
    $this->assertSession()->pageTextContains('Second variation');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-2"]');
    $this->assertSession()->elementNotExists('css', 'input[name="remove-2"]');

    // Confirm that the "Add all to cart" button works.
    $cart_provider = $this->container->get('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    $this->assertEmpty($cart);
    $this->getSession()->getPage()->findButton('Add the entire list to cart')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store);
    $this->assertCount(2, $cart->getItems());

    // Confirm that the "Add to cart" button works.
    $cart_manager = $this->container->get('commerce_cart.cart_manager');
    $cart_manager->emptyCart($cart);
    $button = $this->getSession()->getPage()->find('css', 'input[name="add-to-cart-2"]');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
    $cart = $this->reloadEntity($cart);
    $this->assertCount(1, $cart->getItems());
    $order_items = $cart->getItems();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = reset($order_items);
    $this->assertEquals($this->variation2->id(), $order_item->getPurchasedEntityId());
  }

  /**
   * Tests the user form.
   */
  public function testUserForm() {
    $wishlist_item1 = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->variation1,
      'quantity' => 1,
    ]);
    $wishlist_item2 = $this->createEntity('commerce_wishlist_item', [
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $this->variation2,
      'quantity' => 2,
    ]);
    $this->wishlist->setItems([$wishlist_item1, $wishlist_item2]);
    $this->wishlist->save();

    $this->drupalGet($this->wishlist->toUrl('user-form'));
    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-header-add-all-to-cart"]');
    $this->assertSession()->elementExists('css', 'a[data-drupal-selector="edit-header-share"]');

    $this->assertSession()->pageTextContains('First variation');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-1"]');
    $this->assertSession()->elementExists('css', 'input[name="remove-1"]');
    $this->assertSession()->pageTextContains('Second variation');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-2"]');
    $this->assertSession()->elementExists('css', 'input[name="remove-2"]');

    // Confirm that the "Add all to cart" button works.
    /** @var \Drupal\commerce_cart\CartProviderInterface $cart_provider */
    $cart_provider = $this->container->get('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    $this->assertEmpty($cart);
    $this->getSession()->getPage()->findButton('Add the entire list to cart')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store);
    $this->assertCount(2, $cart->getItems());

    // Confirm that the "Add to cart" button works.
    $cart_manager = $this->container->get('commerce_cart.cart_manager');
    $cart_manager->emptyCart($cart);
    $button = $this->getSession()->getPage()->find('css', 'input[name="add-to-cart-2"]');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
    $cart = $this->reloadEntity($cart);
    $this->assertCount(1, $cart->getItems());
    $order_items = $cart->getItems();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = reset($order_items);
    $this->assertEquals($this->variation2->id(), $order_item->getPurchasedEntityId());

    // Confirm that the "Remove" button works.
    $button = $this->getSession()->getPage()->find('css', 'input[name="remove-2"]');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Second variation has been removed from your wishlist');
    $this->assertSession()->pageTextContains('First variation');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-1"]');
    $this->assertSession()->elementExists('css', 'input[name="remove-1"]');
    $this->assertSession()->elementNotExists('css', 'input[name="add-to-cart-2"]');
    $this->assertSession()->elementNotExists('css', 'input[name="remove-2"]');
    $this->wishlist = $this->reloadEntity($this->wishlist);
    $this->assertFalse($this->wishlist->hasItem($wishlist_item2));
  }

}
