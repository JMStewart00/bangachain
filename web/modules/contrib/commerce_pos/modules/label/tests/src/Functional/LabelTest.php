<?php

namespace Drupal\Tests\commerce_pos_label\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Labels something something....
 *
 * @group commerce_pos
 */
class LabelTest extends WebDriverTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos_print',
    'commerce_pos_label',
    'search_api_db',
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpStore();
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'commerce pos print labels',
    ];
  }

  /**
   * Tests the label interface works and products can be added to print labels.
   *
   * Does NOT test the printing functionality as that is not possible in our
   * current testing framework.
   */
  public function testLabel() {
    $web_assert = $this->assertSession();

    // Test that the main listing page exists.
    $this->drupalGet('admin/commerce/pos/labels');
    $web_assert->pageTextContains(t('Label format'));
    $web_assert->pageTextContains(t('Quantity'));

    $autocomplete_field = $this->getSession()->getPage()->findField('product_search');
    $autocomplete_field->setValue('T-shir');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 't');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $this->click('#edit-product-search-add');

    $web_assert->responseContains('T-shirt');
    $web_assert->responseContains('23.20');
    $web_assert->addressEquals('admin/commerce/pos/labels');

    // I don't think it is actually possible to test the print functionality,
    // since Mink can't test print dialog as far as I know. So this is as far as
    // the test goes.
  }

}
