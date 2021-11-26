<?php

namespace Drupal\commerce_giftcard\Event;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Gift card amount calculation event.
 */
class GiftcardOrderPlaceEvent extends Event {

  /**
   * Order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  private $order;

  /**
   * The gift card.
   *
   * @var \Drupal\commerce_giftcard\Entity\GiftcardInterface
   */
  private $giftcard;

  /**
   * GiftcardAmountCalculateEvent constructor.
   */
  public function __construct(OrderInterface $order, GiftcardInterface $giftcard) {
    $this->order = $order;
    $this->giftcard = $giftcard;
  }

  /**
   * Returns the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Returns the gift card.
   *
   * @return \Drupal\commerce_giftcard\Entity\GiftcardInterface
   *   The gift card.
   */
  public function getGiftcard() {
    return $this->giftcard;
  }

}
