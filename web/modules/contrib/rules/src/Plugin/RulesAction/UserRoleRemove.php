<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserInterface;
use Drupal\rules\Exception\InvalidArgumentException;

/**
 * Provides a 'Remove user role' action.
 *
 * @RulesAction(
 *   id = "rules_user_role_remove",
 *   label = @Translation("Remove user role"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("The user whose roles should be changed."),
 *       assignment_restriction = "selector"
 *     ),
 *     "roles" = @ContextDefinition("entity:user_role",
 *       label = @Translation("Roles"),
 *       description = @Translation("The user role(s) to remove."),
 *       options_provider = "\Drupal\rules\TypedData\Options\RolesOptions",
 *       multiple = TRUE
 *     ),
 *   }
 * )
 */
class UserRoleRemove extends RulesActionBase {

  /**
   * Flag that indicates if the entity should be auto-saved later.
   *
   * @var bool
   */
  protected $saveLater = FALSE;

  /**
   * Remove role from a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object the roles should be removed from.
   * @param \Drupal\user\RoleInterface[] $roles
   *   Array of user roles.
   *
   * @throws \Drupal\rules\Exception\InvalidArgumentException
   */
  protected function doExecute(UserInterface $user, array $roles) {
    foreach ($roles as $role) {
      // Check if user has role.
      if ($user->hasRole($role->id())) {
        // If you try to add anonymous or authenticated role to user, Drupal
        // will throw an \InvalidArgumentException. Anonymous or authenticated
        // role ID must not be assigned manually.
        try {
          $user->removeRole($role->id());
        }
        catch (\InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
        // Set flag that indicates if the entity should be auto-saved later.
        $this->saveLater = TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    if ($this->saveLater) {
      return ['user'];
    }
    return [];
  }

}
