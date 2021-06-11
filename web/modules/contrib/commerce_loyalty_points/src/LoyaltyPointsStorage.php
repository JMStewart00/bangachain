<?php

namespace Drupal\commerce_loyalty_points;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the loyalty points storage.
 */
class LoyaltyPointsStorage extends CommerceContentEntityStorage implements LoyaltyPointsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadAndAggregateUserLoyaltyPoints($uid) {
    $total_loyalty_points = 0;
    $all_loyalty_points = $this->database->query(
      'SELECT loyalty_points FROM {commerce_loyalty_points} WHERE uid=:uid',
      [':uid' => $uid]
    )->fetchCol();

    foreach ($all_loyalty_points as $loyalty_point) {
      $total_loyalty_points += $loyalty_point;
    }

    return $total_loyalty_points;
  }

  /**
   * {@inheritdoc}
   */
  public function isEligibleCustomer($uid, $interval) {
    $last_negative_point_on = $this->database->query(
      'SELECT created FROM {commerce_loyalty_points} WHERE uid=:uid AND loyalty_points < 0 ORDER BY created DESC LIMIT 1',
      [':uid' => $uid]
    )->fetchCol();

    if (count($last_negative_point_on) > 0) {
      $now = time();
      $current_week = date('W', $now);
      $current_month = date('n', $now);
      $current_year = date('Y', $now);

      $timestamp = $last_negative_point_on[0];
      $last_negative_point_week = date('W', $timestamp);
      $last_negative_point_month = date('n', $timestamp);
      $last_negative_point_year = date('Y', $timestamp);

      // Coupon redemption only valid for current calendar year.
      if ($current_year == $last_negative_point_year) {
        switch ($interval) {
          case 'week':
            return ($current_week - $last_negative_point_week) > 0;

            break;
          case 'month':
            return ($current_month - $last_negative_point_month) > 0;

            break;
          case 'quarter':
            return ($current_month - $last_negative_point_month) > 3;

            break;
          case 'six_months':
            return ($current_month - $last_negative_point_month) > 6;

            break;
          case 'year':
            return ($current_year - $last_negative_point_year) > 0;

            break;
          case 'no_restriction':
            return TRUE;

            break;
        }
      }
    }

    return TRUE;
  }

}
