<?php

namespace Drupal\commerce_multi_payment_example\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase;
use Drupal\commerce_multi_payment\MultiplePaymentManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for redeeming a coupon.
 *
 * @CommerceInlineForm(
 *   id = "commerce_multi_payment_example_storecredit_form",
 *   label = @Translation("Store credit example form"),
 * )
 */
class StoreCreditForm extends InlineFormBase {

  /**
   * @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface
   */
  protected $multiPaymentManager;


  /**
   * Constructs a new CouponRedemption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager
   *   The multiple payments manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MultiplePaymentManagerInterface $multi_payment_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->multiPaymentManager = $multi_payment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_multi_payment.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'order_id' => '',
      'payment_gateway_id' => '',
      'balance' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['order_id', 'payment_gateway_id', 'balance'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {

    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    $order = $this->multiPaymentManager->loadOrder($this->configuration['order_id']);
    if (!$order) {
      throw new \RuntimeException('Invalid order_id given to the store credit inline form.');
    }
    assert($order instanceof OrderInterface);

    $payment_gateway_plugin = $this->multiPaymentManager->loadPaymentGatewayPlugin($this->configuration['payment_gateway_id']);

    $staged_payment = NULL;

    $staged_payments = $this->multiPaymentManager->getStagedPaymentsFromOrder($order, $this->configuration['payment_gateway_id']);
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

    $inline_form = [
        '#tree' => TRUE,
        '#theme' => 'commerce_multi_payment_example_storecredit_form',
        '#configuration' => $this->getConfiguration(),
      ] + $inline_form;

    $balance = $this->configuration['balance'];
    $formatted_balance = [
      '#type' => 'inline_template',
      '#template' => '{{ price|commerce_price_format }}',
      '#context' => [
        'price' => $balance,
      ],
    ];


    $inline_form['store_credit'] = [
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

    $inline_form['#staged_payment_id'] = !empty($staged_payment) ? $staged_payment->id() : NULL;

    $inline_form['store_credit']['amount'] = [
      '#title' => t('Amount to apply to order'),
      '#type' => 'commerce_price',
      '#currency_code' => $balance->getCurrencyCode(),
      '#default_value' => $default_amount->toArray(),
    ];

    $inline_form['store_credit']['apply'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#staged_payment_id' => !empty($staged_payment) ? $staged_payment->id() : NULL,
      '#name' => $this->configuration['payment_gateway_id'] . '_apply_store_credit_payment',
      '#limit_validation_errors' => [
        $inline_form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyStoreCredit'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshForm'],
        'element' => $inline_form['#parents'],
      ],
      // Simplify ajaxRefresh() by having all triggering elements
      // on the same level.
      '#parents' => array_merge($inline_form['#parents'], [$this->configuration['payment_gateway_id'] . '_apply_store_credit_payment']),
    ];

    if (!empty($staged_payment)) {
      $element['store_credit']['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => $this->configuration['payment_gateway_id'] . '_remove_store_credit_payment',
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'removeStoreCredit'],
        ],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], [$this->configuration['payment_gateway_id'] . '_remove_store_credit_payment']),
      ];
    }

    return $inline_form;
  }

  /**
   * @param string $payment_gateway_id
   * @param int $user_id
   *
   * @return \Drupal\commerce_price\Price
   *   the balance of the store credit
   */
  protected static function getBalance($payment_gateway_id, $uid) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    /** @var \Drupal\commerce_multi_payment_example\Plugin\Commerce\PaymentGateway\StoreCreditPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $multi_payment_manager->loadPaymentGatewayPlugin($payment_gateway_id);
    return $payment_gateway_plugin->getBalance($uid);
  }

  /**
   * Validates the store credit redemption element.
   *
   * @param array $inline_form
   *   The form .
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('multi_payment_apply', $triggering_element['#parents']) && in_array($this->configuration['payment_gateway_id'], $triggering_element['#parents'])) {

      if ($triggering_element['#name'] == $this->configuration['payment_gateway_id'] . '_apply_store_credit_payment') {
        $amount_parents = array_merge($inline_form['#parents'], [
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
        $order = $order_storage->load($this->configuration['order_id']);

        // Check the store credit balance.
        try {
          $balance = static::getBalance($this->configuration['payment_gateway_id'], $order->getCustomerId());
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
   * Submit callback for the apply store credit button.
   */
  public static function applyStoreCredit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');

    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);
    $staged_payment_id = $inline_form['#staged_payment_id'];

    $amount_parents = array_merge($inline_form['#parents'], [
      'store_credit',
      'amount',
    ]);
    $amount_array = $form_state->getValue($amount_parents);
    $amount = new Price($amount_array['number'], $amount_array['currency_code']);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $multi_payment_manager->loadOrder($inline_form['#configuration']['order_id']);

    $append = FALSE;

    if (empty($staged_payment_id)) {
      $staged_payment = $multi_payment_manager->createStagedPayment([
        'order_id' => $inline_form['#configuration']['order_id'],
        'payment_gateway' => $inline_form['#configuration']['payment_gateway_id'],
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
    $staged_payment_id = $triggering_element['#staged_payment_id'];
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    $staged_payment = $multi_payment_manager->loadStagedPayment($inline_form['#staged_payment_id']);
    $staged_payment->delete();

    $order = $multi_payment_manager->loadOrder($inline_form['#inline_form']->getConfiguration()['order_id']);
    $order->save();

    static::setUserInput($form_state, array_merge($parents, [
      'store_credit',
      'amount',
    ]), NULL);
    $form_state->setRebuild();
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
