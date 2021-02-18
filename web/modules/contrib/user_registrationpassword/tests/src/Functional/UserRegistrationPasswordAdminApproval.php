<?php

namespace Drupal\Tests\user_registrationpassword\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use Drupal\user_registrationpassword\UserRegistrationPassword;

/**
 * Functionality tests for User registration password module.
 *
 * @group user_registrationpassword
 */
class UserRegistrationPasswordAdminApproval extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['user_registrationpassword'];

  /**
   * Implements testing admin approval.
   */
  public function testRegistrationWithAdminApprovalEmailVerificationAndPasswordAdmin() {

    $config = \Drupal::configFactory()->getEditable('user_registrationpassword.settings');
    $user_config = \Drupal::configFactory()->getEditable('user.settings');

    // Set variables like they would be set via configuration form.
    $config
      ->set('registration', UserRegistrationPassword::VERIFICATION_PASS)
      ->save();
    $user_config
      ->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->set('verify_mail', 1)
      ->set('notify.register_pending_approval', 1)
      ->save();

    $this->drupalGet('user/register');
    $this->assertSession()->responseContains('edit-pass-pass1');
  }

}
