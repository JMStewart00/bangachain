<?php

namespace Drupal\commerce_multi_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsProcessingOwnPaymentsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Masterminds\HTML5\Exception;

/**
 * Provides the payment process pane.
 *
 */
class MultiplePaymentProcess extends PaymentProcess {

  /**
   * @var bool
   */
  protected $redirectToPrevious = FALSE;
  
  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $staged_payments = [];
    
    if (!$this->order->get('staged_multi_payment')->isEmpty()) {
      // Convert payments to real payments.
      /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface[] $staged_payments */
      $staged_payments = $this->order->get('staged_multi_payment')->referencedEntities();
      
      foreach ($staged_payments as $staged_payment) {
        if (!$staged_payment->isActive()) {
          continue;
        }
        /** @var \Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $staged_payment->getPaymentGateway()->getPlugin();

        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'new',
          'amount' => $staged_payment->getAmount(),
          'payment_gateway' => $staged_payment->getPaymentGateway()->id(),
          'order_id' => $this->order->id(),
        ]);
        
        try {
          $payment_gateway_plugin->multiPaymentAuthorizePayment($staged_payment, $payment);
        }
        catch (DeclineException $e) {
          $message = $this->t('We encountered an error processing your payment method. Please verify your details and try again.');
          drupal_set_message($message, 'error');
          break;
        }
        catch (PaymentGatewayException $e) {
          \Drupal::logger('commerce_payment')->error($e->getMessage());
          $message = $this->t('We encountered an unexpected error processing your payment method. Please try again later.');
          drupal_set_message($message, 'error');
          break;
        }
      }
      
      // Check that all staged payments completed.
      if (!$this->checkStagedPaymentsState($staged_payments, [StagedPaymentInterface::STATE_AUTHORIZATION, StagedPaymentInterface::STATE_COMPLETED])) {
        // Reverse all the staged payments
        $this->reverseStagedPayments($staged_payments);
        $this->redirectToPreviousStep(TRUE);
      }
    }

    try {
      if (!$this->order->getTotalPrice()->isZero()) {
        // Don't try to process the payments here if the order total is 0.
        // There will be no payment gateway.
        $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);
      }
    }
    catch (\Error $e) {
      // If crash from main pane form processing, reverse payments and go back.
      $this->reverseStagedPayments($staged_payments);
      $this->redirectToPreviousStep(TRUE);
    }
    catch (\Exception $e) {
      // If crash from main pane form processing, reverse payments and go back.
      $this->reverseStagedPayments($staged_payments);
      $this->redirectToPreviousStep(TRUE);
    }

    if ($this->redirectToPrevious) {
      // The primary payment method must have failed.
      $this->reverseStagedPayments($staged_payments);
      $this->redirectToPreviousStep(TRUE);
    } 
    else {
      // Primary payment successfully authorized or captured, staged payments are authorized or completed.
      // Capture the staged payments that are currently authorized.
      try {
        foreach ($staged_payments as $staged_payment) {
          /** @var \Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface $payment_gateway_plugin */
          $payment_gateway_plugin = $staged_payment->getPaymentGateway()->getPlugin();
          $payment_gateway_plugin->multiPaymentCapturePayment($staged_payment);
        }
      }
      catch (Exception $e) {
        // Well crap. We've charged their credit card, authorized all their staged payments, and yet still something
        // failed right here at the end. Let's try to clean up the mess, but may not always work.
        
        
        // First, reverse all the staged payments. This should work.
        $this->reverseStagedPayments($staged_payments);
        
        // Next, let's see if we can reverse the primary payment. Loop through the payments and 
        // try to refund or void all payments that are authorized or completed
        $payments_all_reversed = $this->reverseRealPayments();
        
        if (!$payments_all_reversed) {
          // Print out errors for failures
          /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
          $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
          $payments = $payment_storage->loadMultipleByOrder($this->order);
          foreach ($payments as $payment) {
            if (!in_array($payment->getState(), ['authorized', 'completed'])) {
              // Did not work :(  Tell the user to contact the store owner.
              $message = $this->t('We encountered an error processing the @label payment. Please contact the store owner and reference order/cart #@order_id.', [
                '@label' => $payment->getPaymentGateway()->label(),
                '@order_id' => $this->order->id(),
              ]);
              drupal_set_message($message, 'error');
            }
          }
          $this->redirectToPreviousStep(TRUE);
        }
      }
      
      // If we got here, all payment gateways were successful. Let's delete
      // the staged payments to get rid of the adjustments and call it a day.
      foreach ($staged_payments as $staged_payment) {
        $staged_payment->delete();
      }
      $this->order->get('staged_multi_payment')->setValue(NULL);
      $this->order->save();
      $next_step_id = $this->checkoutFlow->getNextStepId($this->getStepId());
      $this->redirectToStep($next_step_id, TRUE);
    }
    
    return $pane_form;
    
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // This pane can't be used without the PaymentInformation pane.
    $payment_info_pane = $this->checkoutFlow->getPane('payment_information');
    return $payment_info_pane->isVisible() && $payment_info_pane->getStepId() != '_disabled';
  }

  /**
   * @return bool
   */
  protected function reverseRealPayments() {
    $payments_all_reversed = TRUE;
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($this->order);
    foreach ($payments as $payment) {
      if ($payment->getState() == 'authorized') {
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        if ($payment_gateway_plugin instanceof SupportsAuthorizationsInterface) {
          try {
            $payment_gateway_plugin->voidPayment($payment);
          }
          catch (Exception $e) {
            $payments_all_reversed = FALSE;
          }
        }
      }
      elseif ($payment->getState() == 'completed') {
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        if ($payment_gateway_plugin instanceof SupportsRefundsInterface) {
          try {
            $payment_gateway_plugin->refundPayment($payment);}
          catch (Exception $e) {
            $payments_all_reversed = FALSE;
          }
        }
      }
    }
    return $payments_all_reversed;
  }

  /**
   * @param StagedPaymentInterface[] $staged_payments
   * @param string[] $valid_states
   * 
   * @return TRUE
   */
  protected function checkStagedPaymentsState(array $staged_payments, array $valid_states) {
    foreach ($staged_payments as $staged_payment) {
      if (!in_array($staged_payment->getState(), $valid_states)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param StagedPaymentInterface[] $staged_payments
   */
  protected function reverseStagedPayments(array $staged_payments) {
    foreach ($staged_payments as $staged_payment) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      /** @var \Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway = $staged_payment->getPaymentGateway();
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      try {
        $payment_gateway_plugin->multiPaymentVoidPayment($staged_payment);
      } catch (DeclineException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        $message = $this->t('We encountered an error processing your payment method. Please verify your details and try again.');
        drupal_set_message($message, 'error');
      } catch (PaymentGatewayException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        $message = $this->t('We encountered an unexpected error processing your payment method. Please try again later.');
        drupal_set_message($message, 'error');
      }
    }
  }

  /**
   * Redirects to a previous checkout step on error.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  protected function redirectToPreviousStep($immediately = FALSE) {
    if ($immediately) {
      throw new NeedsRedirectException($this->buildPaymentInformationStepUrl()->toString());
    }
    $this->redirectToPrevious = TRUE;
  }

  /**
   * @param string $next_step_id
   */
  protected function redirectToStep($next_step_id, $immediately = FALSE) {
    if ($immediately) {
      $this->checkoutFlow->redirectToStep($next_step_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'capture' => TRUE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['capture'])) {
      $summary = $this->t('Transaction mode: Authorize and capture');
    }
    else {
      $summary = $this->t('Transaction mode: Authorize only');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['capture'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('This setting is only respected if the chosen payment gateway supports authorizations.'),
      '#options' => [
        TRUE => $this->t('Authorize and capture'),
        FALSE => $this->t('Authorize only (requires manual capture after checkout)'),
      ],
      '#default_value' => (int) $this->configuration['capture'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['capture'] = !empty($values['capture']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function originalBuildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      drupal_set_message($this->t('No payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $payment_storage->create([
      'state' => 'new',
      'amount' => $this->order->getTotalPrice(),
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $this->order->id(),
    ]);
    $next_step_id = $this->checkoutFlow->getNextStepId($this->getStepId());

    if ($payment_gateway_plugin instanceof OnsitePaymentGatewayInterface) {
      try {
        $payment->payment_method = $this->order->payment_method->entity;
        $payment_gateway_plugin->createPayment($payment, $this->configuration['capture']);
        $this->redirectToStep($next_step_id);
      }
      catch (DeclineException $e) {
        $message = $this->t('We encountered an error processing your payment method. Please verify your details and try again.');
        drupal_set_message($message, 'error');
        $this->redirectToPreviousStep();
      }
      catch (PaymentGatewayException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        $message = $this->t('We encountered an unexpected error processing your payment method. Please try again later.');
        drupal_set_message($message, 'error');
        $this->redirectToPreviousStep();
      }
    }
    elseif ($payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      $pane_form['offsite_payment'] = [
        '#type' => 'commerce_payment_gateway_form',
        '#operation' => 'offsite-payment',
        '#default_value' => $payment,
        '#return_url' => $this->buildReturnUrl()->toString(),
        '#cancel_url' => $this->buildCancelUrl()->toString(),
        '#exception_url' => $this->buildPaymentInformationStepUrl()->toString(),
        '#exception_message' => $this->t('We encountered an unexpected error processing your payment. Please try again later.'),
        '#capture' => $this->configuration['capture'],
      ];

      $complete_form['actions']['next']['#value'] = $this->t('Proceed to @gateway', [
        '@gateway' => $payment_gateway_plugin->getDisplayLabel(),
      ]);
      // The 'Go back' link needs to use the cancel URL to ensure that the
      // order is unlocked when the customer is sent to the previous page.
      $complete_form['actions']['next']['#suffix'] = Link::fromTextAndUrl($this->t('Go back'), $this->buildCancelUrl())->toString();
      // Hide the actions by default, they are not needed by gateways that
      // embed iframes or redirect via GET. The offsite-payment form can
      // choose to show them when needed (redirect via POST).
      $complete_form['actions']['#access'] = FALSE;

      return $pane_form;
    }
    elseif ($payment_gateway_plugin instanceof ManualPaymentGatewayInterface) {
      try {
        $payment_gateway_plugin->createPayment($payment);
        $this->redirectToStep($next_step_id);
      }
      catch (PaymentGatewayException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        $message = $this->t('We encountered an unexpected error processing your payment. Please try again later.');
        drupal_set_message($message, 'error');
        $this->redirectToPreviousStep();
      }
    }
    else {
      $this->redirectToStep($next_step_id);
    }
  }

  /**
   * Builds the URL to the "return" page.
   *
   * @return \Drupal\Core\Url
   *   The "return" page URL.
   */
  protected function buildReturnUrl() {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @return \Drupal\Core\Url
   *   The "cancel" page URL.
   */
  protected function buildCancelUrl() {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

  /**
   * Builds the URL to the payment information checkout step.
   *
   * @return \Drupal\Core\Url
   *   The URL to the payment information checkout step.
   */
  protected function buildPaymentInformationStepUrl() {
    return Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $this->checkoutFlow->getPane('payment_information')->getStepId(),
    ], ['absolute' => TRUE]);
  }

}
