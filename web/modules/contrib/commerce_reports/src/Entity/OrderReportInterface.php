<?php

namespace Drupal\commerce_reports\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for order reports.
 */
interface OrderReportInterface extends ContentEntityInterface {

  /**
   * Gets the order ID.
   *
   * @return int
   *   The order ID.
   */
  public function getOrderId();

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order entity.
   */
  public function getOrder();

  /**
   * Gets the order report creation timestamp.
   *
   * This is when the order transitions from a draft to a non-draft state,
   * allowing for the report to be generated because it became immutable.
   *
   * @return int
   *   Creation timestamp of the order report.
   */
  public function getCreatedTime();

}
