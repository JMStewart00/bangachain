<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;
use Drupal\commerce_order\Entity\Order;
use Drupal\user\Entity\User;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class OrderLookupTest extends WebDriverTestBase {
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
  ];

  /**
   * The commerce store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Initial store set up.
    $this->store = $this->setUpStore();

    $this->adminUser = $this->drupalCreateUser(
      [
        'access commerce pos order lookup',
        'access commerce pos pages',
        'view pos commerce_order',
      ]
    );
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the POS order lookup function when we have an invalid order ID.
   */
  public function testOrderLookup() {
    $web_assert = $this->assertSession();

    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('There are currently no POS orders.');

    // Test the order lookup error message when we can't find an order.
    $this->getSession()->getPage()->fillField('search_box', '-1');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('The order could not be found or does not exist.');

    // Create our test user.
    $user = User::create();
    $user->setPassword('test');
    $user->enforceIsNew();
    $user->setEmail('test@test.com');
    $user->setUsername('test');
    $user->save();

    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'pos',
      'state' => 'completed',
      'store_id' => $this->store->id(),
      'field_cashier' => $this->adminUser->id(),
      'field_register' => 1,
      'uid' => $user->id(),
      'mail' => 'test2@test.com',
    ]);
    $order->save();

    // Now, go to our POS page and test looking up the order ID.
    $this->drupalGet('admin/commerce/pos/orders');

    // Lookup the order ID.
    $this->getSession()->getPage()->fillField('search_box', $order->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $web_assert->elementContains('css', '#order-lookup-results > table > tbody > tr > td:nth-child(1) > a', $order->id());
    $web_assert->elementContains('css', '#order-lookup-results > table > tbody > tr:nth-child(1) > td:nth-child(5) > a', 'test');
    $web_assert->elementContains('css', '#order-lookup-results > table > tbody > tr > td:nth-child(6)', 'test2@test.com');
    $web_assert->elementContains('css', '#order-lookup-results > table > tbody > tr > td:nth-child(3)', 'Completed');
  }

}
