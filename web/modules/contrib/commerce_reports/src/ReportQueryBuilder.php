<?php

namespace Drupal\commerce_reports;

use Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an aggregate query builder for order reports.
 *
 * @todo Consider providing query factor + aggregate override for our entity.
 */
class ReportQueryBuilder {

  protected $storage;

  /**
   * ReportQueryBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_order_report');
  }

  /**
   * Builds the report query to be executed.
   *
   * @param \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type
   *   The report type.
   * @param string $date_format
   *   The date format.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The aggregate query.
   */
  public function getQuery(ReportTypeInterface $report_type, $date_format = 'F Y') {
    $query = $this->storage->getAggregateQuery();

    // Always filter by the report type.
    $query->condition('type', $report_type->getPluginId());

    // Order ID should always have a count.
    $query->aggregate('order_id', 'COUNT');

    // Tag so we can alter.
    $query->addTag('commerce_reports');

    // Information to use in our alter.
    $query->addMetaData('report_date_format', $date_format);
    $query->addMetaData('report_type_id', $report_type->getPluginId());

    // Let the report type plugin build the query.
    $report_type->buildQuery($query);

    return $query;
  }

  /**
   * Alters the report query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   The alterable query.
   */
  public function alterQuery(AlterableInterface $query) {
    if ($query instanceof SelectInterface) {
      $report_date_format = $query->getMetaData('report_date_format');
      if (empty($report_date_format)) {
        $report_date_format = 'F Y';
      }

      if (Database::getConnection()->databaseType() == 'mysql') {
        $field = 'FROM_UNIXTIME(base_table.created)';
      }
      else {
        $field = 'base_table.created';
      }
      $expression = $this->getDateFormat($field, $report_date_format);
      $query->addExpression($expression, 'formatted_date');
      $query->groupBy('formatted_date');
      $query->orderBy('formatted_date', 'DESC');
    }
  }

  /**
   * Creates cross-database date formatting.
   *
   * @param string $field
   *   An appropriate query expression pointing to the date field.
   * @param string $format
   *   A format string for the result, like 'Y-m-d H:i:s'.
   * @param bool $string_date
   *   For certain databases, date format functions vary depending on string or
   *   numeric storage.
   *
   * @return string
   *   A string representing the field formatted as a date in the format
   *   specified by $format.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::getDateFormat
   *
   * @note This has only been tested on MySQL + SQLite in this module.
   */
  protected function getDateFormat($field, $format, $string_date = FALSE) {
    $db_type = Database::getConnection()->databaseType();
    switch ($db_type) {
      case 'mysql':
        $replace = [
          'Y' => '%Y',
          'y' => '%y',
          'M' => '%b',
          'm' => '%m',
          'n' => '%c',
          'F' => '%M',
          'D' => '%a',
          'd' => '%d',
          'l' => '%W',
          'j' => '%e',
          'W' => '%v',
          'H' => '%H',
          'h' => '%h',
          'i' => '%i',
          's' => '%s',
          'A' => '%p',
        ];
        $format = strtr($format, $replace);
        return "DATE_FORMAT($field, '$format')";

      case 'pgsql':
        $replace = [
          'Y' => 'YYYY',
          'y' => 'YY',
          'M' => 'Mon',
          'm' => 'MM',
          // No format for Numeric representation of a month, without leading
          // zeros.
          'n' => 'MM',
          'F' => 'Month',
          'D' => 'Dy',
          'd' => 'DD',
          'l' => 'Day',
          // No format for Day of the month without leading zeros.
          'j' => 'DD',
          'W' => 'IW',
          'H' => 'HH24',
          'h' => 'HH12',
          'i' => 'MI',
          's' => 'SS',
          'A' => 'AM',
        ];
        $format = strtr($format, $replace);
        if (!$string_date) {
          return "TO_CHAR($field, '$format')";
        }
        // In order to allow for partials (eg, only the year), transform to a
        // date, back to a string again.
        return "TO_CHAR(TO_TIMESTAMP($field, 'YYYY-MM-DD HH24:MI:SS'), '$format')";

      case 'sqlite':
        $replace = [
          'Y' => '%Y',
          // No format for 2 digit year number.
          'y' => '%Y',
          // No format for 3 letter month name.
          'M' => '%m',
          'm' => '%m',
          // No format for month number without leading zeros.
          'n' => '%m',
          // No format for full month name.
          'F' => '%m',
          // No format for 3 letter day name.
          'D' => '%d',
          'd' => '%d',
          // No format for full day name.
          'l' => '%d',
          // No format for day of month number without leading zeros.
          'j' => '%d',
          'W' => '%W',
          'H' => '%H',
          // No format for 12 hour hour with leading zeros.
          'h' => '%H',
          'i' => '%M',
          's' => '%S',
          // No format for AM/PM.
          'A' => '',
        ];
        $format = strtr($format, $replace);

        // Don't use the 'unixepoch' flag for string date comparisons.
        $unixepoch = $string_date ? '' : ", 'unixepoch'";

        // SQLite does not have a ISO week substitution string, so it needs
        // special handling.
        // @see http://wikipedia.org/wiki/ISO_week_date#Calculation
        // @see http://stackoverflow.com/a/15511864/1499564
        if ($format === '%W') {
          $expression = "((strftime('%j', date(strftime('%Y-%m-%d', $field" . $unixepoch . "), '-3 days', 'weekday 4')) - 1) / 7 + 1)";
        }
        else {
          $expression = "strftime('$format', $field" . $unixepoch . ")";
        }
        // The expression yields a string, but the comparison value is an
        // integer in case the comparison value is a float, integer, or numeric.
        // All of the above SQLite format tokens only produce integers. However,
        // the given $format may contain 'Y-m-d', which results in a string.
        // @see \Drupal\Core\Database\Driver\sqlite\Connection::expandArguments()
        // @see http://www.sqlite.org/lang_datefunc.html
        // @see http://www.sqlite.org/lang_expr.html#castexpr
        if (preg_match('/^(?:%\w)+$/', $format)) {
          $expression = "CAST($expression AS NUMERIC)";
        }
        return $expression;
    }
  }

}
