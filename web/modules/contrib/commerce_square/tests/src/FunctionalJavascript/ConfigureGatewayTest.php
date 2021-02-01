<?php

namespace Drupal\Tests\commerce_square\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the creation and configuration of the gateway.
 *
 * @group commerce_square
 */
class ConfigureGatewayTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_square',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce square',
      'administer commerce_payment_gateway',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests that a Square gateway can be configured.
   */
  public function testCreateSquareGateway() {
    $this->drupalGet(Url::fromRoute('commerce_square.settings'));

    $this->getSession()->getPage()->fillField('Application Secret', 'fluff');
    $this->getSession()->getPage()->fillField('Application Name', 'Drupal Commerce 2 Demo');
    $this->getSession()->getPage()->fillField('Application ID', 'sq0idp-nV_lBSwvmfIEF62s09z0-Q');
    $this->getSession()->getPage()->fillField('Sandbox Application ID', 'sandbox-sq0idb-RMT75dFT1toXdUNnW8Ahmw');
    $this->getSession()->getPage()->fillField('Sandbox Access Token', 'EAAAEA3D3KIn2sjtYE0GjRPMJZPl4aigTyCyAhwojBAfWlr99jx4Wfz9GuCbzwfM');
    $this->getSession()->getPage()->pressButton('Save configuration');

    $is_squareup = strpos($this->getSession()->getCurrentUrl(), 'squareup.com');
    $this->assertNotFalse($is_squareup);

    $this->drupalGet('admin/commerce/config/payment-gateways/add');
    $radio_button = $this->getSession()->getPage()->findField('Square');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Populate the label / machine name first.
    $this->getSession()->getPage()->fillField('label', 'Square');
    $this->assertJsCondition('jQuery(".machine-name-value:visible").length > 0');
    $this->getSession()->getPage()->fillField('configuration[square][test][test_location_id]', 'C9HQN1PSN4NKA');
    $this->assertSession()->fieldDisabled('configuration[square][live][live_location_id]');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->responseContains(new FormattableMarkup('Saved the %label payment gateway.', ['%label' => 'Square']));

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/square');
    $radio_button = $this->getSession()->getPage()->findField('Production');
    $radio_button->click();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('You must select a location for the configured mode.');
  }

}
