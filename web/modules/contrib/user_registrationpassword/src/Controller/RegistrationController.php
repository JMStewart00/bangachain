<?php

namespace Drupal\user_registrationpassword\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * User registration password controller class.
 */
class RegistrationController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The status message.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, MessengerInterface $messenger, TimeInterface $time) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->messenger = $messenger;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('messenger'),
      $container->get('datetime.time')
    );
  }

  /**
   * Confirms a user account.
   *
   * @param int $uid
   *   UID of user requesting confirmation.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the login link is for a blocked user or invalid user ID.
   */
  public function confirmAccount($uid, $timestamp, $hash) {
    $route_name = '<front>';
    $route_options = [];
    $current_user = $this->currentUser();

    // Verify that the user exists.
    if ($current_user === NULL) {
      throw new AccessDeniedHttpException();
    }

    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($current_user->isAuthenticated()) {
      // The existing user is already logged in.
      if ($current_user->id() == $uid) {
        $this->messenger->addMessage($this->t('You are currently authenticated as user %user.', ['%user' => $current_user->getAccountName()]));
        // Redirect to user page.
        $route_name = 'entity.user.canonical';
        $route_options = ['user' => $current_user->id()];
      }
      // A different user is already logged in on the computer.
      else {
        $reset_link_account = $this->userStorage->load($uid);
        if (!empty($reset_link_account)) {
          $this->messenger->addMessage($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
            [
              '%other_user' => $current_user->getDisplayName(),
              '%resetting_user' => $reset_link_account->getDisplayName(),
              ':logout' => Url::fromRoute('user.logout')->toString(),
            ]), 'warning');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          $this->messenger->addMessage($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          $route_name = 'user.pass';
        }
      }
    }
    else {
      // Time out, in seconds, until login URL expires. 24 hours = 86400
      // seconds.
      $timeout = $this->config('user_registrationpassword.settings')->get('registration_ftll_timeout');
      $current = $this->time->getRequestTime();
      $timestamp_created = $timestamp - $timeout;

      // Some redundant checks for extra security ?
      $users = $this->userStorage->getQuery()
        ->condition('uid', $uid)
        ->condition('status', 0)
        ->condition('access', 0)
        ->execute();

      // Timestamp can not be larger then current.
      /** @var \Drupal\user\UserInterface $account */
      if ($timestamp_created <= $current && !empty($users) && $account = $this->userStorage->load(reset($users))) {
        // Check if we have to enforce expiration for activation links.
        if ($this->config('user_registrationpassword.settings')->get('registration_ftll_expire') && !$account->getLastLoginTime() && $current - $timestamp > $timeout) {
          $this->messenger->addMessage($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          $route_name = 'user.pass';
        }
        // Else try to activate the account.
        // Password = user's password - timestamp = current request - login =
        // username.
        elseif ($account->id() && $timestamp >= $account->getCreatedTime() && !$account->getLastLoginTime() && $hash == user_pass_rehash($account, $timestamp)) {
          // Format the date, so the logs are a bit more readable.
          $date = $this->dateFormatter->format($timestamp);
          $this->getLogger('user')->notice('User %name used one-time login link at time %timestamp.', ['%name' => $account->getAccountName(), '%timestamp' => $date]);
          // Activate the user and update the access and login time to $current.
          $account
            ->activate()
            ->setLastAccessTime($current)
            ->setLastLoginTime($current)
            ->save();

          // user_login_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize($account);

          // Display default welcome message.
          $this->messenger->addMessage($this->t('You have just used your one-time login link. Your account is now active and you are authenticated.'));
          // Redirect to user.
          $route_name = 'entity.user.canonical';
          $route_options = ['user' => $account->id()];
        }
        // Something else is wrong, redirect to the password
        // reset form to request a new activation email.
        else {
          $this->messenger->addMessage($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          $route_name = 'user.pass';
        }
      }
      else {
        // Deny access, no more clues.
        // Everything will be in the watchdog's
        // URL for the administrator to check.
        $this->messenger->addMessage($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
        $route_name = 'user.pass';
      }
    }

    return $this->redirect($route_name, $route_options);
  }

}
