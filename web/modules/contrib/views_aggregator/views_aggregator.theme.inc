<?php

/**
 * @file
 * Themes the View UI created in the class views_aggregator_plugin_style_table.
 *
 * Based on similar functions in both Views and Views Calc modules.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for style plugin table templates.
 *
 * Default template: views-aggregator-style-plugin-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_views_aggregator_plugin_style_table(array &$variables) {
  $form = $variables['form'];

  // Note these are currently in the Views Calc rather than Views table order.
  $header = [
    t('Field'),
    t('Align'),
    t('Group aggregation'),
    t('Column aggregation'),
    t('Render column'),
    t('Separator'),
    [
      'data' => t('Sortable'),
      'align' => 'center',
    ],
    [
      'data' => t('Default sort'),
      'align' => 'center',
    ],
    [
      'data' => t('Default order'),
      'align' => 'center',
    ],
    [
      'data' => t('Hide empty column'),
      'align' => 'center',
    ],
    [
      'data' => t('Responsive'),
      'align' => 'center',
    ],
  ];

  $rows = [];
  foreach (Element::children($form['columns']) as $id) {
    $row = [];
    $multicell_group = [];
    $multicell_col = [];
    $row[]['data'] = $form['info'][$id]['name'];
    $row[]['data'] = $form['info'][$id]['align'];

    $multicell_group[] = $form['info'][$id]['has_aggr'];
    $multicell_group[] = $form['info'][$id]['aggr'];
    $multicell_group[] = $form['info'][$id]['aggr_par'];
    $row[]['data'] = $multicell_group;

    $multicell_col[] = $form['info'][$id]['has_aggr_column'];
    $multicell_col[] = $form['info'][$id]['aggr_column'];
    $multicell_col[] = $form['info'][$id]['aggr_par_column'];
    $row[]['data'] = $multicell_col;

    $row[]['data'] = $form['columns'][$id];
    $row[]['data'] = $form['info'][$id]['separator'];

    if (!empty($form['info'][$id]['sortable'])) {
      $row[] = [
        'data' => $form['info'][$id]['sortable'],
        'align' => 'center',
      ];
      $row[] = [
        'data' => $form['info'][$id]['default_sort_order'],
        'align' => 'center',
      ];
      $row[] = [
        'data' => $form['default'][$id],
        'align' => 'center',
      ];
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
    }
    $row[] = [
      'data' => $form['info'][$id]['empty_column'],
      'align' => 'center',
    ];
    $row[] = [
      'data' => $form['info'][$id]['responsive'],
      'align' => 'center',
    ];
    $rows[] = $row;
  }
  // Add the special 'None' row.
  $rows[] = [
    ['data' => t('None'), 'colspan' => 8],
    ['align' => 'center', 'data' => $form['default'][-1]],
    ['colspan' => 3],
  ];

  // Unset elements from the form array that are used to build the table
  // so that they are not rendered twice.
  unset($form['default']);
  unset($form['info']);
  unset($form['columns']);

  $variables['table'] = [
    '#type' => 'table',
    '#theme' => 'table__views_aggregator_plugin_style_table',
    '#header' => $header,
    '#rows' => $rows,
  ];

  $variables['form'] = $form;
}
