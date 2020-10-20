<?php

namespace Drupal\commerce_shipping\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * @DataType(
 *   id = "shipment_item",
 *   label = @Translation("Shipment item"),
 *   description = @Translation("Shipment item."),
 *   definition_class = "\Drupal\commerce_shipping\TypedData\ShipmentItemDataDefinition"
 * )
 */
final class ShipmentItem extends TypedData {

  /**
   * The data value.
   *
   * @var \Drupal\commerce_shipping\ShipmentItem
   */
  protected $value;

  /**
   * Gets the array representation of the shipment item.
   *
   * @return array|null
   *   The array.
   */
  public function toArray() {
    return $this->value ? $this->value->toArray() : NULL;
  }

}
