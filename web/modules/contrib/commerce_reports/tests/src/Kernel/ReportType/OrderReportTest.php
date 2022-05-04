<?php

namespace Drupal\Tests\commerce_reports\Kernel\ReportType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_order_report` entity.
 *
 * @group commerce_reports
 */
class OrderReportTest extends CommerceKernelTestBase {

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
  }

  /**
   * Tests and asserts bundle fields present.
   */
  public function testFields() {
    $order_report = OrderReport::create([
      'type' => 'order_report',
    ]);
    $this->assertTrue($order_report->hasField('order_type_id'));
    $this->assertTrue($order_report->hasField('amount'));
    $this->assertTrue($order_report->hasField('mail'));
    $this->assertTrue($order_report->hasField('billing_address'));
  }

  /**
   * Tests order report entity.
   */
  public function testOrderReport() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('order_report');
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
    $order->get('total_price')->setValue(new Price('100.00', 'USD'));

    $report_type_plugin->generateReports($order);
    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(1, count($order_reports));
    $order_report = reset($order_reports);

    $this->assertEquals('1518491883', $order_report->getCreatedTime());
    $this->assertEquals(1234, $order_report->getOrderId());

    $this->assertFalse($order_report->get('order_type_id')->isEmpty());
    $this->assertEquals($order->bundle(), $order_report->get('order_type_id')->first()->target_id);

    $this->assertFalse($order_report->get('amount')->isEmpty());
    $this->assertEquals(new Price('100.00', 'USD'), $order_report->get('amount')->first()->toPrice());

    $this->assertFalse($order_report->get('mail')->isEmpty());
    $this->assertEquals($this->user->getEmail(), $order_report->get('mail')->first()->value);

    $this->assertFalse($order_report->get('billing_address')->isEmpty());
    $this->assertEquals($profile->get('address')->first()->toArray(), $order_report->get('billing_address')->first()->toArray());
  }

  /**
   * Confirm we can do report generation if we have no billing profile.
   */
  public function testOrderReportNoBillingProfile() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('order_report');
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
      'billing_profile' => NULL,
      'store_id' => $this->store->id(),
    ]);
    $order->get('total_price')->setValue(new Price('100.00', 'USD'));

    $report_type_plugin->generateReports($order);
    /** @var \Drupal\commerce_reports\Entity\OrderReport[] $order_reports */
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(1, count($order_reports));
  }

}
