<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Delete entity' action.
 *
 * @todo Add access callback information from Drupal 7.
 *
 * @RulesAction(
 *   id = "rules_entity_delete",
 *   label = @Translation("Delete entity"),
 *   category = @Translation("Entity"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be deleted permanently."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class EntityDelete extends RulesActionBase {

  /**
   * Deletes the Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be deleted.
   */
  protected function doExecute(EntityInterface $entity) {
    $entity->delete();
  }

}
