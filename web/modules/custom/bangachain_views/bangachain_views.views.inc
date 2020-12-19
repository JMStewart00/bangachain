
<?php
/**
 * @file
 *
 * Views will look in this file for any views related hooks. Mainly, this is
 * where you put your hook_views_data() implementation.
 */

function bangachain_views_views_data_alter(array &$data) {
  $data['commerce_product_variation']['commerce_stock_location_level'] = [
    'title' => t('Stock Location Level'),
    'help' => t('The stock location level for the product variation'),
    'relationship' => [
      'base' => 'commerce_stock_location_level',
      'base field' => 'entity_id',
      'field' => 'variation_id',
      'id' => 'standard',
      'label' => t('Stock Location Level'),
      'extra' => [
        0 => [
          'field' => 'entity_type',
          'value' => 'commerce_product_variation',
        ],
      ],
    ],
  ];

  $data['commerce_stock_location_level'] = [
    'table' => [
      'group' => t('Stock Location Level'),
      'provider' => 'commerce_stock_local',
    ],
    'location_id' => [
      'title' => t('Location ID'),
      'help' => t('The Stock Location ID'),
      'field' => ['id' => 'numeric'],
      'filter' => ['id' => 'numeric'],
      'argument' => ['id' => 'numeric'],
      'sort' => ['id' => 'standard'],
    ],
    'entity_id' => [
      'title' => t('Purchasable entity ID'),
      'help' => t('The purchasable entity ID'),
      'field' => ['id' => 'numeric'],
      'filter' => ['id' => 'numeric'],
      'argument' => ['id' => 'numeric'],
      'sort' => ['id' => 'standard'],
    ],
    'entity_type' => [
      'title' => t('Purchasable entity type'),
      'help' => t('The purchasable entity type'),
      'field' => ['id' => 'standard'],
      'filter' => ['id' => 'string'],
      'argument' => ['id' => 'string'],
      'sort' => ['id' => 'standard'],
    ],
    'qty' => [
      'title' => t('Qty'),
      'help' => t('The stock location quantity'),
      'field' => ['id' => 'numeric'],
      'filter' => ['id' => 'numeric'],
      'argument' => ['id' => 'numeric'],
      'sort' => ['id' => 'standard'],
    ],
    'last_transaction_id' => [
      'title' => t('Last transaction ID'),
      'help' => t('The last transaction ID'),
      'field' => ['id' => 'numeric'],
      'filter' => ['id' => 'numeric'],
      'argument' => ['id' => 'numeric'],
      'sort' => ['id' => 'standard'],
    ],
  ];
}
