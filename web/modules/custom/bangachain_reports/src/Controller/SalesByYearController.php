<?php

declare(strict_types = 1);

namespace Drupal\bangachain_reports\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sales By Year controller.
 */
class SalesByYearController extends ControllerBase {

  /**
   * Returns a render-able array for a people clean up page.
   */
  public function content(Request $request): array {
    $year = $request->attributes->get('_raw_variables')->get('year');
    $start_unix_date = strtotime($year . '-01-01 00:00:00');
    $end_unix_date = strtotime($year . '-12-31 11:59:59');

    $order_ids = \Drupal::entityTypeManager()
      ->getStorage('commerce_order')
      ->getQuery()
      ->condition('completed', $start_unix_date, '>=')
      ->condition('completed', $end_unix_date, '<=')
      ->execute();

    $orders = $this->buildRows($order_ids);

    $build['table'] = [
      [
        '#type' => 'table',
        '#header' => [
          'total_retail' => t('total_retail'),
          'total_collected' => t('total_collected'),
          'custom_adj' => t('custom_adj'),
          'fee_adjustments' => t('fee_adjustments'),
          'payout_adjustments' => t('payout_adjustments'),
          'pos' => t('pos'),
          'paypal' => t('paypal'),
          'square' => t('square'),
          'cash' => t('cash'),
          'unknown' => t('unknown'),
        ],
        '#rows' => [$orders['total_payments']],
        '#empty' => t('No content has been found.'),
      ],
      [
        '#type' => 'table',
        '#header' => [
          'order' => t('Order'),
          'order_type' => t('Order Type'),
          'subtotal' => t('Subtotal'),
          'adjustments' => t('Payment Adjustments'),
          'total' => t('Paid Amount'),
        ],
        '#rows' => $orders['rows'],
        '#empty' => t('No content has been found.'),
      ],
    ];

    return [
      '#type' => '#markup',
      '#markup' => render($build),
    ];
  }

  /**
   * Returns an array or rows for the table.
   */
  protected function buildRows(array $ids): array {
    // create a page, add people to it, comment out provider module lines, delete/unpublish people on that list, rerun query.

    $rows = [];
    $total_retail = 0;
    $total_collected = 0;
    $custom_adjustment_total = 0;
    $fee_adjustment_total = 0;
    $payout_adjustment_total = 0;

    $pos_total = 0;
    $paypal_total = 0;
    $square_total = 0;
    $cash_total = 0;
    $unknown_total = 0;

    foreach ($ids as $id) {
      $order = Order::load($id);

      $payment_type = '';
      if ($payment = $order->get('payment_gateway')->entity) {
        $payment_type = $payment->id();
      } else {
        $payment_type = $order->get('type')->entity->id();
      }

      if ($adjustments = $order->get('adjustments')->getAdjustments()) {
        $total_adjustments = 0;
        foreach ($adjustments as $adj) {
          if ($adj_total = $adj->getAmount()->getNumber()) {
            $total_adjustments += $adj_total;
            $adj_type = $adj->getType();
            $payment_type .= ', ' . $adj_type;

            switch ($adj_type) {
              case 'custom':
                $custom_adjustment_total += $adj_total;
                break;
              case 'fee':
                $fee_adjustment_total += $adj_total;
                break;
              case 'commerce_giftcard':
                $payout_adjustment_total += $adj_total;
                break;

              default:
                # code...
                break;
            }
          }
        }
      }

      $total_retail += $order->getSubtotalPrice()->getNumber();
      $total_collected += $order->getTotalPaid()->getNumber();

      if ($order->get('payment_gateway')->entity) {
        $payment_type = $order->get('payment_gateway')->entity->id();

        switch ($payment) {
          case 'cash_payment':
            $cash_total += $order->getTotalPaid()->getNumber();
            break;
          default:
            # code...
            break;
        }
      }

      // $order_items = $order->get('order_items')->referencedEntities();
      // foreach ($order_items as $oi) {
      //   $purchased_entity = $oi->getPurchasedEntityId();
      //   dd(Product::load($purchased_entity));
      //   dd($purchased_entity);
      // }
      // dd($order_items);

      array_push($rows, [
        $order->get('order_number')->value,
        $payment_type,
        $order->getSubtotalPrice(),
        $total_adjustments,
        $order->getTotalPaid()
      ]);
    }
    return [
      'rows' => $rows,
      'total_payments' => [
        'total_retail' => $total_retail,
        'total_collected' => $total_collected,
        'custom_adj' => $custom_adjustment_total,
        'fee_adj' => $fee_adjustment_total,
        'payout_adj' => $payout_adjustment_total,
        'pos' => 0,
        'paypal' => 0,
        'square' => 0,
        'cash' => 0,
        'unknown' => 0,
      ]
    ];
  }

}
