<?php

namespace Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_multi_payment\Plugin\Commerce\PaymentGateway\MultiplePaymentGatewayBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_multi_payment_example_giftcard",
 *   label = "Example: Gift Card",
 *   display_label = "Example: Gift Card",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   }
 * )
 */
class GiftCard extends MultiplePaymentGatewayBase implements GiftCardPaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    if ($payment->getAmount()->greaterThan($this->getBalance($payment->getRemoteId()))) {
      throw new HardDeclineException("Gift card balance is less than requested payment amount.");
    }

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    // Normally, you'd do something to void the transaction here.
    
    $payment->setState('authorization_voided');
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
    $payment->setRemoteId($staged_payment->getData('remote_id'));
    $this->createPayment($payment, FALSE);
    $staged_payment->setPayment($payment);
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentVoidPayment(StagedPaymentInterface $staged_payment) {
    if ($staged_payment->getState() == StagedPaymentInterface::STATE_AUTHORIZATION && !empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'authorization') {
      $this->voidPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION_VOIDED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentExpirePayment(StagedPaymentInterface $staged_payment) {
    if ($staged_payment->getState() == StagedPaymentInterface::STATE_AUTHORIZATION && !empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'authorization') {
      $this->voidPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION_EXPIRED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentCapturePayment(StagedPaymentInterface $staged_payment) {
    $this->capturePayment($staged_payment->getPayment());
    $staged_payment->setState(StagedPaymentInterface::STATE_COMPLETED);
    $staged_payment->save();
  }
  
  /**
   * @inheritDoc
   */
  public function multiPaymentAdjustmentLabel(StagedPaymentInterface $staged_payment) {
    return $this->t('Gift card: @gift_card_number', ['@gift_card_number' => $staged_payment->getData('remote_id')]);
  }


  /**
   * @inheritDoc
   */
  public function multiPaymentBuildForm(array $payment_form, FormStateInterface $form_state, array &$complete_form, OrderInterface $order) {
    
        $payment_form['form'] = [
          '#type' => 'commerce_multi_payment_example_giftcard_form',
          '#order_id' => $order->id(),
          '#payment_gateway_id' => $payment_form['#payment_gateway_id'],
        ] + $payment_form;
      
        return $payment_form;
  }

  /**
   * @inheritDoc
   */
  public function getBalance($card_number) {
    // All gift cards with a 1 in them are valid.
    if (!strstr($card_number, '1')) {
      throw new HardDeclineException(t('Gift card @number has been declined.', ['@number' => $card_number]));
    }
    // All valid gift cards have 500 USD balance
    return new Price(500, 'USD');
  }


}
