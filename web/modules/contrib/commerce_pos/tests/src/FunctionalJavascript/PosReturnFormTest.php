<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS return form.
 *
 * @group commerce_pos
 */
class PosReturnFormTest extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos',
    'commerce_pos_receipt',
  ];

  /**
   * {@inheritdoc}
   */
  protected $cashierUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpStore();

    $this->cashierUser = $this->drupalCreateUser($this->getCashierPermissions());
    $this->drupalLogin($this->cashierUser);
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
   */
  public function testCommercePosReturnForm() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // Now we should be able to select order items.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Assert that the product is listed as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('To Pay $50.00');

    // After selecting something from the autocomplete list the value should be
    // blank again.
    $web_assert->fieldValueEquals('order_items[target_id][product_selector]', '');

    // Add another of the same Jumper.
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '2.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('To Pay $100.00');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Pay Now')->click();

    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('To Pay $100.00');
    $web_assert->pageTextContains('Change $0.00');

    // Test Large amount of cash.
    $this->getSession()->getPage()->fillField('pos_cash[keypad][amount]', '1234.56');
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Cash $1,234.56');
    $web_assert->pageTextContains('Total Paid $1,234.56');
    $web_assert->pageTextContains('Change $1,134.56');

    // Test void Cash button.
    $this->click('input[name="commerce-pos-pay-keypad-remove"]');
    $web_assert->pageTextContains('Payment Voided');
    $web_assert->pageTextContains('Cash VOID');
    $web_assert->pageTextContains('Change $0.00');

    // Test Small amount of cash.
    $this->getSession()->getPage()->fillField('pos_cash[keypad][amount]', '100');
    $this->click('input[name="commerce-pos-pay-keypad-add-pos_cash"]');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');

    // Clicking finish will bring us back to the order item screen - processing
    // a new order.
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextNotContains('Cash');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextNotContains('Jumper');

    // Now, click on the 'Order Lookup' tab.
    $this->drupalGet('admin/commerce/pos/orders');

    // Click on our newly created order's edit button so we can do a return.
    $web_assert->pageTextContains('edit');
    $this->getSession()->getPage()->clickLink('Edit');
    $url = Url::fromRoute('commerce_pos.main', ['commerce_order' => 1]);
    $this->assertEquals($this->getAbsoluteUrl($url->toString()), $this->getUrl());

    // Ensure the totals are correct.
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');

    // Ensure we have all the items as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '2.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][unit_price][number]', '50.00');

    // Ensure the 'Return' button exists and then click it.
    $return_button_xpath = '//*[@id="edit-order-items-target-id-order-items-0-return-order-item"]';
    $return_link = $web_assert->elementExists('xpath', $return_button_xpath);
    $return_link->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Ensure a return item is created.
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][unit_price][number]', '-50.00');

    // Ensure the totals are correct.
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $50.00');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Pay Now')->click();

    // Ensure we don't have to pay anything and the totals are correct.
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $50.00');

    $this->getSession()->getPage()->findButton('Add Cash Refund')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Complete Refund')->click();

    $web_assert->pageTextNotContains('Total $50.00');
    $field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $this->assertNotEmpty($field);

    // Test that we can abandon a return order and go back to a clean order.
    // Now, click on the 'Order Lookup' tab.
    $this->drupalGet('admin/commerce/pos/orders');

    // Click on our newly created order's edit button so we can do a return.
    $web_assert->pageTextContains('edit');
    $this->getSession()->getPage()->clickLink('Edit');
    $web_assert->pageTextContains('Total $50.00');

    $this->getSession()->getPage()->findButton('New Order')->click();
    $web_assert->pageTextNotContains('Total $50.00');
    $field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $this->assertNotEmpty($field);
  }

}
