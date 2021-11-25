<?php

namespace Drupal\bangachain_commerce_update\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a basic condition for orders.
 *
 * @CommerceCondition(
 *   id = "bangachain_commerce_update_no_gift_cards",
 *   label = @Translation("No Gift Cards"),
 *   entity_type = "commerce_order",
 * )
 */
class NoGiftCards extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce\Entity\Order $order */
    $order = $entity;

    if (count($order->get('commerce_giftcards')->getValue()) > 0) {
      return FALSE;
    }

    return TRUE;
  }

}
