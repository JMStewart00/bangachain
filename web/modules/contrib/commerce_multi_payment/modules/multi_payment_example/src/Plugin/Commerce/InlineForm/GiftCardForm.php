<?php

namespace Drupal\commerce_multi_payment_example\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase;
use Drupal\commerce_multi_payment\MultiplePaymentManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for redeeming a coupon.
 *
 * @CommerceInlineForm(
 *   id = "commerce_multi_payment_example_giftcard_form",
 *   label = @Translation("Gift card example form"),
 * )
 */
class GiftCardForm extends InlineFormBase {

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['order_id', 'payment_gateway_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {

    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    $order = $this->multiPaymentManager->loadOrder($this->configuration['order_id']);
    if (!$order) {
      throw new \RuntimeException('Invalid order_id given to the gift card inline form.');
    }
    assert($order instanceof OrderInterface);

    $staged_payments = $this->multiPaymentManager->getStagedPaymentsFromOrder($order, $this->configuration['payment_gateway_id']);
    $payment_gateway_plugin = $this->multiPaymentManager->loadPaymentGatewayPlugin($this->configuration['payment_gateway_id']);

    $inline_form = [
        '#tree' => TRUE,
        '#theme' => 'commerce_multi_payment_example_giftcard_form',
        '#configuration' => $this->getConfiguration(),
      ] + $inline_form;

    foreach ($staged_payments as $staged_payment) {
      $balance = $staged_payment->getData('balance');
      $formatted_balance = [
        '#type' => 'inline_template',
        '#template' => '{{ price|commerce_price_format }}',
        '#context' => [
          'price' => $staged_payment->getData('balance'),
        ],
      ];

      $inline_form[$staged_payment->id()] = [
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

      $inline_form[$staged_payment->id()]['#staged_payment_id'] = !empty($staged_payments[$staged_payment->id()]) ? $staged_payments[$staged_payment->id()]->id() : NULL;

      $inline_form[$staged_payment->id()]['amount'] = [
        '#title' => t('Amount to apply to order'),
        '#type' => 'commerce_price',
        '#currency_code' => $balance->getCurrencyCode(),
        '#default_value' => $default_amount->toArray(),
      ];

      $inline_form[$staged_payment->id()]['apply'] = [
        '#type' => 'submit',
        '#value' => t('Apply'),
        '#staged_payment_id' => $staged_payment->id(),
        '#name' => 'apply_gift_card_payment_' . $staged_payment->id(),
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'applyGiftCard'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], ['apply_gift_card_payment_' . $staged_payment->id()]),
      ];

      $inline_form[$staged_payment->id()]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#staged_payment_id' => $staged_payment->id(),
        '#name' => 'remove_gift_card_payment_' . $staged_payment->id(),
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        '#submit' => [
          [get_called_class(), 'removeGiftCard'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], ['remove_gift_card_payment_' . $staged_payment->id()]),
      ];
    }

    $inline_form['new'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $payment_gateway_plugin->multiPaymentDisplayLabel(),
    ];
    $inline_form['new']['gift_card_number'] = [
      '#type' => 'textfield',
      '#placeholder' => 'Enter code',
    ];
    $inline_form['new']['add_gift_card'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#name' => 'add_gift_card_' . $this->configuration['payment_gateway_id'],
      '#limit_validation_errors' => [
        $inline_form['#parents'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshForm'],
        'element' => $inline_form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'addGiftCard'],
      ],
      // Simplify ajaxRefresh() by having all triggering elements
      // on the same level.
      '#parents' => array_merge($inline_form['#parents'], ['get_balance']),
    ];

    return $inline_form;
  }

  /**
   * Validates the gift card redemption element.
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

      if ($triggering_element['#name'] == 'add_gift_card_' . $this->configuration['payment_gateway_id']) {
        $gift_card_parents = array_merge($inline_form['#parents'], [
          'new',
          'gift_card_number',
        ]);
        $gift_card_number = $form_state->getValue($gift_card_parents);
        $gift_card_path = implode('][', $gift_card_parents);
        // Check to see if the gift card has already been added to the order.
        $order = $this->multiPaymentManager->loadOrder($this->configuration['order_id']);
        $staged_payments = $this->multiPaymentManager->getStagedPaymentsFromOrder($order, $this->configuration['payment_gateway_id']);
        foreach ($staged_payments as $staged_payment) {
          if ($staged_payment->getData('remote_id') == $gift_card_number) {
            $form_state->setErrorByName($gift_card_path, t('This gift card has already been added to the order.'));
          }
        }

        // Check the gift card balance.
        try {
          $payment_gateway_plugin = $this->multiPaymentManager->loadPaymentGatewayPlugin($this->configuration['payment_gateway_id']);
          $balance = $payment_gateway_plugin->getBalance($gift_card_number);
          NestedArray::setValue($inline_form, ['new', '#balance'], $balance);
        } catch (DeclineException $e) {
          $form_state->setErrorByName($gift_card_path, $e->getMessage());
        }
      }
    }

  }

  /**
   * Submit callback for the "Apply gift card" button.
   */
  public static function addGiftCard(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');

    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);
    $gift_card_parents = array_merge($inline_form['#parents'], [
      'new',
      'gift_card_number',
    ]);
    $gift_card_number = $form_state->getValue($gift_card_parents);

    $amount = $inline_form['new']['#balance'];

    $order = $multi_payment_manager->loadOrder($inline_form['#configuration']['order_id']);

    $append = FALSE;
    /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
    if (!empty($inline_form['#staged_payment_id'])) {
      $staged_payment = $multi_payment_manager->loadStagedPayment($inline_form['#staged_payment_id']);
    }
    else {
      $staged_payment = $multi_payment_manager->createStagedPayment([
        'order_id' => $inline_form['#configuration']['order_id'],
        'payment_gateway' => $inline_form['#configuration']['payment_gateway_id'],
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
   * Submit callback for the "Apply gift card" button.
   */
  public static function applyGiftCard(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $triggering_element = $form_state->getTriggeringElement();
    $staged_payment_id = $triggering_element['#staged_payment_id'];
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);
    $amount = new Price($inline_form[$staged_payment_id]['amount']['number']['#value'], $inline_form[$staged_payment_id]['amount']['currency_code']['#value']);
    $order = $multi_payment_manager->loadOrder($inline_form['#inline_form']->getConfiguration()['order_id']);

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
    $inline_form = NestedArray::getValue($form, $parents);

    $staged_payment = $multi_payment_manager->loadStagedPayment($staged_payment_id);
    $staged_payment->delete();

    $order = $multi_payment_manager->loadOrder($inline_form['#inline_form']->getConfiguration()['order_id']);

    $order->save();
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
