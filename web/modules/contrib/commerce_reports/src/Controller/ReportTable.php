<?php

namespace Drupal\commerce_reports\Controller;

use Drupal\commerce_reports\ReportQueryBuilder;
use Drupal\commerce_reports\ReportTypeManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Table report controller.
 */
class ReportTable extends ControllerBase {

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * The report query builder.
   *
   * @var \Drupal\commerce_reports\ReportQueryBuilder
   */
  protected $reportQueryBuilder;

  /**
   * Creates a new ReportTable object.
   *
   * @param \Drupal\commerce_reports\ReportTypeManager $report_type_manager
   *   The report type manager.
   * @param \Drupal\commerce_reports\ReportQueryBuilder $report_query_builder
   *   The report query builder.
   */
  public function __construct(ReportTypeManager $report_type_manager, ReportQueryBuilder $report_query_builder) {
    $this->reportTypeManager = $report_type_manager;
    $this->reportQueryBuilder = $report_query_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_report_type'),
      $container->get('commerce_reports.query_builder')
    );
  }

  /**
   * Views a report.
   *
   * @param string $report_type_id
   *   The report type plugin ID.
   * @param string $type
   *   The report type. valid values are week, day, or month fallback.
   *
   * @return array
   *   The render array.
   */
  public function viewReport($report_type_id, $type = 'month') {
    if ($type == 'week') {
      $date_format = 'W - Y';
    }
    elseif ($type == 'day') {
      $date_format = 'j F Y';
    }
    else {
      $date_format = 'F Y';
    }

    if (!$this->reportTypeManager->hasDefinition($report_type_id)) {
      throw new \InvalidArgumentException("Invalid report type ID for $report_type_id");
    }
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type */
    $report_type = $this->reportTypeManager->createInstance($report_type_id);
    $query = $this->reportQueryBuilder->getQuery($report_type, $date_format);
    $results = $query->execute();

    return $report_type->buildReportTable($results);
  }

}
