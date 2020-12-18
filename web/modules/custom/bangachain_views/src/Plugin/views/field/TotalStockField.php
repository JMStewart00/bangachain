<?php

namespace Drupal\bangachain_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class TotalStockField
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("total_stock")
 */
class TotalStockField extends FieldPluginBase {

   /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
    dd('hey2');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $product_id = $values->_entity->id();

    $variation_ids = \Drupal::entityTypeManager()
        ->getStorage('commerce_product_variation')
        ->getQuery()
        ->condition('field_story_type','our-stories')
        ->execute();

    $count = 0;
    return $count;
  }
}
