<?php

namespace Drupal\commerce_giftcard\Event;

/**
 * Gift card events.
 */
final class GiftcardEvents {

  /**
   * Name of event fired when the gift card amount is calculated.
   *
   * @see \Drupal\commerce_giftcard\EventSubscriber\OrderEventSubscriber
   */
  const GIFTCARD_AMOUNT_CALCULATE = 'commerce_giftcard.giftcard_amount_calculate';

  /**
   * Name of the event fired after saving a new gift card.
   *
   * @Event
   *
   * @see \Drupal\commerce_giftcard\Event\GiftcardEvent
   */
  const GIFTCARD_INSERT = 'commerce_giftcard.commerce_giftcard.insert';


  /**
   * Name of the event fired after saving an existing gift card.
   *
   * @Event
   *
   * @see \Drupal\commerce_giftcard\Event\GiftcardEvent
   */
  const GIFTCARD_UPDATE = 'commerce_giftcard.commerce_giftcard.update';

  /**
   * Name of the event fired before deleting an gift card.
   *
   * @Event
   *
   * @see \Drupal\commerce_giftcard\Event\GiftcardEvent
   */
  const GIFTCARD_PREDELETE = 'commerce_giftcard.commerce_giftcard.predelete';

  /**
   * Name of the event fired after deleting an gift card.
   *
   * @Event
   *
   * @see \Drupal\commerce_giftcard\Event\GiftcardEvent
   */
  const GIFTCARD_DELETE = 'commerce_giftcard.commerce_giftcard.delete';

}
