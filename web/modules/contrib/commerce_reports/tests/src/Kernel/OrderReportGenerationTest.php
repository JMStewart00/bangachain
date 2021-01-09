<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_reports\Entity\OrderReport;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test order report generation.
 *
 * @group commerce_reports
 */
class OrderReportGenerationTest extends CommerceKernelTestBase {

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
  }

  /**
   * Tests that order reports are generated when an order is placed.
   */
  public function testOrderReportGeneration() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $product->save();
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
      'uid' => 0,
    ]);
    $profile->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'cart' => TRUE,
      'mail' => $this->randomString() . '@example.com',
      'uid' => User::getAnonymousUser(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
    ]);
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($variation, [
      'quantity' => 1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();
    $workflow = $order->getState()->getWorkflow();
    $order->getState()->applyTransition($workflow->getTransition('place'));
    $order->save();

    $this->assertEquals([$order->id()], $this->container->get('state')->get('commerce_order_reports'));
    $this->container->get('event_dispatcher')->dispatch(KernelEvents::TERMINATE, new PostResponseEvent($this->container->get('kernel'), new Request(), new Response()));
    $this->assertEquals([], $this->container->get('state')->get('commerce_order_reports'));
    /** @var \Drupal\commerce_reports\Entity\OrderReport $order_report */
    foreach (OrderReport::loadMultiple() as $order_report) {
      $this->assertEquals($order_report->getOrderId(), $order->id());
    }
  }

}
