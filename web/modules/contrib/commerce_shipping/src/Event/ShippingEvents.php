<?php

namespace Drupal\commerce_shipping\Event;

final class ShippingEvents {

  /**
   * Name of the event fired when shipping methods are loaded for a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\FilterShippingMethodsEvent
   */
  const FILTER_SHIPPING_METHODS = 'commerce_shipping.filter_shipping_methods';

  /**
   * Name of the event fired after loading a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_LOAD = 'commerce_shipping.commerce_shipment.load';

  /**
   * Name of the event fired after creating a new shipment.
   *
   * Fired before the shipment is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_CREATE = 'commerce_shipping.commerce_shipment.create';

  /**
   * Name of the event fired before saving a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_PRESAVE = 'commerce_shipping.commerce_shipment.presave';

  /**
   * Name of the event fired after saving a new shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_INSERT = 'commerce_shipping.commerce_shipment.insert';

  /**
   * Name of the event fired after saving an existing shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_UPDATE = 'commerce_shipping.commerce_shipment.update';

  /**
   * Name of the event fired before deleting a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_PREDELETE = 'commerce_shipping.commerce_shipment.predelete';

  /**
   * Name of the event fired after deleting a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShipmentEvent
   */
  const SHIPMENT_DELETE = 'commerce_shipping.commerce_shipment.delete';

  /**
   * Name of the event fired after calculating shipping rates.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\ShippingRatesEvent
   */
  const SHIPPING_RATES = 'commerce_shipping.rates';

}
