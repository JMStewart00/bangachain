<?php

namespace Drupal\commerce_email\Plugin\Commerce\EmailEvent;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Component\EventDispatcher\Event;

/**
 * Provides the OrderPlaced email event.
 *
 * @CommerceEmailEvent(
 *   id = "order_placed",
 *   label = @Translation("Order placed"),
 *   event_name = "commerce_order.place.post_transition",
 *   entity_type = "commerce_order",
 * )
 */
class OrderPlaced extends EmailEventBase {

  /**
   * {@inheritdoc}
   */
  public function extractEntityFromEvent(Event $event) {
    assert($event instanceof WorkflowTransitionEvent);
    return $event->getEntity();
  }

}
