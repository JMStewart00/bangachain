<?php

namespace Drupal\user_registrationpassword;

/**
 * Provides a class defining registration constants.
 */
final class UserRegistrationPassword {
  /**
   * No verification email is sent.
   */
  const NO_VERIFICATION = 'none';

  /**
   * Verification email is sent before password is set.
   */
  const VERIFICATION_DEFAULT = 'default';

  /**
   * Verification email is sent after password is set.
   */
  const VERIFICATION_PASS = 'with-pass';

}
