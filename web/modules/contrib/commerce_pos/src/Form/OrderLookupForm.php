<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an order lookup form to search orders.
 */
class OrderLookupForm extends FormBase {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OrderLookupForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The current user's account proxy.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(AccountProxyInterface $account_proxy, RendererInterface $renderer) {
    $this->currentUser = $account_proxy->getAccount();
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_order_lookup';
  }

  /**
   * Build the order lookup form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'commerce_pos/global';
    $form['#attached']['library'][] = 'commerce_pos/order_lookup';
    // The order search elements.
    $form['order_lookup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order Lookup'),
    ];

    // The search box to look up by order number, customer name or email.
    $form['order_lookup']['search_box'] = [
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#size' => 25,
      '#description' => $this->t('Search by order number, customer name or customer email.'),
      '#ajax' => [
        'callback' => '::orderLookupAjaxRefresh',
        'event' => 'input',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Searching orders...'),
        ],
      ],
    ];

    // Display the results of the lookup below.
    $form['order_lookup']['results'] = [
      '#type' => 'container',
      '#prefix' => '<div id="order-lookup-results">',
      '#suffix' => '</div>',
    ];

    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element)) {
      $form['order_lookup']['results']['result'] = $this->searchOrderResults($form_state);
    }

    return $form;
  }

  /**
   * Submit callback for the order lookup form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit actually needed as this form is ajax refresh only.
  }

  /**
   * Ajax callback for the order lookup submit button.
   */
  public function orderLookupAjaxRefresh(array $form, FormStateInterface &$form_state) {
    $search_text = $form_state->getValue('search_box');

    $results = $this->searchOrderResults($form_state, $search_text);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#order-lookup-results', $results));

    return $response;
  }

  /**
   * Looks up an order based on a search criteria and returns the results.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   FormState passed on for hook_form_alter to work.
   * @param string $search_text
   *   The search criteria. Could be an order ID, customer name, or email.
   * @param string $state
   *   (optional) The order state to match. Defaults to 'draft'.
   * @param string $operator
   *   (optional) The operator to use when matching on state. Defaults to '!='.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $empty_message
   *   (optional) A translated search string to display if no results are
   *   returned.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|array
   *   The render array or a translatable string.
   */
  public function searchOrderResults(FormStateInterface $form_state, $search_text = '', $state = 'draft', $operator = '!=', TranslatableMarkup $empty_message = NULL) {
    $result_limit = $this->config('commerce_pos.settings')->get('order_lookup_limit');

    // Create the query now.
    // If we're doing a like search, form the query differently.
    $query = \Drupal::entityQuery('commerce_order');
    $query->condition('state', $state, $operator)
      ->exists('field_cashier')
      ->exists('field_register')
      ->sort('order_id', 'DESC')
      ->range(0, !empty($result_limit) ? $result_limit : 10);

    if ($search_text) {
      // If the search text was an order ID.
      if (is_numeric($search_text)) {
        $query->condition('order_id', $search_text);
      }
      // Else, we check if we have a matching customer name or email.
      else {
        $conditions = $query->orConditionGroup();
        $conditions->condition('uid.entity.name', '%' . $search_text . '%', 'LIKE')
          ->condition('uid.entity.mail', '%' . $search_text . '%', 'LIKE')
          ->condition('field_cashier.entity.name', '%' . $search_text . '%', 'LIKE')
          ->condition('field_cashier.entity.mail', '%' . $search_text . '%', 'LIKE')
          ->condition('mail', '%' . $search_text . '%', 'LIKE');

        $query->condition($conditions);
      }
    }

    $result = $query->execute();

    // If we've got results, let's output the details in a table.
    if (!empty($result)) {
      $orders = Order::loadMultiple($result);
      return $this->buildOrderTable($form_state, $orders);
    }

    if ($search_text) {
      return $this->t('The order could not be found or does not exist.');
    }

    if ($empty_message) {
      return $empty_message;
    }

    $order_markup = $this->t('There are currently no POS orders.');
    // Convert into something renderable.
    return ['#markup' => $order_markup];
  }

  /**
   * Return a themed table with the order details.
   *
   * @param \Drupal\Core\FormStateInterface $form_state
   *   Formstate passed on for hook_alter.
   * @param array $orders
   *   An array of order entities.
   *
   * @return string
   *   The markup for the themed table.
   */
  public function buildOrderTable(FormStateInterface $form_state, array $orders) {
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $header = [
      $this->t('Order ID'),
      $this->t('Date'),
      $this->t('Status'),
      $this->t('Cashier'),
      $this->t('Customer'),
      $this->t('Contact Email'),
      $this->t('Total'),
      $this->t('Operations'),
    ];

    $rows = [];
    $list_order_ids = [];
    foreach ($orders as $order) {

      /* @var \Drupal\commerce_order\Entity\Order $order */
      // The link to the order.
      $order_url = Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order->id()], [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);

      $cashier = User::load($order->get('field_cashier')
        ->getValue()[0]['target_id']);

      if (isset($cashier)) {
        $cashier_url = Url::fromRoute('entity.user.canonical', [
          'user' => $cashier->id(),
        ], [
          'attributes' => [
            'target' => '_blank',
          ],
        ]);

        $cashier_name = $cashier->getDisplayName();
      }

      $customer_url = Url::fromRoute('entity.user.canonical', [
        'user' => $order->getCustomer()
          ->id(),
      ], [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);

      // Format the total price of the order.
      $store = $order->getStore();
      $default_currency = $store->getDefaultCurrency();
      $total_price = $order->getTotalPrice();
      if (!empty($total_price)) {
        $formatted_amount = $currency_formatter->format($total_price->getNumber(), $total_price->getCurrencyCode());
      }
      else {
        $formatted_amount = $currency_formatter->format('0', $default_currency->getCurrencyCode());
      }

      // Form the customer link and email.
      $customer = [
        '#type' => 'inline_template',
        '#template' => '{{ user_link }} <br \> {{ user_email }}',
        '#context' => [
          'user_link' => Link::fromTextAndUrl($order->getCustomer()->getDisplayName(), $customer_url),
          'user_email' => $order->getCustomer()->getEmail(),
        ],
      ];

      // Build operations row.
      $operations = [
        '#type' => 'operations',
        '#links' => [],
      ];
      // The link to the POS order edit page.
      if ($order->access('update')) {
        $edit_url = Url::fromRoute('commerce_pos.main', ['commerce_order' => $order->id()]);
        $operations['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => $edit_url,
        ];
      }

      // Now, add each row to the rows array.
      $rows[] = [
        'order_url' => Link::fromTextAndUrl($order->id(), $order_url),
        'date' => DrupalDateTime::createFromTimestamp($order->getChangedTime())->format('Y-m-d H:i'),
        'status' => $order->getState()->getLabel(),
        'cashier' => isset($cashier) ? Link::fromTextAndUrl($cashier_name, $cashier_url) : '',
        'customer' => ['data' => $customer],
        'customer_email' => $order->getEmail(),
        'total' => $formatted_amount,
        'operations' => ['data' => $operations],
      ];

      $list_order_ids[] = $order->id();
    }

    $form_state->set('order_ids', $list_order_ids);

    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $output['pager'] = [
      '#type' => 'pager',
    ];

    return $output;
  }

}
