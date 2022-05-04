<?php

namespace Drupal\commerce_square\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\commerce_square\ErrorHelper;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Models\Address;
use Square\Models\CreateOrderRequest;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\Models\Order;
use Square\Models\OrderLineItem;
use Square\Models\OrderLineItemDiscount;
use Square\Models\RefundPaymentRequest;
use Square\Models\CompletePaymentRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides the Square payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "square",
 *   label = "Square",
 *   display_label = "Square",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_square\PluginForm\Square\PaymentMethodAddForm",
 *   },
 *   modes = {
 *     "test" = @Translation("Sandbox"),
 *     "live" = @Translation("Production"),
 *   },
 *   js_library = "commerce_square/form",
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex",
 *     "dinersclub",
 *     "discover",
 *     "jcb",
 *     "mastercard",
 *     "visa",
 *     "unionpay",
 *   },
 * )
 */
class Square extends OnsitePaymentGatewayBase implements SquareInterface {

  /**
   * The Connect application.
   *
   * @var \Drupal\commerce_square\Connect
   */
  protected $connect;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->connect = $container->get('commerce_square.connect');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [
      'test_location_id' => '',
      'live_location_id' => '',
      'enable_credit_card_icons' => TRUE,
    ];
    return $default_configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    if (empty($this->connect->getAppId(Environment::SANDBOX)) && empty($this->connect->getAccessToken(Environment::SANDBOX))) {
      $this->messenger()->addError($this->t('Square has not been configured, please go to :link', [
        ':link' => Link::fromTextAndUrl($this->t('the settings form'), Url::fromRoute('commerce_square.settings')),
      ]));
    }

    foreach (array_keys($this->getSupportedModes()) as $mode) {
      $form[$mode] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#title' => $this->t('@mode location', ['@mode' => $this->pluginDefinition['modes'][$mode]]),
      ];
      $form[$mode][$mode . '_location_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Location'),
        '#description' => $this->t('The location for the transactions.'),
        '#default_value' => $this->configuration[$mode . '_location_id'],
        '#required' => TRUE,
      ];

      $api_mode = $mode === 'test' ? Environment::SANDBOX : Environment::PRODUCTION;
      $client = $this->connect->getClient($api_mode);

      $success = TRUE;
      try {
        $locations_api = $client->getLocationsApi();
        $api_response = $locations_api->listLocations();

        if ($api_response->isError()) {
          $success = FALSE;
        }
      }
      catch (\Exception $e) {
        $success = FALSE;
      }

      if ($success) {
        $locations_response = $api_response->getResult();
        $location_options = $locations_response->getLocations();
        $options = [];
        foreach ($location_options as $location_option) {
          $options[$location_option->getId()] = $location_option->getName();
        }
        $form[$mode][$mode . '_location_id']['#options'] = $options;
      }
      else {
        $form[$mode][$mode . '_location_id']['#disabled'] = TRUE;
        $form[$mode][$mode . '_location_id']['#options'] = ['_none' => 'Not configured'];
      }
    }

    $form['enable_credit_card_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Credit Card Icons'),
      '#description' => $this->t('Enabling this setting will display credit card icons in the payment section during checkout.'),
      '#default_value' => $this->configuration['enable_credit_card_icons'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $mode = $values['mode'];
    if (empty($values[$mode][$mode . '_location_id'])) {
      $form_state->setError($form[$mode][$mode . '_location_id'], $this->t('You must select a location for the configured mode.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    foreach (array_keys($this->getSupportedModes()) as $mode) {
      $this->configuration[$mode . '_location_id'] = $values[$mode][$mode . '_location_id'];
    }
    $this->configuration['enable_credit_card_icons'] = $values['enable_credit_card_icons'];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiClient() {
    $api_mode = $this->getMode() == 'test' ? Environment::SANDBOX : Environment::PRODUCTION;
    return $this->connect->getClient($api_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    $paid_amount = $payment->getAmount();
    $currency = $paid_amount->getCurrencyCode();

    // Square only accepts integers and not floats.
    // @see https://developer.squareup.com/docs/build-basics/common-data-types/working-with-monetary-amounts
    $square_total_amount = $this->minorUnitsConverter->toMinorUnits($paid_amount);

    // Total amount of money.
    $square_total_money = new Money();
    $square_total_money->setCurrency($currency);
    $square_total_money->setAmount($square_total_amount);

    $billing = $payment_method->getBillingProfile();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $billing->get('address')->first();

    $mode = $this->getMode();
    $order = new Order($this->configuration[$mode . '_location_id']);
    $order->setReferenceId($payment->getOrderId());

    $line_items = [];
    $line_item_total = 0;
    foreach ($payment->getOrder()->getItems() as $item) {
      $line_item = new OrderLineItem($item->getQuantity());
      $base_price_money = new Money();
      $square_amount = $this->minorUnitsConverter->toMinorUnits($item->getUnitPrice());
      $base_price_money->setAmount($square_amount);
      $base_price_money->setCurrency($currency);
      $line_item->setBasePriceMoney($base_price_money);
      $line_item->setName($item->getTitle());
      $square_amount = $this->minorUnitsConverter->toMinorUnits($item->getTotalPrice());
      $line_item_total += $square_amount;
      $line_items[] = $line_item;
    }

    // Square requires the order total to match the payment amount.
    if ($line_item_total != $square_total_amount) {
      $diff = $square_total_amount - $line_item_total;
      if ($diff < 0) {
        $discount_money = new Money();
        $discount_money->setCurrency($currency);
        $discount_money->setAmount(-$diff);
        $discount = new OrderLineItemDiscount();
        $discount->setAmountMoney($discount_money);
        $discount->setName('Adjustments');
      }
      else {
        $line_item = new OrderLineItem("1");
        $total_money = new Money();
        $total_money->setAmount($diff);
        $total_money->setCurrency($currency);
        $line_item->setBasePriceMoney($total_money);
        $line_item->setName('Adjustments');
        $line_items[] = $line_item;
      }
    }

    $order->setLineItems($line_items);
    if (isset($discount)) {
      $order->setDiscounts([$discount]);
    }

    // Billing address.
    $billing_address = new Address();
    $billing_address->setAddressLine1($address->getAddressLine1());
    $billing_address->setAddressLine2($address->getAddressLine2());
    $billing_address->setLocality($address->getLocality());
    $billing_address->setSublocality($address->getDependentLocality());
    $billing_address->setAdministrativeDistrictLevel1($address->getAdministrativeArea());
    $billing_address->setPostalCode($address->getPostalCode());
    $billing_address->setCountry($address->getCountryCode());

    try {
      $api_client = $this->getApiClient();
      // Create order request.
      $order_request = new CreateOrderRequest();
      $order_request->setIdempotencyKey(uniqid($payment->getOrderId() . '-', TRUE));
      $order_request->setOrder($order);
      // Create order.
      $orders_api = $api_client->getOrdersApi();
      $orders_api_request = $orders_api->createOrder($order_request);
      if ($orders_api_request->isSuccess()) {
        $order_response = $orders_api_request->getResult();
        // Create payment request.
        $payment_request = new CreatePaymentRequest(
          $payment_method->getRemoteId(),
          uniqid('', TRUE),
          $square_total_money
        );
        $payment_request->setOrderId($order_response->getOrder()->getId());
        $payment_request->setAutocomplete($capture);
        $payment_request->setIdempotencyKey(uniqid('', TRUE));
        $payment_request->setBuyerEmailAddress($payment->getOrder()->getEmail());
        $payment_request->setBillingAddress($billing_address);
        // Create payment.
        $payment_api = $api_client->getPaymentsApi();
        $payment_api_request = $payment_api->createPayment($payment_request);
        if ($payment_api_request->isSuccess()) {
          $payment_response = $payment_api_request->getResult();
          $next_state = $capture ? 'completed' : 'authorization';
          $payment->setState($next_state);
          $payment->setRemoteId($payment_response->getPayment()->getId());
          $payment->setAuthorizedTime($payment_response->getPayment()->getCreatedAt());
          if ($capture) {
            $payment->setCompletedTime($payment_response->getPayment()->getCreatedAt());
          }
          else {
            $expires = $this->time->getRequestTime() + (3600 * 24 * 6) - 5;
            $payment->setExpiresTime($expires);
          }
          $payment->save();
        }
        else {
          throw ErrorHelper::convertException(
            new ApiException(
              $payment_api_request->getBody(),
              $payment_api_request->getRequest()
            )
          );
        }
      }
      else {
        throw ErrorHelper::convertException(
          new ApiException(
            $orders_api_request->getBody(),
            $orders_api_request->getRequest()
          )
        );
      }
    }
    catch (ApiException $e) {
      throw ErrorHelper::convertException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      'payment_method_nonce', 'card_type', 'last4',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    // @todo Make payment methods reusable. Currently they represent 24hr nonce.
    // @see https://docs.connect.squareup.com/articles/processing-recurring-payments-ruby
    // Meet specific requirements for reusable, permanent methods.
    $payment_method->setReusable(FALSE);
    $payment_method->card_type = $this->mapCreditCardType($payment_details['card_type']);
    $payment_method->card_number = $payment_details['last4'];
    $payment_method->card_exp_month = $payment_details['exp_month'];
    $payment_method->card_exp_year = $payment_details['exp_year'];
    $remote_id = $payment_details['payment_method_nonce'];
    $payment_method->setRemoteId($remote_id);

    // Nonces expire after 24h. We reduce that time by 5s to account for the
    // time it took to do the server request after the JS tokenization.
    $expires = $this->time->getRequestTime() + (3600 * 24) - 5;
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // @todo Currently there are no remote records stored.
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    $amount = $amount ?: $payment->getAmount();

    try {
      $api_client = $this->getApiClient();
      $payment_api = $api_client->getPaymentsApi();
      $body = new CompletePaymentRequest();
      $payment_api_request = $payment_api->completePayment($payment->getRemoteId(), $body);
      if ($payment_api_request->isSuccess()) {
        $payment->setState('completed');
        $payment->setAmount($amount);
        $payment->setCompletedTime($this->time->getRequestTime());
        $payment->save();
      }
      else {
        throw ErrorHelper::convertException(
          new ApiException(
            $payment_api_request->getBody(),
            $payment_api_request->getRequest()
          )
        );
      }
    }
    catch (ApiException $e) {
      throw ErrorHelper::convertException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    try {
      $api_client = $this->getApiClient();
      $payment_api = $api_client->getPaymentsApi();
      $payment_api_request = $payment_api->cancelPayment($payment->getRemoteId());
      if ($payment_api_request->isSuccess()) {
        $payment->setState('authorization_voided');
        $payment->save();
      }
      else {
        throw ErrorHelper::convertException(
          new ApiException(
            $payment_api_request->getBody(),
            $payment_api_request->getRequest()
          )
        );
      }
    }
    catch (ApiException $e) {
      throw ErrorHelper::convertException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);

    $amount = $amount ?: $payment->getAmount();
    // Square only accepts integers and not floats.
    // @see https://developer.squareup.com/docs/build-basics/common-data-types/working-with-monetary-amounts
    $square_amount = $this->minorUnitsConverter->toMinorUnits($amount);
    // Total amount of money.
    $amount_money = new Money();
    $amount_money->setAmount($square_amount);
    $amount_money->setCurrency($amount->getCurrencyCode());
    // Refund payment request.
    $refund_request = new RefundPaymentRequest(
      uniqid('', TRUE),
      $amount_money,
    );
    $refund_request->setReason((string) $this->t('Refunded through store backend'));
    $refund_request->setPaymentId($payment->getRemoteId());
    try {
      $api_client = $this->getApiClient();
      $payment_api = $api_client->getRefundsApi();
      $payment_api_request = $payment_api->refundPayment($refund_request);
      if ($payment_api_request->isSuccess()) {
        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);
        if ($new_refunded_amount->lessThan($payment->getAmount())) {
          $payment->setState('partially_refunded');
        }
        else {
          $payment->setState('refunded');
        }

        $payment->setRefundedAmount($new_refunded_amount);
        $payment->save();
      }
      else {
        throw ErrorHelper::convertException(
          new ApiException(
            $payment_api_request->getBody(),
            $payment_api_request->getRequest()
          )
        );
      }
    }
    catch (ApiException $e) {
      throw ErrorHelper::convertException($e);
    }
  }

  /**
   * Maps the Square credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Square credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType(string $card_type) {
    $map = [
      'AMERICAN_EXPRESS' => 'amex',
      'CHINA_UNIONPAY' => 'unionpay',
      'DISCOVER_DINERS' => 'dinersclub',
      'DISCOVER' => 'discover',
      'JCB' => 'jcb',
      'MASTERCARD' => 'mastercard',
      'VISA' => 'visa',
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

}
