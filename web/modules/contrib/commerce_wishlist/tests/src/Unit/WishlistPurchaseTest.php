<?php

namespace Drupal\Tests\commerce_wishlist\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\commerce_wishlist\WishlistPurchase;

/**
 * Tests the WishlistPurchase class.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistPurchase
 * @group commerce_wishlist
 */
class WishlistPurchaseTest extends UnitTestCase {

  /**
   * The purchase.
   *
   * @var \Drupal\commerce_wishlist\WishlistPurchase
   */
  protected $purchase;

  /**
   * Tests creating a purchase from an invalid array.
   *
   * ::covers __construct.
   * ::covers fromArray().
   */
  public function testCreateFromInvalidArray() {
    $this->expectException(\InvalidArgumentException::class);
    WishlistPurchase::fromArray([]);
  }

  /**
   * Tests creating a purchase from a valid array.
   *
   * ::covers __construct
   * ::covers fromArray().
   */
  public function testCreateFromValidArray() {
    $time = time();
    $purchase_array = [
      'order_id' => '12',
      'quantity' => '3',
      'purchased' => $time,
    ];
    $purchase = WishlistPurchase::fromArray($purchase_array);
    $this->assertEquals('12', $purchase->getOrderId());
    $this->assertEquals('3', $purchase->getQuantity());
    $this->assertEquals($time, $purchase->getPurchasedTime());
  }

  /**
   * Tests getters.
   *
   * ::covers getOrderId
   * ::covers getQuantity
   * ::covers getPurchasedTime
   * ::covers toArray.
   */
  public function testGetters() {
    $time = time();
    $this->purchase = new WishlistPurchase('10', 2, $time);
    $this->assertEquals('10', $this->purchase->getOrderId());
    $this->assertEquals(2, $this->purchase->getQuantity());
    $this->assertEquals($time, $this->purchase->getPurchasedTime());
    $expected = [
      'order_id' => '10',
      'quantity' => '2',
      'purchased' => $time,
    ];
    $this->assertEquals($expected, $this->purchase->toArray());
  }

}
