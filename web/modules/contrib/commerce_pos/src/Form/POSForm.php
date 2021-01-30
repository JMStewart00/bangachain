<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_pos\Controller\POS;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\CurrentStore;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Link;

/**
 * Provides the main POS form for using the POS to checkout customers.
 */
class POSForm extends ContentEntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current store object.
   *
   * @var \Drupal\commerce_store\CurrentStore
   */
  protected $currentStore;

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The commerce log storage.
   *
   * @var \Drupal\commerce_log\LogStorage
   */
  protected $logStorage;

  /**
   * Constructs a new POSForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Repository sent to parent.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_store\CurrentStore $current_store
   *   The current store object.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Used for storing the current active order.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, CurrentStore $current_store, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->currentStore = $current_store;
    // This is a hack around what I think is a php bug, at least in 7.1.15
    // logStorage static can lose reference to the entityType object sometimes.
    $this->logStorage = clone $entity_manager->getStorage('commerce_log');
    $this->tempStore = $temp_store_factory->get('commerce_pos');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_store.current_store'),
      $container->get('tempstore.private'),
      $container->get('messenger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'commerce_pos/global';
    $form['#attached']['library'][] = 'commerce_pos/form';

    $step = $form_state->get('step');
    $step = $step ?: 'order';
    $form_state->set('step', $step);

    if ($step == 'order') {
      $form = $this->buildOrderForm($form, $form_state);
    }
    elseif ($step == 'payment') {
      $form = $this->buildPaymentForm($form, $form_state);
    }

    // Add order note form.
    $form = $this->buildOrderCommentForm($form, $form_state);

    $this->addTotalsDisplay($form, $form_state);

    // Change the contact email field into an ajax field so that any changes
    // to the email automatically get saved to the order.
    $form['mail']['#prefix'] = '<div id="order-mail-wrapper">';
    $form['mail']['#suffix'] = '</div>';
    $form['mail']['widget'][0]['value']['#element_key'] = 'order-mail';
    $form['mail']['widget'][0]['value']['#limit_validation_errors'] = [
      ['mail'],
    ];
    $form['mail']['widget'][0]['value']['#ajax'] = [
      'wrapper' => 'order-mail-wrapper',
      'callback' => '::emailAjaxRefresh',
      'event' => 'change',
    ];

    // Hide Contact email field on Order form.
    if ($form_state->get('step') == 'order') {
      $form['mail']['widget'][0]['value']['#access'] = FALSE;
    }

    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;

    // Save the email if it has been changed.
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#element_key']) && $triggering_element['#element_key'] == 'order-mail') {
      $order->setEmail($form_state->getValue('mail'));
      $order->save();
    }

    return $form;
  }

  /**
   * Build the POS Order Form.
   */
  protected function buildOrderForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $form_state->set('commerce_pos_order_id', $order->id());

    $wrapper_id = 'commerce-pos-order-form-wrapper';
    $form_state->wrapper_id = $wrapper_id;

    $form = parent::buildForm($form, $form_state);

    $form['#theme'] = 'commerce_pos_form_order';
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['customer'] = [
      '#type' => 'details',
      '#title' => t('Customer'),
    ];

    $form['adjustments']['#type'] = 'details';
    $form['adjustments']['#title'] = t('Adjustments');
    $form['coupons']['#type'] = 'details';
    $form['coupons']['#title'] = t('Coupons');

    $form['uid']['#group'] = 'customer';
    $form['mail']['#group'] = 'customer';

    $form['list'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit']['#value'] = $this->t('Pay Now');
    $form['actions']['submit']['#element_key'] = 'pay-now';

    // Modify the delete label.
    $form['actions']['delete']['#title'] = $this->t('Void Order');

    // Ensure the user is redirected back to this page after deleting an order.
    if (isset($form['actions']['delete']['#url']) && $form['actions']['delete']['#url'] instanceof Url) {
      $form['actions']['delete']['#url']->mergeOptions([
        'query' => [
          'destination' => Url::fromRoute('commerce_pos.main')->toString(),
        ],
      ]);
    }

    $form['actions']['park_order'] = [
      '#type' => 'submit',
      '#value' => $this->t('Park Order'),
      '#weight' => 99,
      '#submit' => ['::parkOrder'],
      '#validate' => ['::validateForm'],
      '#access' => $this->orderCanBeParked($order),
      '#limit_validation_errors' => [['no_product_selected'], ['invalid_customer_id']],
    ];

    $form['actions']['clear_order'] = [
      '#type' => 'submit',
      '#value' => $this->t('New Order'),
      '#weight' => 99,
      '#submit' => ['::submitClearOrder'],
      '#access' => $this->entity->get('state')->value !== 'draft',
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Build the payment form, this is the second and final step of a POS order.
   *
   * @param array $form
   *   The form in 'payment' step.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state in 'payment' step.
   *
   * @return array
   *   The updated form ready to take payments.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildPaymentForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $wrapper_id = 'commerce-pos-pay-form-wrapper';
    $form_state->wrapper_id = $wrapper_id;

    $form['#theme'] = 'commerce_pos_form_payment';
    $form['#prefix'] = '<div id="' . $wrapper_id . '" class="sale">';
    $form['#suffix'] = '</div>';
    $form['#validate'][] = '::validatePaymentForm';

    // Is this too clunky?
    $parent_form = parent::buildForm($form, $form_state);
    $form['mail'] = $parent_form['mail'];

    // Get order customer information.
    $customer = $order->getCustomer();

    // If customer is 'Anonymous', wipe the value from 'Customer email' field.
    if ($customer->isAnonymous()) {
      $form['mail']['widget'][0]['value']['#value'] = '';
      $form['mail']['widget'][0]['value']['#default_value'] = '';
    }
    else {
      $form['mail']['widget'][0]['value']['#value'] = $customer->getEmail();
    }

    $form['order_id'] = [
      '#type' => 'value',
      '#value' => $order->id(),
    ];

    $form['payment_gateway'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($order);
    $order_balance = $this->getOrderBalance();
    $balance_paid = $order_balance->getNumber() <= 0;

    $form['payment_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($payment_gateways as $payment_gateway) {
      $form[$payment_gateway->id()] = [
        '#type' => 'details',
        '#title' => $payment_gateway->label(),
        '#name' => 'commerce-pos-payment-option-' . $payment_gateway->id(),
        '#group' => 'payment_tabs',
        '#payment_option_id' => $payment_gateway->id(),
        '#disabled' => $balance_paid,
        '#limit_validation_errors' => [],
      ];
    }

    $form['keypad'] = [
      '#type' => 'container',
      '#group' => 'payment_tabs',
      '#id' => 'commerce-pos-sale-keypad-wrapper',
      '#tree' => TRUE,
    ];

    // If no triggering element is set, grab the default payment method.
    $default_payment_gateway = $this->config('commerce_pos.settings')
      ->get('default_payment_gateway') ?: 'pos_cash';

    $form['payment_tabs']['#default_tab'] = 'edit-' . str_replace('_', '-', $default_payment_gateway);
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($default_payment_gateway) && !empty($payment_gateways[$default_payment_gateway]) && empty($triggering_element['#payment_option_id'])) {
      $triggering_element['#payment_option_id'] = $default_payment_gateway;
    }

    if (!empty($triggering_element['#payment_option_id']) && !$balance_paid) {
      $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
      $order_balance_amount_format = $currency_formatter->format($order_balance->getNumber(), $order_balance->getCurrencyCode());
      $keypad_amount = preg_replace('/[^0-9\.,]/', '', $order_balance_amount_format);

      /* @var PaymentGateway $payment_gayeway */
      foreach ($payment_gateways as $payment_gateway) {
        $form[$payment_gateway->id()]['keypad']['add'] = [
          '#type' => 'submit',
          '#group' => 'payment_tabs',
          '#value' => $this->t('Add @label Payment', [
            '@label' => $payment_gateway->label(),
          ]),
          '#name' => 'commerce-pos-pay-keypad-add-' . $payment_gateway->id(),
          '#submit' => ['::submitForm'],
          '#payment_gateway_id' => $payment_gateway->id(),
          '#element_key' => 'add-payment',
          '#ajax' => [
            'wrapper' => $form_state->wrapper_id,
            'callback' => '::ajaxRefresh',
          ],
        ];
        $form[$payment_gateway->id()]['keypad']['amount'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Enter @title Amount', [
            '@title' => $payment_gateway->label(),
          ]),
          '#required' => TRUE,
          '#default_value' => $keypad_amount,
          '#commerce_pos_keypad' => TRUE,
          '#attributes' => [
            'autofocus' => 'autofocus',
            'size' => 10,
            'autocomplete' => 'off',
            'class' => [
              'commerce-pos-payment-keypad-amount',
            ],
          ],
        ];
      }

      $form['#attached']['drupalSettings']['commerce_pos'] = [
        'commercePosPayment' => [
          'focusInput' => TRUE,
          'selector' => '.commerce-pos-payment-keypad-amount',
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['finish'] = [
      '#type' => 'submit',
      '#value' => $this->t('Complete Order'),
      '#disabled' => !$balance_paid,
      '#name' => 'commerce-pos-finish',
      '#submit' => ['::submitForm'],
      '#element_key' => 'finish-order',
      '#button_type' => 'primary',
    ];

    $form['actions']['park_order'] = [
      '#type' => 'submit',
      '#value' => $this->t('Park Order'),
      '#weight' => 99,
      '#submit' => ['::parkOrder'],
      '#access' => $this->orderCanBeParked($order),
      '#limit_validation_errors' => [],
    ];

    $form['actions']['clear_order'] = [
      '#type' => 'submit',
      '#value' => $this->t('New Order'),
      '#weight' => 99,
      '#submit' => ['::submitClearOrder'],
      '#access' => $this->entity->get('state')->value !== 'draft',
      '#limit_validation_errors' => [],
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back To Order'),
      '#name' => 'commerce-pos-back-to-order',
      '#submit' => ['::submitForm'],
      '#element_key' => 'back-to-order',
    ];

    return $form;
  }

  /**
   * Build the elements for the order comment form.
   */
  protected function buildOrderCommentForm(array $form, FormStateInterface $form_state) {
    $form['order_comments'] = [
      '#type' => 'container',
      '#prefix' => '<div id="commerce-pos-order-comments-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['order_comments']['add_order_comment'] = [
      '#type' => 'container',
      '#prefix' => '<div id="commerce-pos-add-order-comment-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $form['order_comments']['display_order_comment'] = [
      '#type' => 'container',
      '#prefix' => '<div id="commerce-pos-display-order-comment-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    $form['order_comments']['add_order_comment']['order_comment_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add Order Comment'),
    ];

    $order_comment = $this->displayOrderComment();
    $form['order_comments']['display_order_comment']['order_comment'] = $order_comment;

    return $form;
  }

  /**
   * Adds a commerce log to an order.
   */
  protected function saveOrderComment(array &$form, FormStateInterface $form_state) {
    $order = $this->entity;

    $comment = $form_state->getValue([
      'order_comments',
      'add_order_comment',
      'order_comment_text',
    ]);

    $triggering_element = $form_state->getTriggeringElement();
    $element_value = $triggering_element["#value"];

    // Adding another check because order comment not saving for park orders.
    if (empty($comment) && $element_value == 'Park Order') {
      $order_comment_user_input = $form_state->getUserInput();
      $comment = $order_comment_user_input['order_comments']['add_order_comment']['order_comment_text'];
    }
    // In order to add a comment to an order it needs to be saved. This should
    // never be the case but this is defensive code.
    if (!empty($comment)) {
      if ($order->isNew()) {
        $order->save();
      }
      $this->logStorage->generate($order, 'order_comment', [
        'comment' => $comment,
      ])->save();

      // Remove the user input as we no longer need it.
      $user_input = $form_state->getUserInput();
      unset($user_input['order_comments']['add_order_comment']['order_comment_text']);
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * Defines displayOrderComment helper function.
   */
  protected function displayOrderComment() {
    // Get the default commerce log view.
    $view = Views::getView('commerce_activity');
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    if ($view) {
      $view->setDisplay('default');
      $view->setArguments([$order->id(), 'commerce_order']);
      // Get generated views.
      $render = $view->render();
    }
    return $render;
  }

  /**
   * AJAX callback for the Pay form keypad.
   */
  public function keypadAjaxRefresh($form, &$form_state) {
    return $form['keypad'];
  }

  /**
   * AJAX callback for the contact email.
   */
  public function emailAjaxRefresh($form, &$form_state) {
    return $form['mail'];
  }

  /**
   * AJAX callback for the payment form.
   */
  public function ajaxRefresh($form, &$form_state) {
    return $form;
  }

  /**
   * Validate the values in the payment form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePaymentForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (substr($triggering_element['#name'], 0, 27) == 'commerce-pos-pay-keypad-add') {
      $payment_gateway = $triggering_element['#payment_gateway_id'];
      $keypad_amount = $form_state->getValue($payment_gateway)['keypad']['amount'];

      if (!is_numeric($keypad_amount)) {
        $form_state->setError($form[$payment_gateway]['keypad']['amount'], $this->t('Payment amount must be a number.'));
      }

      /** @var int $fraction_digits */
      $fraction_digits = $this->currentStore->getStore()
        ->getDefaultCurrency()
        ->getFractionDigits();
      list($whole, $decimal) = sscanf($keypad_amount, '%d.%s');
      if (strlen(rtrim($decimal, '0')) > $fraction_digits) {
        $form_state->setError($form['keypad']['amount'], $this->t('The amount should be of @fraction_digit digit precision.', ['@fraction_digit' => $fraction_digits]));
      }
    }
  }

  /**
   * Validates the 'Pay Now' button.
   *
   * Checks for at least one product, sets no_product_selected error.
   * Checks for valid customer, sets invalid_customer_id error.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only validates 'Order' form.
    if ($form_state->get('step') == 'order') {

      // TODO Suppress the default 'This value should not be null' message.
      // If no product has been added to order...
      if (empty($this->entity->getItems())) {
        $form_state->setErrorByName('no_product_selected',
          $this->t('Cannot submit an empty order')->render());
      }

      // Get order customer information.
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->entity;
      $customer_id = $order->getCustomerId();

      // If customer is not set aka 'Anonymous'.
      if ($customer_id == 0) {

        // This protects against if the 'Remove' customer button does not clear
        // 'Customer email' field.
        $form['mail']['widget'][0]['value']['#value'] = '';
        $form['mail']['widget'][0]['value']['#default_value'] = '';

        // Get input from customer widget.
        $customer_input = $form_state->getValue(['uid'])[0]['target_id']['order_customer']['customer_textfield'];

        // If customer widget has input.
        if (!empty($customer_input)) {

          // If input is not a valid email address.
          if (!filter_var($customer_input, FILTER_VALIDATE_EMAIL)) {

            // If customer widget has not matched input with a user.
            if ($customer_input != User::load($customer_id)->getUsername()) {

              // Set 'invalid_customer_id' error.
              $link = Link::createFromRoute(t('Create New Customer Account'),
                'user.admin_create')->toString();
              $form_state->setErrorByName('invalid_customer_id',
                $this->t('Customer account for "@input" not found. @link',
                  ['@link' => $link, '@input' => $customer_input]));
            }
          }
        }
      }

    }
  }

  /**
   * Handles submiting the form in all states.
   *
   * @param array $form
   *   The form in whichever state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state in whichever state.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_key = $triggering_element['#element_key'] ?: '';
    $step = $form_state->get('step');

    if ($step == 'order') {
      parent::submitForm($form, $form_state);
      $this->entity->save();
      $form_state->setRebuild();
    }

    switch ($triggering_element_key) {
      case 'pay-now':

        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $this->entity;
        // If customer is not set and customer widget field is not empty,
        // Create new user from input.
        if ($order->getCustomerId() == 0 && !empty($form_state->getUserInput()['uid'][0]['target_id']['order_customer']['customer_textfield'])) {
          $this->createUserFromEmail($form, $form_state);
        }
        $form_state->set('step', 'payment');
        break;

      case 'add-payment':
        $this->submitPayment($form, $form_state);
        break;

      case 'back-to-order':
        $form_state->set('step', 'order');
        $form_state->setRebuild(TRUE);
        break;

      case 'finish-order':
        $this->finishOrder($form, $form_state);

        // Explicitly reroute to the POS page as we might have been editing an
        // order through POS.
        $form_state->setRedirect('commerce_pos.main');
        break;

      case 'remove-payment':
        $this->voidPayment($form, $form_state);
        // Save the payment, in case we leave and go to another screen. Missing
        // a payment would be bad also helps if we're loading it somewhere else,
        // like for the receipt trickyness.
        $this->entity->save();
        $form_state->setRebuild(TRUE);
        break;
    }

    $this->saveOrderComment($form, $form_state);
  }

  /**
   * Add a payment to the pos order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function submitPayment(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $store = $this->entity->getStore();
    $default_currency = $store->getDefaultCurrency();

    // Right now all the payment methods are manual, we'll have to change this
    // up once we want to support integrated payment methods.
    $payment_gateway = $triggering_element['#payment_gateway_id'];
    $values = [
      'payment_gateway' => $payment_gateway,
      'order_id' => $this->entity->id(),
      'state' => 'pending',
      'amount' => [
        'number' => $form_state->getValue($payment_gateway, 'keypad', 'amount')['keypad']['amount'],
        'currency_code' => $default_currency->getCurrencyCode(),
      ],
    ];

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create($values);
    $payment->save();

    $this->entity->save();

    $form_state->setRebuild(TRUE);
  }

  /**
   * Void a payment to the pos order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function voidPayment(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $order = $this->entity;
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);

    // Get the payment id from the triggering element.
    $payment_id = $triggering_element['#payment_id'];

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payments[$payment_id];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\Manual $payment_gateway */
    $plugin_manager = \Drupal::service('plugin.manager.commerce_payment_gateway');

    // Right now all the payment methods are manual, we'll have to change this
    // up once we want to support integrated payment methods.
    $payment_gateway = $plugin_manager->createInstance('manual');
    $payment_gateway->voidPayment($payment);
    $this->messenger()->addMessage($this->t('Payment Voided'));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Finish the current order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function finishOrder(array &$form, FormStateInterface $form_state) {
    $this->completePayments();

    $order = $this->entity;

    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();

    $this->clearOrder($form_state);
  }

  /**
   * Build the totals display for the sidebar.
   */
  protected function addTotalsDisplay(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $store = $order->getStore();
    $default_currency = $store->getDefaultCurrency();
    $totals = [];

    // Collecting the Subtotal.
    $form['totals'] = [
      '#type' => 'container',
    ];
    $current_register = \Drupal::service('commerce_pos.current_register')->get()->getName();
    $form['totals']['register'] = [
      '#markup' => '<div class="current-register">Register: ' . $current_register . '</div>',
    ];

    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $order_total_summary = \Drupal::service('commerce_order.order_total_summary');
    $order_summary = $order_total_summary->buildTotals($order);

    // Commerce sets these to null instead of zero and causes things to blow up
    // TODO this should probably be backported to commerce.
    if (empty($order_summary['subtotal'])) {
      $order_summary['subtotal'] = new Price(0, $default_currency->getCurrencyCode());
    }
    if (empty($order_summary['total'])) {
      $order_summary['total'] = new Price(0, $default_currency->getCurrencyCode());
    }

    $sub_total_price = $order_summary['subtotal'];
    $formatted_amount = $currency_formatter->format($sub_total_price->getNumber(), $sub_total_price->getCurrencyCode());
    $totals[] = [$this->t('Subtotal'), $formatted_amount];

    foreach ($order_summary['adjustments'] as $adjustment) {
      if (!empty($adjustment['total'])) {
        $formatted_amount = $currency_formatter->format($adjustment['total']->getNumber(), $adjustment['total']->getCurrencyCode());
        $totals[] = [$adjustment['label'], $formatted_amount];
      }
    }

    $total_price = $order_summary['total'];
    $formatted_amount = $currency_formatter->format($total_price->getNumber(), $total_price->getCurrencyCode());
    $totals[] = [$this->t('Total'), $formatted_amount];

    $form['totals']['totals'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => 'commerce-pos--totals--totals',
      ],
      '#rows' => $totals,
    ];

    // Collect payments.
    $payment_totals = [];
    $form['totals']['payments'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => 'commerce-pos--totals--payments',
      ],
    ];
    foreach ($this->getOrderPayments() as $payment) {
      $amount = $payment->getAmount();
      $rendered_amount = $payment->getState()->value === 'voided' ? $this->t('VOID') : $currency_formatter->format($amount->getNumber(), $amount->getCurrencyCode());
      $remove_button = [
        '#type' => 'submit',
        '#value' => $this->t('void'),
        '#name' => 'commerce-pos-pay-keypad-remove',
        '#submit' => ['::submitForm'],
        '#payment_id' => $payment->id(),
        '#payment_gateway_id' => $payment->get('payment_gateway')->target_id,
        '#element_key' => 'remove-payment',
        '#attributes' => [
          'class' => [
            'commerce-pos-pay-keypad-remove',
            'link',
          ],
        ],
        '#access' => $payment->getState()->value !== 'voided' && $order->getState()->value != 'completed',
      ];

      // Only add non-voided payments to the order total.
      if ($payment->getState()->value !== 'voided') {
        if (!isset($payment_totals[$amount->getCurrencyCode()])) {
          // Initialise the payment total.
          $payment_totals[$amount->getCurrencyCode()] = 0;
        }
        $payment_totals[$amount->getCurrencyCode()] += $amount->getNumber();
      }

      $form['totals']['payments'][$payment->id()] = [
        'gateway' => [
          'gateway' => [
            '#plain_text' => $payment->getPaymentGateway()->label(),
          ],
          'void_link' => $remove_button,
        ],
        'amount' => [
          'amount' => [
            '#plain_text' => $rendered_amount,
          ],
        ],
      ];
    }

    // Collect the balances.
    $balances = [];
    foreach ($payment_totals as $currency_code => $amount) {
      $balances[] = [
        'class' => 'commerce-pos--totals--total-paid',
        'data' => [
          $this->t('Total Paid'),
          $currency_formatter->format((string) $amount, $currency_code),
        ],
      ];
    }

    // If we didn't set a total paid above, we should set it to 0.
    if (empty($balances)) {
      $balances[] = [
        'class' => 'commerce-pos--totals--total-paid',
        'data' => [
          $this->t('Total Paid'),
          $currency_formatter->format('0', $order->getStore()->getDefaultCurrency()->getCurrencyCode()),
        ],
      ];
    }
    $remaining_balance = $this->getOrderBalance();

    $to_pay = $remaining_balance->getNumber();
    if ($to_pay < 0) {
      $to_pay = '0';
    }
    $formatted_amount = $currency_formatter->format($to_pay, $remaining_balance->getCurrencyCode());
    $balances[] = [
      'class' => 'commerce-pos--totals--to-pay',
      'data' => [$this->t('To Pay'), $formatted_amount],
    ];

    $change = -$remaining_balance->getNumber();
    if ($change < 0) {
      $change = '0';
    }
    $formatted_change_amount = $currency_formatter->format((string) $change, $remaining_balance->getCurrencyCode());
    $balances[] = [
      'class' => [
        'commerce-pos--totals--change',
      ],
      'data' => [$this->t('Change'), $formatted_change_amount],
    ];

    $form['totals']['balance'] = [
      '#type' => 'table',
      '#rows' => $balances,
    ];
  }

  /**
   * Get the current balance of the order.
   *
   * Once https://www.drupal.org/node/2804227 is in commerce we should be able
   * to do this directly from the order.
   *
   * @return \Drupal\commerce_price\Price
   *   The total remaining balance amount.
   */
  protected function getOrderBalance() {
    $payments = $this->getOrderPayments();
    $total_price = $this->entity->getTotalPrice();
    $total_price_amount = !empty($total_price) ? $total_price->getNumber() : 0;
    $currency_code = !empty($total_price) ? $total_price->getCurrencyCode() : $this->entity->getStore()
      ->getDefaultCurrency()
      ->getCurrencyCode();
    $balance_paid_amount = 0;

    foreach ($payments as $payment) {
      if (!in_array($payment->getState()->value, ['voided', 'refunded'])) {
        $balance_paid_amount += $payment->getBalance()->getNumber();
      }
    }

    $balance_remaining = (string) ($total_price_amount - $balance_paid_amount);

    return new Price($balance_remaining, $currency_code);
  }

  /**
   * Get an array of payment entities for the current order.
   *
   * @return array
   *   The Payment entities attached to this order.
   */
  protected function getOrderPayments() {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    return $payment_storage->loadMultipleByOrder($this->entity);
  }

  /**
   * Set the order's payments to completed.
   */
  protected function completePayments() {
    foreach ($this->getOrderPayments() as $payment) {
      if ($payment->getState()->value == 'pending') {
        $payment->setState('completed');
        $payment->save();
      }
    }

  }

  /**
   * Clear the existing order, so a new one can be created.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearOrder(FormStateInterface $form_state) {
    $this->tempStore->delete(POS::CURRENT_ORDER_KEY);
    // Redirecting back to the main route will create a new draft order.
    $pos_url = Url::fromRoute('commerce_pos.main');
    $form_state->setRedirectUrl($pos_url);
  }

  /**
   * Orders require a customer, so a user is created from customer widget field.
   *
   * @param array $form
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUserFromEmail(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entity;

    // Get the entered customer email address and create user.
    $customer_email = $form_state->getUserInput()['uid'][0]['target_id']['order_customer']['customer_textfield'];
    if (filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
      $user = User::create([
        'name' => $customer_email,
        'mail' => $customer_email,
        'pass' => user_password(),
        'status' => TRUE,
      ]);
      $user->save();
      $order->setEmail($customer_email);
      $order->setCustomerId($user->id());

      $this->messenger->addMessage($this->t('Customer account created for @email', ['@email' => $customer_email]));

      // Notify new user about the account.
      \Drupal::service('plugin.manager.mail')->mail('user', 'register_admin_created', $user->getEmail(), $user->getPreferredLangcode(), ['account' => $user]);
    }
  }

  /**
   * Parks current order.
   *
   * @param array $form
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function parkOrder(array &$form, FormStateInterface $form_state) {
    // If we've got any pending comments we probably want to save those as well.
    $this->saveOrderComment($form, $form_state);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entity;

    // If customer is not set and customer widget is a new valid email.
    // Create new user from input.
    if ($order->getCustomerId() == 0 && !empty($form_state->getUserInput()['uid'][0]['target_id']['order_customer']['customer_textfield'])) {
      $this->createUserFromEmail($form, $form_state);
    }

    // Defensive code to ensure we never park an order that is not a draft. The
    // Park button should not be accessible if the order is not a draft but this
    // is just in case.
    if ($order->get('state')->value !== 'draft') {
      throw new \RuntimeException('Cannot park an order that is not in the draft state');
    }

    $order->set('state', 'parked')->save();

    $this->clearOrder($form_state);

    $this->messenger->addMessage($this->t('Order @order_id has been parked', ['@order_id' => $order->id()]));
  }

  /**
   * Submit callback for clearing the current and starting a new order.
   *
   * @param array $form
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitClearOrder(array &$form, FormStateInterface $form_state) {
    $this->clearOrder($form_state);
  }

  /**
   * Determines whether the given order can be parked or not.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to check.
   *
   * @return bool
   *   TRUE when the given order can be parked, false otherwise.
   */
  public function orderCanBeParked(OrderInterface $order) {
    $state = $order->getState();
    $workflow = $state->getWorkflow();

    $transition_id = 'park';
    $transition = $workflow->getTransition($transition_id);
    $transitions = $workflow->getAllowedTransitions($state->value, $order);

    if (in_array($transition_id, array_keys($transitions))) {
      return TRUE;
    }

    return FALSE;
  }

}
