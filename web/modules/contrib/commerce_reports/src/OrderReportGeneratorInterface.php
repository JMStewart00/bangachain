<?php

namespace Drupal\commerce_reports;

/**
 * Generates order reports.
 */
interface OrderReportGeneratorInterface {

  /**
   * Generates order reports for the given order IDs.
   *
   * New order reports are created for all orders that have been placed,
   * regardless of whether order reports already exist for the orders.
   * Specify a report type plugin id to generate reports for a single
   * report type; otherwise, reports for all types will be generated.
   *
   * @param array $order_ids
   *   An array of order IDs.
   * @param string $plugin_id
   *   (optional) The report type plugin id to be used to generate reports.
   *
   * @return int
   *   The number of orders for which reports were generated.
   */
  public function generateReports(array $order_ids, $plugin_id = NULL);

  /**
   * Refreshes order reports for the given order IDs.
   *
   * In addition to generating new order reports for orders that have
   * not yet been processed, existing order reports are replaced with
   * new order reports, using the orders' current data.
   * Specify a report type plugin id to generate reports for a single
   * report type; otherwise, reports for all types will be generated.
   *
   * @param array $order_ids
   *   An array of order IDs.
   * @param string $plugin_id
   *   (optional) The report type plugin id to be used to generate reports.
   *
   * @return int
   *   The number of orders for which reports were generated.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function refreshReports(array $order_ids, $plugin_id = NULL);

}
