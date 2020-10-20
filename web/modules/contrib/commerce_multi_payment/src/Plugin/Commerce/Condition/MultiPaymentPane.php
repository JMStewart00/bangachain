<?php

namespace Drupal\commerce_multi_payment\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the store condition for orders.
 *
 * @CommerceCondition(
 *   id = "commerce_multi_payment_pane",
 *   label = @Translation("Multiple Payments Pane"),
 *   display_label = @Translation("Limit by multiple payment context"),
 *   category = @Translation("Other"),
 *   entity_type = "commerce_order",
 * )
 */
class MultiPaymentPane extends ConditionBase implements ContainerFactoryPluginInterface {


  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MultiPaymentPane object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'context' => [],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['context'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Context'),
      '#default_value' => $this->configuration['context'],
      '#options' => [
        'checkout_multi_payment_pane' => $this->t('Checkout Page: Multiple Payments Pane'),
        'checkout_other' => $this->t('Checkout Page: Other Panes'),
        'admin_create_payment' => $this->t('Admin: Order Add Payment Form'),
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['context'] = array_values(array_filter($values['context']));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $current_route = $this->routeMatch->getRouteName();
    if ($current_route === 'commerce_checkout.form') {
      if (in_array('checkout_multi_payment_pane', $this->configuration['context']) && !empty($entity->is_multi_payment_pane)) {
        return TRUE;
      }
      elseif (in_array('checkout_other', $this->configuration['context']) && empty($entity->is_multi_payment_pane)) {
        return TRUE;
      }
    }
    elseif ($current_route === 'entity.commerce_payment.add_form' && in_array('admin_create_payment', $this->configuration['context'])) {
      return TRUE;
    }
    return FALSE;
  }

}
