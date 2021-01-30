<?php

namespace Drupal\commerce_pos;

use Drupal\Core\Database\Connection;

/**
 * Cashier Users Class.
 */
class CashierUsers {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a new CashierUsers Object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Pass in the connection via dependency injection, standard for fields.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

}
