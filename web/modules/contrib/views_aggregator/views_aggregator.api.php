<?php

/**
 * @file
 * API documentation for Views Aggregator Plus module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define your own group and column aggregation functions.
 *
 * @return array
 *   Aggregation display names indexed by associated function name.
 */
function hook_views_aggregation_functions_info() {
  $functions = [
    'views_aggregator_variance' => [
      'group' => t('Variance'),

      // Use NULL for column, if not applicable.
      'column' => t('Variance'),

      // If your function operates on a numeric field, but the result is no
      // longer a (single) number, for example when enumerating values, then the
      // original renderer is not appropriate. In that case set this to FALSE.
      // The default value is TRUE.
      'is_renderable' => TRUE,
    ],
  ];
  return $functions;
}

/**
 * Alter existing aggregation functions.
 *
 * @param array $aggregation_functions
 *   An array of aggregation functions currently defined.
 */
function hook_views_aggregation_functions_info_alter(array &$aggregation_functions) {
}

/**
 * @} End of "addtogroup hooks".
 */
