<?php

declare(strict_types = 1);

namespace Drupal\bangachain_reports\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\Core\Controller\ControllerBase;
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
      ->condition('state', 'completed')
      ->sort('completed', 'DESC')
      ->execute();

    $orders = $this->buildRows($order_ids);

    $build['table'] = [
      [
        '#type' => 'table',
        '#header' => [
          'total_retail' => t('Total Orders'),
          'total_collected' => t('Total $ Collected'),
          'pos' => t('POS Total'),
          'payout_adjustments' => t('Payout Used'),
          'custom_adj' => t('Adjustments (GC & Fees)'),
          'cash' => t('Cash Total'),
          'pos_gift_card' => t('POS Gift Card'),
          'paypal' => t('Paypal'),
          'square' => t('Square'),
        ],
        '#rows' => [$orders['total_payments']],
        '#empty' => t('No content has been found.'),
      ]
    ];

    return [
      '#type' => '#markup',
      '#markup' => render($build),
      '#cache' => [
        'keys' => [$year . '_reports'],
        'contexts' => ['user.roles'],
        'max-age' => 1000 * 60 * 60 * 24,
        'tags' => [$year . '_reports'],
      ],
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
    $misc_adjustments = 0;
    $payout_adjustment_total = 0;

    $pos_total = 0;
    $paypal_total = 0;
    $square_total = 0;
    $cash_total = 0;
    $pos_giftcard_total = 0;

    foreach ($ids as $id) {
      $order = Order::load($id);

      $subtotal = floatval($order->getSubtotalPrice()->getNumber());
      $paid_amount = floatval($order->getTotalPaid()->getNumber());

      $total_retail += $subtotal;
      $total_collected += $paid_amount;

      if ($payment = Payment::load($id)) {
        $payment_type = $payment->bundle();
        $payment_gateway = $payment->getPaymentGatewayId();
      }
      else if ($payment_id = \Drupal::entityQuery('commerce_payment')->condition('order_id', $id)->execute()) {
        $payment = Payment::load(array_shift($payment_id));
        $payment_type = $payment->bundle();
        $payment_gateway = $payment->getPaymentGatewayId();
      }
      else {
        $payment_type = '';
        $payment_gateway = 'ALL PAYOUT';
      }

      // Payment methods logic, pos v. paypal, square, cash, unknown.
      if ($payment_type === 'payment_manual') {
        switch ($payment_gateway) {
          case 'pos_cash':
          case 'cash_payment':
            $cash_total += $paid_amount;
            break;
          case 'pos_credit':
            $square_total += $paid_amount;
            break;
          case 'pos_gift_card':
            $pos_giftcard_total += $paid_amount;
            break;

          default:
            # code...
            break;
        }

        if (substr($payment_gateway, 0, 4) === 'pos_') {
          $pos_total += $paid_amount;
        }
      }
      else {
        switch ($payment_gateway) {
          case 'square':
            $square_total += $paid_amount;
            break;
          case 'paypal':
            $paypal_total += $paid_amount;
            break;
          default:
            break;
        }
      }

      $total_adjustments = 0;
      if ($adjustments = $order->get('adjustments')->getAdjustments()) {
        foreach ($adjustments as $adj) {
          if ($adj_total = $adj->getAmount()->getNumber()) {
            $total_adjustments += abs($adj_total);
            $adj_type = $adj->getType();

            switch ($adj_type) {
              case 'custom':
              case 'fee':
                $misc_adjustments += abs($adj_total);
                break;
              case 'commerce_giftcard':
                $payout_adjustment_total += abs($adj_total);
                break;

              default:
                # code...
                break;
            }
          }
        }
      }

      // array_push($rows, [
      //   $order->get('order_number')->value,
      //   date("m.d.y", intval($order->get('completed')->value)),
      //   $payment_type,
      //   $payment_gateway,
      //   $this::formatMoney($subtotal),
      //   $this::formatMoney($total_adjustments),
      //   $this::formatMoney($paid_amount),
      // ]);

    }
    return [
      'total_payments' => [
        'total_retail' => $this::formatMoney($total_retail),
        'total_collected' => $this::formatMoney($total_collected),
        'pos' => $this::formatMoney($pos_total),
        'payout_adj' => $this::formatMoney($payout_adjustment_total),
        'custom_adj' => $this::formatMoney($misc_adjustments),
        'cash' => $this::formatMoney($cash_total),
        'pos_gift_card' => $this::formatMoney($pos_giftcard_total),
        'paypal' => $this::formatMoney($paypal_total),
        'square' => $this::formatMoney($square_total),
      ]
    ];
  }

  /**
   * Returns money formatted number.
   *
   * @param mixed $value
   */
  protected static function formatMoney($value): string {
    return '$' . number_format(floatval($value), 2);
  }
}
