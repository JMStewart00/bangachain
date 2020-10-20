<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies promotions to orders during the order refresh process.
 *
 * @see \Drupal\commerce_promotion\CouponOrderProcessor
 */
class MultiplePaymentOrderProcessor implements OrderProcessorInterface {
  
  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface[] $staged_payments */
    $staged_payments = $order->get('staged_multi_payment')->referencedEntities();
    foreach ($staged_payments as $index => $staged_payment) {
      if ($staged_payment->isActive()) {
        $adjustment = new Adjustment([
          'type' => 'staged_multi_payment',
          // @todo Change to label from UI when added in #2770731.
          'label' => $staged_payment->getPaymentGateway()->getPlugin()->multiPaymentAdjustmentLabel($staged_payment),
          'amount' => $staged_payment->getAmount()->multiply('-1'),
          'source_id' => $staged_payment->id(),
        ]);
        $order->addAdjustment($adjustment);
        
        // If the order total has gone negative, change the staged payment and re-apply the adjustment.
        if ($order->getTotalPrice()->getNumber() < 0) {
          $staged_payment->setAmount($staged_payment->getAmount()->add($order->getTotalPrice()));
          $staged_payment->save();
          $order->removeAdjustment($adjustment);
          $adjustment = new Adjustment([
            'type' => 'staged_multi_payment',
            // @todo Change to label from UI when added in #2770731.
            'label' => $staged_payment->getPaymentGateway()->getPlugin()->multiPaymentAdjustmentLabel($staged_payment),
            'amount' => $staged_payment->getAmount()->multiply('-1'),
            'source_id' => $staged_payment->id(),
          ]);
          $order->addAdjustment($adjustment);
          
        }
      }
    }
  }

}
