<?php

namespace Drupal\commerce_giftcard\Event;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the gift card event.
 *
 * @see \Drupal\commerce_giftcard\Event\GiftcardEvents
 */
class GiftcardEvent extends Event implements GiftcardEventInterface {

  /**
   * The gift card.
   *
   * @var \Drupal\commerce_giftcard\Entity\GiftcardInterface
   */
  protected $giftcard;

  /**
   * Constructs a new GiftcardEvent.
   *
   * @param \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard
   *   The gift card
   */
  public function __construct(GiftcardInterface $giftcard) {
    $this->giftcard = $giftcard;
  }

  /**
   *{@inheritdoc }
   */
  public function getGiftcard() {
    return $this->giftcard;
  }

}
