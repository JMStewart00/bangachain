<?php

namespace Drupal\commerce_reports;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Generates order reports for orders.
 */
class OrderReportGenerator implements OrderReportGeneratorInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The order report storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderReportStorage;

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * Constructs a new OrderReportGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_reports\ReportTypeManager $report_type_manager
   *   The order report type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReportTypeManager $report_type_manager) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderReportStorage = $entity_type_manager->getStorage('commerce_order_report');
    $this->reportTypeManager = $report_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReports(array $order_ids, $plugin_id = NULL) {
    $orders = $this->orderStorage->loadMultiple($order_ids);
    $plugin_types = $this->reportTypeManager->getDefinitions();
    $generated = 0;

    // Generate reports for a single report type.
    if ($plugin_id) {
      if (!isset($plugin_types[$plugin_id])) {
        return $generated;
      }
      $plugin_types = [$plugin_types[$plugin_id]];
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    foreach ($orders as $order) {
      // Do not generate order reports for orders that have not been placed.
      if (empty($order->getPlacedTime())) {
        continue;
      }
      foreach ($plugin_types as $plugin_type) {
        /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $instance */
        $instance = $this->reportTypeManager->createInstance($plugin_type['id'], []);
        $instance->generateReports($order);
      }
      $generated++;
    }
    return $generated;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshReports(array $order_ids, $plugin_id = NULL) {
    // Delete any existing reports.
    $query = $this->orderReportStorage->getQuery()
      ->condition('order_id', $order_ids, 'IN');

    if ($plugin_id) {
      $query->condition('type', $plugin_id);
    }
    $order_report_ids = $query->execute();
    $reports = $this->orderReportStorage->loadMultiple($order_report_ids);
    $this->orderReportStorage->delete($reports);

    return $this->generateReports($order_ids, $plugin_id);
  }

}
