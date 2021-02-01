<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Allows promotions to clear their changes during the order refresh process.
 */
class EarlyOrderProcessor implements OrderProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EarlyOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // Check to see if there are any promotion adjustments present on the order
    // that need to be cleared.
    $promotion_ids = [];
    foreach ($order->collectAdjustments(['promotion']) as $adjustment) {
      if (empty($adjustment->getSourceId())) {
        continue;
      }
      $promotion_ids[] = $adjustment->getSourceId();
    }

    // No promotions were found, stop here.
    if (!$promotion_ids) {
      return;
    }

    $promotions = $this->entityTypeManager->getStorage('commerce_promotion')->loadMultiple($promotion_ids);
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    foreach ($promotions as $promotion) {
      $promotion->clear($order);
    }
  }

}
