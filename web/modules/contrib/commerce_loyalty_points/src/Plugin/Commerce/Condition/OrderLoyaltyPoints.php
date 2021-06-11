<?php

namespace Drupal\commerce_loyalty_points\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides the loyalty points condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_loyalty_points",
 *   label = @Translation("Loyalty points redemption"),
 *   display_label = @Translation("Limit by loyalty points"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderLoyaltyPoints extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'min_loyalty_points' => NULL,
      'loyalty_points_usage_interval' => 'month',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Usage options.
    $options = [
      'week' => t('Week'),
      'month' => t('Month'),
      'quarter' => t('Quarter'),
      'six_months' => t('6 months'),
      'year' => t('Year'),
      'no_restriction' => t('No restriction'),
    ];

    $form['min_loyalty_points'] = [
      '#type' => 'textfield',
      '#title' => t('Minimum loyalty points required to avail this offer'),
      '#default_value' => $this->configuration['min_loyalty_points'],
      '#description' => t('This many points will be deducted from customer\'s accumulated loyalty points.'),
      '#required' => TRUE,
    ];
    $form['loyalty_points_usage_interval'] = [
      '#type' => 'select',
      '#title' => t('A cutomer can redeem loyalty points every'),
      '#options' => $options,
      '#default_value' => $this->configuration['loyalty_points_usage_interval'],
      '#description' => t('This will restrict a customer from using loyalty points more than once in a given calendar period. For example, Every Month means if a coupon was redeemed on January 31, another can be redeemed on February 1 for the month of february.'),
      '#required' => TRUE,
    ];

    $form['loyalty_points_usage_note'] = [
      '#type' => 'item',
      '#title' => t('NOTE: Loyalty points redemption is only valid for current calendar year - January to December - and cannot be carried forward. However, customer accumulated points will be carried forward from year to year.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['min_loyalty_points'] = $values['min_loyalty_points'];
    $this->configuration['loyalty_points_usage_interval'] = $values['loyalty_points_usage_interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $customer_id = $order->getCustomerId();

    // Check if this customer is enrolled for Loyalty points program.
    if ($customer_id) {
      $account = User::load($customer_id);
      if ($account->hasRole('loyalty_points_subscriber')) {
        $min_required_points = $this->configuration['min_loyalty_points'];
        $interval = $this->configuration['loyalty_points_usage_interval'];

        /** @var \Drupal\commerce_loyalty_points\LoyaltyPointsStorageInterface $loyalty_points_storage */
        $loyalty_points_storage = \Drupal::entityTypeManager()->getStorage('commerce_loyalty_points');

        // Check if this customer is eligible.
        if ($loyalty_points_storage->isEligibleCustomer($customer_id, $interval)) {
          $available_loyalty_points = $loyalty_points_storage->loadAndAggregateUserLoyaltyPoints($customer_id);
          return ($available_loyalty_points >= $min_required_points);
        }
      }
    }
    return FALSE;
  }

}
