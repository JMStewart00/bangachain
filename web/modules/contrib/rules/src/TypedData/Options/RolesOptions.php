<?php

namespace Drupal\rules\TypedData\Options;

use Drupal\Core\Session\AccountInterface;

/**
 * Options provider to return a list of user roles.
 */
class RolesOptions extends OptionsProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    // Use parameter FALSE to include 'Anonymous'.
    $roles = user_role_names(FALSE);

    // Sort by the role name.
    asort($roles);

    return $roles;
  }

}
