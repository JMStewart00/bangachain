<?php

namespace Drupal\commerce_square\PluginForm\Square;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\commerce_order\Plugin\Commerce\InlineForm\CustomerProfile;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Square\Environment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Payment method add form for Square.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * The 'commerce_square.settings' config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->config = $container->get('config.factory')->get('commerce_square.settings');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\Square $plugin */
    $plugin = $this->plugin;
    if ($plugin->collectsBillingInformation()) {
      $billing_information_inline_form = $form['billing_information']['#inline_form'];
      assert($billing_information_inline_form instanceof CustomerProfile);
      $billing_profile = $billing_information_inline_form->getEntity();
      if (!$billing_profile->get('address')->isEmpty()) {
        $address = $billing_profile->get('address')->first();
        assert($address instanceof AddressItem);
        $postalCode = ($address->getPostalCode()) ? $address->getPostalCode() : null;
        $form['#attached']['drupalSettings']['commerceSquare']['customerPostalCode'] = $postalCode;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\Square $plugin */
    $plugin = $this->plugin;
    $configuration = $plugin->getConfiguration();
    $api_mode = ($configuration['mode'] == 'test') ? Environment::SANDBOX : Environment::PRODUCTION;

    $element['#attached']['library'][] = 'commerce_square/form';
    $element['#attached']['drupalSettings']['commerceSquare'] = [
      'applicationId' => $this->config->get($api_mode . '_app_id'),
      'apiMode' => $api_mode,
      'drupalSelector' => 'edit-' . str_replace('_', '-', implode('-', $element['#parents'])),
    ];
    $element['#attributes']['class'][] = 'square-form';
    // Populated by the JS library.
    $element['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-nonce']],
    ];
    $element['card_type'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-card-type']],
    ];
    $element['last4'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-last4']],
    ];
    $element['exp_month'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-exp-month']],
    ];
    $element['exp_year'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-exp-year']],
    ];

    // Display credit card logos in checkout form.
    if ($plugin->getConfiguration()['enable_credit_card_icons']) {
      $element['#attached']['library'][] = 'commerce_square/credit_card_icons';
      $element['#attached']['library'][] = 'commerce_payment/payment_method_icons';

      $supported_credit_cards = [];
      foreach ($plugin->getCreditCardTypes() as $credit_card) {
        $supported_credit_cards[] = $credit_card->getId();
      }

      $element['credit_card_logos'] = [
        '#theme' => 'commerce_square_credit_card_logos',
        '#credit_cards' => $supported_credit_cards,
      ];
    }

    $element['number'] = [
      '#type' => 'item',
      '#title' => t('Card number'),
      '#markup' => '<div id="square-card-number"></div>',
    ];

    $element['details'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'credit-card-form__expiration',
        ],
      ],
    ];
    $element['details']['expiration'] = [
      '#type' => 'item',
      '#title' => t('Expiration'),
      '#markup' => '<div id="square-expiration-date"></div>',
    ];
    $element['details']['cvv'] = [
      '#type' => 'item',
      '#title' => t('CVV'),
      '#markup' => '<div id="square-cvv"></div>',
    ];
    $element['details']['postal-code'] = [
      '#type' => 'item',
      '#markup' => '<div id="square-postal-code"></div>',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

}
