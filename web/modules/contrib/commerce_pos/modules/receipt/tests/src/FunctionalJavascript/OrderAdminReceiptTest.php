<?php

namespace Drupal\Tests\commerce_pos_receipt\FunctionalJavascript;

use Drupal\Tests\commerce_order\FunctionalJavascript\OrderWebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests receipt functionality.
 *
 * @group commerce_pos_receipt
 */
class OrderAdminReceiptTest extends OrderWebDriverTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos_receipt',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpStore();
    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests receipt function on a non-POS order.
   */
  public function testReceipt() {
    // Hopefully this whole generic section will make its way into Commerce.
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/orders');
    $this->getSession()->getPage()->clickLink('Create a new order');

    $this->getSession()->getPage()->fillField('type[value]', 'default');
    $this->getSession()->getPage()->fillField('store_id[value]', $this->store->id());
    $this->getSession()->getPage()->fillField('customer_type', 'new');
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('mail', 'test@test.com');

    $this->getSession()->getPage()->findButton('Create')->click();

    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][given_name]',
      $this->randomMachineName()
    );
    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][family_name]',
      $this->randomMachineName()
    );
    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][address_line1]',
      '1060 West Addison'
    );
    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][locality]',
      'Chicago'
    );
    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][administrative_area]',
      'IL'
    );
    $this->getSession()->getPage()->fillField(
      'billing_profile[0][profile][address][0][address][postal_code]',
      '60613'
    );

    $this->getSession()->getPage()->findButton('Add new order item')->click();
    $web_assert->assertWaitOnAjaxRequest();

    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[form][inline_entity_form][purchased_entity][0][target_id]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    // Click on the result of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Currently due to a bug in commerce you must override the price.
    $this->getSession()->getPage()->fillField(
      'order_items[form][inline_entity_form][unit_price][0][override]',
      '1'
    );
    $this->getSession()->getPage()->fillField(
      'order_items[form][inline_entity_form][unit_price][0][amount][number]',
      '9.99'
    );

    $this->getSession()->getPage()->findButton('Create order item')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Save')->click();
    $this->getSession()->getPage()->findButton('Place order')->click();

    // This is the only receipt specific part, the rest is just making
    // sure everything works right and getting to the point where we can test the receipt.
    $this->getSession()->getPage()->clickLink('Show receipt');
  }

}
