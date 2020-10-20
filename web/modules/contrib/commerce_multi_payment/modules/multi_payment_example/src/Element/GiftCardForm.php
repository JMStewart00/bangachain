<?php

namespace Drupal\commerce_multi_payment_example\Element;

use Drupal\bake_store_credit\Entity\StoreCreditInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_multi_payment\CommercePaneFormElementTrait;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for applying gift cards
 *
 * Usage example:
 *
 * @code
 * $form['gift_card'] = [
 *   '#type' => 'commerce_multi_payment_example_giftcard_form',
 *   '#title' => t('Gift Card'),
 *   '#payment_gateway_id' => $payment_form['#payment_gateway_id'],
 *   '#order_id' => $order_id,
 * ];
 * @endcode
 * The element takes the gift card list from
 *   $order->get('staged_multi_payment'). The order is saved when a gift card
 *   is added or removed.
 *
 * @FormElement("commerce_multi_payment_example_giftcard_form")
 */
class GiftCardForm extends FormElement {

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

      '#title' => t('Gift Card'),
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
   * Processes the gift card redemption form.
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
      throw new \InvalidArgumentException('The commerce_multi_payment_example_giftcard_form element requires the #order_id property.');
    }
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $order = $multi_payment_manager->loadOrder($element['#order_id']);
    if (!$order instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_multi_payment_example_giftcard_form #order_id must be a valid order ID.');
    }

    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';

    $staged_payments = $multi_payment_manager->getStagedPaymentsFromOrder($order, $element['#payment_gateway_id']);


    $element = [
        '#tree' => TRUE,
        '#theme' => 'commerce_multi_payment_example_giftcard_form',
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        // Pass the id along to other methods.
        '#wrapper_id' => $wrapper_id,
      ] + $element;


    $payment_gateway_plugin = $multi_payment_manager->loadPaymentGatewayPlugin($element['#payment_gateway_id']);
    
    foreach ($staged_payments as $staged_payment) {
      $balance = $staged_payment->getData('balance');
      $formatted_balance = [
        '#type' => 'inline_template',
        '#template' => '{{ price|commerce_price_format }}',
        '#context' => [
          'price' => $staged_payment->getData('balance'),
        ],
      ];
      
      $element[$staged_payment->id()] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('@label @number: @balance available', [
          '@label' => $payment_gateway_plugin->multiPaymentDisplayLabel(),
          '@number' => $staged_payment->getData('remote_id'),
          '@balance' => render($formatted_balance),
        ]),
      ];

      if (!empty($staged_payments[$staged_payment->id()])) {
        $default_amount = $staged_payments[$staged_payment->id()]->getAmount();
      }
      else {
        if ($order->getTotalPrice()->compareTo($balance) <= 0) {
          $default_amount = $order->getTotalPrice();
        }
        else {
          $default_amount = $balance;
        }
      }

      $element[$staged_payment->id()]['#staged_payment_id'] = !empty($staged_payments[$staged_payment->id()]) ? $staged_payments[$staged_payment->id()]->id() : NULL;

      $element[$staged_payment->id()]['amount'] = [
        '#title' => t('Amount to apply to order'),
        '#type' => 'commerce_price',
        '#currency_code' => $balance->getCurrencyCode(),
        '#default_value' => $default_amount->toArray(),
      ];

      $element[$staged_payment->id()]['apply'] = [
        '#type' => 'submit',
        '#value' => t('Apply'),
        '#staged_payment_id' => $staged_payment->id(),
        '#name' => 'apply_gift_card_payment_' . $staged_payment->id(),
        '#limit_validation_errors' => [
          $element['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'applyGiftCard'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($element['#parents'], ['apply_gift_card_payment_' . $staged_payment->id()]),
      ];

      $element[$staged_payment->id()]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#staged_payment_id' => $staged_payment->id(),
        '#name' => 'remove_gift_card_payment_' . $staged_payment->id(),
        '#limit_validation_errors' => [
          $element['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'removeGiftCard'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($element['#parents'], ['remove_gift_card_payment_' . $staged_payment->id()]),
      ];
    }

    $element['new'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $payment_gateway_plugin->multiPaymentDisplayLabel(),
    ];
    $element['new']['gift_card_number'] = [
      '#type' => 'textfield',
      '#placeholder' => 'Enter code',
    ];
    $element['new']['add_gift_card'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#name' => 'add_gift_card_' . $element['#payment_gateway_id'],
      '#limit_validation_errors' => [
        $element['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'addGiftCard'],
      ],
      '#ajax' => [
        'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
      ],
      // Simplify ajaxRefresh() by having all triggering elements
      // on the same level.
      '#parents' => array_merge($element['#parents'], ['get_balance']),
    ];

    return $element;
  }

  /**
   * Submit callback for the "Apply gift card" button.
   */
  public static function addGiftCard(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    $gift_card_parents = array_merge($element['#parents'], [
      'new',
      'gift_card_number',
    ]);
    $gift_card_number = $form_state->getValue($gift_card_parents);

    $amount = $element['new']['#balance'];

    $order = $multi_payment_manager->loadOrder($element['#order_id']);

    $append = FALSE;
    /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
    if (!empty($element['#staged_payment_id'])) {
      $staged_payment = $multi_payment_manager->loadStagedPayment($element['#staged_payment_id']);
    }
    else {
      $staged_payment = $multi_payment_manager->createStagedPayment([
        'order_id' => $element['#order_id'],
        'payment_gateway' => $element['#payment_gateway_id'],
        'data' => ['remote_id' => $gift_card_number],
      ]);
      $append = TRUE;
    }

    $staged_payment->setData('balance', $amount);

    $staged_payment->setAmount($amount);
    $amount = $multi_payment_manager->getAdjustedPaymentAmount($staged_payment);

    $staged_payment->setAmount($amount);
    $staged_payment->save();

    if ($append) {
      $order->get('staged_multi_payment')->appendItem($staged_payment);
    }
    $order->save();
    static::setUserInput($form_state, array_merge($parents, [
      'new',
      'gift_card_number',
    ]), NULL);

    $form_state->setRebuild();

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
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('multi_payment_apply', $triggering_element['#parents']) && in_array($element['#payment_gateway_id'], $triggering_element['#parents'])) {

      if ($triggering_element['#name'] == 'add_gift_card_' . $element['#payment_gateway_id']) {
        $gift_card_parents = array_merge($element['#parents'], [
          'new',
          'gift_card_number',
        ]);
        $gift_card_number = $form_state->getValue($gift_card_parents);
        $gift_card_path = implode('][', $gift_card_parents);

        // Check to see if the gift card has already been added to the order.
        $order = $multi_payment_manager->loadOrder($element['#order_id']);
        $staged_payments = $multi_payment_manager->getStagedPaymentsFromOrder($order, $element['#payment_gateway_id']);
        foreach ($staged_payments as $staged_payment) {
          if ($staged_payment->getData('remote_id') == $gift_card_number) {
            $form_state->setErrorByName($gift_card_path, t('This gift card has already been added to the order.'));
          }
        }

        // Check the gift card balance.
        try {
          $balance = static::getBalance($element['#payment_gateway_id'], $gift_card_number);
          NestedArray::setValue($element, ['new', '#balance'], $balance);
        } catch (DeclineException $e) {
          $form_state->setErrorByName($gift_card_path, $e->getMessage());
        }
      }
    }

  }

  /**
   * Submit callback for the "Apply gift card" button.
   */
  public static function applyGiftCard(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $triggering_element = $form_state->getTriggeringElement();
    $staged_payment_id = $triggering_element['#staged_payment_id'];
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    $amount = new Price($element['#value'][$staged_payment_id]['amount']['number'], $element['#value'][$staged_payment_id]['amount']['currency_code']);

    $order = $multi_payment_manager->loadOrder($element['#order_id']);

    $staged_payment = $multi_payment_manager->loadStagedPayment($staged_payment_id);

    $staged_payment->setAmount($amount);
    $amount = $multi_payment_manager->getAdjustedPaymentAmount($staged_payment);

    $staged_payment->setAmount($amount);
    $staged_payment->setStatus(TRUE);
    $staged_payment->save();

    $order->save();

    static::setUserInput($form_state, array_merge($parents, [$staged_payment_id], ['amount']), NULL);

    $form_state->setRebuild();
  }

  /**
   * Remove gift card submit callback.
   */
  public static function removeGiftCard(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $triggering_element = $form_state->getTriggeringElement();
    $staged_payment_id = $triggering_element['#staged_payment_id'];
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);

    $staged_payment = $multi_payment_manager->loadStagedPayment($staged_payment_id);
    $staged_payment->delete();

    $order = $multi_payment_manager->loadOrder($element['#order_id']);

    $order->save();
    $form_state->setRebuild();
  }


  /**
   * @param string $payment_gateway_id
   * @param string $gift_card_number
   *
   * @return \Drupal\commerce_price\Price
   *   the balance on the gift card
   */
  protected static function getBalance($payment_gateway_id, $card_number) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    /** @var \Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway\GiftCardPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $multi_payment_manager->loadPaymentGatewayPlugin($payment_gateway_id);
    return $payment_gateway_plugin->getBalance($card_number);
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
