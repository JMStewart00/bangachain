<?php

namespace Drupal\commerce_multi_payment\Entity;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Staged payment entities.
 *
 * @ingroup commerce_multi_payment
 */
interface StagedPaymentInterface extends ContentEntityInterface  {
  
  const STATE_NEW = 'new';
  const STATE_AUTHORIZATION = 'authorization';
  const STATE_AUTHORIZATION_VOIDED = 'authorization_voided';
  const STATE_AUTHORIZATION_EXPIRED = 'authorization_expired';
  const STATE_COMPLETED = 'completed';
  const STATE_REFUNDED = 'refunded';

  /**
   * Gets the payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   *   The payment gateway entity, or null if unknown.
   */
  public function getPaymentGateway();

  /**
   * Gets the payment gateway ID.
   *
   * @return int|null
   *   The payment gateway ID, or null if unknown.
   */
  public function getPaymentGatewayId();

  /**
   * Gets an order data value with the given key.
   *
   * Used to store temporary data during order processing (i.e. checkout).
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets an order data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * Gets the stage payment state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The payment state.
   */
  public function getState();

  /**
   * Sets the staged payment state.
   *
   * @param string $state_id
   *   The new state ID.
   *
   * @return $this
   */
  public function setState($state_id);

  /**
   * Gets the parent order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order entity, or null.
   */
  public function getOrder();

  /**
   * Gets the parent order ID.
   *
   * @return int|null
   *   The order ID, or null.
   */
  public function getOrderId();


  /**
   * Gets the real commerce payment, if any.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface|null
   *   The payment ID, or null.
   */
  public function getPayment();

  /**
   * Gets the real commerce payment, if any.
   *
   * @return int|null
   *   The payment ID, or null.
   */
  public function getPaymentId();

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *
   * @return static
   */
  public function setPayment(PaymentInterface $payment);

  /**
   * @param int $payment_id
   *
   * @return static
   */
  public function setPaymentId($payment_id);

  /**
   * @return static
   */
  public function emptyPayment();

  /**
   * Gets the payment amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The payment amount, or NULL.
   */
  public function getAmount();

  /**
   * Sets the payment amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The payment amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount);

  /**
   * Gets whether the payment has expired.
   *
   * @return bool
   *   TRUE if the payment has expired, FALSE otherwise.
   */
  public function isExpired();

  /**
   * Gets the payment expiration timestamp.
   *
   * Marks the time after which the payment can no longer be completed.
   *
   * @return int
   *   The payment expiration timestamp.
   */
  public function getExpiresTime();

  /**
   * Sets the payment expiration timestamp.
   *
   * @param int $timestamp
   *   The payment expiration timestamp.
   *
   * @return $this
   */
  public function setExpiresTime($timestamp);

  /**
   * Gets whether the payment is active.
   *
   * @return bool
   *   TRUE if the payment is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Sets the payment active status.
   *
   * @param bool $status
   *
   * @return $this
   */
  public function setStatus($status);

}
