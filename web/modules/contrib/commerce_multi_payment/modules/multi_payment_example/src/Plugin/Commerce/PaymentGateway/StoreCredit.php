<?php

namespace Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_multi_payment\Plugin\Commerce\PaymentGateway\MultiplePaymentGatewayBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_multi_payment_example_storecredit",
 *   label = "Example: Store Credit",
 *   display_label = "Example: Store Credit",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   }
 * )
 */
class StoreCredit extends MultiplePaymentGatewayBase implements StoreCreditPaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    if ($payment->getAmount()->greaterThan($this->getBalance($payment->getOrder()->getCustomerId()))) {
      throw new HardDeclineException("Store credit balance is less than requested payment amount.");
    }
    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->save();
  }


  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * @inheritDoc
   */
  public function getDisplayLabel() {
    $label = parent::getDisplayLabel();
    
    return $label;
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentDisplayLabel() {
    return parent::getDisplayLabel();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentAuthorizePayment(StagedPaymentInterface $staged_payment, PaymentInterface $payment) {
      // This example payment gateway does not support authorization, so we will capture and refund.
      try {
        $this->createPayment($payment, TRUE);
      }
      catch (DeclineException $e) {
        $staged_payment->setStatus(FALSE);
        $staged_payment->save();
        $order = $staged_payment->getOrder();
        $order->save();
        
        throw $e;
      }
      $staged_payment->setPayment($payment);
      $staged_payment->setState(StagedPaymentInterface::STATE_COMPLETED);
      $staged_payment->save();
    
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentVoidPayment(StagedPaymentInterface $staged_payment) {
    if (!empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'completed') {
      $this->refundPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_REFUNDED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentExpirePayment(StagedPaymentInterface $staged_payment) {
    if (!empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'completed') {
      $this->refundPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION_EXPIRED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentCapturePayment(StagedPaymentInterface $staged_payment) {
    // There is nothing to do here because we captured in the authorize step.
  }
  

  /**
   * @inheritDoc
   */
  public function multiPaymentAdjustmentLabel(StagedPaymentInterface $staged_payment) {
    return $this->t('Store credit');
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentBuildForm(array $payment_form, FormStateInterface $form_state, array &$complete_form, OrderInterface $order) {
    try {
      $balance = $this->getBalance($order->getCustomerId());
      $payment_form['form'] = [
          '#type' => 'commerce_multi_payment_example_storecredit_form',
          '#order_id' => $order->id(),
          '#payment_gateway_id' => $payment_form['#payment_gateway_id'],
          '#balance' => $balance,
        ] + $payment_form;
    }
    catch (DeclineException $e) {
      // if there is no balance, we should not show the form.
      return [];
    }
    return $payment_form;
  }

  /**
   * @inheritDoc
   */
  public function getBalance($uid) {
    // Store credit exists for users with "credit" in their username.
    $account = User::load($uid);
    if (strstr( $account->getAccountName(), 'credit')) {
      return new Price(300, 'USD');
    }

    throw new HardDeclineException(t('No store credit for this user'));
  }


}
