<?php

namespace Drupal\Tests\commerce_reports\Kernel\ReportType;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_order_report` entity.
 *
 * @group commerce_reports
 */
class PromotionReportTest extends CommerceKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_number_pattern',
    'commerce_order',
    'commerce_promotion',
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
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig('commerce_order', 'commerce_promotion');
    $this->installSchema('commerce_number_pattern', ['commerce_number_pattern_sequence']);

    $this->reportTypeManager = $this->container->get('plugin.manager.commerce_report_type');
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->user = $this->reloadEntity($user);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

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
    $this->order = Order::create([
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
  }

  /**
   * Tests and asserts bundle fields present.
   */
  public function testFields() {
    $order_report = OrderReport::create([
      'type' => 'promotion_report',
    ]);
    $this->assertTrue($order_report->hasField('promotion_id'));
    $this->assertTrue($order_report->hasField('promotion_label'));
    $this->assertTrue($order_report->hasField('promotion_amount'));
    $this->assertTrue($order_report->hasField('coupon_id'));
    $this->assertTrue($order_report->hasField('coupon_code'));
  }

  /**
   * Tests order report entity creation for orders without promotions.
   */
  public function testNoPromotions() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('promotion_report');

    $order_item1 = OrderItem::create([
      'title' => 'Product 1',
      'type' => 'test',
      'unit_price' => new Price('12.00', 'USD'),
      'quantity' => 2,
    ]);
    $order_item1->save();
    $this->order->addItem($order_item1);

    $order_item2 = OrderItem::create([
      'title' => 'Product 2',
      'type' => 'test',
      'unit_price' => new Price('30.00', 'USD'),
      'quantity' => 4,
    ]);

    $order_item2->save();
    $this->order->addItem($order_item2);

    $this->order->save();
    $this->assertEquals(2, count($this->order->getItems()));

    // Testing order without adjustments.
    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(0, count($order_reports));

    // Add non-promotion adjustments.
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

    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(0, count($order_reports));
  }

  /**
   * Tests order report entity creation for order with promotions but no coupons.
   */
  public function testPromotionNoCoupons() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('promotion_report');

    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();

    $this->assertNotEmpty($promotion->applies($this->order));
    $promotion->apply($this->order);
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();
    $order_items = $this->order->getItems();
    $order_item = reset($order_items);
    $adjustments = $order_item->getAdjustments();
    $this->assertEquals(1, count($adjustments));

    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(1, count($order_reports));

    $order_report = reset($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals(1234, $order_report->getOrderId());

    $this->assertFalse($order_report->get('promotion_id')->isEmpty());
    $this->assertEquals($promotion->id(), $order_report->get('promotion_id')->target_id);
    $this->assertFalse($order_report->get('promotion_label')->isEmpty());
    $this->assertEquals('Promotion 1', $order_report->get('promotion_label')->value);
    $this->assertFalse($order_report->get('promotion_amount')->isEmpty());
    $this->assertEquals(new Price('-4.00', 'USD'), $order_report->get('promotion_amount')->first()->toPrice());

    $this->assertTrue($order_report->get('coupon_id')->isEmpty());
    $this->assertTrue($order_report->get('coupon_code')->isEmpty());
  }

  /**
   * Tests order report entity creation for order with promotions and coupon.
   */
  public function testPromotionWithCoupons() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('promotion_report');

    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion_with_coupon = Promotion::create([
      'name' => 'Promotion (with coupon)',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion_with_coupon->save();

    $coupon = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion_with_coupon->get('coupons')->appendItem($coupon);
    $promotion_with_coupon->save();

    $this->order->get('coupons')->appendItem($coupon);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();
    $this->assertEquals(1, count($this->order->collectAdjustments()));

    $report_type_plugin->generateReports($this->order);

    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(1, count($order_reports));

    $order_report = reset($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals(1234, $order_report->getOrderId());

    $this->assertFalse($order_report->get('promotion_id')->isEmpty());
    $this->assertEquals($promotion_with_coupon->id(), $order_report->get('promotion_id')->target_id);
    $this->assertFalse($order_report->get('promotion_label')->isEmpty());
    $this->assertEquals('Promotion (with coupon)', $order_report->get('promotion_label')->value);
    $this->assertFalse($order_report->get('promotion_amount')->isEmpty());
    $this->assertEquals(new Price('-4.00', 'USD'), $order_report->get('promotion_amount')->first()->toPrice());

    $this->assertFalse($order_report->get('coupon_id')->isEmpty());
    $this->assertEquals($coupon->id(), $order_report->get('coupon_id')->target_id);
    $this->assertFalse($order_report->get('coupon_code')->isEmpty());
    $this->assertEquals($coupon->getCode(), $order_report->get('coupon_code')->value);
  }

}
