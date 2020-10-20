<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MultiplePaymentManager implements MultiplePaymentManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * MultiplePaymentManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiPaymentGateways(OrderInterface $order) {
    return array_filter($this->loadGatewaysForOrder($order), function($gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
      $payment_gateway_plugin = $gateway->getPlugin();
      $configuration = $payment_gateway_plugin->getConfiguration();
      return ($payment_gateway_plugin instanceof SupportsMultiplePaymentsInterface);
    });
  }

  /**
   * Submit callback for the "Apply coupon" button.
   */
  public function applyPendingPayment(PaymentInterface $pending_payment) {
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($pending_payment->getOrderId());
    $append = $pending_payment->isNew();
    
    $amount = $pending_payment->getAmount();
    
    if (!is_null($amount)) {
      // Prevent the payment amount from being more than the total order price, with existing adjustments.
      $order_total_with_adjustment = $order->getTotalPrice();
      if (!$pending_payment->isNew()) {
        foreach ($order->getAdjustments() as $adjustment) {
          if ($adjustment->getType() == 'multi_payment' && $adjustment->getSourceId() == $pending_payment->id()) {
            // Subtract because adjustment is negative
            $order_total_with_adjustment = $order_total_with_adjustment->subtract($adjustment->getAmount());
          }
        }
      }
      if ($order_total_with_adjustment->lessThan($amount)) {
        // Trying to pay too much, change the amount.
        $amount = $order_total_with_adjustment;
      }

      $pending_payment->setAmount($amount);
    }
    
    $pending_payment->save();

    if ($append) {
      $order->get('multi_payment')->appendItem($pending_payment);
    }
    $order->save();
    return $pending_payment;
  }

  /**
   * {@inheritdoc}
   */
  public function loadPaymentGateway($payment_gateway_id) {
    return $this->entityTypeManager->getStorage('commerce_payment_gateway')->load($payment_gateway_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadPaymentGatewayPlugin($payment_gateway_id) {
    return $this->loadPaymentGateway($payment_gateway_id)->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadGatewaysForOrder(OrderInterface $order) {
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways */
    $payment_gateways = $payment_gateway_storage->loadMultiple();
    // Allow the list of payment gateways to be filtered via code.
    $order->is_multi_payment_pane = TRUE;
    $event = new FilterPaymentGatewaysEvent($payment_gateways, $order);
    $this->eventDispatcher->dispatch(PaymentEvents::FILTER_PAYMENT_GATEWAYS, $event);
    $payment_gateways = $event->getPaymentGateways();

    // Evaluate conditions for the remaining ones.
    foreach ($payment_gateways as $payment_gateway_id => $payment_gateway) {
      if (!$payment_gateway->applies($order)) {
        unset($payment_gateways[$payment_gateway_id]);
      }
    }
    $entity_type = $this->entityTypeManager->getStorage('commerce_payment_gateway')->getEntityType();
    uasort($payment_gateways, [$entity_type->getClass(), 'sort']);

    $order->is_multi_payment_pane = FALSE;

    return $payment_gateways;
  }

  /**
   * {@inheritdoc}
   */
  public function loadStagedPayment($staged_payment_id) {
    return $this->entityTypeManager->getStorage('commerce_staged_multi_payment')->load($staged_payment_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadOrder($order_id) {
    return $this->entityTypeManager->getStorage('commerce_order')->load($order_id);
  }

  /**
   * {@inheritdoc}
   */
  public function createStagedPayment(array $values, $save = FALSE) {
    $staged_payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_staged_multi_payment');
    $staged_payment = $staged_payment_storage->create($values);
    if ($save) {
      $staged_payment->save();
    }
    return $staged_payment;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedPaymentAmount(StagedPaymentInterface $staged_payment) {
    $amount = $staged_payment->getAmount();
    // Prevent the payment amount from being more than the total order price, with existing adjustments.
    $order_total_with_adjustment = $staged_payment->getOrder()->getTotalPrice();
    if (!$staged_payment->isNew()) {
      foreach ($staged_payment->getOrder()->getAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'staged_multi_payment' && $adjustment->getSourceId() == $staged_payment->id()) {
          // Subtract because adjustment is negative
          $order_total_with_adjustment = $order_total_with_adjustment->subtract($adjustment->getAmount());
        }
      }
    }
    if ($order_total_with_adjustment->lessThan($amount)) {
      // Trying to pay too much, change the amount.
      $amount = $order_total_with_adjustment;
    }
    return $amount;
  }

  /**
   * {@inheritdoc}
   */
  public function getStagedPaymentsFromOrder(OrderInterface $order, $for_payment_gateway_id = NULL) {
    $staged_payments = [];
    if (!$order->get('staged_multi_payment')->isEmpty()) {
      foreach ($order->get('staged_multi_payment')->referencedEntities() as $staged_payment) {
        /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
        if (empty($for_payment_gateway_id) || $staged_payment->getPaymentGatewayId() === $for_payment_gateway_id) {
          $staged_payments[$staged_payment->id()] = $staged_payment;
        }
      }
    }
    return $staged_payments;
  }

}
