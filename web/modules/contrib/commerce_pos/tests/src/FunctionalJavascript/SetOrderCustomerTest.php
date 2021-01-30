<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests setting the order customer functionality via the POS form.
 *
 * @group commerce_pos
 */
class SetOrderCustomerTest extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected $cashierUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    $this->cashierUser = $this->drupalCreateUser($this->getCashierPermissions(), 'cashierUser-name');
    $this->drupalLogin($this->cashierUser);
    $cashier1 = $this->drupalCreateUser($this->getCashierPermissions(), 'cashier-1-name');
    $cashier1->set('field_commerce_pos_phone_number', '123-456-7890');
    $cashier1->save();

    $cashier2 = $this->drupalCreateUser($this->getCashierPermissions(), 'cashier-2-name');
    $cashier2->set('field_commerce_pos_phone_number', '1234567890');
    $cashier2->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCashierPermissions() {
    return [
      'view the administration theme',
      'access commerce pos pages',
      'create pos commerce_order',
      'delete pos commerce_order',
      'update pos commerce_order',
      'view pos commerce_order',
      'access commerce pos order lookup',
      'access content',
    ];
  }

  /**
   * Tests adding and removing products from the POS form.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  public function testCustomerWidget() {
    $web_assert = $this->assertSession();

    // Open Register.
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // (1.) Confirm 'Anon' user by completing order without setting a customer.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    self::assertCount(1, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    // Click 'Pay Now'.
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Enter Cash Payment.
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    // Click 'Complete Order'.
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();
    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('Anonymous');

    // (2.) New customer by email and confirm order completion.
    $this->drupalGet('admin/commerce/pos/main');
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    self::assertCount(1, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    // Create a new user by email and set that user as the order customer.
    $this->getSession()->getPage()->findButton('Customer')->click();
    $user_email_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $user_email_field->setValue('test@test.com');
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Finish checkout.
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();

    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('test@test.com');

    // (3.) Autocomplete existing user by email (cashier-1-name@example.com).
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('cashier-1-name@');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'e');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('jum1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'M');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    self::assertCount(1, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Finish checkout.
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();
    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('cashier-1-name@example.com');

    // (4.) Invalid email test.
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('test@.com');
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $web_assert->pageTextContains('Customer account for "test@.com" not found.');

    // (5.) AutoComplete by user name (cashier-2-name).
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('cashier-');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '2');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('jum1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'M');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    self::assertCount(1, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Finish checkout.
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();
    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('cashier-2-name@example.com');

    // (6.) Invalid username test (cashier-3-name).
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('cashier-3-name');
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $web_assert->pageTextContains('Customer account for "cashier-3-name" not found');

    // (7.) Autocomplete existing user by phone (123-456-7890 => cashier-1-name)
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('123-456-78');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '9');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('jum1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'M');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    // Finish checkout.
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();
    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('cashier-1-name@example.com');

    // (8.) Invalid phone number test (cashier-1 in diff format).
    $this->drupalGet('admin/commerce/pos/main');
    $this->getSession()->getPage()->findButton('Customer')->click();
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][customer_textfield]');
    $autocomplete_field->setValue('(123) 456-7890');
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $web_assert->pageTextContains('Customer account for "(123) 456-7890" not found.');
  }

}
