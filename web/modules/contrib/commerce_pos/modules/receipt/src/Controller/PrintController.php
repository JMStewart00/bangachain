<?php

namespace Drupal\commerce_pos_receipt\Controller;

use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_pos_receipt\Ajax\CompleteOrderCommand;
use Drupal\commerce_pos_receipt\Ajax\PrintReceiptCommand;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PrintController.
 */
class PrintController extends ControllerBase {

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * Constructs a new AdjustmentTransformer object.
   *
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   */
  public function __construct(AdjustmentTransformerInterface $adjustment_transformer) {
    $this->adjustmentTransformer = $adjustment_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('commerce_order.adjustment_transformer'));
  }

  /**
   * A controller callback.
   */
  public function ajaxReceipt(OrderInterface $commerce_order, $print_or_email) {
    $renderer = \Drupal::service('renderer');

    $build = $this->showReceipt($commerce_order);
    unset($build['#receipt']['print']);
    $build = $renderer->render($build);

    $response = new AjaxResponse();

    // If the user opted to get an email with the receipt.
    if ($print_or_email == 'email' || $print_or_email == 'print_and_email') {
      $this->sendEmailReceipt($commerce_order, $build);

      // Finally, if the user only wants an email to be sent, we just call the
      // complete order command which submits the form as usual.
    }

    // If the user opted to print the receipt.
    if ($print_or_email == 'print' || $print_or_email == 'print_and_email') {
      $module_handler = \Drupal::service('module_handler');
      $module_path = $module_handler->getModule('commerce_pos_receipt')->getPath();

      $response->addCommand(new HtmlCommand('#commerce-pos-receipt', $build));
      $response->addCommand(new SettingsCommand([
        'commercePosReceipt' => [
          'cssUrl' => Url::fromUri('base:' . $module_path . '/css/commerce_pos_receipt_print.css', ['absolute' => TRUE])->toString(),
        ],
      ], TRUE));
      $response->addCommand(new PrintReceiptCommand('#commerce-pos-receipt'));
    }

    if ($print_or_email == 'email' || $print_or_email == 'none') {
      $response->addCommand(new CompleteOrderCommand());
    }

    return $response;
  }

  /**
   * A controller callback.
   */
  public function showReceipt(OrderInterface $commerce_order) {
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $sub_total_price = $commerce_order->getSubtotalPrice();
    $formatted_amount = $currency_formatter->format($sub_total_price->getNumber(), $sub_total_price->getCurrencyCode());

    // In the future add a setting to display group or individual for same skus.
    $has_return_items = FALSE;
    $items = $commerce_order->getItems();
    foreach ($items as $item) {
      $totals[] = [
        $item->getTitle() . ' (' . $item->getQuantity() . ')',
        $currency_formatter->format($item->getAdjustedTotalPrice()->getNumber(), $sub_total_price->getCurrencyCode()),
      ];

      // Set a flag if we have return item types.
      if ($item->type->getValue()[0]['target_id'] == 'return') {
        $has_return_items = TRUE;
      }
    }

    $totals[] = ['----------', '----------'];
    $totals[] = ['SUBTOTAL', $formatted_amount];
    $totals[] = ['----------', '----------'];

    // Collect Adjustments/Taxes.
    $adjustments = $commerce_order->collectAdjustments();
    $adjustments = $this->adjustmentTransformer->processAdjustments($adjustments);
    foreach ($adjustments as $adjustment) {
      if (!empty($adjustment)) {
        $amount = $adjustment->getAmount();
        $formatted_amount = $currency_formatter->format($amount->getNumber(), $amount->getCurrencyCode());
        $totals[] = [
          $adjustment->getLabel(),
          $formatted_amount,
        ];
      }
    }

    // Collecting the total price on the cart.
    $total_price = $commerce_order->getTotalPrice();
    $formatted_amount = $currency_formatter->format($total_price->getNumber(), $total_price->getCurrencyCode());
    $totals[] = ['----------', '----------'];
    $totals[] = [
      'TOTAL',
      $formatted_amount,
    ];
    $totals[] = ['----------', '----------'];

    $total_payment = 0;
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($commerce_order);
    foreach ($payments as $payment) {
      $amount = $payment->getAmount();
      $total_payment += $payment->getBalance()->getNumber();

      $rendered_amount = $payment->getState()->value === 'voided' ? $this->t('VOIDED') : $currency_formatter->format($amount->getNumber(), $amount->getCurrencyCode());
      $totals[] = [$payment->getPaymentGateway()->label(), $rendered_amount];
    }

    $totals[] = ['----------', '----------'];
    $totals[] = [
      'TENDER',
      $currency_formatter->format($total_payment, $sub_total_price->getCurrencyCode()),
    ];

    $ajax_url = URL::fromRoute('commerce_pos_receipt.ajax', ['commerce_order' => $commerce_order->id()], [
      'attributes' => [
        'class' => ['use-ajax', 'button'],
      ],
    ]);

    $config = \Drupal::config('commerce_pos_receipt.settings');
    $build = ['#theme' => 'commerce_pos_receipt'];
    $build['#receipt'] = [
      'title' => $has_return_items ? $this->t('Return Receipt for Order #@order_id', ['@order_id' => $commerce_order->id()]) : $this->t('Receipt for Order #@order_id', ['@order_id' => $commerce_order->id()]),
      'header' => [
        '#markup' => check_markup($config->get('header'), $config->get('header_format')),
      ],
      'body' => [
        '#type' => 'table',
        '#header' => [
          'Item',
          'Total',
        ],
        '#rows' => $totals,
      ],
      'footer' => [
        '#markup' => check_markup($config->get('footer'), $config->get('footer_format')),
      ],
      'timestamp' => [
        '#markup' => DrupalDateTime::createFromTimestamp(time())->format("c"),
      ],
      'print' => [
        '#title' => $this->t('Print receipt'),
        '#prefix' => '<div id="commerce-pos-receipt"></div>',
        '#type' => 'link',
        '#url' => $ajax_url,
      ],
    ];
    if ($commerce_order->hasField('field_cashier')) {
      if (!empty($commerce_order->get('field_cashier')->getValue()[0]['target_id'])) {
        $cashier_id = $commerce_order->get('field_cashier')->getValue()[0]['target_id'];
        if (!empty($cashier_id)) {
          $build['#receipt']['cashier']['#markup'] = User::load($cashier_id)->getDisplayName();
        }
      }
    }

    return $build;
  }

  /**
   * Sends an email with the order receipt.
   *
   * @param object $commerce_order
   *   The order entity.
   * @param string $build
   *   The receipt markup.
   */
  public function sendEmailReceipt($commerce_order, $build) {
    $renderer = \Drupal::service('renderer');

    // Send an email with the receipt.
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $module = 'commerce_pos_receipt';
    $key = 'commerce_pos_order_receipt';
    $to = $commerce_order->getEmail();
    $customer = $commerce_order->getCustomer();
    $themed_email_message = [
      '#theme' => 'commerce-pos-receipt-email',
      '#customer_name' => $customer->getAccountName(),
      '#order_id' => $commerce_order->id(),
      '#receipt_markup' => $build,
      '#site_name' => \Drupal::config('system.site')->get('name'),
    ];
    $params['from'] = \Drupal::config('system.site')->get('mail');
    if (!empty($commerce_order->getStore())) {
      $params['from'] = $commerce_order->getStore()->getEmail();
    }
    $params['message'] = $renderer->render($themed_email_message);
    $params['order_id'] = $commerce_order->id();
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;

    // Officially, send the email.
    $result = $mail_manager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    // If there was a problem sending the email.
    if ($result['result'] !== TRUE) {
      $message = $this->t('There was a problem sending the email to @mail.', [
        '@mail' => $commerce_order->getEmail(),
      ]);

      $this->messenger()->addError($message);
      \Drupal::logger('commerce_pos_receipt')->error($message);

      return;
    }

    $message = $this->t('An email with the receipt has been successfully sent to @mail.', [
      '@mail' => $commerce_order->getEmail(),
    ]);

    $this->messenger()->addStatus($message);
    \Drupal::logger('commerce_pos_receipt')->notice($message);
  }

  /**
   * Checks if the receipt should be printable. The order needs to be placed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(OrderInterface $commerce_order) {
    return AccessResult::allowedIf($this->currentUser()->hasPermission('access commerce pos pages') && $commerce_order->getPlacedTime())->cachePerPermissions()->cacheUntilEntityChanges($commerce_order);
  }

}
