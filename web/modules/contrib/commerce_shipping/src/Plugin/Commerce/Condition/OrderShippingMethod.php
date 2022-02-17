<?php

namespace Drupal\commerce_shipping\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the shipping method condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_shipping_method",
 *   label = @Translation("Shipping method"),
 *   display_label = @Translation("Shipping method"),
 *   category = @Translation("Shipment"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderShippingMethod extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityUuidMapper = $container->get('commerce.entity_uuid_mapper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The shipping method UUIDS.
      'shipping_methods' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Map the UUIDs back to IDs for the form element.
    $shipping_method_ids = $this->entityUuidMapper->mapToIds('commerce_shipping_method', $this->configuration['shipping_methods']);
    $form['shipping_methods'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Shipping methods'),
      '#default_value' => $shipping_method_ids,
      '#target_type' => 'commerce_shipping_method',
      '#hide_single_entity' => FALSE,
      '#multiple' => TRUE,
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
    $this->configuration['shipping_methods'] = $this->entityUuidMapper->mapFromIds('commerce_shipping_method', $values['shipping_methods']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    if (!$order->hasField('shipments') ||
      $order->get('shipments')->isEmpty()) {
      // The shipping method is not known yet, the condition cannot pass.
      return FALSE;
    }
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $order->get('shipments')->referencedEntities();
    $shipment = reset($shipments);
    $shipping_method = $shipment->getShippingMethod();

    if (!$shipping_method) {
      return FALSE;
    }

    return in_array($shipping_method->uuid(), $this->configuration['shipping_methods'], TRUE);
  }

}
