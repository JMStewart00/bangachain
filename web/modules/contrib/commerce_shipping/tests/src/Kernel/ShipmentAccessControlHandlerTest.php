<?php

namespace Drupal\Tests\commerce_shipping\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_shipping\Entity\Shipment;

/**
 * Tests the shipment access control.
 *
 * @coversDefaultClass \Drupal\commerce_shipping\ShipmentAccessControlHandler
 * @group commerce
 */
class ShipmentAccessControlHandlerTest extends ShippingKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'canceled',
    ]);
    $order->save();
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = Shipment::create([
      'type' => 'default',
      'title' => 'Shipment',
      'order_id' => $order->id(),
    ]);
    $shipment->save();
    $shipment = $this->reloadEntity($shipment);

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($shipment->access('view', $account));
    $this->assertFalse($shipment->access('update', $account));
    $this->assertFalse($shipment->access('delete', $account));

    $account = $this->createUser([], ['view commerce_order']);
    $this->assertTrue($shipment->access('view', $account));
    $this->assertFalse($shipment->access('update', $account));
    $this->assertFalse($shipment->access('delete', $account));

    $account = $this->createUser([], ['update default commerce_order']);
    $this->assertFalse($shipment->access('view', $account));
    $this->assertTrue($shipment->access('update', $account));
    $this->assertFalse($shipment->access('delete', $account));

    $account = $this->createUser([], ['view commerce_order', 'update default commerce_order']);
    $this->assertTrue($shipment->access('view', $account));
    $this->assertTrue($shipment->access('update', $account));
    $this->assertFalse($shipment->access('delete', $account));

    $account = $this->createUser([], [
      'manage default commerce_shipment',
    ]);
    $this->assertTrue($shipment->access('view', $account));
    $this->assertTrue($shipment->access('update', $account));
    $this->assertTrue($shipment->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_shipment']);
    $this->assertTrue($shipment->access('view', $account));
    $this->assertTrue($shipment->access('update', $account));
    $this->assertTrue($shipment->access('delete', $account));

    // Broken order reference.
    $shipment->set('order_id', '999');
    $account = $this->createUser([], ['manage default commerce_order_item']);
    $this->assertFalse($shipment->access('view', $account));
    $this->assertFalse($shipment->access('update', $account));
    $this->assertFalse($shipment->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_shipment');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('default', $account));

    $account = $this->createUser([], ['administer commerce_shipment']);
    $this->assertTrue($access_control_handler->createAccess('default', $account));

    $account = $this->createUser([], ['manage default commerce_shipment']);
    $this->assertTrue($access_control_handler->createAccess('default', $account));
  }

}
