<?php

namespace Drupal\bangachain_commerce_update\EventSubscriber;

use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VariationFilterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::FILTER_VARIATIONS => 'onFilterVariations',
    ];
  }

  /**
   * Handle the filter variations event.
   *
   * @param \Drupal\commerce_product\Event\FilterVariationsEvent $event
   *   The event.
   */
  public function onFilterVariations(FilterVariationsEvent $event) {
    $stockServiceManager = \Drupal::service('commerce_stock.service_manager');
    $variations = $event->getVariations();
    $filtered_variations = [];
    foreach ($variations as $variation) {
      // Where $entity is a product variation with a field_stock_level.
      $stock = intval($stockServiceManager->getStockLevel($variation));
      if ($stock > 0) {
        $filtered_variations[] = $variation;
      }
    }
    $event->setVariations($filtered_variations);
  }

}
