<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'List count comparison' condition.
 *
 * @todo Add access callback information from Drupal 7?
 *
 * @Condition(
 *   id = "rules_list_count_is",
 *   label = @Translation("List count comparison"),
 *   category = @Translation("Data"),
 *   context_definitions = {
 *     "list" = @ContextDefinition("list",
 *       label = @Translation("List"),
 *       description = @Translation("A multi-valued data element to have its count compared, specified by using a data selector, eg 'node.uid.entity.roles'."),
 *       assignment_restriction = "selector"
 *     ),
 *     "operator" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator."),
 *       options_provider = "\Drupal\rules\TypedData\Options\ComparisonOperatorNumericOptions",
 *       assignment_restriction = "input",
 *       default_value = "==",
 *       required = FALSE
 *     ),
 *     "value" = @ContextDefinition("integer",
 *       label = @Translation("Count"),
 *       description = @Translation("The count to compare the data count with.")
 *     ),
 *   }
 * )
 */
class DataListCountIs extends RulesConditionBase {

  /**
   * Compare the value to the count of the list.
   *
   * @param array $list
   *   The list to compare the value to.
   * @param string $operator
   *   The type of comparison to do, may be one of '==', '<', or '>'.
   * @param int $value
   *   The value of that the count is to compare to.
   *
   * @return bool
   *   TRUE if the comparison returns true.
   */
  protected function doEvaluate(array $list, $operator, $value) {
    switch ($operator) {
      case '==':
        return count($list) == $value;

      case '<':
        return count($list) < $value;

      case '<=':
        return count($list) <= $value;

      case '>':
        return count($list) > $value;

      case '>=':
        return count($list) >= $value;

    }
  }

}
