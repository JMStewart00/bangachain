<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Test Views integration.
 *
 * @group commerce_reports
 */
class ViewsIntegrationTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_reports',
    'views',
  ];

  public function testViewsData() {
    $views_data = $this->container->get('views.views_data')->getAll();

    // Assert that the bundle field tables exist.
    $this->assertNotEmpty($views_data['commerce_order_report__order_type_id']);
    $this->assertNotEmpty($views_data['commerce_order_report__amount']);
    $this->assertNotEmpty($views_data['commerce_order_report__mail']);
    $this->assertNotEmpty($views_data['commerce_order_report__billing_address']);
    $this->assertNotEmpty($views_data['commerce_order_report__order_item_type_id']);
    $this->assertNotEmpty($views_data['commerce_order_report__order_item_id']);
    $this->assertNotEmpty($views_data['commerce_order_report__title']);
    $this->assertNotEmpty($views_data['commerce_order_report__quantity']);
    $this->assertNotEmpty($views_data['commerce_order_report__unit_price']);
    $this->assertNotEmpty($views_data['commerce_order_report__total_price']);
    $this->assertNotEmpty($views_data['commerce_order_report__adjusted_unit_price']);
    $this->assertNotEmpty($views_data['commerce_order_report__adjusted_total_price']);

    // Make sure hook_field_views_data() is respected.
    $this->assertNotEmpty($views_data['commerce_order_report__amount']['amount_number']['field']);
    $this->assertNotEmpty($views_data['commerce_order_report__unit_price']['unit_price_number']['field']);
    $this->assertNotEmpty($views_data['commerce_order_report__total_price']['total_price_number']['field']);
    $this->assertNotEmpty($views_data['commerce_order_report__adjusted_unit_price']['adjusted_unit_price_number']['field']);
    $this->assertNotEmpty($views_data['commerce_order_report__adjusted_total_price']['adjusted_total_price_number']['field']);

    $this->assertNotEmpty($views_data['commerce_order_report__billing_address']['billing_address_country_code']['field']);
  }

}
