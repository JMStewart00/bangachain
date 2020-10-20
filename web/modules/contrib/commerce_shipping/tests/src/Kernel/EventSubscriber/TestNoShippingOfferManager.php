<?php

namespace Drupal\Tests\commerce_shipping\Kernel\EventSubscriber;

use Drupal\commerce_promotion\PromotionOfferManager;
use Drupal\commerce_shipping\Plugin\Commerce\PromotionOffer\ShipmentPromotionOfferInterface;

class TestNoShippingOfferManager extends PromotionOfferManager {

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    $definitions = array_filter($definitions, function ($definition) {
      return !is_subclass_of($definition['class'], ShipmentPromotionOfferInterface::class);
    });
    return $definitions;
  }

}
