<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Html;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class OrderCommentsTest extends WebDriverTestBase {

  use CommercePosCreateStoreTrait;

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'search_api_db',
    'commerce_pos',
  ];

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
      'view default commerce_order',
      'view commerce_order',
      'view the administration theme',
      'access commerce pos pages',
      'create pos commerce_order',
      'delete pos commerce_order',
      'update pos commerce_order',
      'access commerce pos order lookup',
      'access content',
    ];
  }

  /**
   * Tests comments on POS orders.
   */
  public function testCommercePosFormOrderComments() {
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

    // Add a comment.
    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', 'Test comment');
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $results = $this->getSession()->getPage()->findAll('css', '#edit-order-comments-display-order-comment table tbody tr');
    $this->assertCount(1, $results);
    $this->assertStringContainsString('Test comment', $results[0]->getText());

    // Add another comment with XSS.
    $this->getSession()->getPage()->findButton('Back To Order')->click();
    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', "<script>alert('here');</script>");
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $results = $this->getSession()->getPage()->findAll('css', '#edit-order-comments-display-order-comment table tbody tr');
    $this->assertCount(2, $results);
    $web_assert->pageTextContains("<script>alert('here');</script>");
    $web_assert->pageTextContains('Test comment');

    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', "Test parked");
    $this->getSession()->getPage()->findButton('Park Order')->click();

    // Ensure the comment has been logged and saved.
    $logStorage = $this->container->get('entity_type.manager')->getStorage('commerce_log');
    $order = Order::load(1);
    $logs = $logStorage->loadMultipleByEntity($order);
    $this->assertEquals(4, count($logs));

    $logViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_log');
    $build = $logViewBuilder->view($logs[1]);
    $this->assertStringContainsString('Test comment', (string) $this->container->get('renderer')->renderPlain($build));
    $build = $logViewBuilder->view($logs[2]);
    // The script tag should be escaped.
    $this->assertStringContainsString(Html::escape("<script>alert('here');</script>"), (string) $this->container->get('renderer')->renderPlain($build));
    $build = $logViewBuilder->view($logs[3]);
    $this->assertStringContainsString('Test parked', (string) $this->container->get('renderer')->renderPlain($build));
    // Automatic park entry
    $build = $logViewBuilder->view($logs[4]);
    $this->assertStringContainsString('<p>Order moved from <em>Draft</em> to <em>Parked</em> by the <em>Park order</em> transition.</p>', (string) $this->container->get('renderer')->renderPlain($build));
  }

}
