<?php

namespace Drupal\commerce_shipping\Event;

use Drupal\commerce\EventBase;
use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Defines the shipment event.
 *
 * @see \Drupal\commerce_shipping\Event\ShippingEvents
 */
class ShipmentEvent extends EventBase {

  /**
   * The shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * Constructs a new ShipmentEvent.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   */
  public function __construct(ShipmentInterface $shipment) {
    $this->shipment = $shipment;
  }

  /**
   * Gets the shipment.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   Gets the shipment.
   */
  public function getShipment() {
    return $this->shipment;
  }

}
