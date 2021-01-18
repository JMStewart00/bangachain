<?php

namespace Drupal\commerce_dashboard\Plugin\rest\resource;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the dashboard data Resource.
 *
 * @RestResource(
 *   id = "commerce_dashboard",
 *   label = @Translation("Commerce dashboard data"),
 *   uri_paths = {
 *     "canonical" = "/commerce_dashboard/data"
 *   }
 * )
 */
class CommerceDashboardResource extends ResourceBase {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity type manager service instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default commerce store entity.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $defaultStore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->defaultStore = $container->get('commerce_store.default_store_resolver')->resolve();
    return $instance;
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The rest resource response.
   */
  public function get() {
    $sales = $this->getSalesData();
    $top_products = $this->getTopProducts();
    $best_selling_products = $this->bestSellingProducts(4);
    $currency_code = $this->defaultStore->getDefaultCurrencyCode();
    $response = [
      'sales' => $sales,
      'topProducts' => $top_products,
      'currencyCode' => $currency_code,
      'bestSelling' => $best_selling_products,
    ];
    return new ResourceResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function getSalesData() {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $query = $order_storage->getQuery();
    $query->condition('state', 'completed');
    $query->condition('placed', strtotime('now -14 days'), '>=');
    $query->accessCheck(FALSE);
    $order_ids = $query->execute();
    $order_storage = $order_storage->loadMultiple($order_ids);
    $totals = [
      'today' => 0,
      'yesterday' => 0,
      'prevYesterday' => 0,
      'week' => 0,
      'prevWeek' => 0,
      'lineChart' => [],
    ];
    $day = strtotime('now -14 days');
    do {
      $totals['lineChart'][date('Y-m-d', $day)] = 0;
      $day++;
    } while ($day < time());

    foreach ($order_storage as $order) {
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d')) {
        $totals['today'] += $order->getTotalPrice()->getNumber();
      }
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d', strtotime('yesterday'))) {
        $totals['yesterday'] += $order->getTotalPrice()->getNumber();
      }
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d', strtotime('now -2 days'))) {
        $totals['prevYesterday'] += $order->getTotalPrice()->getNumber();
      }
      if ($order->getPlacedTime() > strtotime('now -7 days')) {
        $totals['week'] += $order->getTotalPrice()->getNumber();
      }
      else {
        $totals['prevWeek'] += $order->getTotalPrice()->getNumber();
      }
      $totals['lineChart'][date('Y-m-d', $order->getPlacedTime())] += $order->getTotalPrice()->getNumber();
    }

    return [
      'today' => [
        'amount' => $totals['today'],
        'changeIndicator' => ($totals['today'] > $totals['yesterday']) ? 'increased' : 'decreased',
        'changePercentage' => '-',
      ],
      'yesterday' => [
        'amount' => $totals['yesterday'],
        'changeIndicator' => ($totals['yesterday'] > $totals['prevYesterday']) ? 'increased' : 'decreased',
        'changePercentage' => '-',
      ],
      'week' => [
        'amount' => $totals['week'],
        'changeIndicator' => ($totals['week'] > $totals['prevWeek']) ? 'increased' : 'decreased',
        'changePercentage' => '-',
      ],
      'lineChart' => $totals['lineChart'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTopProducts() {
    $query = $this->database->select('commerce_order', 'commerce_order');
    $query->addJoin('LEFT', 'commerce_order_item', 'order_item', 'order_item.order_id = commerce_order.order_id');
    $query->fields('order_item', ['purchased_entity', 'title']);
    $query->addExpression('SUM(order_item.total_price__number)', 'total');
    $query->addExpression('SUM(quantity)', 'purchases');
    $query->groupBy('order_item.purchased_entity, order_item.title');
    $query->condition('commerce_order.placed', strtotime('now -14 days'), '>=');
    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }


    /**
   * {@inheritdoc}
   */
  public function bestSellingProducts($number_of_products) {
    $query = $this->database->select('commerce_order', 'co')
      ->fields('pvfd', ['product_id']);
    $count_field = $query->addExpression('COUNT(pvfd.product_id)', 'count');
    $query->leftJoin('commerce_order_item', 'coi', 'co.order_id=coi.order_id');
    $query->leftJoin('commerce_product_variation_field_data', 'pvfd', 'coi.purchased_entity=pvfd.variation_id');
    $query->condition('co.state', 'completed');
    $query->isNotNull('pvfd.product_id');
    $query->groupBy('pvfd.product_id');
    $query->orderBy($count_field, 'DESC');

    $product_ids = $query->execute()->fetchCol();

    $products = $this->isPublishedProducts($product_ids, $number_of_products);

    return $products;
  }

  /**
   * Get published products.
   */
  private function isPublishedProducts($product_ids, $number_of_products) {
    $products = [];
    foreach ($product_ids as $product_id) {
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $product = $this->entityTypeManager->getStorage('commerce_product')
        ->load($product_id);

      if ($product instanceof ProductInterface && $product->isPublished()) {
        $products[$product->id()] = $product;
      }
      if (count($products) >= $number_of_products) {
        break;
      }
    }
    return $products;
  }
}
