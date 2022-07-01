<?php

namespace Drupal\commerce_email\Plugin\Commerce\EmailEvent;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\Component\EventDispatcher\Event;

/**
 * Provides the OrderPaid email event.
 *
 * @CommerceEmailEvent(
 *   id = "order_paid",
 *   label = @Translation("Order paid"),
 *   event_name = "commerce_order.order.paid",
 *   entity_type = "commerce_order",
 * )
 */
class OrderPaid extends EmailEventBase {

  /**
   * {@inheritdoc}
   */
  public function extractEntityFromEvent(Event $event) {
    assert($event instanceof OrderEvent);
    return $event->getOrder();
  }

}
