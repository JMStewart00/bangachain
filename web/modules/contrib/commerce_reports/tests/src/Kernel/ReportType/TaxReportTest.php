<?php

namespace Drupal\Tests\commerce_reports\Kernel\ReportType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_order_report` entity.
 *
 * @group commerce_reports
 */
class TaxReportTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_reports',
    'commerce_tax',
  ];

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_order_report');
    $this->installEntitySchema('commerce_tax_type');
    $this->installConfig('commerce_order', 'commerce_tax');
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->store->set('prices_include_tax', TRUE);
    $this->store->save();

    $this->reportTypeManager = $this->container->get('plugin.manager.commerce_report_type');

    // The default store is US-WI, so imagine that the US has VAT.
    TaxType::create([
      'id' => 'us_vat',
      'label' => 'US VAT',
      'plugin' => 'custom',
      'configuration' => [
        'display_inclusive' => TRUE,
        'rates' => [
          [
            'id' => 'standard',
            'label' => 'Standard',
            'percentage' => '0.2',
          ],
        ],
        'territories' => [
          ['country_code' => 'US', 'administrative_area' => 'WI'],
          ['country_code' => 'US', 'administrative_area' => 'SC'],
        ],
      ],
    ])->save();
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'placed' => '1518491883',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests and asserts bundle fields present.
   */
  public function testFields() {
    $order_report = OrderReport::create([
      'type' => 'tax_report',
    ]);
    $this->assertTrue($order_report->hasField('tax_amount'));
    $this->assertTrue($order_report->hasField('tax_type_id'));
    $this->assertTrue($order_report->hasField('tax_type_label'));
    $this->assertTrue($order_report->hasField('zone_id'));
    $this->assertTrue($order_report->hasField('zone_label'));
    $this->assertTrue($order_report->hasField('rate_id'));
    $this->assertTrue($order_report->hasField('rate_label'));
  }

  /**
   * Tests order report entity creation for orders without taxes.
   */
  public function testNoTaxes() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('tax_report');

    $adjustments = $this->order->collectAdjustments();
    $this->assertEmpty($adjustments);
    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(0, count($order_reports));
  }

  /**
   * Tests order report entity creation for order with taxes.
   */
  public function testOrderReport() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('tax_report');

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();
    $adjustments = $this->order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('tax', $adjustment->getType());
    $this->assertEquals(new Price('2.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('us_vat|default|standard', $adjustment->getSourceId());

    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(1, count($order_reports));

    $order_report = reset($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals($this->order->id(), $order_report->getOrderId());

    $this->assertFalse($order_report->get('tax_amount')->isEmpty());
    $this->assertEquals($adjustment->getAmount(), $order_report->get('tax_amount')->first()->toPrice());
    $this->assertFalse($order_report->get('tax_type_id')->isEmpty());
    $this->assertEquals('us_vat', $order_report->get('tax_type_id')->target_id);
    $this->assertFalse($order_report->get('tax_type_label')->isEmpty());
    $this->assertEquals('US VAT', $order_report->get('tax_type_label')->value);
    $this->assertFalse($order_report->get('zone_id')->isEmpty());
    $this->assertEquals('default', $order_report->get('zone_id')->value);
    $this->assertFalse($order_report->get('zone_label')->isEmpty());
    $this->assertEquals('Default', $order_report->get('zone_label')->value);
    $this->assertFalse($order_report->get('rate_id')->isEmpty());
    $this->assertEquals('standard', $order_report->get('rate_id')->value);
    $this->assertFalse($order_report->get('rate_label')->isEmpty());
    $this->assertEquals('Standard', $order_report->get('rate_label')->value);
  }

}
