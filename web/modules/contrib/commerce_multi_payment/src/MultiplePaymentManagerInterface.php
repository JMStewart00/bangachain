<?php

namespace Drupal\commerce_multi_payment;


use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

interface MultiplePaymentManagerInterface {

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface
   */
  public function getMultiPaymentGateways(OrderInterface $order);

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $pending_payment
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   */
  public function applyPendingPayment(PaymentInterface $pending_payment);

  /**
   * @param string $payment_gateway_id
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   */
  public function loadPaymentGateway($payment_gateway_id);

  /**
   * @param int $payment_gateway_id
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface
   */
  public function loadPaymentGatewayPlugin($payment_gateway_id);


  /**
   * @param int $staged_payment_id
   *
   * @return \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface|null
   */
  public function loadStagedPayment($staged_payment_id);

  /**
   * @param int $order_id
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   */
  public function loadOrder($order_id);

  /**
   * @param array $values
   * @param bool $save
   *
   * @return \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface
   */
  public function createStagedPayment(array $values, $save = FALSE);

  /**
   * Get an adjusted amount that prevents staged payments from creating negative order totals.
   *
   * @param \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment
   *
   * @return \Drupal\commerce_price\Price|null
   */
  public function getAdjustedPaymentAmount(StagedPaymentInterface $staged_payment);

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param string|null $for_payment_gateway_id
   *
   * @return \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface[]
   */
  public function getStagedPaymentsFromOrder(OrderInterface $order, $for_payment_gateway_id = NULL);
  
}
