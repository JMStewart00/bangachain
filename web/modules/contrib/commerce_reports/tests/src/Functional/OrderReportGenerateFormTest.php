<?php

namespace Drupal\Tests\commerce_reports\Functional;

use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the OrderReportGenerateForm.
 *
 * @group commerce_reports
 */
class OrderReportGenerateFormTest extends CommerceBrowserTestBase {

  /**
   * The number of orders to create for processing (evenly divisible by 3).
   *
   * @var int
   */
  const ORDER_COUNT = 45;

  /**
   * The test orders.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $orders = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_reports',
  ];

  protected static $configSchemaCheckerExclusions = [
    'views.view.sales_report',
    'views.view.purchased_items_report',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access commerce reports',
      'generate commerce order reports',
      'administer commerce_order',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $profile = $this->createEntity('profile', [
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
    // Create ORDER_COUNT orders, with 1/3 in draft state.
    for ($i = 0; $i < self::ORDER_COUNT; $i++) {
      $order_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_order_item');
      $order_item = $order_item_storage->createFromPurchasableEntity($variation, [
        'quantity' => rand(1, 10),
      ]);
      $order_item->save();
      $order_item = $order_item_storage->load($order_item->id());

      $order_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_order');
      $is_completed = ($i % 3 != 0);
      $order = $this->createEntity('commerce_order', [
        'type' => 'default',
        'store_id' => $this->store->id(),
        'cart' => !$is_completed,
        'state' => !$is_completed ? 'draft' : 'completed',
        'mail' => $this->randomString() . '@example.com',
        'uid' => User::getAnonymousUser(),
        'ip_address' => '127.0.0.1',
        'order_number' => '6',
        'billing_profile' => $profile,
        'placed' => $is_completed ? time() : NULL,
      ]);
      $order->addItem($order_item);
      $order->save();
      $this->orders[] = $order_storage->load($order->id());
    }
  }

  /**
   * Tests bulk order report processing for a single order report type.
   */
  public function testGenerateOrderReports() {
    $this->assertEquals(self::ORDER_COUNT, count($this->orders));
    $report_count = (self::ORDER_COUNT * 2) / 3;
    $this->drupalGet('/admin/commerce/config/reports/generate-reports');
    $this->assertSession()->statusCodeEquals(200);

    // Check the integrity of the form and set values.
    $this->assertSession()->fieldExists('plugin_id');
    $this->getSession()->getPage()->selectFieldOption('plugin_id', 'order_report');
    $this->getSession()->getPage()->pressButton('Generate');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains("Generated reports for $report_count orders.");

    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals($report_count, count($order_reports), 'Reports created');
    foreach ($order_reports as $id => $order_report) {
      $this->assertEquals('order_report', $order_report->bundle());
    }
  }

  /**
   * Tests bulk order report processing for all order report types.
   */
  public function testGenerateAllReports() {
    $this->assertEquals(self::ORDER_COUNT, count($this->orders));
    $report_count = (self::ORDER_COUNT * 2) / 3;
    $this->drupalGet('/admin/commerce/config/reports/generate-reports');

    // Check the integrity of the form and set values.
    $this->assertSession()->fieldExists('plugin_id');
    $this->getSession()->getPage()->selectFieldOption('plugin_id', '');
    $this->getSession()->getPage()->pressButton('Generate');
    $this->checkForMetaRefresh();

    $this->assertSession()->pageTextContains("Generated reports for $report_count orders.");

    $order_reports = OrderReport::loadMultiple();
    $this->assertEquals(2 * $report_count, count($order_reports), 'Reports created');
    $order_report_count = 0;
    $order_item_report_count = 0;
    foreach ($order_reports as $order_report) {
      if ($order_report->bundle() == 'order_report') {
        $order_report_count++;
      }
      if ($order_report->bundle() == 'order_items_report') {
        $order_item_report_count++;
      }
    }
    $this->assertEquals($report_count, $order_report_count);
    $this->assertEquals($report_count, $order_item_report_count);
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
