<?php

declare(strict_types = 1);

namespace Drupal\bangachain_reports\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Coupon Sales controller.
 */
class CouponSales extends ControllerBase {

  /**
   * Returns a render-able array for a people clean up page.
   */
  public function content(): array {

    $order_ids = \Drupal::entityTypeManager()
      ->getStorage('commerce_order')
      ->getQuery()
      ->condition('state', 'completed')
      ->sort('completed', 'DESC')
      ->execute();

    $promos = [];

    foreach ($order_ids as $id) {
      $order = Order::load($id);
      $subtotal = floatval($order->getSubtotalPrice()->getNumber());

      $promotion_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
      $applied_promotions = [];

      foreach ($order->collectAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'promotion') {
          $applied_promotions[] = $promotion_storage->load($adjustment->getSourceId());
        }
      }

      if (!empty($applied_promotions)) {
        if (!is_null($applied_promotions[0])) {
          $promo_title = $applied_promotions[0]->get('name')->getValue()[0]['value'];

          if (array_key_exists($promo_title, $promos)) {
            $each = array_merge($promos[$promo_title]['each'], [ $subtotal . ' ' . $order->id() ]);
            $promos[$promo_title] = [
              'subtotals' => $promos[$promo_title]['subtotals'] + $subtotal,
              'each' => $each,
              'count' => $promos[$promo_title]['count'] + 1,
            ];
          } else {
            $promos[$promo_title] = [
              'subtotals' => $subtotal,
              'each' => [ $subtotal . ' ' . $order->id() ],
              'count' => 1,
            ];
          }
        }
      }

    }

    $rows = [];
    foreach($promos as $promo_title => $promo) {
      array_push($rows, [
        $promo_title,
        $promo['count'],
        $promo['subtotals'],
        $promo['subtotals'] * .1,
      ]);
    }

    $build['table'] = [
      [
        '#type' => 'table',
        '#header' => [
          'coupon' => t('Coupon'),
          'orders_count' => t('# of Orders'),
          'sales_generated' => t('Total Sales'),
          '10_percent' => t('10% of Sales'),
        ],
        '#rows' => $rows,
        '#empty' => t('No content has been found.'),
      ],
    ];

    return $build;
  }
}
