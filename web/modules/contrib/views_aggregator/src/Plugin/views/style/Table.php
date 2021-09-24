<?php

namespace Drupal\views_aggregator\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\style\Table as ViewsTable;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Style plugin to render each item as a row in a table.
 *
 * Based on the default Views table style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_aggregator_plugin_style_table",
 *   title = @Translation("Table with aggregation options"),
 *   help = @Translation("Creates a tabular UI for the user to define aggregation functions."),
 *   theme = "views_aggregator_results_table",
 *   display_types = {"normal"}
 * )
 */
class Table extends ViewsTable {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_aggregation'] = [
      'contains' => [
        'group_aggregation_results' => ['default' => 0],
        'result_label_prefix' => ['default' => ''],
        'result_label_suffix' => ['default' => ''],
        'grouping_row_class' => ['default' => ''],
        'grouping_field_class' => ['default' => ''],
      ],
    ];
    $options['column_aggregation'] = [
      'contains' => [
        'totals_per_page' => ['default' => TRUE],
        'totals_row_position' => ['default' => [1 => 0, 2 => 2, 3 => 0]],
        'totals_row_class' => ['default' => ''],
        'precision' => ['default' => 2],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Note: bulk of form is provided by superclass Table.
    parent::buildOptionsForm($form, $form_state);
    $handlers = $this->displayHandler->getHandlers('field');
    $columns = $this->sanitizeColumns($this->options['columns']);
    foreach ($columns as $field => $column) {
      if ($field == $column) {
        // Make all columns potentially sortable, excluding "Global: Counter".
        // Views Field View is not click sortable by default
        // (module plugin definition). To enable it, set its
        // "public function clickSortable()" to return TRUE.
        $plugin_id = $handlers[$field]->getPluginId();
        if ($plugin_id === 'counter') {
          $handlers[$field]->definition['click sortable'] = FALSE;
          $form['info'][$field]['sortable'] = NULL;
          $form['default'][$field] = NULL;
        }
        else {
          $handlers[$field]->definition['click sortable'] = TRUE;
        }
      }
    }

    // See function views_aggregator_theme().
    $form['#theme'] = 'views_aggregator_plugin_style_table';

    // Views style of grouping (splitting table into many) interferes,
    // so get rid of the form.
    unset($form['grouping']);

    $form['description_markup'] = [
      '#prefix' => '<div class="description form-item">',
      '#markup' => $this->t('Column aggregation functions may be enabled independently of group aggregation functions. Every group aggregation function, except <em>Filter rows (by regexp)</em>, requires exactly <strong>one</strong> field to be assigned the <em>Group and compress</em> function. With that done, select any of the other aggregation functions for some or all of the fields. Functions marked with an asterisk take an optional parameter. For the aggregation functions <em>Enumerate, Range</em> and <em>Tally</em> the optional parameter is a delimiter to separate items. <br/>If you rewrite the results of a field, the aggregation functions will still use the original value. You can use a Global:Custom text field and Twig syntax instead to rewrite the results and apply aggregation functions on it. The Twig number_format() filter can be applied as last filter, its settings for decimal and thousand separator will be respected by the aggregation functions.<br/>You may combine multiple fields into the same render column. If you do, the separator specified will be used to separate the fields. You can control column order and field labels in the Fields section of the main configuration page.'),
      '#suffix' => '</div>',
    ];

    foreach ($columns as $field => $column) {

      $form['info'][$field]['has_aggr'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Apply group function'),
        '#default_value' => isset($this->options['info'][$field]['has_aggr']) ? $this->options['info'][$field]['has_aggr'] : FALSE,
      ];

      $group_options = [];
      $column_options = [];
      foreach (views_aggregator_get_aggregation_functions_info() as $function => $display_names) {
        if (!empty($display_names['group'])) {
          $group_options[$function] = $display_names['group'];
        }
        if (!empty($display_names['column'])) {
          $column_options[$function] = $display_names['column'];
        }
      }
      $form['info'][$field]['aggr'] = [
        '#type' => 'select',
        '#options' => $group_options,
        '#multiple' => TRUE,
        '#default_value' => empty($this->options['info'][$field]['aggr']) ? ['views_aggregator_first'] : $this->options['info'][$field]['aggr'],
        '#states' => [
          'visible' => [
            'input[name="style_options[info][' . $field . '][has_aggr]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
      // Optional parameter for the selected aggregation function.
      $parameter_label = $this->t('Parameter');
      $form['info'][$field]['aggr_par'] = [
        '#type' => 'textfield',
        '#size' => 23,
        '#title' => $parameter_label,
        '#default_value' => isset($this->options['info'][$field]['aggr_par']) ? $this->options['info'][$field]['aggr_par'] : '',
        '#states' => [
          'visible' => [
            'input[name="style_options[info][' . $field . '][has_aggr]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];

      $form['info'][$field]['has_aggr_column'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Apply column function'),
        '#default_value' => isset($this->options['info'][$field]['has_aggr_column']) ? $this->options['info'][$field]['has_aggr_column'] : FALSE,
      ];
      $form['info'][$field]['aggr_column'] = [
        '#type' => 'select',
        '#options' => $column_options,
        '#multiple' => FALSE,
        '#default_value' => empty($this->options['info'][$field]['aggr_column']) ? 'views_aggregator_sum' : $this->options['info'][$field]['aggr_column'],
        '#states' => [
          'visible' => [
            'input[name="style_options[info][' . $field . '][has_aggr_column]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
      // Optional parameter for the selected column aggregation function.
      $form['info'][$field]['aggr_par_column'] = [
        '#type' => 'textfield',
        '#size' => 24,
        '#title' => $parameter_label,
        '#default_value' => isset($this->options['info'][$field]['aggr_par_column']) ? $this->options['info'][$field]['aggr_par_column'] : '',
        '#states' => [
          'visible' => [
            'input[name="style_options[info][' . $field . '][has_aggr_column]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }
    if (isset($this->options['group_aggregation']['group_aggregation_results'])) {
      $group_aggregation_results = $this->options['group_aggregation']['group_aggregation_results'];
    }
    else {
      $group_aggregation_results = 0;
    }
    $form['group_aggregation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Group aggregation options'),
      '#weight' => -3,
    ];
    $form['group_aggregation']['group_aggregation_results'] = [
      '#title' => $this->t('Aggregation results per group'),
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('results will be aggregated with one row per group'),
        1 => $this->t('no aggregation, results will be shown after each group (like subtotals)'),
      ],
      '#description' => $this->t('Select the second option, if you want to show subtotals'),
      '#default_value' => $group_aggregation_results,
      '#weight' => -2,
    ];
    $form['group_aggregation']['grouping_field_class'] = [
      '#title' => $this->t('Grouping field cell class'),
      '#type' => 'textfield',
      '#description' => $this->t('The CSS class to provide on each cell of the column/field that is being <em>Grouped and compressed</em>.'),
      '#default_value' => $this->options['group_aggregation']['grouping_field_class'],
    ];
    $form['group_aggregation']['result_label_prefix'] = [
      '#title' => $this->t('Label prefix'),
      '#type' => 'textfield',
      '#description' => $this->t('<em>If no aggregation selected: </em>Attach this <em>before</em> the label in the column/field that is being <em>Grouped and compressed</em>.'),
      '#default_value' => $this->options['group_aggregation']['result_label_prefix'],
    ];
    $form['group_aggregation']['result_label_suffix'] = [
      '#title' => $this->t('Label suffix'),
      '#type' => 'textfield',
      '#description' => $this->t('<em>If no aggregation selected: </em>Attach this <em>after</em> the label in the column/field that is being <em>Grouped and compressed</em>.'),
      '#default_value' => $this->options['group_aggregation']['result_label_suffix'],
    ];
    $form['group_aggregation']['grouping_row_class'] = [
      '#title' => $this->t('Grouping row class'),
      '#type' => 'textfield',
      '#description' => $this->t('<em>If no aggregation selected: </em>The CSS class to provide on each subtotals result row.'),
      '#default_value' => $this->options['group_aggregation']['grouping_row_class'],
    ];

    $form['column_aggregation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Column aggregation options'),
      '#weight' => -1,
    ];
    $form['column_aggregation']['totals_row_position'] = [
      '#title' => $this->t('Column aggregation position'),
      '#description' => $this->t('The <em>caption</em> option simply strings all column aggregations together with a space in between. So in order to add a comment or label you probably want to assign a <em>Label</em> column aggregation function to one of the first Views fields, before displaying any numeric aggregations.'),
      '#type' => 'checkboxes',
      '#options' => [
        1 => $this->t('in the table header'),
        2 => $this->t('in the table footer'),
        3 => $this->t('in the table caption'),
      ],
      '#default_value' => $this->options['column_aggregation']['totals_row_position'],
    ];
    $form['column_aggregation']['totals_per_page'] = [
      '#title' => $this->t('Column aggregation row applies to'),
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('the page shown, if a pager is enabled'),
        0 => $this->t('the entire result set (CAUTION: To enable this, create a copy of this view display, disable the pager there, set the <i>machine name</i> to <i>DISPLAY_NAME_no_pager</i> (e.g. page_1_no_pager) and give it a different path.)'),
      ],
      '#description' => $this->t('If your view does not have a pager, then the two options are equivalent.'),
      '#default_value' => $this->options['column_aggregation']['totals_per_page'],
      '#weight' => 1,
    ];
    $form['column_aggregation']['precision'] = [
      '#title' => $this->t('Column aggregation row default numeric precision'),
      '#type' => 'textfield',
      '#size' => 3,
      '#description' => $this->t('The number of decimals to use for column aggregations whose precisions are not defined elsewhere.'),
      '#default_value' => $this->options['column_aggregation']['precision'],
      '#weight' => 2,
    ];
    $form['column_aggregation']['totals_row_class'] = [
      '#title' => $this->t('Column aggregation row class'),
      '#type' => 'textfield',
      '#description' => $this->t('The CSS class to provide on the row containing the column aggregations.'),
      '#default_value' => $this->options['column_aggregation']['totals_row_class'],
      '#weight' => 3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $allowed_tags = ['b', 'br', 'em', 'i', 'p', 'strong', 'u'];
    $tag_msg = $this->t('<strong>Parameter</strong> field contains an illegal character or illegal HTML tag. Allowed tags are: %tags', ['%tags' => implode(', ', $allowed_tags)]);

    // Count the number of occurrences of the grouping and other aggregation
    // functions.
    $num_grouped = 0;
    $num_aggregation_functions = 0;
    foreach ($form_state->getValue(['style_options', 'info']) as $field_name => $options) {
      if (!empty($options['has_aggr'])) {
        if (in_array('views_aggregator_group_and_compress', $options['aggr'])) {
          $num_grouped++;
        }
        elseif (!in_array('views_aggregator_row_filter', $options['aggr'])) {
          $num_aggregation_functions += count($options['aggr']);
        }
      }
      $filtered = Xss::filter($options['aggr_par'], $allowed_tags);
      if ($options['aggr_par'] != $filtered) {
        $form_state->setError($form['info'][$field_name]['aggr_par'], $tag_msg);
      }
      $filtered = Xss::filter($options['aggr_par_column'], $allowed_tags);
      if ($options['aggr_par_column'] != $filtered) {
        $form_state->setError($form['info'][$field_name]['aggr_par_column'], $tag_msg);
      }
    }
    // When we have no aggregation functions, we must have 0 or 1 grouping
    // function. When we have aggregation functions, there must be 1 grouping.
    $ok = ($num_aggregation_functions == 0) ? $num_grouped <= 1 : $num_grouped == 1;
    if (!$ok) {
      $msg = $this->t('When applying group aggregation functions, you must also select <em>"Group and compress"</em> on exactly one field.');
      foreach ($form_state->getValue(['style_options', 'info']) as $field_name => $options) {
        $form_state->setError($form['info'][$field_name]['aggr'], $msg);
        $msg = '';
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Note that this class being a views_plugin, rather than a views_handler,
   * it does not have a post_execute() function.
   *
   * This function applies to the currently visible page only. If paging is
   * enabled for this display view->result may only contain part of the entire
   * result set.
   */
  public function preRender($results) {
    if (isset($this->view->is_temp_views_aggregator)) {
      return;
    }

    parent::preRender($results);

    if (empty($this->view->result)) {
      return;
    }
    $functions = $this->collectAggregationFunctions();
    $show_global_totals_with_pager = empty($this->options['column_aggregation']['totals_per_page']) && !empty($this->view->total_rows);

    if ($show_global_totals_with_pager) {
      $this->view->is_temp_views_aggregator = TRUE;
      $args = $this->view->args;
      $display_id = $this->view->current_display;

      $view_displays = $this->view->displayHandlers->getInstanceIds();
      if (in_array($display_id . '_no_pager', $view_displays)) {
        $clone = $this->view->createDuplicate();
        $clone->is_temp_views_aggregator = TRUE;
        $clone->executeDisplay($display_id . '_no_pager', $args);

        // First apply the row filters (if any), then aggregate the columns.
        // Only interested in column aggregation, so only 'column' group needed.
        $column_group = ['column' => []];
        foreach ($clone->result as $num => $row) {
          $column_group['column'][$num] = $row;
        }
        $totals = $clone->style_plugin->executeAggregationFunctions($column_group, $functions);
        $clone->postExecute();
        $clone->destroy();
      }
    }
    // Because we are going to need the View results AFTER token replacement,
    // we render the result set here. This is NOT duplication of CPU time,
    // because self::renderFields(), if called for a second time, will do
    // nothing when self::$rendered_fields has been populated already.
    // render_fields() will puts currency signs in front of moneys, embeds node
    // and taxonomy term references in hyperlinks etc.
    $this->renderFields($results);

    // Apply the row filters first, then aggregate the groups.
    $this->applyRowFilters();
    $groups = $this->aggregateGroups();
    $with_pager = FALSE;
    $values = $this->executeAggregationFunctions($groups, $functions);
    unset($groups['column']);

    // Prepare for aggregation based on selected option.
    if (isset($this->options['group_aggregation']['group_aggregation_results'])) {
      $group_aggregation_results = $this->options['group_aggregation']['group_aggregation_results'];
    }
    else {
      $group_aggregation_results = 0;
    }
    // Normal aggregation selected - write the results and compress the groups.
    if ($group_aggregation_results == 0) {
      // Write group aggregation results into the View results.
      $this->setAggregatedGroupValues($groups, $values, $group_aggregation_results);
      // With the aggregation functions now complete, destroy rows not part
      // of the aggregation.
      $this->compressGroupedResults($groups);
    }

    // Sort the table based on the selected sort column, i.e. $this->active.
    if (isset($this->active)) {
      // To aid in sorting, add the row's index to each row object.
      foreach ($this->view->result as $num => $row) {
        $this->view->result[$num]->num = $num;
      }
      // First sort by default order.
      if ($this->options['default']) {
        // Store current sorting options.
        $sort = $this->active;
        $order = $this->order;
        // Set default sorting options.
        $this->active = $this->options['default'];
        $this->order = !empty($this->options['order']) ? $this->options['order'] : 'asc';
        $this->order = isset($this->options['info'][$this->active]['default_sort_order']) ? $this->options['info'][$this->active]['default_sort_order'] : $this->order;
        // Sort by default
        uasort($this->view->result, [$this, 'compareResultRows']);
        // Restore current sorting options.
        $this->order = $order;
        $this->active = $sort;
      }
      // Fix counter fields.
      $this->fixCounterFields();
      // Now sort by active order.
      uasort($this->view->result, [$this, 'compareResultRows']);
    }
    else {
      // Fix counter fields anyways.
      $this->fixCounterFields();
    }

    // Set the totals after eventual sorting has finished.
    if (empty($this->view->totals)) {
      // If not already set above, write the column aggregation result row on
      // the View object. This row will be rendered via
      // template_preprocess_views_aggregator_results_table().
      $this->view->totals = $this->setTotalsRow($values);
    }

    // If we have a pager enabled with option set to show total
    // for whole resultset insteead of page - overwrite the
    // page totals as last step.
    if (isset($totals)) {
      $this->view->totals = $this->setTotalsRow($totals);
    }
    // Aggregate the results per group and show them in a separate row,
    // without compression.
    if ($group_aggregation_results == 1) {
      $this->setAggregatedGroupValues($groups, $values, $group_aggregation_results);
    }
  }

  /**
   * Filters out rows from the table based on a field cell matching a regexp.
   */
  protected function applyRowFilters() {
    $field_handlers = $this->view->field;
    foreach ($this->options['info'] as $field_name => $options) {
      if (!empty($options['has_aggr']) && in_array('views_aggregator_row_filter', $options['aggr'])) {
        views_aggregator_row_filter($this, $field_handlers[$field_name], $options['aggr_par']);
      }
    }
  }

  /**
   * Aggregate and compress the View's rows into groups.
   *
   * @return array
   *   An array of aggregated groups.
   */
  protected function aggregateGroups() {
    $field_handlers = $this->view->field;
    // Find the one column to group by and execute the grouping.
    foreach ($this->options['info'] as $field_name => $options) {
      if (!empty($options['has_aggr']) && in_array('views_aggregator_group_and_compress', $options['aggr'], FALSE)) {
        $groups = views_aggregator_group_and_compress($this->view->result, $field_handlers[$field_name], $options['aggr_par']);
        break;
      }
    }
    if (empty($groups)) {
      // If there are no regular groups, create a special group for column
      // aggregation. This group holds all View result rows.
      foreach ($this->view->result as $num => $row) {
        $groups['column'][$num] = $row;
      }
    }
    return $groups;
  }

  /**
   * Collect the aggregation functions from the Views UI.
   *
   * @return array
   *   Functions used for aggregation.
   */
  protected function collectAggregationFunctions() {
    $functions = [];
    foreach ($this->options['info'] as $field_name => $options) {
      // Make a list of the group and column functions to call for this field.
      if (!empty($options['has_aggr'])) {
        foreach ($options['aggr'] as $function) {
          if ($function != 'views_aggregator_row_filter' && $function != 'views_aggregator_group_and_compress') {
            if (empty($functions[$field_name]) || !in_array($function, $functions[$field_name])) {
              $functions[$field_name][] = $function;
            }
          }
        }
      }
      // Column aggregation function, if requested, is last.
      if (!empty($options['has_aggr_column'])) {
        $function = $options['aggr_column'];
        if (empty($functions[$field_name]) || !in_array($function, $functions[$field_name])) {
          $functions[$field_name][] = $function;
        }
      }
    }
    return $functions;
  }

  /**
   * Executes the supplied aggregation functions with the groups as arguments.
   *
   * @param array $groups
   *   Groups of aggregated rows.
   * @param array $functions
   *   Aggregation functions to use.
   *
   * @return array
   *   Function return values.
   */
  protected function executeAggregationFunctions(array $groups, array $functions) {
    $field_handlers = $this->view->field;
    $values = [];
    foreach ($functions as $field_name => $field_functions) {
      if (empty($field_handlers[$field_name])) {
        continue;
      }
      $options = $this->options['info'][$field_name];
      foreach ($field_functions as $function) {
        $group_par = (!isset($options['aggr_par']) || $options['aggr_par'] == '') ? NULL : $options['aggr_par'];
        $column_par = (!isset($options['aggr_par_column']) || $options['aggr_par_column'] == '') ? NULL : $options['aggr_par_column'];
        $aggr_values = $function($groups, $field_handlers[$field_name], $group_par, $column_par);
        // $aggr_values is indexed by group value and/or 'column'.
        // 'column' is the last evaluated value for the field.
        if (isset($aggr_values['column'])) {
          $field_handlers[$field_name]->last_render = $aggr_values['column'];
        }
        foreach ($aggr_values as $group => $value) {
          // 'column' function is last so may override earlier value.
          if (!isset($values[$field_name][$group]) || $group == 'column') {
            $values[$field_name][$group] = $value;
          }
        }
      }
    }
    return $values;
  }

  /**
   * Removes no longer needed View result rows from the set.
   *
   * @param array $groups
   *   Groups of aggregated rows.
   */
  protected function compressGroupedResults(array $groups) {
    $rows_per_group = 0;
    foreach ($groups as $rows) {
      if (isset($rows)) {
        $rows_per_group = count($rows);
      }
      $is_first = TRUE;
      foreach ($rows as $num => $row) {
        // The aggregated row is the first of each group. Destroy the others.
        if (!$is_first) {
          unset($this->rendered_fields[$num]);
          unset($this->view->result[$num]);
        }
        $is_first = FALSE;
      }
    }
    // Reindex the result arrays.
    $this->rendered_fields = array_values(array_filter($this->rendered_fields));
    $this->view->result = array_values(array_filter($this->view->result));

    // Used for Views Bulk Operations to rename the checkboxes.
    $field_handlers = $this->view->field;
    $moduleHandler = \Drupal::service('module_handler');
    foreach ($field_handlers as $field_name => $handler) {
      if ($handler instanceof BulkForm ||
        ($moduleHandler->moduleExists('views_bulk_operations') && is_a($handler,
            '\\Drupal\\views_bulk_operations\\Plugin\\views\\field\\ViewsBulkOperationsBulkForm'))) {
        foreach ($this->rendered_fields as $num => $row) {
          $this->rendered_fields[$num][$field_name] = ViewsRenderPipelineMarkup::create('<!--form-item-' . $field_name . '--' . $num . '-->');
        }
      }
    }
  }

  /**
   * Returns the raw or rendered result at the intersection of column and row.
   *
   * @param object $field_handler
   *   The handler associated with the result column being requested.
   * @param int $row_num
   *   The result row number.
   * @param bool $render
   *   Whether the rendered or raw value should be returned.
   *
   * @return string
   *   Returns empty string if there are no results for the requested row_num.
   */
  public function getCell($field_handler, $row_num, $render = FALSE) {
    // Some functions need rendered values (e.g. enumerate).
    // For the rendered_fields array we need id, otherwise take entity_field.
    if (isset($field_handler->options['entity_field']) && $render == FALSE) {
      $field_name = $field_handler->options['entity_field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }
    if (isset($this->rendered_fields[$row_num][$field_name])) {
      // Special handling of rendered fields, "Global: Custom text",
      // "Global: View" and custom plugin fields.
      if ($render === TRUE || $this->isCustomTextField($field_handler) || $this->isViewsFieldView($field_handler) || !in_array($field_handler->getProvider(), ['views', 'webform_views'])) {
        return trim(strip_tags((string) $this->rendered_fields[$row_num][$field_name]));
      }
      // Special handling of "Webform submission data".
      if ($this->isWebformNumeric($field_handler) || $this->isWebformField($field_handler)) {
        $webform_raw_value = $this->getCellRaw($field_handler, $field_handler->view->result[$row_num], TRUE);
        return $webform_raw_value;
      }
    }
    if (!isset($field_handler->view->result[$row_num])) {
      return '';
    }
    $field_handler->view->row_index = $row_num;
    return $this->getCellRaw($field_handler, $field_handler->view->result[$row_num], TRUE);
  }

  /**
   * Returns the raw, unrendered result at the intersection of column and row.
   *
   * Should normally not be called, especially not for
   * "Global: Custom text" fields.
   *
   * @param object $field_handler
   *   The handler associated with the result column being requested.
   * @param object $result_row
   *   The result row.
   * @param bool $compressed
   *   If the result is a (nested) array, return the first primitive value.
   *
   * @return string
   *   the raw contents of the cell
   */
  private function getCellRaw($field_handler, $result_row, $compressed = TRUE) {
    if (isset($field_handler->options['entity_field'])) {
      $field_name = $field_handler->options['entity_field'];
    }
    elseif ($this->isWebformNumeric($field_handler) || $this->isWebformField($field_handler)) {
      $field_name = $field_handler->definition['webform_submission_field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }
    // Get field from the relationship_entities, otherwise from the entity.
    if ($field_handler->options['relationship'] && $field_handler->options['relationship'] != 'none') {
      $relationship = $field_handler->options['relationship'];
      $source = $result_row->_relationship_entities[$relationship];
    }
    else {
      $source = $result_row->_entity;
    }

    if (isset($source->{$field_name})) {
      $field = $source->{$field_name};
    }

    // "Commerce" fields - prepare totals of multiple currencies.
    if ($this->isCommerceField($field_handler)) {
      $field_id = $field_handler->options['id'];
      if (isset($field->currency_code)) {
        $field_value = $field->number;
        // Write the values into an array (field, currency, value)
        if (!isset($this->commerce_field_values[$field_id])) {
          $this->commerce_field_values[$field_id] = [$field->currency_code => [$field->number]];
        }
        else {
          if (isset($this->commerce_field_values[$field_id][$field->currency_code])) {
            $this->commerce_field_values[$field_id][$field->currency_code][] = $field->number;
          }
          else {
            $this->commerce_field_values[$field_id][$field->currency_code] = [$field->number];
          }
        }
      }
    }

    // Get the commerce number.
    if (isset($field->number)) {
      $value = $field->number;
    }
    // Get the commerce value.
    elseif (isset($field->value)) {
      $value = $field->value;
    }
    // Webform data.
    elseif ($this->isWebformField($field_handler) || $this->isWebformNumeric($field_handler)) {
      if (isset($source->getData()[$field_name])) {
        $value = $source->getData()[$field_name];
      }
    }
    // Commerce attributes.
    elseif (substr($field_name, 0, 10) === 'attribute_') {
      $attribute_values = $source->getAttributeValue($field_name);
      $value = $attribute_values->getName();
    }
    elseif (isset($field) && NULL !== ($field->getValue())) {
      $value = $field->getValue();
    }
    elseif (NULL !== ($this->getFieldValue($result_row->index, $field_name))) {
      $value = $this->getFieldValue($result_row->index, $field_name);
    }
    else {
      $value = '';
    }
    // Deal with multiple subvalues like Entity reference multivalue fields
    // (lists), AddressFields etc.
    // The value is an array - only count function makes sense here, e.g. sum
    // will return some weird number, summing up the first id in the list.
    if ($compressed && is_array($value)) {
      $value = reset($value);
      if (is_array($value)) {
        $value = reset($value);
      }
    }
    return $value;
  }

  /**
   * This is needed.
   */
  public function getFormats() {
    return [];
  }

  /**
   * Render and set a raw value on the table cell in specified column and row.
   *
   * @param object $field_handler
   *   The field handler associated with the table column being requested.
   * @param int $row_num
   *   The result row number. Must be specified.
   * @param mixed $new_values
   *   A single or array of values to set. This should be the raw value(s),
   *   otherwise sorting may not work properly.
   * @param string $separator
   *   The separator to use, when $new_values is an array.
   *
   * @return mixed
   *   The rendered value.
   */
  public function setCell($field_handler, $row_num, $new_values, $separator) {
    $rendered_value = FALSE;

    if (isset($field_handler->options['entity_field'])) {
      $field_name = $field_handler->options['entity_field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }

    // Check, whether we need to aggregate and compress or not.
    if (isset($this->options['group_aggregation']['group_aggregation_results'])) {
      $group_aggregation_results = $this->options['group_aggregation']['group_aggregation_results'];
    }
    else {
      $group_aggregation_results = 0;
    }

    // The webform submission id comes in as views_handler_field_numeric, so all
    // we have to detect it is its name, i.e. 'sid'.
    $is_webform_value = ($field_name == 'sid') || $this->isWebformNumeric($field_handler);

    // Depending on the aggregation function applied, default rendering may be
    // inappropriate. For instance "Trains (4)" cannot be rendered numerically.
    if ($is_renderable = $this->isRenderable($field_name, FALSE)) {
      if ($is_webform_value) {
        $rendered_value = $this->renderNewWebformValue($field_handler, $row_num, $new_values, $separator);
      }
      else {
        $rendered_value = $this->renderNewValue($field_handler, $row_num, $new_values, $separator);
      }
    }
    elseif ($is_webform_value) {
      $rendered_value = $new_values;
    }

    if ($rendered_value === FALSE && !$is_webform_value) {
      $rendered_value = is_array($new_values) ? implode($separator, $new_values) : $new_values;
    }

    if ($group_aggregation_results == 0) {
      return $this->rendered_fields[$row_num][$field_handler->options['id']] = $rendered_value;
    }
    else {
      return $rendered_value;
    }
  }

  /**
   * Returns the rendered value for a new (raw) value of a table cell.
   *
   * @param object $field_handler
   *   The handler associated with the field/table-column being requested.
   * @param int $row_num
   *   The result row number.
   * @param mixed $new_values
   *   The raw value or array of raw values to render.
   * @param string $separator
   *   Separator to use between rendered values, when $new_values is an array.
   *
   * @return mixed
   *   The rendered new value or FALSE if the value could not be rendered.
   */
  protected function renderNewValue($field_handler, $row_num, $new_values, $separator) {
    $new_values = is_array($new_values) ? $new_values : [$new_values];

    // If the field_handler belongs to an entity Field (as in the field module),
    // then we call renderFromRaw(), which uses the _relationship_entities array
    // to find, format and replace the supplied value.
    if (isset($field_handler->options['entity_field'])) {
      $field_name = $field_handler->options['entity_field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }
    $rendered_values = [];
    foreach ($new_values as $new_value) {
      if ($this->isCustomTextField($field_handler)) {
        $custom_formula = $field_handler->options['alter']['text'];
        // Use only the "number_format" at the end of the
        // expression to format the result only.
        if (strrpos($custom_formula, '|number_format(')) {
          $start_pos = strrpos($custom_formula, '|number_format(') - 1;
          $custom_format = preg_match('/number_format\((.*)\)/', substr($custom_formula, $start_pos), $matches, PREG_OFFSET_CAPTURE);
          $custom_delimiters = str_getcsv($matches[1][0], ',', "'");
          // Check if arguments are set, otherwise use dot for default decimal.
          $rendered_values[] = number_format($new_value, isset($custom_delimiters[0]) ? $custom_delimiters[0] : 0, isset($custom_delimiters[1]) ? $custom_delimiters[1] : '.', isset($custom_delimiters[2]) ? $custom_delimiters[2] : '');
        }
        else {
          $rendered_values[] = $new_value;
        }
      }
      elseif ($this->isCommerceField($field_handler)) {
        // Check which function is set on the field for group aggregation.
        $aggr_operation = substr(reset($this->options['info'][$field_handler->options['id']]['aggr']), 17);
        $aggr = $this->options['info'][$field_handler->options['id']]['has_aggr'];
        $col_aggr = $this->options['info'][$field_handler->options['id']]['has_aggr_column'];
        $col_operation = substr($this->options['info'][$field_handler->options['id']]['aggr_column'], 17);
        $operations = [
          'sum',
          'average',
          'median',
          'maximum',
          'minimum',
          'range',
        ];

        // The value of attributes is stored differently.
        if (substr($field_name, 0, 10) === 'attribute_') {
          $rendered_values[] = $new_value;
        }
        elseif (in_array($aggr_operation, $operations) && $aggr == 1) {
          $rendered_values[] = $this->renderFromRaw($field_handler, $row_num, $new_value);
        }
        elseif (in_array($col_operation, $operations) && $col_aggr == 1) {
          $rendered_values[] = $this->renderFromRaw($field_handler, $row_num, $new_value);
        }
        else {
          $rendered_values[] = $new_value;
        }
      }
      elseif ($this->isStandardField($field_handler)) {
        $rendered_values[] = $this->renderFromRaw($field_handler, $row_num, $new_value);
      }
      elseif ($this->isViewsFieldView($field_handler)) {
        $rendered_values[] = $new_value;
      }
      elseif ($this->isWebformNumeric($field_handler)) {
        $separator = $this->options['info'][$field_name]['separator'];
        $rendered_values[] = $this->renderNewWebformValue($field_handler, $row_num, $new_value, $separator);
      }
      else {
        $rendered_values[] = $new_value;
      }
    }
    $rendered_value = implode(empty($separator) ? ' - ' : $separator, $rendered_values);
    return is_array($rendered_value) ? $this->getRenderer()->render($rendered_value) : $rendered_value;
  }

  /**
   * Returns whether the supplied field is a standard Views field.
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is a standard Views field.
   */
  protected function isStandardField($field_handler) {
    return ($field_handler->getPluginId() === 'field');
  }

  /**
   * Checks if the field is a Views field of type "Global: Custom text".
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is of type "Global: Custom text".
   */
  protected function isCustomTextField($field_handler) {
    return ($field_handler->getPluginId() === 'custom');
  }

  /**
   * Checks if the field is a Views field of type "Global: Custom text".
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is of type "Global: Custom text".
   */
  protected function isViewsFieldView($field_handler) {
    return ($field_handler->getPluginId() === 'view');
  }

  /**
   * Checks if the field is a Webform Submission Data Numeric field.
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is a Webform Submission Data Numeric field.
   */
  protected function isWebformNumeric($field_handler) {
    return ($field_handler->getPluginId() === 'webform_submission_field_numeric');
  }

  /**
   * Checks if the field is a Webform Submission Data field.
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is a Webform Submission Data field.
   */
  protected function isWebformField($field_handler) {
    return ($field_handler->getPluginId() === 'webform_submission_field');
  }

  /**
   * Checks if the field comes from a Commerce entity.
   *
   * @param object $field_handler
   *   The object belonging to the View result field.
   *
   * @return bool
   *   TRUE if the field is from a Commerce entity.
   */
  protected function isCommerceField($field_handler) {
    if (isset($field_handler->definition['entity_type'])) {
      return (substr($field_handler->definition['entity_type'], 0, 9) === 'commerce_');
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the rendered representation for a new webform value.
   *
   * @param object $field_handler
   *   The webform handler associated with the
   *   field/table-column being requested.
   * @param int $row_num
   *   The result row number.
   * @param array $new_values
   *   The raw value(s) to render using the webform's rounding, prefix, suffix.
   * @param string $separator
   *   Separator to use between rendered values, when $new_values is an array.
   *
   * @return string
   *   The rendered value.
   */
  protected function renderNewWebformValue($field_handler, $row_num, array $new_values, $separator) {
    $result_row = isset($row_num) ? $field_handler->view->result[$row_num] : reset($field_handler->view->result);
    $nid = $field_handler->options['id'];
    if (isset($field_handler->options['entity_field'])) {
      $nid = $field_handler->options['entity_field'];
    }
    else {
      $nid = $field_handler->options['id'];
    }
    $cid = $field_handler->definition['webform_submission_field'];
    $data = $result_row->_entity->getData();
    $rendered_values = [];
    $new_values = is_array($new_values) ? $new_values : [$new_values];

    foreach ($new_values as $new_value) {
      if (is_array($data[$cid])) {
        $data[$cid] = [$new_value];
      }
      else {
        $data[$cid] = $new_value;
      }
      $result_row->_entity->setData($data);
      $rendered = $field_handler->render($result_row);

      if (is_object($rendered[$cid]['#value'])) {
        $rendered_values[] = empty($rendered) ? $new_value : $rendered[$cid]['#value']->__toString();
      }
      elseif (isset($rendered[$cid]['#value']['#plain_text'])) {
        $rendered_values[] = empty($rendered) ? $new_value : $rendered[$cid]['#value']['#plain_text'];
      }
      elseif (isset($rendered[$cid]['#value']['#items'])) {
        if (is_array($rendered[$cid]['#value']['#items'][0])) {
          $rendered_values[] = empty($rendered) ? $new_value : $rendered[$cid]['#value']['#items'][0]['#plain_text'];
        }
        else {
          $rendered_values[] = empty($rendered) ? $new_value : $rendered[$cid]['#value']['#items'][0]->__toString();
        }
      }
      else {
        $rendered_values[] = empty($rendered) ? $new_value : $rendered[$cid]['#value'];
      }
    }

    $rendered_value = implode(empty($separator) ? ' - ' : $separator, $rendered_values);
    return is_array($rendered_value) ? $this->getRenderer()->renderPlain($rendered_value) : $rendered_value;
  }

  /**
   * Render a standard views field from a raw value.
   *
   * The field will be rendered with appropriate CSS classes, without label.
   *
   * @param object $field_handler
   *   The views_handler_field_field object belonging to the View result field.
   * @param int $row_num
   *   The view result row number to change. Pass NULL to simply render
   *   $raw_value outside the context of a View, without affecting any rows.
   * @param mixed $raw_value
   *   Compound or simple value.
   *   If NULL the row value of the field is re-rendered using its current
   *   (raw) value.
   *
   * @return string
   *   The rendered value or FALSE, if the type of field is not supported.
   */
  protected function renderFromRaw($field_handler, $row_num = NULL, $raw_value = NULL) {
    if (isset($field_handler->options['entity_field'])) {
      $field_name = $field_handler->options['entity_field'];
    }
    elseif (isset($field_handler->options['field'])) {
      $field_name = $field_handler->options['field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }

    if (isset($row_num)) {
      $row = $field_handler->view->result[$row_num];
    }
    else {
      // Get the first non-empty result row.
      foreach ($field_handler->view->result as $row_id => $row_value) {
        if (isset($row_value->_entity->{$field_name})) {
          $row = $row_value;
          break;
        }
        elseif (count(array_keys($row_value->_relationship_entities)) > 0) {
          foreach ($row_value->_relationship_entities as $key => $rel) {
            if (isset($row_value->_relationship_entities[$rel]->{$field_name})) {
              $row = $row_value;
              break;
            }
          }
        }
      }
    }
    $display = [
      'type' => $field_handler->options['type'],
      'settings' => $field_handler->options['settings'],
      'label' => 'hidden',
    ];

    // Check, if the field is in _entity (base table).
    if (isset($row->_entity->{$field_name})) {
      $field = $row->_entity->{$field_name};
    }

    // Check, if the field is in _relationship_entities.
    elseif (isset($row->_relationship_entities)) {
      $relationship_entity = array_keys($row->_relationship_entities);
      foreach ($relationship_entity as $key => $rel) {
        if (isset($row->_relationship_entities[$rel]->{$field_name})) {
          $field = $row->_relationship_entities[$rel]->{$field_name};
        }
      }
    }
    // Value not found - mark the field and exit.
    else {
      return 'Not found: ' . $field_name . ' > ' . $raw_value;
    }

    // Commerce fields with currency format (e.g. price)
    // store value in "number".
    if (isset($field->currency_code)) {
      $field->number = $raw_value;
    }
    else {
      $field->value = $raw_value;
    }

    $render_array = $field->view($display);
    $rendered_value = $this->getRenderer()->renderPlain($render_array);

    return strip_tags((string) $rendered_value);
  }

  /**
   * Write the aggregated results back into the View's rendered results.
   *
   * @param array $groups
   *   An array of groups, indexed by group name.
   * @param array $values
   *   An array of value arrays, indexed by field name first and group second.
   * @param int $group_aggregation_results
   *   Options flag, whether to aggregate the values or not.
   */
  protected function setAggregatedGroupValues(array $groups, array $values, $group_aggregation_results) {
    $subtotals = [];
    $label_prefix = ($this->options['group_aggregation']['result_label_prefix']) ? $this->options['group_aggregation']['result_label_prefix'] : '';
    $label_suffix = ($this->options['group_aggregation']['result_label_suffix']) ? $this->options['group_aggregation']['result_label_suffix'] : '';
    $field_handlers = $this->view->field;
    foreach ($this->options['info'] as $field_name => $options) {
      if (!empty($options['has_aggr']) && in_array('views_aggregator_group_and_compress', $options['aggr'], FALSE)) {
        $field_label = $field_name;
      }
      foreach ($groups as $group => $rows) {
        if ($group != 'column' && isset($values[$field_name][$group])) {
          $current_row = 1;
          foreach ($rows as $num => $row) {
            $separator = $this->options['info'][$field_name]['aggr_par'];
            $group_rows = count(array_keys($rows));
            if ($group_aggregation_results == 1) {
              if ($current_row == $group_rows) {
                if (isset($field_label)) {
                  $field_value = $this->getCell($field_handlers[$field_label], $num, TRUE);
                  $subtotals[$num][$field_label] = $this->t($label_prefix) . trim($field_value) . $this->t($label_suffix);
                }
                $subtotals[$num][$field_name] = $this->setCell($field_handlers[$field_name], $num, $values[$field_name][$group], $separator);
              }
            }
            else {
              $this->setCell($field_handlers[$field_name], $num, $values[$field_name][$group], $separator);
              // Only need to set on the first member of the group.
              break;
            }
            $current_row++;
          }
        }
      }
    }
    $this->view->subtotals = $subtotals;
  }

  /**
   * Write the aggregated results back into the View results totals (footer).
   *
   * @param array $values
   *   An array of field value arrays, indexed by field name and 'column'.
   */
  protected function setTotalsRow(array $values) {
    $totals = [];
    foreach ($values as $field_name => $group) {
      if (!empty($this->options['info'][$field_name]['has_aggr_column']) && isset($group['column'])) {
        // For Commerce fields - only care about the following operations.
        $commerce_operations = [
          'sum',
          'average',
          'median',
          'maximum',
          'minimum',
          'range',
        ];

        // Check which column operation we have on the field.
        $col_operation = substr($this->options['info'][$field_name]['aggr_column'], 17);
        $total = $group['column'];

        if ($this->isRenderable($field_name, TRUE)) {
          $field_handler = $this->view->field[$field_name];
          $is_webform_value = is_a($field_handler, 'webform_handler_field_submission_data');
          // This is to make render_text() work properly.
          $field_handler->original_value = $total;
          $separator = $this->options['info'][$field_name]['aggr_par_column'];
          $totals[$field_name] = $is_webform_value
            ? $this->renderNewWebformValue($field_handler, 0, $total, $separator)
            : $this->renderNewValue($field_handler, NULL, $total, $separator);
        }
        else {
          $totals[$field_name] = ['#markup' => $total];
        }

        // Calculate proper totals for commerce fields with
        // multiple currencies in one column (results per currency).
        // Conditions:
        // 1. the commerce_values array is set AND
        // 2. the field has more than one currency AND
        // 3. more than one entry per currency AND
        // 4. it is a math function (sum, min, max average, median)
        if (isset($this->commerce_field_values)) {
          if (array_key_exists($field_name, $this->commerce_field_values)) {
            if (in_array($col_operation, $commerce_operations)) {
              if (count($this->commerce_field_values[$field_name]) > 1) {
                $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
                $options = ['currency_display' => $field_handler->options['settings']['currency_display']];
                $commerce_rendered = [];
                foreach ($this->commerce_field_values[$field_name] as $currency_code => $number) {
                  // Do the operations in case we have multiple values.
                  if (count($number) > 1) {
                    if ($col_operation == 'sum') {
                      $new_value = array_sum($number);
                    }
                    elseif ($col_operation == 'average') {
                      $new_value = array_sum($number) / count($number);
                    }
                    elseif ($col_operation == 'min') {
                      $new_value = min($number);
                    }
                    elseif ($col_operation == 'max') {
                      $new_value = max($number);
                    }
                    elseif ($col_operation == 'median') {
                      sort($number);
                      // Total numbers in array.
                      $count = count($number);
                      // Find the middle value, or the lowest middle value.
                      $middleval = floor(($count - 1) / 2);
                      // Odd number, middle is the median.
                      if ($count % 2) {
                        $new_value = $number[$middleval];
                      }
                      // Even number, calculate avg of 2 medians.
                      else {
                        $low = $number[$middleval];
                        $high = $number[$middleval + 1];
                        $new_value = (($low + $high) / 2);
                      }
                    }
                    elseif ($col_operation == 'range') {
                      $new_value_min = $currency_formatter->format(min($number), $currency_code, $options);
                      $new_value_max = $currency_formatter->format(max($number), $currency_code, $options);
                    }
                    if ($col_operation == 'range') {
                      $commerce_rendered[] = $new_value_min . ' - ' . $new_value_max;
                    }
                    else {
                      $commerce_rendered[] = $currency_formatter->format($new_value, $currency_code, $options);
                    }
                  }
                  // Just render the value.
                  else {
                    $commerce_rendered[] = $currency_formatter->format($number[0], $currency_code, $options);
                  }
                }
                $commerce_new_total = implode('<br/>', $commerce_rendered);
                $totals[$field_name] = ['#markup' => $commerce_new_total];
              }
            }
          }
        }
      }
    }
    return $totals;
  }

  /**
   * Returns if the supplied field is renderable through its native function.
   *
   * @param string $field_name
   *   Field name to check.
   * @param bool $is_column
   *   TRUE, if we handle column aggregation.
   *
   * @return bool
   *   TRUE, if the field is renderable through its native function.
   */
  public function isRenderable($field_name, $is_column = FALSE) {
    if (empty($this->options['info'][$field_name][$is_column ? 'has_aggr_column' : 'has_aggr'])) {
      return TRUE;
    }
    $aggr_functions = $this->options['info'][$field_name][$is_column ? 'aggr_column' : 'aggr'];
    $aggr_function = is_array($aggr_functions) ? end($aggr_functions) : $aggr_functions;
    $aggr_function_info = views_aggregator_get_aggregation_functions_info($aggr_function);

    // Aggregation functions are considered renderable unless set to FALSE.
    return !isset($aggr_function_info['is_renderable']) || !empty($aggr_function_info['is_renderable']);
  }

  /**
   * Records the "active" field, i.e. the column clicked to be sorted.
   *
   * Also records the sort order ('asc' or 'desc').
   * This is identical to Table::buildSortPost, except
   * for the last statement, which has a condition added.
   */
  public function buildSortPost() {
    $query = $this->view->getRequest()->query;
    $order = $query->get('order');
    if (!isset($order)) {
      // Check for a 'default' clicksort. If there isn't one, exit gracefully.
      if (empty($this->options['default'])) {
        return;
      }
      $sort = $this->options['default'];
      if (!empty($this->options['info'][$sort]['default_sort_order'])) {
        $this->order = $this->options['info'][$sort]['default_sort_order'];
      }
      else {
        $this->order = !empty($this->options['order']) ? $this->options['order'] : 'asc';
      }
    }
    else {
      $sort = $order;
      // Store the $order for later use.
      $request_sort = $query->get('sort');
      $this->order = !empty($request_sort) ? strtolower($request_sort) : 'asc';
    }
    // If a sort we don't know anything about gets through, exit gracefully.
    if (empty($this->view->field[$sort])) {
      return;
    }

    // Ensure $this->order is valid.
    if ($this->order != 'asc' && $this->order != 'desc') {
      $this->order = 'asc';
    }

    // Store the $sort for later use.
    $this->active = $sort;

    // Tell the field to click-sort, but only if it is a standard Views field or
    // a field not aggregated, in which cases sorting will be dealt with in
    // $this->pre_render().
    $plugin_id = $this->view->field[$sort]->getPluginId();
    if (!in_array($plugin_id, [
      'addressfield',
      'taxonomy_term_reference',
      'custom',
      'view',
      'webform_submission_field_numeric',
      'webform_submission_field',
    ]) && ($this->options['info'][$sort]['has_aggr'] == 0)) {
      $this->view->field[$sort]->clickSort($this->order);
    }
  }

  /**
   * Compare function for aggregated groups, for use in sorting functions.
   *
   * @param \Drupal\views\ResultRow $row1
   *   The first aggregated group of result rows.
   * @param \Drupal\views\ResultRow $row2
   *   The second aggregated group of result rows.
   *
   * @return int
   *   The compare code indicating whether $row1 is smaller than (-1), equal
   *   to (0) or greater than (1) $row2.
   */
  protected function compareResultRows(ResultRow $row1, ResultRow $row2) {

    // The sorting data may be raw or rendered, while the sorting style may be
    // alphabetical or numeric.
    //
    // Columns that need to be sorted using raw values:
    // o numbers and money, so that "$1,000" comes AFTER "$9.99" (ascending)
    // o dates and date ranges
    //
    // Columns that need to be sorted using rendered, post-aggregated values:
    // o Custom: Text field, Views Field View, addresses, taxonomy terms.
    $field_handler = $this->view->field[$this->active];
    if (isset($field_handler->options['entity_field'])) {
      $field_name = $field_handler->options['entity_field'];
    }
    else {
      $field_name = $field_handler->options['id'];
    }
    $plugin_id = $field_handler->getPluginId();

    // AddressFields, taxonomy terms and custom text fields,
    // views and webform fields are compared in rendered format.
    $compare_rendered = in_array($plugin_id, [
      'addressfield',
      'taxonomy_term_reference',
      'custom',
      'view',
      'webform_submission_field_numeric',
      'webform_submission_field',
    ]);

    // Get the cells from rendered fields.
    if ($compare_rendered) {
      // Special handling of "Webform submission data".
      if ($plugin_id === 'webform_submission_field_numeric' || $plugin_id === 'webform_submission_field') {
        $field_name = $field_handler->definition['webform_submission_field'];
        $data1 = $field_handler->view->result[$row1->num]->_entity->getData();
        $cell1 = $data1[$field_name];
        $data2 = $field_handler->view->result[$row2->num]->_entity->getData();
        $cell2 = $data2[$field_name];
      }
      else {
        $cell1 = trim((string) strip_tags($this->getField($row1->num, $field_name)));
        $cell2 = trim((string) strip_tags($this->getField($row2->num, $field_name)));
      }
    }
    // Get the cells from the raw fields.
    else {
      $cell1 = $this->getFieldValue($row1->num, $field_name);
      $cell2 = $this->getFieldValue($row2->num, $field_name);
    }

    if ((double) $cell1 == (double) $cell2) {
      // If both cells cast to zero, then compare alphabetically.
      $compare = ($cell1 == $cell2) ? 0 : ($cell1 < $cell2 ? -1 : 1);
    }
    else {
      // Compare numerically, i.e. "20 km" comes after "9.5 km".
      // The double cast causes a read up to the first non-number related char.
      $compare = (double) $cell1 < (double) $cell2 ? -1 : 1;
    }
    return ($this->order == 'asc') ? $compare : -$compare;
  }

  /**
   * Set correct couter field order after sorting.
   */
  private function fixCounterFields() {
    // Get the list of counter fields
    $fields = [];
    foreach ($this->view->field as $field_name => $properties) {
      if ($properties->pluginId == 'counter') {
        $fields[$field_name] = $properties->options['counter_start'];
      }
    }
    // If we use a pager - do not reset the counter on each page.
    $counter_offset = 0;
    if ($this->view->usePager()) {
      $page_items = $this->view->pager->getItemsPerPage();
      $current_page = $this->view->pager->current_page;
      $counter_offset = $current_page * $page_items;
    }
    // Update fields and use the offset of previous pages.
    foreach ($fields as $field_name => $start) {
      foreach ($this->view->result as $key => $value) {
        $this->rendered_fields[$key][$field_name] = $start++ + $counter_offset;
      }
    }
  }
}
