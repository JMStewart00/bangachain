<?php

namespace Drupal\Tests\commerce_wishlist\FunctionalJavascript;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistType;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the wishlist anonymous access.
 *
 * @group commerce_wishlist
 */
class WishlistAnonymousTest extends CommerceWebDriverTestBase {

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
    'commerce_product',
    'commerce_cart',
    'commerce_wishlist',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $form_display = EntityViewDisplay::load('commerce_product.default.default');
    $form_display->setComponent('variations', [
      'type' => 'commerce_add_to_cart',
      'weight' => 1,
      'label' => 'hidden',
      'settings' => [
        'combine' => TRUE,
      ],
      'third_party_settings' => [
        'commerce_wishlist' => [
          'show_wishlist' => TRUE,
          'weight_wishlist' => 99,
          'label_wishlist' => 'Add to wishlist',
          'region' => 'content',
        ],
      ],
    ]);
    $form_display->save();

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
  }

  /**
   * Test add to wishlist access.
   */
  public function testNotAllowed() {
    $this->drupalGet('product/1');
    $this->assertSession()->buttonExists('Add to wishlist');

    $this->drupalLogout();
    drupal_flush_all_caches();

    $this->drupalGet('product/1');
    $this->assertSession()->buttonNotExists('Add to wishlist');
  }

  /**
   * Tests adding wishlist.
   */
  public function testAddWishlist() {
    // Allow adding wishlist for anonymous user.
    $wishlist_type = WishlistType::load('default');
    $wishlist_type->setAllowAnonymous(TRUE);
    $wishlist_type->save();
    $this->reloadEntity($wishlist_type);

    $this->drupalLogout();
    $this->drupalGet('product/1');
    $this->getSession()->getPage()->hasButton('edit-wishlist');
    $this->getSession()->getPage()->findButton('Add to wishlist')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalGet('wishlist');
    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-header-add-all-to-cart"]');
    $this->assertSession()->elementExists('css', 'a[data-drupal-selector="edit-header-share"]');

    $this->assertSession()->pageTextContains('First variation');
    $this->assertSession()->pageTextContains('Quantity: 1');
    $this->assertSession()->elementExists('css', 'a[data-drupal-selector="edit-items-1-details-edit"]');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-1"]');
    $this->assertSession()->elementExists('css', 'input[name="remove-1"]');

    $this->getSession()->getPage()->clickLink('Edit details');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->buttonExists('Update details');

    // Clear session, to verify access for anonymous user which is not owner.
    $this->getSession()->reset();

    $wishlist = Wishlist::load(1);
    $this->assertNotEmpty($wishlist);
    $this->drupalGet($wishlist->toUrl('user-form'));

    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-header-add-all-to-cart"]');
    $this->assertSession()->elementNotExists('css', 'a[data-drupal-selector="edit-header-share"]');
    $this->assertSession()->pageTextContains('First variation');
    $this->assertSession()->pageTextContains('Quantity: 1');
    $this->assertSession()->elementNotExists('css', 'a[data-drupal-selector="edit-items-1-details-edit"]');
    $this->assertSession()->elementExists('css', 'input[name="add-to-cart-1"]');
    $this->assertSession()->elementNotExists('css', 'input[name="remove-1"]');
  }

}
