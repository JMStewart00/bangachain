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
class CashierLoginTest extends WebDriverTestBase {

  use CommercePosCreateStoreTrait;

  /**
   * A user with the cashier permissions for testing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $cashier;

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
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    $this->cashier = $this->drupalCreateUser($this->getCashierPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function getCashierPermissions() {
    return [
      'access commerce pos pages',
    ];
  }

  /**
   * Tests that the basic login functionality works, does not test the quick-select currently.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCashierLogin() {
    $login_url = Url::fromRoute('commerce_pos.login');
    $this->drupalGet($login_url);

    $this->getSession()->getPage()->fillField('name', $this->cashier->getAccountName());
    $this->getSession()->getPage()->fillField('pass', $this->cashier->passRaw);

    $this->getSession()->getPage()->findButton('Login')->click();

    $main_url = Url::fromRoute('commerce_pos.main');
    $this->assertEquals($this->getAbsoluteUrl($main_url->toString()),
      $this->getUrl());

    $this->drupalLogout();
    // Due to https://www.drupal.org/project/commerce_pos/issues/2986152 we have
    // to clear the cache to test that the login page has the user's name in the
    // recently-logged-in users.
    drupal_flush_all_caches();
    $this->drupalGet($login_url);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->click('.commerce-pos-login__pane__toggle--users');
    $this->assertSession()->pageTextContains($this->cashier->getAccountName());
  }

  /**
   * Tests that an bad login keeps the user on the right page.
   *
   * Tried to also test for error messaging but testbot seems to have problems with that.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCashierLoginFailed() {
    $login_url = Url::fromRoute('commerce_pos.login');

    $this->drupalGet($login_url);

    $this->getSession()->getPage()->fillField('name', 'wronguser');
    $this->getSession()->getPage()->fillField('pass', 'wrongpass');
    $this->getSession()->getPage()->findButton('Login')->click();

    $this->assertEquals($this->getAbsoluteUrl($login_url->toString()), $this->getUrl());
  }

  /**
   * Tests that the login page logs out the user and displays a message properly.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCashierLogout() {
    $login_url = Url::fromRoute('commerce_pos.login');

    $this->drupalGet($login_url);

    $this->getSession()->getPage()->fillField('name', $this->cashier->getUsername());
    $this->getSession()->getPage()->fillField('pass', $this->cashier->passRaw);

    $this->getSession()->getPage()->findButton('Login')->click();

    $main_url = Url::fromRoute('commerce_pos.main');
    $this->assertEquals($this->getAbsoluteUrl($main_url->toString()),
      $this->getUrl());

    $this->drupalGet($login_url);

    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/screen.jpg');

    $this->assertSession()->pageTextContains('You\'ve been logged out');

    // Test that we've actually been logged out and can login again.
    $this->getSession()->getPage()->fillField('name', $this->cashier->getUsername());
    $this->getSession()->getPage()->fillField('pass', $this->cashier->passRaw);

    $this->getSession()->getPage()->findButton('Login')->click();

    $main_url = Url::fromRoute('commerce_pos.main');
    $this->assertEquals($this->getAbsoluteUrl($main_url->toString()),
      $this->getUrl());
  }

}
