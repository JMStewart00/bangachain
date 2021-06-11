<?php

namespace Drupal\commerce_loyalty_points\EventSubscriber;

use Drupal\commerce_loyalty_points\Entity\LoyaltyPoints;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Increase or decrease the loyalty points for a user.
 */
class AddUserLoyaltyPoints implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ModuleHandlerInterface $moduleHandler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['adjustLoyaltyPointsOnOrderComplete'];

    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   WorkflowTransitionEvent object.
   */
  public function adjustLoyaltyPointsOnOrderComplete(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $items = $order->getItems();
    $currency_code = $order->getStore()->getDefaultCurrencyCode();

    foreach ($items as $item) {
      $user_id = $item->getOrder()->get('uid')->target_id;
      $subscribed = User::load($user_id)->hasRole('loyalty_points_subscriber');

      // Only continue if this is a subscribed user.
      if ($subscribed) {
        if ($item->getOrder()->getState()->getValue()['value'] == 'completed') {
          $purchased_entity = $item->getPurchasedEntity();
          $quantity = $item->getQuantity();
          $total_price = $item->getTotalPrice();
          $adjustments = $item->getAdjustments();

          // Continue only if loyalty points are given by the admin.
          $loyalty_points_multiplier = $purchased_entity->field_loyalty_points->value;
          if (!empty($loyalty_points_multiplier)) {
            $reason = t('Purchased @quantity @unit of @product with @points loyalty @point_unit for every @currency spent. Order ID: @order_id', [
              '@quantity' => $quantity,
              '@unit' => ($quantity == 1) ? 'unit' : 'units',
              '@product' => $purchased_entity->getOrderItemTitle(),
              '@points' => $loyalty_points_multiplier,
              '@point_unit' => ($loyalty_points_multiplier == 1) ? 'point' : 'points',
              '@currency' => $currency_code,
              '@order_id' => $order->id(),
            ]);

            // Remove adjustments from total price.
            $total_adjustments = new Price(0, $currency_code);
            foreach ($adjustments as $adjustment) {
              $total_adjustments = $total_adjustments->add($adjustment->getAmount());
            }
            $actual_price = $total_price->subtract($total_adjustments);
            $loyalty_points = $actual_price->multiply($loyalty_points_multiplier);

            // Allow other modules to alter loyalty points.
            $operation = 'add';
            $this->moduleHandler->alter('loyalty_points', $operation, $loyalty_points);

            $add_loyalty_points = LoyaltyPoints::create([
              'uid' => $user_id,
              'loyalty_points' => $loyalty_points->getNumber(),
              'reason' => $reason,
              'created' => time(),
            ]);
            $add_loyalty_points->save();
          }
        }
      }
    }

    // Add negative loyalty points when a promo code is applied.
    if (isset($order->toArray()['coupons'][0]['target_id'])) {
      $coupon_id = $order->toArray()['coupons'][0]['target_id'];
      $coupon = Coupon::load($coupon_id);
      $promo_id = $coupon->getPromotionId();
      $promo = Promotion::load($promo_id)->toArray();

      foreach ($promo['conditions'] as $key => $value) {
        if ($value['target_plugin_id'] == 'order_loyalty_points') {

          // Calculate loyalty points to deduct.
          $deduct_points = $value['target_plugin_configuration']['min_loyalty_points'];
          $deduct_points = new Price($deduct_points, $currency_code);

          // Prepare reason.
          $reason = t('Deducted @loyalty_points points for a promotion code used on @created. Order ID: @order_id', [
            '@loyalty_points' => $deduct_points->getNumber(),
            '@promo' => strtoupper(''),
            '@created' => date('m/d/Y', time()),
            '@order_id' => $order->id(),
          ]);
          $deduct_points = $deduct_points->multiply('-1');

          // Allow other modules to alter loyalty points.
          $operation = 'deduct';
          $this->moduleHandler->alter('loyalty_points', $operation, $deduct_points);

          $add_negative_loyalty_points = LoyaltyPoints::create([
            'uid' => $order->get('uid')->target_id,
            'loyalty_points' => $deduct_points->getNumber(),
            'reason' => $reason,
            'created' => time(),
          ]);
          $add_negative_loyalty_points->save();
          break;
        }
      }
    }
  }

}
