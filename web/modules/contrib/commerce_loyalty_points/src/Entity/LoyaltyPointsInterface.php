<?php

namespace Drupal\commerce_loyalty_points\Entity;

/**
 * Interface LoyaltyPointsInterface.
 */
interface LoyaltyPointsInterface {

  /**
   * Loyalty points for an entity.
   *
   * @return float
   *   Loyalty points - positive value to add, negative to deduct.
   */
  public function getLoyaltyPoints();

  /**
   * User ID.
   *
   * @return int
   *   Unique user ID.
   */
  public function getUserId();

  /**
   * User object.
   *
   * @return \Drupal\user\Entity\User
   *   User loaded entity.
   */
  public function getUser();

  /**
   * Reason for adding/deducting loyalty points.
   *
   * @return string
   *   Short description for entity.
   */
  public function getReason();

}
