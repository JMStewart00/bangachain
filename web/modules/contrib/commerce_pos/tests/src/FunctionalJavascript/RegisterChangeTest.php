<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Register selection.
 *
 * @group commerce_pos
 */
class RegisterChangeTest extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos',
    'search_api_db',
  ];

  /**
   * A user with the cashier role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $cashier;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();
    $this->cashier = $this->drupalCreateUser([], $this->randomString());
    $this->cashier->addRole('pos_cashier');
    $this->cashier->save();
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests adding and removing products from the POS form.
   */
  public function testRegisterSelection() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();
    $current_register_name = Register::load($this->getSession()->getCookie('commerce_pos_register'))->getName();

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

    $this->drupalGet('admin/commerce/pos/register');
    $web_assert->pageTextContains('You have no other registers to switch to.');

    $register = Register::create([
      'store_id' => $this->store->id(),
      'name' => 'Other register',
      'default_float' => new Price('100.00', 'USD'),
    ]);
    $register->save();

    $this->drupalGet('admin/commerce/pos/register');

    // Asserting current register.
    $field = $this->assertSession()->optionExists('register', 1)->getText();
    $this->assertEquals($field, $current_register_name);
    $this->getSession()->getPage()->fillField('register', '2');
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Switch Register')->click();
    $web_assert->pageTextContains('Register changed to Other register');

    $this->drupalGet('admin/commerce/pos/main');
    $web_assert->pageTextNotContains("Register: $current_register_name");
    $current_register_name = Register::load($this->getSession()->getCookie('commerce_pos_register'))->getName();
    $web_assert->pageTextContains("Register: $current_register_name");

    // Assert that the product is listed as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('To Pay $50.00');
  }

}
