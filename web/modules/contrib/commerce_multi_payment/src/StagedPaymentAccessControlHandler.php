<?php

namespace Drupal\commerce_multi_payment;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Staged payment entity.
 *
 * @see \Drupal\commerce_multi_payment\Entity\StagedPayment.
 */
class StagedPaymentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published staged payment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit staged payment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete staged payment entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add staged payment entities');
  }

}
