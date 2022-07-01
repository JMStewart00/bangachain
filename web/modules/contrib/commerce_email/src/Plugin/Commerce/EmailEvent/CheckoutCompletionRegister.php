<?php

namespace Drupal\commerce_email\Plugin\Commerce\EmailEvent;

use Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent;
use Drupal\Component\EventDispatcher\Event;

/**
 * Provides the CheckoutCompletionRegister email event.
 *
 * @CommerceEmailEvent(
 *   id = "checkout_completion_register",
 *   label = @Translation("Checkout completion register"),
 *   event_name = "commerce_checkout.completion_register",
 *   entity_type = "commerce_order",
 * )
 */
class CheckoutCompletionRegister extends EmailEventBase {

  /**
   * {@inheritdoc}
   */
  public function extractEntityFromEvent(Event $event) {
    assert($event instanceof CheckoutCompletionRegisterEvent);
    return $event->getOrder();
  }

}
