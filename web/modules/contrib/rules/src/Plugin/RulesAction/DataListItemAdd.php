<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides an 'Add list item' action.
 *
 * @todo Add access callback information from Drupal 7?
 *
 * @RulesAction(
 *   id = "rules_list_item_add",
 *   label = @Translation("Add list item"),
 *   category = @Translation("Data"),
 *   context_definitions = {
 *     "list" = @ContextDefinition("list",
 *       label = @Translation("List"),
 *       description = @Translation("The data list, to which an item is to be added."),
 *       assignment_restriction = "selector"
 *     ),
 *     "item" = @ContextDefinition("any",
 *       label = @Translation("Item"),
 *       description = @Translation("Item to add.")
 *     ),
 *     "unique" = @ContextDefinition("boolean",
 *       label = @Translation("Enforce uniqueness"),
 *       description = @Translation("Only add the item to the list if it is not yet contained."),
 *       options_provider = "\Drupal\rules\TypedData\Options\YesNoOptions",
 *       default_value = FALSE,
 *       required = FALSE
 *     ),
 *     "position" = @ContextDefinition("string",
 *       label = @Translation("Insert position"),
 *       description = @Translation("Position to insert the item."),
 *       options_provider = "\Drupal\rules\TypedData\Options\ListPositionOptions",
 *       default_value = "end",
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class DataListItemAdd extends RulesActionBase {

  /**
   * Add an item to a list.
   *
   * @param array $list
   *   A list to which an item is added.
   * @param mixed $item
   *   An item being added to the list.
   * @param bool $unique
   *   (optional) Whether or not we can add duplicate items.
   * @param string $position
   *   (optional) Determines if item will be added at beginning or end.
   *   Allowed values:
   *   - "start": Add to beginning of the list.
   *   - "end": Add to end of the list.
   */
  protected function doExecute(array $list, $item, $unique = FALSE, $position = 'end') {
    // Optionally, only add the list item if it is not yet contained.
    if (!((bool) $unique && in_array($item, $list))) {
      if ($position === 'start') {
        array_unshift($list, $item);
      }
      else {
        $list[] = $item;
      }
    }

    $this->setContextValue('list', $list);
  }

}
