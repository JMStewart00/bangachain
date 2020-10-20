<?php

namespace Drupal\commerce_multi_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_multi_payment\MultiplePaymentManagerInterface;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "multi_payment_apply",
 *   label = @Translation("Apply Multiple Payments"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class ApplyPayment extends CheckoutPaneBase implements CheckoutPaneInterface {
  
  
  /**
   * @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface
   */
  protected $multiplePaymentManager;

  /**
   * Constructs a new ApplyPayment object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multiple_payment_manager
   *   The multiple payments manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, MultiplePaymentManagerInterface $multiple_payment_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->multiplePaymentManager = $multiple_payment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('commerce_multi_payment.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function getWrapperElement() {
    return $this->configuration['wrapper_element'];
  }

  /**
   * @inheritDoc
   */
  public function getDisplayLabel() {
    return !empty($this->configuration['display_label']) ? $this->configuration['display_label'] : NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_label' => $this->t('Apply Multiple Payments'), 
        'wrapper_element' => 'fieldset',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $summary[] = $this->t('Display label: @label', ['@label' => $this->configuration['display_label']]);
    $summary[] = $this->t('Wrapper element: @element', ['@element' => $this->configuration['wrapper_element']]);
    return implode(', ', $summary);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['display_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['display_label'],
    ];
    $form['wrapper_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Element'),
      '#default_value' => $this->configuration['wrapper_element'],
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
      $this->configuration['display_label'] = $values['display_label'];
      $this->configuration['wrapper_element'] = $values['wrapper_element'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    $payment_gateways = $this->multiplePaymentManager->getMultiPaymentGateways($this->order);
    return !empty($payment_gateways);
  }
  

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $output = [];
    $payment_gateways = $this->multiplePaymentManager->getMultiPaymentGateways($this->order);
    if (!$this->order->get('staged_multi_payment')->isEmpty()) {
      foreach ($this->order->get('staged_multi_payment')->referencedEntities() as $staged_payment) {
        /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
        if (in_array($staged_payment->getPaymentGatewayId(), array_keys($payment_gateways))) {
          $output[] = $this->entityTypeManager->getViewBuilder('commerce_staged_multi_payment')->view($staged_payment);
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $payment_gateways = $this->multiplePaymentManager->getMultiPaymentGateways($this->order);
    foreach ($payment_gateways as $payment_gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      /** @var \Drupal\commerce_multi_payment\MultiplePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      
      $payment_gateway_form = [
        '#type' => 'container',
        '#element_ajax' => [
          [CheckoutFlowWithPanesBase::class, 'ajaxRefreshPanes'],
        ],
      ];
      
      $payment_gateway_form['#payment_gateway_id'] =  $payment_gateway->id();
      $pane_form[$payment_gateway->id()] = $payment_gateway_plugin->multiPaymentBuildForm($payment_gateway_form, $form_state, $complete_form, $this->order);
    }
    return $pane_form;
  }


}
