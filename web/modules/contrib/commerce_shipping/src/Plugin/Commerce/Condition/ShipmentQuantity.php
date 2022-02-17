<?php

namespace Drupal\commerce_shipping\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\commerce_price\Calculator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the quantity condition for shipments.
 *
 * @CommerceCondition(
 *   id = "shipment_quantity",
 *   label = @Translation("Shipment quantity"),
 *   category = @Translation("Shipment"),
 *   entity_type = "commerce_shipment",
 * )
 */
class ShipmentQuantity extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operator' => '>',
      'quantity' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $operator = $this->configuration['operator'];
    $quantity = $this->configuration['quantity'];

    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $this->getComparisonOperators(),
      '#default_value' => $operator,
      '#required' => TRUE,
    ];
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('quantity'),
      '#default_value' => $quantity,
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
    $this->configuration['operator'] = $values['operator'];
    $this->configuration['quantity'] = $values['quantity'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $entity;

    // Get total quantity.
    $items = $shipment->getItems();
    $quantity = 0;
    foreach ($items as $item) {
      $quantity = Calculator::add($quantity, $item->getQuantity());
    }

    switch ($this->configuration['operator']) {
      case '>=':
        return $quantity >= $this->configuration['quantity'];

      case '>':
        return $quantity > $this->configuration['quantity'];

      case '<=':
        return $quantity <= $this->configuration['quantity'];

      case '<':
        return $quantity < $this->configuration['quantity'];

      case '==':
        return $quantity == $this->configuration['quantity'];

      default:
        throw new \InvalidArgumentException("Invalid operator {$this->configuration['operator']}");
    }
  }

}
