<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'Add user role' action.
 *
 * @todo Add access callback information from Drupal 7.
 * @todo Add port for rules_user_roles_options_list.
 *
 * @RulesAction(
 *   id = "rules_user_role_add",
 *   label = @Translation("Add user role"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("The user whose roles should be changed.")
 *     ),
 *     "roles" = @ContextDefinition("entity:user_role",
 *       label = @Translation("Roles"),
 *       description = @Translation("The user role(s) to add."),
 *       options_provider = "\Drupal\rules\TypedData\Options\RolesOptions",
 *       multiple = TRUE
 *     ),
 *   }
 * )
 */
class UserRoleAdd extends RulesActionBase {

  /**
   * Flag that indicates if the entity should be auto-saved later.
   *
   * @var bool
   */
  protected $saveLater = FALSE;

  /**
   * Assign role to a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object.
   * @param \Drupal\user\RoleInterface[] $roles
   *   Array of UserRoles to assign.
   *
   * @throws \Drupal\rules\Exception\InvalidArgumentException
   */
  protected function doExecute(UserInterface $user, array $roles) {
    foreach ($roles as $role) {
      // Skip adding the role to the user if they already have it.
      if (!$user->hasRole($role->id())) {
        // If you try to add anonymous or authenticated role to user, Drupal
        // will throw an \InvalidArgumentException. Anonymous or authenticated
        // role ID must not be assigned manually.
        try {
          $user->addRole($role->id());
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
