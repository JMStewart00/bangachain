<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test order report generator service.
 *
 * @group commerce_reports
 */
class OrderReportGeneratorTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_reports',
  ];

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The profile.
   *
   * @var \Drupal\profile\Entity\Profile
   */
  protected $profile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installEntitySchema('commerce_order_report');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $this->variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $this->variation->save();

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->product->save();

    /** @var \Drupal\profile\Entity\Profile $profile */
    $this->profile = Profile::create([
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
      'uid' => 0,
    ]);
    $this->profile->save();
  }

  /**
   * Tests the generate reports method.
   */
  public function testGenerateReports() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    /** @var \Drupal\commerce_reports\OrderReportGeneratorInterface $orderReportGenerator */
    $orderReportGenerator = $this->container->get('commerce_reports.order_report_generator');

    // Verify that no reports are created for a draft order.
    $processed = $orderReportGenerator->generateReports([$order->id()]);
    $this->assertEquals($processed, 0);
    $order_reports = OrderReport::loadMultiple();
    $this->assertEmpty($order_reports);

    // Set the state to 'completed' for the order.
    $order->set('state', 'completed');
    $order->save();

    // Verify order reports generated for placed order.
    $processed = $orderReportGenerator->generateReports([$order->id()]);
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertNotEmpty($order_reports);

    /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    foreach ($order_reports as $order_report) {
      $this->assertEquals($order_report->getOrderId(), $order->id());
    }
    $order_report = OrderReport::load(1);

    // Create a second order.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order_2 */
    $order_2 = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item_2 = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item_2->save();
    $order_2->addItem($order_item_2);
    $order_2->save();

    // Only the placed order should be processed by the report generator.
    $processed = $orderReportGenerator->generateReports([$order->id(), $order_2->id()]);
    $this->assertEquals($processed, 1);

    // Now both orders should be processed.
    $order_2->set('state', 'completed');
    $order_2->save();
    $processed = $orderReportGenerator->generateReports([$order->id(), $order_2->id()]);
    $this->assertEquals($processed, 2);
  }

  /**
   * Tests the refresh reports method.
   */
  public function testRefreshReports() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->set('state', 'completed');
    $order->save();

    /** @var \Drupal\commerce_reports\OrderReportGeneratorInterface $orderReportGenerator */
    $orderReportGenerator = $this->container->get('commerce_reports.order_report_generator');

    // Verify order reports generated.
    $processed = $orderReportGenerator->refreshReports([$order->id()]);
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertNotEmpty($order_reports);
    $reports_created = count($order_reports);

    /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    foreach (OrderReport::loadMultiple() as $order_report) {
      $this->assertEquals($order_report->getOrderId(), $order->id());
    }

    // Change the order amount and verify that new order report is generated.
    $order_item->setQuantity(2);
    $order_item->save();
    $order->setItems([$order_item]);
    $order->save();

    $processed = $orderReportGenerator->refreshReports([$order->id()]);
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertNotEmpty($order_reports);

    // Verify that previously generated order reports have been deleted.
    for ($index = 1; $index <= $reports_created; $index++) {
      $this->assertEmpty(OrderReport::load($index));
    }

    // Create a second order.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order_2 */
    $order_2 = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item_2 = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item_2->save();
    $order_2->addItem($order_item_2);
    $order_2->save();

    // Only the placed order should be processed by the report generator.
    $processed = $orderReportGenerator->refreshReports([$order->id(), $order_2->id()]);
    $this->assertEquals($processed, 1);

    // Now both orders should be processed.
    $order_2->set('state', 'completed');
    $order_2->save();
    $processed = $orderReportGenerator->refreshReports([$order->id(), $order_2->id()]);
    $this->assertEquals($processed, 2);
  }

  /**
   * Tests the generate reports method for a single plugin type.
   */
  public function testGenerateSinglePluginReports() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    // Set the state to 'completed' for the order.
    $order->set('state', 'completed');
    $order->save();

    /** @var \Drupal\commerce_reports\OrderReportGeneratorInterface $orderReportGenerator */
    $orderReportGenerator = $this->container->get('commerce_reports.order_report_generator');

    // Verify that no reports are created for an invalid plugin type.
    $processed = $orderReportGenerator->generateReports([$order->id()], 'none');
    $this->assertEquals($processed, 0);
    $order_reports = OrderReport::loadMultiple();
    $this->assertEmpty($order_reports);

    // Verify that only order item reports are generated.
    $processed = $orderReportGenerator->generateReports([$order->id()], 'order_items_report');
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertNotEmpty($order_reports);
    foreach ($order_reports as $order_report) {
      $this->assertEquals('order_items_report', $order_report->bundle());
    }
  }

  /**
   * Tests the refresh reports method for a single plugin method.
   */
  public function testRefreshSinglePluginReports() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $this->profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->set('state', 'completed');
    $order->save();

    /** @var \Drupal\commerce_reports\OrderReportGeneratorInterface $orderReportGenerator */
    $orderReportGenerator = $this->container->get('commerce_reports.order_report_generator');

    // Verify that no reports are created for an invalid plugin type.
    $processed = $orderReportGenerator->refreshReports([$order->id()], 'none');
    $this->assertEquals($processed, 0);
    $order_reports = OrderReport::loadMultiple();
    $this->assertEmpty($order_reports);

    // Verify that only order reports are generated.
    $processed = $orderReportGenerator->refreshReports([$order->id()], 'order_report');
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(count($order_reports), 1);
     /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    $order_report = reset($order_reports);
    // Verify order report data.
    $this->assertEquals('order_report', $order_report->bundle());
    $this->assertEquals($order_report->getOrderId(), $order->id());
    $this->assertTrue($order_report->hasField('amount'), 'Default order report has the amount field');
    $this->assertEquals($order_report->get('amount')->first()->toPrice(), $order->getTotalPrice());

    // Verify that existing order reports are not deleted when order item reports generated.
    $processed = $orderReportGenerator->refreshReports([$order->id()], 'order_items_report');
    $this->assertEquals($processed, 1);
    $order_reports = OrderReport::loadMultiple();
    $this->assertNotEmpty($order_reports);
    $has_order_reports = FALSE;
    $has_order_items_reports = FALSE;
    foreach ($order_reports as $order_report) {
      if ($order_report->bundle() == 'order_report') {
        $has_order_reports = TRUE;
      }
      if ($order_report->bundle() == 'order_items_report') {
        $has_order_items_reports = TRUE;
      }
    }
    $this->assertTrue($has_order_reports);
    $this->assertTrue($has_order_items_reports);
  }

}
