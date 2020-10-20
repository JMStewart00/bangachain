<?php

namespace Drupal\commerce_multi_payment_example\Element;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_multi_payment\CommercePaneFormElementTrait;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for applying store credit
 *
 * Usage example:
 *
 * @code
 * $form['store_credit'] = [
 *   '#type' => 'commerce_multi_payment_example_storecredit_form',
 *   '#title' => t('Store Credit'),
 *   '#payment_gateway_id' => $payment_form['#payment_gateway_id'],
 *   '#order_id' => $order_id,
 * ];
 * @endcode
 * The element takes the store credit list from $order->get('staged_multi_payment').
 * The order is saved when a store credit is added or removed.
 *
 * @FormElement("commerce_multi_payment_example_storecredit_form")
 */
class StoreCreditForm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#element_ajax' => [],
      // If NULL, the cardinality is unlimited.
      '#cardinality' => 1,
      '#order_id' => NULL,

      '#title' => t('Store Credit'),
      '#description' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the store credit redemption form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #order_id property is empty or invalid.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#order_id'])) {
      throw new \InvalidArgumentException('The commerce_multi_payment_example_storecredit_form element requires the #order_id property.');
    }
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $order = $multi_payment_manager->loadOrder($element['#order_id']);
    if (!$order instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_multi_payment_example_storecredit_form #order_id must be a valid order ID.');
    }


    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';

    /** @var \Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway\StoreCredit $payment_gateway_plugin */
    $payment_gateway_plugin = $multi_payment_manager->loadPaymentGatewayPlugin($element['#payment_gateway_id']);
    
    $staged_payment = NULL;

    $staged_payments = $multi_payment_manager->getStagedPaymentsFromOrder($order, $element['#payment_gateway_id']);
    if (!empty($staged_payments)) {
      $staged_payment = reset($staged_payments);
      try {
        // Remove store credit if it is no longer valid.
        $balance = $payment_gateway_plugin->getBalance($staged_payment->getOrder()->getCustomerId());
        if ($balance->lessThan($staged_payment->getAmount())) {
          throw new HardDeclineException("Store credit balance is less than requested amount.");
        }
      }
      catch (DeclineException $e) {
        $order = $staged_payment->getOrder();
        $staged_payment->delete();
        $order->save();
        $staged_payment = NULL;
      }
    }

    $element = [
        '#tree' => TRUE,
        '#theme' => 'commerce_multi_payment_example_storecredit_form',
        '#prefix' => '<div data-drupal-selector="' . $wrapper_id . '" id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#wrapper_id' => $wrapper_id,
      ] + $element;

    $balance = $element['#balance'];
    $formatted_balance = [
      '#type' => 'inline_template',
      '#template' => '{{ price|commerce_price_format }}',
      '#context' => [
        'price' => $balance,
      ],
    ];
    

    $element['store_credit'] = [
      '#type' => 'details',
      '#open' => !empty($staged_payment),
      '#title' => t('@label: @balance available', [
        '@label' => $payment_gateway_plugin->multiPaymentDisplayLabel(),
        '@balance' => render($formatted_balance),
      ]),
    ];

    if (!empty($staged_payment)) {
      $default_amount = $staged_payment->getAmount();
    }
    else {
      if ($order->getTotalPrice()->compareTo($balance) <= 0) {
        $default_amount = $order->getTotalPrice();
      }
      else {
        $default_amount = $balance;
      }
    }

    $element['#staged_payment_id'] = !empty($staged_payment) ? $staged_payment->id() : NULL;

    $element['store_credit']['amount'] = [
      '#title' => t('Amount to apply to order'),
      '#type' => 'commerce_price',
      '#currency_code' => $balance->getCurrencyCode(),
      '#default_value' => $default_amount->toArray(),
    ];

    $element['store_credit']['apply'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#staged_payment_id' => !empty($staged_payment) ? $staged_payment->id() : NULL,
      '#name' => $element['#payment_gateway_id'] . '_apply_store_credit_payment',
      '#limit_validation_errors' => [
        $element['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyStoreCredit'],
      ],
      '#ajax' => [
        'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
      ],
      // Simplify ajaxRefresh() by having all triggering elements
      // on the same level.
      '#parents' => array_merge($element['#parents'], [$element['#payment_gateway_id'] . '_apply_store_credit_payment']),
    ];

    if (!empty($staged_payment)) {
      $element['store_credit']['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => $element['#payment_gateway_id'] . '_remove_store_credit_payment',
        '#limit_validation_errors' => [
          $element['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'removeStoreCredit'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($element['#parents'], [$element['#payment_gateway_id'] . '_remove_store_credit_payment']),
      ];
    }

    return $element;
  }

  /**
   * Validates the gift card redemption element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('multi_payment_apply', $triggering_element['#parents']) && in_array($element['#payment_gateway_id'], $triggering_element['#parents'])) {

      if ($triggering_element['#name'] == $element['#payment_gateway_id'] . '_apply_store_credit_payment') {
        $amount_parents = array_merge($element['#parents'], [
          'store_credit',
          'amount',
        ]);
        $amount_path = implode('][', $amount_parents);
        $amount_array = $form_state->getValue($amount_parents);
        $amount = new Price($amount_array['number'], $amount_array['currency_code']);


        // Check to see if the gift card has already been added to the order.
        $order_storage = \Drupal::entityTypeManager()
          ->getStorage('commerce_order');
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $order_storage->load($element['#order_id']);

        // Check the store credit balance.
        try {
          $balance = static::getBalance($element['#payment_gateway_id'], $order->getCustomerId());
          $element['#balance'] = $balance;

          if ($balance->lessThan($amount)) {
            $form_state->setErrorByName($amount_path, t('Applied amount is greater than store credit balance.'));
          }

        } catch (DeclineException $e) {
          $form_state->setErrorByName($amount_path, $e->getMessage());
        }
      }
    }

  }

  /**
   * Submit callback for the "Apply store credit" button.
   */
  public static function applyStoreCredit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    
    $triggering_element = $form_state->getTriggeringElement();

    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    $staged_payment_id = $element['#staged_payment_id'];

    $amount_parents = array_merge($element['#parents'], [
      'store_credit',
      'amount',
    ]);
    $amount_array = $form_state->getValue($amount_parents);
    $amount = new Price($amount_array['number'], $amount_array['currency_code']);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $multi_payment_manager->loadOrder($element['#order_id']);

    $append = FALSE;

    if (empty($staged_payment_id)) {
      $staged_payment = $multi_payment_manager->createStagedPayment([
        'order_id' => $element['#order_id'],
        'payment_gateway' => $element['#payment_gateway_id'],
      ]);
      $append = TRUE;
    }
    else {
      $staged_payment = $multi_payment_manager->loadStagedPayment($staged_payment_id);
      $staged_payment->setStatus(TRUE);
    }

    // Prevent the payment amount from being more than the total order price, with existing adjustments.
    $staged_payment->setAmount($amount);
    $amount = $multi_payment_manager->getAdjustedPaymentAmount($staged_payment);

    $staged_payment->setAmount($amount);
    $staged_payment->save();

    if ($append) {
      $order->get('staged_multi_payment')->appendItem($staged_payment);
    }
    $order->save();

    static::setUserInput($form_state, array_merge($parents, [
      'store_credit',
      'amount',
    ]), NULL);

    $form_state->setRebuild();
  }

  /**
   * Remove store credit submit callback.
   */
  public static function removeStoreCredit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);

    $staged_payment = $multi_payment_manager->loadStagedPayment($element['#staged_payment_id']);
    $staged_payment->delete();

    $order = $multi_payment_manager->loadOrder($element['#order_id']);
    $order->save();

    static::setUserInput($form_state, array_merge($parents, [
      'store_credit',
      'amount',
    ]), NULL);
    $form_state->setRebuild();
  }


  /**
   * @param string $gift_card_number
   *
   * @return \Drupal\commerce_price\Price
   *   the balance on the gift card
   */
  protected static function getBalance($payment_gateway_id, $uid) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    /** @var \Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway\StoreCreditPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $multi_payment_manager->loadPaymentGatewayPlugin($payment_gateway_id);
    return $payment_gateway_plugin->getBalance($uid);
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $parents
   * @param mixed|null $value
   */
  public static function setUserInput(FormStateInterface &$form_state, array $parents, $value = NULL) {
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, $parents, $value);
  }

}
