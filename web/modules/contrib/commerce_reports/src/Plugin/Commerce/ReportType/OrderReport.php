<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the basic Order Report.
 *
 * @CommerceReportType(
 *   id = "order_report",
 *   label = @Translation("Order Report"),
 *   description = @Translation("Basic order report with order id, total, and created date")
 * )
 */
class OrderReport extends ReportTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['order_type_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Order type'))
      ->setDescription(t('The order type.'))
      ->setSetting('target_type', 'commerce_order_type')
      ->setReadOnly(TRUE);
    $fields['amount'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Total Amount'))
      ->setDescription(t('The total amount of the order'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the order.'))
      ->setCardinality(1)
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['billing_address'] = BundleFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The billing address.'))
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReports(OrderInterface $order) {
    $billing_profile = $order->getBillingProfile();
    $values = [
      'order_type_id' => $order->bundle(),
      'amount' => $order->getTotalPrice(),
      'mail' => $order->getEmail(),
    ];
    if ($billing_profile) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $billing_profile->get('address')->first();
      if ($address !== NULL) {
        $values['billing_address'] = $address->toArray();
      }
    }
    $this->createFromOrder($order, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery(QueryAggregateInterface $query) {
    $query->aggregate('mail', 'COUNT');
    $query->aggregate('amount.number', 'SUM');
    $query->aggregate('amount.number', 'AVG');
    $query->groupBy('amount.currency_code');
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableHeaders() {
    return [
      'formatted_date' => t('Date'),
      'order_id_count' => t('# Orders'),
      'mail_count' => t('# Customers'),
      'amountnumber_sum' => t('Total revenue'),
      'amountnumber_avg' => t('Average revenue'),
      'amount_currency_code' => t('Currency'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableRow(array $result) {
    $currency_code = $result['amount_currency_code'];
    $row = [
      $result['formatted_date'],
      $result['order_id_count'],
      $result['mail_count'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['amountnumber_sum'], $currency_code),
          ],
        ],
      ],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['amountnumber_avg'], $currency_code),
          ],
        ],
      ],
      $currency_code,
    ];
    return $row;
  }

}
