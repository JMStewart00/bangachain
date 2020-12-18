
<?php
/**
 * @file
 *
 * Views will look in this file for any views related hooks. Mainly, this is
 * where you put your hook_views_data() implementation.
 */


/**
 * Implements hook_views_data().
 */
function bangachain_views_views_data_alter(array &$data) {
    $data['commerce_product']['bangachain_views_total_stock'] = array(
        'title' => t('Total Stock for Products'),
        'field' => array(
          'title' => t('Total Stock for Products'),
          'help' => t('Stock numbers'),
          'id' => 'total_stock',
        ),
      );

    $data['commerce_product']['stock_filter'] = array(
      'title' => t('Total Stock Filter'),
      'filter' => array(
        'title' => t('Total Stock Filter'),
        'help' => t('Provides a custom filter for products by their total stock.'),
        'field' => 'total_stock',
        'id' => 'integer'
      ),
    );
}
