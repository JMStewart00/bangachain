<?php

namespace Drupal\Tests\commerce_reports\Kernel\ReportType;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_order_report` entity.
 *
 * @group commerce_reports
 */
class OrderItemsReportTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_number_pattern',
    'commerce_order',
    'commerce_reports',
  ];

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_order_report');
    $this->installConfig('commerce_order');
    $this->installSchema('commerce_number_pattern', ['commerce_number_pattern_sequence']);

    $this->reportTypeManager = $this->container->get('plugin.manager.commerce_report_type');
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->user = $this->reloadEntity($user);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests and asserts bundle fields present.
   */
  public function testFields() {
    $order_report = OrderReport::create([
      'type' => 'order_items_report',
    ]);
    $this->assertTrue($order_report->hasField('order_item_type_id'));
    $this->assertTrue($order_report->hasField('order_item_id'));
    $this->assertTrue($order_report->hasField('title'));
    $this->assertTrue($order_report->hasField('quantity'));
    $this->assertTrue($order_report->hasField('unit_price'));
    $this->assertTrue($order_report->hasField('total_price'));
    $this->assertTrue($order_report->hasField('adjusted_unit_price'));
    $this->assertTrue($order_report->hasField('adjusted_total_price'));
  }

  /**
   * Tests order report entity.
   */
  public function testOrderReport() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('order_items_report');
    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $this->user->id(),
    ]);
    $profile->save();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'order_id' => 1234,
      'type' => 'default',
      'state' => 'completed',
      'placed' => '1518491883',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
    ]);

    $order_item1 = OrderItem::create([
      'title' => 'Product 1',
      'type' => 'test',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    // Set quantity so total price calculates.
    $order_item1->setQuantity(2);
    $order_item1->save();
    $order->addItem($order_item1);

    $order_item2 = OrderItem::create([
      'title' => 'Product 2',
      'type' => 'test',
      'unit_price' => new Price('30.00', 'USD'),
    ]);
    // Set quantity so total price calculates.
    $order_item2->setQuantity(4);
    $order_item2->save();
    $order->addItem($order_item2);

    $order->save();
    $this->assertEquals(2, count($order->getItems()));

    $report_type_plugin->generateReports($order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(2, count($order_reports));
    $order_report = reset($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals(1234, $order_report->getOrderId());
    $this->assertFalse($order_report->get('order_item_type_id')->isEmpty());
    $this->assertEquals('test', $order_report->get('order_item_type_id')->first()->target_id);
    $this->assertEquals(2, $order_report->get('quantity')->value);
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('12.00', 'USD'), $order_report->get('unit_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('24.00', 'USD'), $order_report->get('total_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('12.00', 'USD'), $order_report->get('adjusted_unit_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('24.00', 'USD'), $order_report->get('adjusted_total_price')->first()->toPrice());

    // Test order items with adjustments.
    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-3.00', 'USD'),
      'percentage' => '0.1',
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => 'Random fee',
      'amount' => new Price('2.00', 'USD'),
    ]);
    $order_item2->setAdjustments($adjustments);
    $order_item2->save();
    $this->assertEquals($adjustments, $order_item2->getAdjustments());
    $this->assertEquals(new Price('29.75', 'USD'), $order_item2->getAdjustedUnitPrice());
    $this->assertEquals(new Price('119.00', 'USD'), $order_item2->getAdjustedTotalPrice());

    /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    $order_report = OrderReport::create([
      'type' => 'order_items_report',
      'order_id' => $order->id(),
      'created' => $order->getPlacedTime(),
    ]);

    // Delete order reports and regenerate.
    foreach ($order_reports as $order_report) {
      $order_report->delete();
    }
    $report_type_plugin->generateReports($order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(2, count($order_reports));
    $order_report = next($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals(1234, $order_report->getOrderId());
    $this->assertFalse($order_report->get('order_item_type_id')->isEmpty());
    $this->assertEquals('test', $order_report->get('order_item_type_id')->first()->target_id);
    $this->assertEquals(4, $order_report->get('quantity')->value);

    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('30.00', 'USD'), $order_report->get('unit_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('120.00', 'USD'), $order_report->get('total_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('29.75', 'USD'), $order_report->get('adjusted_unit_price')->first()->toPrice());
    $this->assertFalse($order_report->get('unit_price')->isEmpty());
    $this->assertEquals(new Price('119.00', 'USD'), $order_report->get('adjusted_total_price')->first()->toPrice());
  }

}
