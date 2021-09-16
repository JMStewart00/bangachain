<?php

namespace Drupal\commerce_product_limits\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the "maximum_order_quantity" trait.
 *
 * @CommerceEntityTrait(
 *   id = "maximum_order_quantity",
 *   label = @Translation("Maximum order quantity"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class MaximumOrderQuantity extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['maximum_order_quantity'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Maximum quantity per order'))
      ->setSetting('size', 'tiny')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ]);

    return $fields;
  }

}
