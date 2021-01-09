<?php

namespace Drupal\commerce_reports;

use Drupal\commerce\CommerceEntityViewsData;
use Drupal\Core\Field\FieldDefinitionInterface;

class OrderReportViewsData extends CommerceEntityViewsData {

  /**
   * Corrects the views data for commerce_price base fields.
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForCreated($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    $views_field['field']['id'] = 'commerce_reports_report_date_field';
    $views_field['sort']['id'] = 'commerce_reports_date';
  }

  /**
   * Corrects the views data for commerce_price base fields.
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForChanged($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    $views_field['field']['id'] = 'commerce_reports_report_date_field';
    $views_field['sort']['id'] = 'commerce_reports_date';
  }

}
