<?php

namespace Drupal\commerce_shipping\EventSubscriber;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_shipping\Event\ShipmentEvent;
use Drupal\commerce_shipping\Event\ShippingEvents;
use Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShipmentSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The shipment notification mail service.
   *
   * @var \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface
   */
  protected $shipmentConfirmationMail;

  /**
   * A static cache of shipments to clear on destruct(), keyed by order ID.
   *
   * @var array
   */
  protected $shipmentsToClear;

  /**
   * Constructs a new ShipmentSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface $shipment_confirmation_mail
   *   The shipment confirmation mail service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ShipmentConfirmationMailInterface $shipment_confirmation_mail) {
    $this->entityTypeManager = $entity_type_manager;
    $this->shipmentConfirmationMail = $shipment_confirmation_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_shipment.ship.post_transition' => ['onShip'],
      ShippingEvents::SHIPMENT_DELETE => ['onShipmentDelete'],
    ];
  }

  /**
   * Checks shipment mail settings and sends confirmation email to customer.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onShip(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $event->getEntity();
    /** @var \Drupal\commerce_shipping\Entity\ShipmentTypeInterface $shipment_type */
    $shipment_type = $this->entityTypeManager->getStorage('commerce_shipment_type')->load($shipment->bundle());
    $order = $shipment->getOrder();
    assert($order !== NULL);

    // Continue only if settings are configured to send confirmation.
    if ($shipment_type->shouldSendConfirmation()) {
      $this->shipmentConfirmationMail->send($shipment, $order->getEmail(), $shipment_type->getConfirmationBcc());
    }
  }

  /**
   * Reacts to a shipment being deleted.
   *
   * When a shipment gets deleted, ensure the order no longer references it
   * on destruct(), and make sure the adjustments added by this shipment are
   * removed. The reason why we're queuing this is to ensure we don't
   * save the order during an order refresh which potentially causes data loss.
   *
   * @param \Drupal\commerce_shipping\Event\ShipmentEvent $event
   *   The shipment event.
   */
  public function onShipmentDelete(ShipmentEvent $event) {
    $shipment = $event->getShipment();
    if (!$shipment->getOrderId()) {
      return;
    }
    // Shipment adjustments are transferred "unlocked" to the order
    // (See LateOrderProcessor), we store them in static cache so we can
    // properly remove them on destruct() if they still exist on the order.
    $adjustments = array_map(function (Adjustment $adjustment) {
      if ($adjustment->isLocked()) {
        $adjustment = new Adjustment([
          'locked' => FALSE,
        ] + $adjustment->toArray());
      }

      return $adjustment;
    }, $shipment->getAdjustments());

    // Because we wouldn't be able to load the shipment that is about to be
    // deleted on destruct, store the adjustments that must be removed as well
    // as the shipment ID.
    // See ::clearShipments().
    $this->shipmentsToClear[$shipment->getOrderId()][] = [
      'adjustments' => (array) $adjustments,
      'id' => $shipment->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if ($this->shipmentsToClear) {
      $this->clearShipments();
    }
  }

  /**
   * Clears the shipments that were deleted during this request.
   *
   * This will ensure the order no longer references the deleted shipments and
   * take care of removing any leftover adjustments.
   */
  protected function clearShipments() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    $orders = $this->entityTypeManager->getStorage('commerce_order')->loadMultiple(array_keys($this->shipmentsToClear));

    foreach ($orders as $order) {
      // Skip orders that no longer references shipments.
      if (!$order->hasField('shipments') ||
        $order->get('shipments')->isEmpty()) {
        continue;
      }
      $order_shipments = $order->get('shipments');
      $save_order = FALSE;
      foreach ($this->shipmentsToClear[$order->id()] as $shipment_to_clear) {
        $shipment_id = $shipment_to_clear['id'];
        // Make sure the shipment is not being referenced by the order anymore.
        /** @var \Drupal\Core\Field\FieldItemListInterface $order_shipments */
        foreach ($order_shipments as $delta => $item) {
          if ($item->target_id != $shipment_id) {
            continue;
          }
          $save_order = TRUE;
          // The shipment is still referenced by the order, clear the reference.
          $order_shipments->removeItem($delta);
          /** @var \Drupal\commerce_order\Adjustment[] $shipment_adjustments */
          $shipment_adjustments = $shipment_to_clear['adjustments'];
          $adjustments = [];

          // Loop over the order adjustments, to remove any adjustment added
          // by the shipment that was removed during the request.
          foreach ($order->getAdjustments() as $adjustment) {
            // Ensure the shipping adjustment is skipped/i.e not re-added
            // if present.
            if ($adjustment->getSourceId() == $shipment_id) {
              continue;
            }
            $matching_adjustment = FALSE;
            // Loop over the shipment adjustments, to see if the order has a
            // matching adjustment that needs to be removed.
            foreach ($shipment_adjustments as $shipment_adjustment) {
              if ($shipment_adjustment->toArray() != $adjustment->toArray()) {
                continue;
              }
              // A matching adjustment was found on the order, no need to
              // continue the loop.
              $matching_adjustment = TRUE;
              break;
            }
            // A matching adjustment was found on the order, it should be
            // removed, skipping it will ensure it's not re-added to the order.
            if ($matching_adjustment) {
              continue;
            }
            $adjustments[] = $adjustment;
          }
          $order->setAdjustments($adjustments);
        }
      }

      if ($save_order) {
        $order->save();
      }
    }
  }

}
