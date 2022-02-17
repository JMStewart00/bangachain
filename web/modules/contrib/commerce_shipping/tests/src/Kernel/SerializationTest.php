<?php

namespace Drupal\Tests\commerce_shipping\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\Component\Serialization\Json;
use Drupal\physical\Weight;
use Drupal\profile\Entity\Profile;

/**
 * Tests serialization of shipments.
 *
 * @group commerce_shipping
 */
class SerializationTest extends ShippingKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'jsonapi',
  ];

  /**
   * Tests serialization of a shipment.
   *
   * @dataProvider formatProvider
   */
  public function testSerialization($format) {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();
    $order = $this->reloadEntity($order);
    /** @var \Drupal\commerce_shipping\Entity\ShippingMethodInterface $shipping_method */
    $shipping_method = ShippingMethod::create([
      'name' => $this->randomString(),
      'status' => 1,
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_label' => 'Flat rate',
          'rate_amount' => new Price('1', 'USD'),
        ],
      ],
      'weight' => 1,
    ]);
    $shipping_method->save();
    $shipping_method = $this->reloadEntity($shipping_method);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);
    /** @var \Drupal\commerce_shipping\Entity\Shipment $shipment */
    $shipment = Shipment::create([
      'type' => 'default',
      'state' => 'ready',
      'order_id' => $order->id(),
      'title' => 'Shipment',
      'amount' => new Price('12.00', 'USD'),
      'shipping_profile' => $profile,
      'shipping_method' => $shipping_method,
    ]);
    $items[] = new ShipmentItem([
      'order_item_id' => 10,
      'title' => 'T-shirt (red, large)',
      'quantity' => 2,
      'weight' => new Weight('40', 'kg'),
      'declared_value' => new Price('30', 'USD'),
    ]);
    $items[] = new ShipmentItem([
      'order_item_id' => 9,
      'title' => 'T-shirt (blue, large)',
      'quantity' => 2,
      'weight' => new Weight('30', 'kg'),
      'declared_value' => new Price('30', 'USD'),
    ]);
    $shipment->setItems($items);
    $shipment->save();

    if ($format === 'json') {
      $serializer = $this->container->get('serializer');
    }
    elseif ($format === 'api_json') {
      $serializer = $this->container->get('jsonapi.serializer');
    }
    else {
      throw new \InvalidArgumentException('$format must be json or api_json');
    }

    $normalized = $serializer->normalize($shipment, $format);
    $this->assertAllPrimitives($normalized);

    $json = $serializer->serialize($shipment, $format);
    $decoded = Json::decode($json);
    foreach ($decoded['items'] as $shipment_item_values) {
      // An invalid definition will throw an exception.
      new ShipmentItem($shipment_item_values['value']);
    }
  }

  /**
   * Test data provider.
   *
   * @return \Generator
   *   The test data.
   */
  public function formatProvider(): \Generator {
    yield ['json'];
    yield ['api_json'];
  }

  /**
   * Asserts all items are a primitive value.
   *
   * @param iterable $traversable
   *   The data.
   */
  private function assertAllPrimitives(iterable $traversable) {
    assert(is_array($traversable) || $traversable instanceof \Traversable);
    foreach ($traversable as $value) {
      if (is_array($value) || $value instanceof \Traversable) {
        $this->assertAllPrimitives($value);
      }
      else {
        $this->assertIsNotObject($value);
      }
    }
  }

}
