<?php

namespace Drupal\Tests\user_registrationpassword\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests user_registrationpassword_mail_notify()
 *
 * @group user_registrationpassword
 */
class UserRegistrationPasswordMailNotifyTest extends EntityKernelTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['user_registrationpassword'];

  /**
   * Data provider for user mail testing.
   *
   * @return array
   *   An array of notify options.
   */
  public function userRegistrationMailsProvider() {
    return [
      ['register_confirmation_with_pass', ['register_confirmation_with_pass']],
    ];
  }

  /**
   * Tests mails are sent.
   *
   * @param string $op
   *   The operation being performed on the account.
   * @param array $mail_keys
   *   The mail keys to test for.
   *
   * @dataProvider userRegistrationMailsProvider
   */
  public function testUserRegistrationMailsSent($op, array $mail_keys) {
    $this->config('user_registrationpassword.settings')->set('notify.' . $op, 1)->save();

    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass'] = user_password();
    $edit['status'] = 0;
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->createUser($edit);

    $return = _user_registrationpassword_mail_notify($op, $account);
    $this->assertTrue($return, '_user_registrationpassword_mail_notify() returns TRUE.');
    foreach ($mail_keys as $key) {
      $filter = ['key' => $key];
      $this->assertNotEmpty($this->getMails($filter), "Mails with $key exists.");
    }
    $this->assertCount(count($mail_keys), $this->getMails(), 'The expected number of emails sent.');
  }

}
