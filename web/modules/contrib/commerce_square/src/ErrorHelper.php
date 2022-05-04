<?php

namespace Drupal\commerce_square;

use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Square\Exceptions\ApiException;
use Drupal\Component\Serialization\Json;

/**
 * Translates Square exceptions and errors into Commerce exceptions.
 *
 * @see https://docs.connect.squareup.com/api/connect/v2/#type-errorcategory
 * @see https://docs.connect.squareup.com/api/connect/v2/#type-errorcode
 */
class ErrorHelper {

  /**
   * Translates Square exceptions into Commerce exceptions.
   *
   * @param \Square\Exceptions\ApiException $exception
   *   The Square exception.
   *
   * @return \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function convertException(ApiException $exception) {
    $errors = Json::decode($exception->getMessage());
    $error = isset($errors['errors']) ? reset($errors['errors']) : [];

    if (!empty($error)) {
      switch ($error['category']) {
        case 'PAYMENT_METHOD_ERROR':
          return new SoftDeclineException($error['detail']);

        case 'REFUND_ERROR':
          return new HardDeclineException($error['detail']);

        default:
          // All other error categories are API request related.
          return new InvalidResponseException($exception->getMessage(), $exception->getCode(), $exception);
      }
    }
    else {
      return new InvalidResponseException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

}
