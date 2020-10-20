<?php

namespace Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsProcessingOwnPaymentsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the example_onsite payment gateway.
 *
 * The OnsitePaymentGatewayInterface is the base interface which all on-site
 * gateways implement. The other interfaces signal which additional capabilities
 * the gateway has. The gateway plugin is free to expose additional methods,
 * which would be defined below.
 */
interface GiftCardPaymentGatewayInterface extends MultiplePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param bool $capture
   *   Whether the created payment should be captured (VS authorized only).
   *   Allowed to be FALSE only if the plugin supports authorizations.
   *
   * @throws \InvalidArgumentException
   *   If $capture is FALSE but the plugin does not support authorizations.
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE);

  /**
   * @param string $card_number
   *
   * @return \Drupal\commerce_price\Price
   *   the balance on the gift card
   * 
   * @throws \Drupal\commerce_payment\Exception\DeclineException
   */
  public function getBalance($card_number);
  
}
