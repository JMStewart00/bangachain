<?php

namespace Drupal\Tests\commerce_multi_payment\FunctionalJavascript;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests the integration between payments and checkout.
 *
 * @group commerce
 */
class MultiplePaymentsCheckoutTest extends CommerceBrowserTestBase {
  
  use JavascriptTestTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * A non-reusable order payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $orderPaymentMethod;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_multi_payment',
    'commerce_multi_payment_example'
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer profile',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '750',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
      'conditions' => [
        [
          'plugin' => 'order_total_price',
          'configuration' => [
            'operator' => '>',
            'amount' => [
              'number' => '0',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'gift_card',
      'label' => 'Gift Card',
      'plugin' => 'commerce_multi_payment_example_giftcard',
      'status' => FALSE,
      'configuration' => [
        'display_label' => 'Apply a gift card',
        'multi_payment' => TRUE,
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'store_credit',
      'label' => 'Store Credit',
      'plugin' => 'commerce_multi_payment_example_storecredit',
      'status' => FALSE,
      'configuration' => [
        'display_label' => 'Apply store credit',
        'multi_payment' => TRUE,
      ],
    ]);
    $gateway->save();

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
      'uid' => $this->adminUser->id(),
    ]);
    $payment_method = $this->createEntity('commerce_payment_method', [
      'uid' => $this->adminUser->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1111',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();

  }

  /**
   * Tests the structure of the PaymentInformation and Apply Multiple Payments checkout pane.
   */
  public function testPaymentInformation() {
    $cart = $this->addProductToCart($this->product);
    
    $this->drupalGet('checkout/' . $cart->id());
    $this->assertSession()->pageTextContains('Payment information');

    $css_selector_converter = new CssSelectorConverter();

    $standard_payment_options = $this->getStandardPaymentOptionsFromPaymentInfoPane();
    $this->assertEquals(2, count($standard_payment_options));
    $this->assertNotEmpty($standard_payment_options[1]);
    $this->assertNotEmpty($standard_payment_options['new--credit_card--onsite']);
    $this->assertNotEmpty($standard_payment_options[1]['is_checked']);

    $xpath = $css_selector_converter->toXPath('#edit-multi-payment-apply #edit-multi-payment-apply-gift-card details');
    $gift_card_details = $this->xpath($xpath);
    $this->assertEquals(1, count($gift_card_details));
    $new_gift_card = reset($gift_card_details);
    $this->assertEquals('open', $new_gift_card->getAttribute('open'));
    $label = $new_gift_card->find('xpath', $css_selector_converter->toXPath('summary'));
    $this->assertNotEmpty($label);
    $this->assertEquals('Apply a gift card', $label->getText());
    
    // Admin user can't see store credit because UID = 2, and name is random.
    $xpath = $css_selector_converter->toXPath('#edit-multi-payment-apply #edit-multi-payment-apply-store-credit details');
    $store_credit_details = $this->xpath($xpath);
    $this->assertEmpty($store_credit_details);
    
    // Add "credit" to user's name to activate example credit.
    $this->adminUser->set('name', $this->adminUser->get('name')->value . '_credit')->save();
    
    $this->drupalGet('checkout/' . $cart->id());
    $store_credit_details = $this->xpath($xpath);
    
    $this->assertEquals(1, count($store_credit_details));
    $store_credit = reset($store_credit_details);
    $this->assertEmpty($store_credit->getAttribute('open'));
    $label = $store_credit->find('xpath', $css_selector_converter->toXPath('summary'));
    $this->assertNotEmpty($label);
    $this->assertEquals('Apply store credit: $300.00 available', $label->getText());

    // Check the Summary for Adjustments
    
    // Add gift card
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());

    // Try to apply same gift card again. Should only be one.
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('This gift card has already been added to the order.');
    
    // Try to apply an invalid gift card.
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3222');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('Gift card 3222 has been declined.');

    // Add a second gift card to the order
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '5111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextNotContains('This gift card has already been added to the order.');
    $this->assertSession()->pageTextNotContains('Gift card 3222 has been declined.');
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 5111', '-$250.00'));
    
    $xpath = $css_selector_converter->toXPath('#edit-multi-payment-apply #edit-multi-payment-apply-gift-card details');
    $gift_card_details = $this->xpath($xpath);
    $this->assertEquals(3, count($gift_card_details));
    $standard_payment_options = $this->getStandardPaymentOptionsFromPaymentInfoPane();
    $this->assertEquals(0, count($standard_payment_options));
    
    // Try to add more from gift card #2. Should be ignored because we have already paid for entire order.
    $amount_field_name = $gift_card_details[1]->find('xpath', $css_selector_converter->toXPath('input[type=text]'))->getAttribute('name');
    preg_match('/(\d+)\]\[amount\]\[number\]$/', $amount_field_name, $matches);
    $stored_payment_id = $matches[1];
    
    $apply_button_field_name = 'apply_gift_card_payment_' . $stored_payment_id;
    $this->getSession()->getPage()->fillField($amount_field_name, '400');
    $this->getSession()->getPage()->pressButton($apply_button_field_name);
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 5111', '-$250.00'));
    $this->assertEquals('250.00', $gift_card_details[1]->find('xpath', $css_selector_converter->toXPath('input[type=text]'))->getAttribute('value'));
    
    // Remove the second gift card
    $remove_button_field_name = 'remove_gift_card_payment_' . $stored_payment_id;
    $this->getSession()->getPage()->pressButton($remove_button_field_name);
    $this->waitForAjaxToFinish();
    $this->assertFalse($this->orderAdjustmentExists('Gift card: 5111'));
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));

    $xpath = $css_selector_converter->toXPath('#edit-multi-payment-apply #edit-multi-payment-apply-gift-card details');
    $gift_card_details = $this->xpath($xpath);
    $this->assertEquals(2, count($gift_card_details));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());
    $standard_payment_options = $this->getStandardPaymentOptionsFromPaymentInfoPane();
    $this->assertEquals(2, count($standard_payment_options));


    // Add store credit to make the order free.
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="multi_payment_apply-store_credit-form-ajax-wrapper"] details summary');
    $this->getSession()->getPage()->find('xpath', $xpath)->click();
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '250');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$250.00'));
    $this->assertEquals('$0.00', $this->getOrderTotalFromSummary());
    
    // Confirm that payment information pane removes the CC options
    $standard_payment_options = $this->getStandardPaymentOptionsFromPaymentInfoPane();
    $this->assertEquals(0, count($standard_payment_options));
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="edit-payment-information"] [data-drupal-selector="edit-payment-information-billing-information"]');
    $this->assertNotEmpty($this->xpath($xpath));
  }

  /**
   * Tests the structure review page when multiple payments are staged for the order.
   */
  public function testReviewPage() {
    // Add "credit" to user's name to activate example credit.
    $this->adminUser->set('name', $this->adminUser->get('name')->value . '_credit')->save();
    
    $cart = $this->addProductToCart($this->product);

    $this->drupalGet('checkout/' . $cart->id());

    $css_selector_converter = new CssSelectorConverter();

    // Add gift card
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());

    // Check review page for gift card being listed.
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    
    // Go back to payment information page
    $this->clickLink('Go back');
    
    // Add a second gift card for a small amount.
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '5111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 5111', '-$250.00'));
    
    $xpath = $css_selector_converter->toXPath('#edit-multi-payment-apply #edit-multi-payment-apply-gift-card details');
    $gift_card_details = $this->xpath($xpath);;
    $amount_field_name = $gift_card_details[1]->find('xpath', $css_selector_converter->toXPath('input[type=text]'))->getAttribute('name');
    preg_match('/(\d+)\]\[amount\]\[number\]$/', $amount_field_name, $matches);
    $stored_payment_id = $matches[1];

    $apply_button_field_name = 'apply_gift_card_payment_' . $stored_payment_id;
    $this->getSession()->getPage()->fillField($amount_field_name, '50');
    $this->getSession()->getPage()->pressButton($apply_button_field_name);
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 5111', '-$50.00'));
    
    // Go to review page, check for both cards listed.
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    $this->assertSession()->pageTextContains('Gift card: 5111: $50.00');
    
    // Go back to review page, add store credit for remaining.
    $this->clickLink('Go back');
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="multi_payment_apply-store_credit-form-ajax-wrapper"] details summary');
    $this->getSession()->getPage()->find('xpath', $xpath)->click();
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '200');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$200.00'));
    
    // Go to review page, check that all payments are shown.
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    $this->assertSession()->pageTextContains('Gift card: 5111: $50.00');
    $this->assertSession()->pageTextContains('Store credit: $200.00');

    
  }

  /**
   * Tests checkouts when all the payment providers succeed.
   */
  public function testSuccessfulCheckout() {
    // Add "credit" to user's name to activate example credit.
    $this->adminUser->set('name', $this->adminUser->get('name')->value . '_credit')->save();
    
    $cart = $this->addProductToCart($this->product);
    $this->drupalGet('checkout/' . $cart->id());

    $css_selector_converter = new CssSelectorConverter();

    // Add gift card
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());

    // Add store credit, for not the full amount.
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="multi_payment_apply-store_credit-form-ajax-wrapper"] details summary');
    $this->getSession()->getPage()->find('xpath', $xpath)->click();
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '200');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$200.00'));
    $this->assertEquals('$50.00', $this->getOrderTotalFromSummary());

    // Go to review page, check that all payments are shown.
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    $this->assertSession()->pageTextContains('Store credit: $200.00');
    $this->assertSession()->pageTextContains('Visa ending in 1111');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$cart->id()]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($cart->id());
    
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertEquals(3, count($payments));
    $found_payment_gateways = [];
    foreach ($payments as $payment) {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      switch ($payment->getPaymentGatewayId()) {
        case 'gift_card':
          $found_payment_gateways['gift_card'] = 'gift_card';
          $this->assertEquals(500, $payment->getAmount()->getNumber());
          $this->assertEquals('USD', $payment->getAmount()->getCurrencyCode());
          $this->assertEquals('3111', $payment->getRemoteId());
          $this->assertEquals('completed', $payment->getState()->getString());
          break;
        case 'store_credit':
          $found_payment_gateways['store_credit'] = 'store_credit';
          $this->assertEquals(200, $payment->getAmount()->getNumber());
          $this->assertEquals('USD', $payment->getAmount()->getCurrencyCode());
          $this->assertEquals('completed', $payment->getState()->getString());
          break;
        case 'onsite':
          $found_payment_gateways['onsite'] = 'onsite';
          $this->assertEquals(50, $payment->getAmount()->getNumber());
          $this->assertEquals('USD', $payment->getAmount()->getCurrencyCode());
          $this->assertEquals('completed', $payment->getState()->getString());
          break;
      }
    }
    $this->assertEquals(3, count($found_payment_gateways));

    // Create a second order, with only payments from multi-payment.
    $cart = $this->addProductToCart($this->product);
    $this->drupalGet('checkout/' . $cart->id());

    $css_selector_converter = new CssSelectorConverter();

    // Add gift card
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());

    // Add store credit, for the full amount
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="multi_payment_apply-store_credit-form-ajax-wrapper"] details summary');
    $this->getSession()->getPage()->find('xpath', $xpath)->click();
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '250');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$250.00'));
    $this->assertEquals('$0.00', $this->getOrderTotalFromSummary());

    // Go to review page, check that all payments are shown.
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    $this->assertSession()->pageTextContains('Store credit: $250.00');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 2.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$cart->id()]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($cart->id());

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertEquals(2, count($payments));
    $found_payment_gateways = [];
    foreach ($payments as $payment) {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      switch ($payment->getPaymentGatewayId()) {
        case 'gift_card':
          $found_payment_gateways['gift_card'] = 'gift_card';
          $this->assertEquals(500, $payment->getAmount()->getNumber());
          $this->assertEquals('USD', $payment->getAmount()->getCurrencyCode());
          $this->assertEquals('3111', $payment->getRemoteId());
          $this->assertEquals('completed', $payment->getState()->getString());
          break;
        case 'store_credit':
          $found_payment_gateways['store_credit'] = 'store_credit';
          $this->assertEquals(250, $payment->getAmount()->getNumber());
          $this->assertEquals('USD', $payment->getAmount()->getCurrencyCode());
          $this->assertEquals('completed', $payment->getState()->getString());
          break;
      }
    }
    $this->assertEquals(2, count($found_payment_gateways));
  }

  /**
   * Tests checkouts when payment providers fail
   */
  public function testFailedCheckout() {
    // Add "credit" to user's name to activate example credit.
    $this->adminUser->set('name', $this->adminUser->get('name')->value . '_credit')->save();

    // Tests when first multi-payment fails.
    
    $cart = $this->addProductToCart($this->product);
    $this->drupalGet('checkout/' . $cart->id());

    $css_selector_converter = new CssSelectorConverter();

    // Add gift card
    $this->getSession()->getPage()->fillField('multi_payment_apply[gift_card][form][new][gift_card_number]', '3111');
    $this->getSession()->getPage()->pressButton('add_gift_card_gift_card');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Gift card: 3111', '-$500.00'));
    $this->assertEquals('$250.00', $this->getOrderTotalFromSummary());

    // Add store credit, for not the full amount.
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="multi_payment_apply-store_credit-form-ajax-wrapper"] details summary');
    $this->getSession()->getPage()->find('xpath', $xpath)->click();
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '200');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$200.00'));
    $this->assertEquals('$50.00', $this->getOrderTotalFromSummary());

    // Go to review page, check that all payments are shown.
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Gift card: 3111: $500.00');
    $this->assertSession()->pageTextContains('Store credit: $200.00');
    $this->assertSession()->pageTextContains('Visa ending in 1111');

    // Change the remote ID for the gift card, so that it will fail on submit.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $staged_payment_storage */
    $staged_payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_staged_multi_payment');
    $staged_payments = $staged_payment_storage->loadByProperties(['order_id' => $cart->id()]);
    foreach ($staged_payments as $staged_payment) {
      /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
      if ($staged_payment->getPaymentGatewayId() == 'gift_card') {
        $staged_payment->setData('remote_id', '3222')->save();
      }
    }
    
    $this->submitForm([], 'Pay and complete purchase');
    
    $this->assertContains('checkout/' . $cart->id() . '/order_information', $this->getSession()->getCurrentUrl());
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$cart->id()]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($cart->id());

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertEquals(0, count($payments));
    
    // Fix the gift card staged payment.
    foreach ($staged_payments as $staged_payment) {
      /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
      if ($staged_payment->getPaymentGatewayId() == 'gift_card') {
        $staged_payment->setData('remote_id', '3111')->save();
      }
    }

    // Change user name so that store credit fails.
    $this->adminUser->set('name', 'admin_person')->save();

    $this->submitForm([], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');

    $this->assertContains('checkout/' . $cart->id() . '/order_information', $this->getSession()->getCurrentUrl());
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method');

    // Gift card payment should exist, but be refunded.
    $order = $order_storage->load($cart->id());
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertEquals(1, count($payments));
    $payment = reset($payments);
    
    $this->assertEquals('gift_card', $payment->getPaymentGatewayId());
    $this->assertEquals('authorization_voided', $payment->getState()->getString());
    
    // Delete this payment to clear things up for next test.
    $payment->delete();

    // Change user name so that store credit succeeds.
    $this->adminUser->set('name', 'admin_credit')->save();
    
    // Now, we test with a failed CC after both multi-payments are complete
    $this->drupalGet('checkout/' . $cart->id() . '/order_information');
    
    // Add store credit, for not the full amount.
    $this->getSession()->getPage()->fillField('multi_payment_apply[store_credit][form][store_credit][amount][number]', '200');
    $this->getSession()->getPage()->pressButton('store_credit_apply_store_credit_payment');
    $this->waitForAjaxToFinish();
    $this->assertTrue($this->orderAdjustmentExists('Store credit', '-$200.00'));
    $this->assertEquals('$50.00', $this->getOrderTotalFromSummary());

    $radio_button = $this->getSession()->getPage()->findField('New credit card');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4111111111111111',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2020',
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'Somewhere',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'WI',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '53140',
    ], 'Continue to review');

    $this->submitForm([], 'Pay and complete purchase');
    
    $this->assertContains('checkout/' . $cart->id() . '/order_information', $this->getSession()->getCurrentUrl());
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($cart->id());

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertEquals(2, count($payments));
    $found_payment_gateways = [];
    foreach ($payments as $payment) {
      $this->assertTrue(in_array($payment->getState()->getString(), ['authorization_voided', 'refunded']));
      $found_payment_gateways[$payment->getPaymentGatewayId()] = $payment->getPaymentGatewayId();
    }
    $this->assertEquals(2, count($found_payment_gateways));
  }
  
  protected function getStandardPaymentOptionsFromPaymentInfoPane() {
    $css_selector_converter = new CssSelectorConverter();
    $xpath = $css_selector_converter->toXPath('[data-drupal-selector="edit-payment-information-payment-method"] .form-item');
    $standard_payment_options = [];
    foreach ($this->xpath($xpath) as $option) {
      $label = $option->find('xpath', $css_selector_converter->toXPath('label'));
      $radio = $option->find('xpath', $css_selector_converter->toXPath('input[type=radio]'));
      $value = $radio->getAttribute('value');
      $standard_payment_options[$value] = [
        'label' => $label->getText(),
        'is_checked' => $radio->isChecked(),
      ];
    }
    return $standard_payment_options;
  }

  /**
   * @return mixed
   */
  protected function getOrderTotalFromSummary() {
    $css_selector_converter = new CssSelectorConverter();
    $xpath = $css_selector_converter->toXPath('.checkout-pane-order-summary .order-total-line__total');
    $total = $this->xpath($xpath);
    $total = reset($total);
    return $total->find('xpath', $css_selector_converter->toXPath('.order-total-line-value'))->getText();
  }
  
  /**
   * @param string $label
   * @param string|null $value
   *
   * @return bool
   */
  protected function orderAdjustmentExists($label, $value = NULL) {
    foreach ($this->getOrderAdjustments() as $adjustment) {
      if ($adjustment['label'] == $label && (is_null($value) || $value == $adjustment['value'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @return array
   */
  protected function getOrderAdjustments() {
    $adjustments = [];
    $css_selector_converter = new CssSelectorConverter();
    $xpath = $css_selector_converter->toXPath('.checkout-pane-order-summary .order-total-line__adjustment');
    $order_adjustments = $this->xpath($xpath);
    foreach ($order_adjustments as $adjustment) {
      $adjustments[] = [
        'label' => $adjustment->find('xpath', $css_selector_converter->toXPath('.order-total-line-label'))->getText(),
        'value' => $adjustment->find('xpath', $css_selector_converter->toXPath('.order-total-line-value'))->getText(),
      ];
    }
    return $adjustments;
  }

  /**
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   */
  protected function addProductToCart(ProductInterface $product) {
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    /** @var \Drupal\commerce_cart\CartProviderInterface $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    
    $cart_provider->clearCaches();

    $variations = $this->product->getVariations();
    $variation = reset($variations);

    // Load product variation and get store.
    $variation_price = $variation->getPrice();
    $stores = $variation->getStores();
    $store = reset($stores);

    // get or create cart for the store.
    $cart = $cart_provider->getCart('default', $store);
    if (!$cart) {
      $cart = $cart_provider->createCart('default', $store);
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity_type_manager->getStorage('commerce_order_item')->create([
      'type' => 'default',
      'purchased_entity' => (string) $variation->id(),
      'quantity' => 1,
      'unit_price' => $variation_price,
    ]);
    $order_item->save();
    $cart_manager->addOrderItem($cart, $order_item);
    return $cart;
  }

}
