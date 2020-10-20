<?php

namespace Drupal\commerce_multi_payment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

class StagedPaymentAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    return $order->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIf($order->hasField('staged_multi_payment') && !$order->get('staged_multi_payment')->isEmpty()));
  }


}
