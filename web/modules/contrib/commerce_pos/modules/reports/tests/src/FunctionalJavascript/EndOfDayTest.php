<?php

namespace Drupal\Tests\commerce_pos_reports\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_pos\Entity\Register;

/**
 * Tests the Commerce EOD Report form.
 *
 * @group commerce_pos_reports
 */
class EndOfDayTest extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'search_api_db',
    'commerce_price',
    'commerce_pos',
    'commerce_pos_reports',
    'commerce_store',
    'commerce_price',
    'commerce_pos_print',
    'commerce_pos_reports',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_pos_print_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $nonPrivilegedUser;

  /**
   * {@inheritdoc}
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpStore();
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->nonPrivilegedUser = $this->drupalCreateUser([]);

    $this->register->open();
    $this->register->setOpeningFloat($this->register->getDefaultFloat());
    $this->register->save();

    PaymentGateway::create([
      'id' => 'not_pos_credit_card',
      'label' => 'Not POS Credit Card',
      'plugin' => 'manual',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'access commerce pos administration pages',
      'access commerce pos reports',
      'access site reports',
    ];
  }

  /**
   * Tests that all the menu hooks return pages for privileged users.
   */
  public function testCommercePosReportsEndOfDayMenu() {
    // Confirm privileged user can access the report pages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/commerce/pos/reports');

    // Confirm unprivileged user cannot access the report pages.
    $this->drupalLogin($this->nonPrivilegedUser);
    $this->drupalGet('admin/commerce/pos/reports');
  }

  /**
   * Tests that the end of day report runs and has correct values.
   */
  public function testCommercePosReportsEndOfDayForm() {
    $this->drupalLogin($this->adminUser);
    $web_assert = $this->assertSession();

    \Drupal::service('commerce_pos.current_register')->set($this->register);

    // Let's create some sales transactions.
    $transaction_summary = $this->generateTransactions();

    // Now, go to the EOD reports form and verify the values.
    $this->drupalGet('admin/commerce/pos/reports/end-of-day');

    $this->getSession()->getPage()->fillField('register_id', $this->register->id());
    $web_assert->assertWaitOnAjaxRequest();

    // Check the EOD report's expected amounts to make sure they match up with
    // the transactions we generated.
    foreach ($transaction_summary as $payment_method => $totals) {
      $expected_amount_element = $this->xpath('(//div[@class="commerce-pos-report-expected-amount" and @data-payment-method-id="' . $payment_method . '"][1])');

      // Casting the xpath element to a string gets us the element's inner HTML.
      $expected_amount = (string) $expected_amount_element[0]->getText();

      $this->assertSame($expected_amount, $totals['amount_total_formatted']);
    }

    $this->assertSession()->responseContains('Cash');
    $this->assertSession()->responseContains('Credit');
    $this->assertSession()->responseContains('Debit');
    $this->assertSession()->responseContains('Gift card');

    // No pos orders 'Not POS Credit Card'.
    $this->assertSession()->responseNotContains('Not POS Credit Card');

    $this->getSession()->getPage()->pressButton('Close Register & Save');

    $this->getSession()->getPage()->fillField('register_id', $this->register->id());
    $web_assert->assertWaitOnAjaxRequest();

    $this->assertSession()->buttonExists('Print');
  }

  /**
   * Tests end of day calculate report feature.
   */
  public function testCalculateReport() {
    $this->drupalLogin($this->adminUser);
    $web_assert = $this->assertSession();

    \Drupal::service('commerce_pos.current_register')->set($this->register);

    // Let's create some sales transactions.
    $this->generateTransactions();

    // Now, go to the EOD reports form and verify the values.
    $this->drupalGet('admin/commerce/pos/reports/end-of-day');

    $this->getSession()->getPage()->fillField('register_id', $this->register->id());
    $web_assert->assertWaitOnAjaxRequest();

    $date = date('Y-m-d', time());
    // Insert declared values into form.
    $this->getSession()->getPage()->fillField("USD[rows][pos_cash][declared][1][$date]", 175.98);
    $this->getSession()->getPage()->fillField("USD[rows][pos_credit][declared][1][$date]", 15.99);
    $this->getSession()->getPage()->fillField("USD[rows][pos_debit][declared][1][$date]", 35.99);
    $this->getSession()->getPage()->fillField("USD[rows][pos_gift_card][declared][1][$date]", 16.99);

    // Press calculate button and wait for it to finish.
    $this->getSession()->getPage()->pressButton('Calculate');
    $web_assert->assertWaitOnAjaxRequest();

    // Verify our values updated without saving the form.
    /** @var \Behat\Mink\Element\NodeElement $element */
    $this->assertEquals("($0.00)", $this->xpath('//div[@data-payment-method-id="pos_cash"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_credit"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_debit"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_gift_card"]')[1]->getText());
    $web_assert->pageTextNotContains('Successfully saved the declared values for register');
    $web_assert->pageTextNotContains('has been closed');

  }

  /**
   * Tests end of day calculate report feature with multiple registers.
   *
   * This is the same as testCalculateReport() but instead of testing with the
   * first register it creates a second one and uses that for the test.
   */
  public function testMultipleRegisterCalculateReport() {
    $register2 = Register::create([
      'store_id' => $this->store->id(),
      'name' => 'Test register 2',
      'default_float' => new Price('100.00', 'USD'),
    ]);
    $register2->save();

    $this->register = $register2;
    $this->register->open();
    $this->register->setOpeningFloat($this->register->getDefaultFloat());
    $this->register->save();

    $this->drupalLogin($this->adminUser);
    $web_assert = $this->assertSession();

    \Drupal::service('commerce_pos.current_register')->set($this->register);

    // Let's create some sales transactions.
    $this->generateTransactions();

    // Now, go to the EOD reports form and verify the values.
    $this->drupalGet('admin/commerce/pos/reports/end-of-day');

    $register_id = $this->register->id();
    $this->getSession()->getPage()->fillField('register_id', $register_id);
    $web_assert->assertWaitOnAjaxRequest();

    $date = date('Y-m-d', time());

    // Insert declared values into form.
    $this->getSession()->getPage()->fillField("USD[rows][pos_cash][declared][$register_id][$date]", 175.98);
    $this->getSession()->getPage()->fillField("USD[rows][pos_credit][declared][$register_id][$date]", 15.99);
    $this->getSession()->getPage()->fillField("USD[rows][pos_debit][declared][$register_id][$date]", 35.99);
    $this->getSession()->getPage()->fillField("USD[rows][pos_gift_card][declared][$register_id][$date]", 16.99);

    // Press calculate button and wait for it to finish.
    $this->getSession()->getPage()->pressButton('Calculate');
    $web_assert->assertWaitOnAjaxRequest();

    // Verify our values updated without saving the form.
    /** @var \Behat\Mink\Element\NodeElement $element */
    $this->assertEquals("($0.00)", $this->xpath('//div[@data-payment-method-id="pos_cash"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_credit"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_debit"]')[1]->getText());
    $this->assertEquals("$0.00", $this->xpath('//div[@data-payment-method-id="pos_gift_card"]')[1]->getText());
    $web_assert->pageTextNotContains('Successfully saved the declared values for register');
    $web_assert->pageTextNotContains('has been closed');
  }

  /**
   * Generates POS transactions and payments.
   *
   * @return array
   *   An associative array of generated totals, keyed by the payment method.
   *
   *   array(
   *     'pos_cash' => ['total_amount' => 550],
   *     'pos_credit' => ['total_amount' => 450.50],
   *   );
   */
  private function generateTransactions() {
    // Initialize the pos_cash with the register float value.
    $transaction_summary = [
      'pos_cash' => [
        'amount_total' => $this->register->getOpeningFloat()->getNumber(),
      ],
    ];

    $payment_methods = [
      ['pos_cash' => '55.99'],
      ['pos_cash' => '19.99'],
      ['pos_credit' => '15.99'],
      ['pos_debit' => '35.99'],
      ['pos_gift_card' => '16.99'],
    ];
    $currency_code = 'USD';
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    foreach ($payment_methods as $payment_method) {
      $payment_method_id = key($payment_method);
      $amount = $payment_method[$payment_method_id];

      /** @var \Drupal\commerce_product\Entity\Product $variation */
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => 'prod-test',
        'title' => 'Test Product',
        'status' => 1,
        'price' => new Price($amount, $currency_code),
      ]);
      $variation->save();

      /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
      $order_item = OrderItem::create([
        'type' => 'default',
        'quantity' => 1,
        'unit_price' => new Price($amount, $currency_code),
        'purchasable_entity' => $variation,
      ]);
      $order_item->save();

      /* @var \Drupal\commerce_order\Entity\Order $order */
      $order = Order::create([
        'type' => 'pos',
        'state' => 'draft',
        'store_id' => $this->store->id(),
        'field_cashier' => \Drupal::currentUser()->id(),
        'field_register' => $this->register->id(),
        'order_items' => [$order_item],
      ]);
      $order->save();

      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = Payment::create([
        'type' => 'payment_manual',
        'payment_gateway' => $payment_method_id,
        'order_id' => $order,
        'amount' => new Price($amount, $currency_code),
        'state' => 'completed',
      ]);
      $payment->save();

      $amount_total = isset($transaction_summary[$payment_method_id]['amount_total']) ? $transaction_summary[$payment_method_id]['amount_total'] + (float) $amount : (float) $amount;
      $transaction_summary[$payment_method_id]['amount_total'] = $amount_total;
      $transaction_summary[$payment_method_id]['amount_total_formatted'] = $currency_formatter->format((string) $amount_total, $currency_code);
      $transaction_summary[$payment_method_id]['orders'][$order->id()] = $payment;
    }

    return $transaction_summary;
  }

}
