<?php

namespace Drupal\commerce_giftcard\Event;


/**
 * Defines the gift card event.
 *
 * @see \Drupal\commerce_giftcard\Event\GiftcardEvents
 */
interface GiftcardEventInterface {

  /**
   * Gets the gift card.
   *
   * @return \Drupal\commerce_giftcard\Entity\GiftcardInterface
   *   The gift card.
   */
  public function getGiftcard();

}
