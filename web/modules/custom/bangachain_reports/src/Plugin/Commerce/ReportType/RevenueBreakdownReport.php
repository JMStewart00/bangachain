<?php

namespace Drupal\bangachain_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeBase;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Revenue Breakdown Report.
 *
 * @CommerceReportType(
 *   id = "revenue_breakdown",
 *   label = @Translation("Revenue Breakdown"),
 *   description = @Translation("Revenue Breakdown.")
 * )
 */
class RevenueBreakdownReport extends ReportTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['order_item_type_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item type'))
      ->setDescription(t('The order item type.'))
      ->setSetting('target_type', 'commerce_order_item_type')
      ->setRequired(TRUE);
    $fields['order_item_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item'))
      ->setDescription(t('The parent order item.'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setRequired(TRUE);
    $fields['title'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The purchased item title.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 512,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableHeaders() {
    return [
      'formatted_date' => t('Date'),
      'order_id_count' => t('# Orders'),
      'quantity_sum' => t('# Sold'),
      'adjusted_unit_price_sum' => t('Total revenue'),
      'adjusted_total_price_sum' => t('Average revenue'),
      'unit_price_currency_code' => t('Currency'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableRow(array $result) {
    $currency_code = $result['unit_price_currency_code'];
    $row = [
      $result['formatted_date'],
      $result['order_id_count'],
      $result['quantity_sum'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['adjusted_unit_price_sum'], $currency_code),
          ],
        ],
      ],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['adjusted_total_price_sum'], $currency_code),
          ],
        ],
      ],
      $currency_code,
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReports(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      $values = [
        'order_item_type_id' => $order_item->bundle(),
        'order_item_id' => $order_item->id(),
        'title' => $order_item->label(),
        'quantity' => $order_item->getQuantity(),
        'unit_price' => $order_item->getUnitPrice(),
        'total_price' => $order_item->getTotalPrice(),
        'adjusted_unit_price' => $order_item->getAdjustedUnitPrice(),
        'adjusted_total_price' => $order_item->getAdjustedTotalPrice(),
      ];
      $this->createFromOrder($order, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery(QueryAggregateInterface $query) {
    $query->aggregate('title', 'COUNT');
    $query->aggregate('quantity', 'SUM');
    $query->aggregate('unit_price.number', 'SUM');
    $query->aggregate('total_price.number', 'SUM');
    $query->aggregate('adjusted_unit_price.number', 'SUM');
    $query->aggregate('adjusted_total_price.number', 'SUM');
    $query->groupBy('title');
    $query->groupBy('unit_price.currency_code');
  }

}
