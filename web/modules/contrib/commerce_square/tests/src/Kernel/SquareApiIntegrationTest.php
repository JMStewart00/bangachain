<?php

namespace Drupal\Tests\commerce_square\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\commerce_square\Plugin\Commerce\PaymentGateway\Square;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use SquareConnect\ApiClient;

/**
 * Tests the Square SDK integration with Commerce.
 *
 * @group commerce_square
 */
class SquareApiIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'profile',
    'entity_reference_revisions',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_square',
  ];

  /**
   * The test gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGateway
   */
  protected $gateway;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    $this->container->get('config.factory')
      ->getEditable('commerce_square.settings')
      ->set('sandbox_app_id', 'sandbox-sq0idb-RMT75dFT1toXdUNnW8Ahmw')
      ->set('sandbox_access_token', 'EAAAEA3D3KIn2sjtYE0GjRPMJZPl4aigTyCyAhwojBAfWlr99jx4Wfz9GuCbzwfM')
      ->save();

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'square_connect',
      'label' => 'Square',
      'plugin' => 'square',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'test_location_id' => 'C9HQN1PSN4NKA',
      'mode' => 'test',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();
    $this->gateway = $gateway;
  }

  /**
   * Tests that an API client can be retrieved from gateway plugin.
   */
  public function testGetApiClient() {
    $plugin = $this->gateway->getPlugin();
    $this->assertInstanceOf(Square::class, $plugin);
    $this->assertInstanceOf(ApiClient::class, $plugin->getApiClient());
  }

  /**
   * Tests creating a payment.
   */
  public function testCreatePayment() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-ok'));
  }

  /**
   * Tests creating a payment, with invalid CVV error.
   *
   * @expectedException \Drupal\commerce_payment\Exception\SoftDeclineException
   * @expectedExceptionCode 0
   * @expectedExceptionMessage Card verification code check failed.
   */
  public function testCreatePaymentBadCvv() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-rejected-cvv'));
  }

  /**
   * Tests creating a payment, with invalid postal code error.
   *
   * @expectedException \Drupal\commerce_payment\Exception\SoftDeclineException
   * @expectedExceptionCode 0
   * @expectedExceptionMessage Postal code check failed.
   */
  public function testCreatePaymentBadPostalCode() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-rejected-postalcode'));
  }

  /**
   * Tests creating a payment, with invalid expiration date error.
   *
   * @expectedException \Drupal\commerce_payment\Exception\SoftDeclineException
   * @expectedExceptionCode 0
   * @expectedExceptionMessage Invalid card expiration date.
   */
  public function testCreatePaymentBadExpiryDate() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-rejected-expiration'));
  }

  /**
   * Tests creating a payment, declined.
   *
   * @expectedException \Drupal\commerce_payment\Exception\SoftDeclineException
   * @expectedExceptionCode 0
   * @expectedExceptionMessage Card declined.
   *
   * @todo This should be a hard decline.
   */
  public function testCreatePaymentDeclined() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-declined'));
  }

  /**
   * Tests creating a payment, invalid/used nonce.
   *
   * @expectedException \Drupal\commerce_payment\Exception\SoftDeclineException
   * @expectedExceptionCode 0
   * @expectedExceptionMessage Card verification code check failed.
   */
  public function testCreatePaymentAlreadyUsed() {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\SquareInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('cnon:card-nonce-rejected-cvv'));
  }

  /**
   * Generates a test payment to send over the Square gateway.
   *
   * Square provides specific nonce values which can test different error codes,
   * and how to handle them.
   *
   * @param string $nonce
   *   The test nonce.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The test payment.
   *
   * @see https://docs.connect.squareup.com/articles/using-sandbox
   */
  protected function generateTestPayment($nonce) {
    $user = $this->createUser();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'My order item',
      'quantity' => '2',
      'unit_price' => new Price('5.00', 'USD'),
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
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
      'uid' => $user->id(),
    ]);
    $profile->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->gateway->id(),
      // Thu, 16 Jan 2020.
      'expires' => '1579132800',
      'uid' => $user->id(),
      'remote_id' => $nonce,
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();

    $payment = Payment::create([
      'state' => 'new',
      'amount' => new Price('10.00', 'USD'),
      'payment_gateway' => $this->gateway->id(),
      'payment_method' => $payment_method->id(),
      'order_id' => $order->id(),
    ]);
    return $payment;
  }

}
