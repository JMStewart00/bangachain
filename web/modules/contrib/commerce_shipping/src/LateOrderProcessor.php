<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Completes the order refresh process for shipments.
 *
 * Saves any previously modified shipments.
 * Transfers the shipment amount and adjustments to the order.
 *
 * Runs after other order processors (promotion, tax, etc).
 *
 * @see \Drupal\commerce_shipping\EarlyOrderProcessor
 */
class LateOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  /**
   * The shipping order manager.
   *
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface
   */
  protected $shippingOrderManager;

  /**
   * Constructs a new LateOrderProcessor object.
   *
   * @param \Drupal\commerce_shipping\ShippingOrderManagerInterface $shipping_order_manager
   *   The shipping order manager.
   */
  public function __construct(ShippingOrderManagerInterface $shipping_order_manager) {
    $this->shippingOrderManager = $shipping_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if (!$this->shippingOrderManager->hasShipments($order)) {
      return;
    }
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $order->get('shipments')->referencedEntities();
    $single_shipment = count($shipments) === 1;

    foreach ($shipments as $shipment) {
      if ($this->shouldSave($shipment)) {
        $shipment->save();
      }

      if ($amount = $shipment->getAmount()) {
        // Shipments without an amount are incomplete / unrated.
        $order->addAdjustment(new Adjustment([
          'type' => 'shipping',
          'label' => $single_shipment ? $this->t('Shipping') : $shipment->getTitle(),
          'amount' => $amount,
          'source_id' => $shipment->id(),
        ]));
        foreach ($shipment->getAdjustments() as $adjustment) {
          if ($adjustment->isLocked()) {
            // Locked shipment adjustments must be transferred unlocked
            // so that they're cleared at the beginning of order refresh.
            $adjustment = new Adjustment([
              'locked' => FALSE,
            ] + $adjustment->toArray());
          }
          $order->addAdjustment($adjustment);
        }
      }
    }
  }

  /**
   * Whether shipments should be saved during processing.
   *
   * Normally this is true, but in certain circumstances, saving
   * should not occur. e.g. during shipment estimates.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return bool
   *   Whether save should occur.
   */
  protected function shouldSave(ShipmentInterface $shipment): bool {
    return $shipment->hasTranslationChanges();
  }

}
