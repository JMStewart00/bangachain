<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;

interface MultiplePaymentGatewayInterface extends PaymentGatewayInterface, SupportsMultiplePaymentsInterface {

}
