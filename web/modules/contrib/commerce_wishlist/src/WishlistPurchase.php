<?php

namespace Drupal\commerce_wishlist;

/**
 * Provides a value object for wishlist purchases.
 */
final class WishlistPurchase {

  /**
   * The order ID.
   *
   * @var int
   */
  protected $orderId;

  /**
   * The quantity.
   *
   * @var string
   */
  protected $quantity;

  /**
   * The purchased timestamp.
   *
   * @var int
   */
  protected $purchased;

  /**
   * Constructs a new WishlistPurchase object.
   *
   * @param int $order_id
   *   The order id.
   * @param string $quantity
   *   The quantity.
   * @param int $purchased
   *   The purchased timestamp.
   */
  public function __construct($order_id, $quantity, $purchased) {
    $this->orderId = $order_id;
    $this->quantity = $quantity;
    $this->purchased = $purchased;
  }

  /**
   * Creates a new purchase from the given array.
   *
   * @param array $purchase
   *   The purchase array, with the "order_id", "quantity" and "purchased" keys.
   *
   * @return static
   */
  public static function fromArray(array $purchase) {
    if (!isset($purchase['order_id'], $purchase['quantity'], $purchase['purchased'])) {
      throw new \InvalidArgumentException('WishlistPurchase::fromArray() called with a malformed array.');
    }
    return new static($purchase['order_id'], $purchase['quantity'], $purchase['purchased']);
  }

  /**
   * Gets the order ID.
   *
   * @return int
   *   The order ID.
   */
  public function getOrderId() {
    return $this->orderId;
  }

  /**
   * Gets the quantity.
   *
   * @return string
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the purchased timestamp.
   *
   * @return int
   *   The purchased timestamp.
   */
  public function getPurchasedTime() {
    return $this->purchased;
  }

  /**
   * Gets the array representation of the purchase.
   *
   * @return array
   *   The array representation of the purchase.
   */
  public function toArray() {
    return [
      'order_id' => $this->orderId,
      'quantity' => $this->quantity,
      'purchased' => $this->purchased,
    ];
  }

}
