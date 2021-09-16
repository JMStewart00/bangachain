<?php

namespace Drupal\commerce_product_limits\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the "minimum_order_quantity" trait.
 *
 * @CommerceEntityTrait(
 *   id = "minimum_order_quantity",
 *   label = @Translation("Minimum order quantity"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class MinimumOrderQuantity extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['minimum_order_quantity'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Minimum quantity per order'))
      ->setSetting('size', 'tiny')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ]);

    return $fields;
  }

}
