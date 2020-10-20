<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Form\FormStateInterface;

interface SupportsMultiplePaymentsInterface {

  /**
   * @return string
   */
  public function multiPaymentDisplayLabel();

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *
   * @return string
   */
  public function multiPaymentAdjustmentLabel(StagedPaymentInterface $staged_payment);

  /**
   * @param \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment
   */
  public function multiPaymentAuthorizePayment(StagedPaymentInterface $staged_payment, PaymentInterface $payment);

  /**
   * @param \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment
   */
  public function multiPaymentVoidPayment(StagedPaymentInterface $staged_payment);

  /**
   * @param \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment
   */
  public function multiPaymentExpirePayment(StagedPaymentInterface $staged_payment);

  /**
   * @param \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment
   */
  public function multiPaymentCapturePayment(StagedPaymentInterface $staged_payment);

  /**
   * @param array $payment_form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   * @param \Drupal\commerce_order\Entity\OrderInterface $order;
   *
   * @return array
   */
  public function multiPaymentBuildForm(array $payment_form, FormStateInterface $form_state, array &$complete_form, OrderInterface $order);

  
}
