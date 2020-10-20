<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for payments.
 */
class PaymentListBuilder extends \Drupal\commerce_payment\PaymentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Payment');
    $header['remote_id'] = $this->t('Remote ID');
    $header['gateway'] = $this->t('Payment Gateway');
    $header['state'] = $this->t('State');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $amount = $entity->getAmount();
    // @todo Refactor the number formatter to work with just a currency code.
    $currency = Currency::load($amount->getCurrencyCode());
    $formatted_amount = $this->numberFormatter->formatCurrency($amount->getNumber(), $currency);
    $refunded_amount = $entity->getRefundedAmount();
    if ($refunded_amount && !$refunded_amount->isZero()) {
      $formatted_amount .= ' Refunded: ' . $this->numberFormatter->formatCurrency($refunded_amount->getNumber(), $currency);
    }

    $row['label'] = $formatted_amount;
    $row['remote_id'] = $entity->getRemoteId() ?: $this->t('N/A');
    $row['gateway'] = $entity->getPaymentGateway()->label();
    $row['state'] = $entity->getState()->getLabel();

    return $row + parent::buildRow($entity);
  }

}
