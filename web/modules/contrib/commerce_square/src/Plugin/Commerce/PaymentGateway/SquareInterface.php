<?php

namespace Drupal\commerce_square\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Square payment gateway.
 */
interface SquareInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Gets a configured API client.
   *
   * @return \SquareConnect\ApiClient
   *   The API client.
   *
   * @deprecated Use \Drupal\commerce_square\Connect::getClient
   */
  public function getApiClient();

}
