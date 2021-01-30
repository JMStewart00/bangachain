<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS form with decimal amounts enabled & disabled.
 *
 * @group commerce_pos
 */
class PosFormWithDecimals extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'search_api_db',
    'commerce_pos',
    'field_ui',
    'field',
    'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();
    $this->adminUser = $this->drupalCreateUser($this->getAdminPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return [
      'view default commerce_order',
      'view commerce_order',
      'view the administration theme',
      'access commerce pos pages',
      'create pos commerce_order',
      'access commerce administration pages',
      'administer commerce_order_type',
      'access content',
      'administer commerce_order form display',
    ];
  }

  /**
   * Tests POS form with decimals enabled.
   */
  public function testPosFormWithDecimals() {
    $web_assert = $this->assertSession();

    // Navigate to settings form and explicitly set our step.
    $this->drupalGet('admin/commerce/config/order-types/pos/edit/form-display');
    $this->xpath('//*[@data-drupal-selector="edit-fields-order-items-settings-edit"]')[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('fields[order_items][settings_edit_form][settings][decimal_step]', '0.01');
    $this->getSession()->getPage()->findButton('Update')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Allow decimal quantities: Yes');
    $web_assert->pageTextContains('Quantity step: 0.01');
    $this->getSession()->getPage()->findButton('Save')->click();
    $web_assert->assertWaitOnAjaxRequest();

    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // Search for Jumper XL item and select it.
    $autocomplete_field = $this->getSession()
      ->getPage()
      ->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()
      ->getDriver()
      ->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()
      ->getPage()
      ->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Jumper');

    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity][quantity]', '1.013423232323');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '1.01');
    $step = $this->getSession()->getPage()->findField('order_items[target_id][order_items][0][quantity][quantity]');
    $this->assertEquals('0.01', $step->getAttribute('step'));

    // Park the order so we can go in and enable decimals - could also void.
    $this->getSession()->getPage()->findButton('Park Order')->click();
    // Navigate to settings form and disable decimals.
    $this->drupalGet('admin/commerce/config/order-types/pos/edit/form-display');
    $this->xpath('//*[@data-drupal-selector="edit-fields-order-items-settings-edit"]')[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->xpath('//*[@name="fields[order_items][settings_edit_form][settings][allow_decimal]"]')[0]->click();
    $this->getSession()->getPage()->findButton('Update')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Allow decimal quantities: No');
    $this->getSession()->getPage()->findButton('Save')->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Head back to the main page to verify our settings are working.
    $this->drupalGet('admin/commerce/pos/main');

    $autocomplete_field = $this->getSession()
      ->getPage()
      ->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()
      ->getDriver()
      ->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()
      ->getPage()
      ->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Jumper');
    // We disabled decimals so ensure we have the right number.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity][quantity]', '4.4398');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity][quantity]', '4');
  }

}
