<?php

namespace Drupal\commerce_product_limits;

use Drupal\commerce\Context;
use Drupal\commerce_cart\OrderItemMatcherInterface;
use Drupal\commerce_order\AvailabilityCheckerInterface;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Availability checker.
 */
class AvailabilityChecker implements AvailabilityCheckerInterface {

  use StringTranslationTrait;

  /**
   * The order item matcher.
   *
   * @var \Drupal\commerce_cart\OrderItemMatcherInterface
   */
  protected $orderItemMatcher;

  /**
   * Constructs a new AvailabilityChecker object.
   *
   * @param \Drupal\commerce_cart\OrderItemMatcherInterface $order_item_matcher
   *   The order item matcher.
   */
  public function __construct(OrderItemMatcherInterface $order_item_matcher) {
    $this->orderItemMatcher = $order_item_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();
    return $purchased_entity instanceof ProductVariationInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    $requested_quantity = $this->getRequestedQuantity($order_item);
    $entity = $order_item->getPurchasedEntity();
    // Checking on minimum allowed value.
    if ($entity->hasField('minimum_order_quantity')
      && !$entity->get('minimum_order_quantity')->isEmpty()
      && $entity->get('minimum_order_quantity')->value > $requested_quantity) {
      $error_message = new TranslatableMarkup(
        "You must order at least @min of this product at a time.",
        [
          '@min' => $entity->get('minimum_order_quantity')->value,
        ]
      );
      return AvailabilityResult::unavailable($error_message);
    }
    // Checking on maximum allowed value.
    if ($entity->hasField('maximum_order_quantity')
      && !$entity->get('maximum_order_quantity')->isEmpty()
      && $entity->get('maximum_order_quantity')->value < $requested_quantity) {
      $error_message = new TranslatableMarkup(
        "You cannot order more than @max of this product at a time.",
        [
          '@max' => $entity->get('maximum_order_quantity')->value,
        ]
      );
      return AvailabilityResult::unavailable($error_message);
    }
  }

  /**
   * Gets the "actual" requested quantity (This logic checks if the requested
   * product is already in cart and adds the quantity in cart to the requested
   * quantity.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return int
   *   The requested quantity.
   */
  protected function getRequestedQuantity(OrderItemInterface $order_item) {
    $order = $order_item->getOrder();
    $requested_quantity = $order_item->getQuantity();
    // Add the quantity already in cart (if any), this assumes the order items
    // are combined.
    if ($order && $order_item->isNew()) {
      $matching_order_item = $this->orderItemMatcher->match($order_item, $order->getItems());
      if ($matching_order_item) {
        $requested_quantity = Calculator::add($matching_order_item->getQuantity(), $requested_quantity);
      }
    }

    return (int) $requested_quantity;
  }

}
