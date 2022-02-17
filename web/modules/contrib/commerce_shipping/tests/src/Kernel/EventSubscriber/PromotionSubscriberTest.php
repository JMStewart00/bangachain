<?php

namespace Drupal\Tests\commerce_shipping\Kernel\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\commerce_shipping\Entity\ShippingMethodInterface;
use Drupal\commerce_shipping\Event\ShippingRatesEvent;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\physical\Weight;
use Drupal\Tests\commerce_shipping\Kernel\ShippingKernelTestBase;

/**
 * Tests the promotion subscriber.
 *
 * @coversDefaultClass \Drupal\commerce_shipping\EventSubscriber\PromotionSubscriber
 *
 * @group commerce_shipping
 */
class PromotionSubscriberTest extends ShippingKernelTestBase implements ServiceModifierInterface {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A sample shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');

    $user = $this->createUser(['mail' => strtolower($this->randomString()) . '@example.com']);
    $this->user = $this->reloadEntity($user);

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
    ]);
    $this->order->save();

    $this->shipment = Shipment::create([
      'type' => 'default',
      'title' => 'Shipment',
      'items' => [
        new ShipmentItem([
          'order_item_id' => 10,
          'title' => 'T-shirt (red, large)',
          'quantity' => 1,
          'weight' => new Weight('10', 'kg'),
          'declared_value' => new Price('15.00', 'USD'),
        ]),
      ],
      'order_id' => $this->order->id(),
      'amount' => new Price("57.88", "USD"),
    ]);
    $this->shipment->save();
  }

  /**
   * Test that things does not crash when we have no shipping promotions.
   */
  public function testSubscriberNoShippingPromotions() {
    /** @var \Drupal\commerce_shipping\EventSubscriber\PromotionSubscriber $subscriber */
    $subscriber = $this->container->get('commerce_shipping.promotion_subscriber');
    $rates = [
      new ShippingRate([
        'shipping_method_id' => 'test',
        'service' => new ShippingService('test', 'Test'),
        'amount' => Price::fromArray([
          'currency_code' => 'USD',
          'number' => 100,
        ]),
      ]),
    ];
    $method = $this->createMock(ShippingMethodInterface::class);
    $event = new ShippingRatesEvent($rates, $method, $this->shipment);

    // Create a promotion as well.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.5',
        ],
      ],
    ]);
    $promotion->save();
    // Now run the subscriber.
    $subscriber->onCalculate($event);
    $this->assertCount(1, $event->getRates());
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('plugin.manager.commerce_promotion_offer')->setClass(TestNoShippingOfferManager::class);
  }

}
