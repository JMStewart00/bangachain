<?php

namespace Drupal\commerce_loyalty_points;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for loyalty points storage.
 */
interface LoyaltyPointsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads aggregrated loyalty points of a user.
   *
   * @param int $uid
   *   User ID.
   *
   * @return float
   *   Aggregated loyalty points.
   */
  public function loadAndAggregateUserLoyaltyPoints($uid);

  /**
   * Check if the last used redemption promo code is beyond the set interval.
   *
   * @param int $uid
   *   User ID.
   * @param string $interval
   *   Week, month, year, and so on.
   *
   * @return bool
   *   TRUE if customer is eligible for loyalty points redemption.
   */
  public function isEligibleCustomer($uid, $interval);

}
