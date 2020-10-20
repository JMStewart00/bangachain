<?php

namespace Drupal\commerce_shipping\Normalizer;

use Drupal\commerce_shipping\Plugin\DataType\ShipmentItem as ShipmentItemDataType;
use Drupal\serialization\Normalizer\NormalizerBase;

class ShipmentItemNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ShipmentItemDataType::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    assert($object instanceof ShipmentItemDataType);
    return $object->toArray();
  }

}
