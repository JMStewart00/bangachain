<?php

// @see: web/modules/contrib/feeds/tests/modules/feeds_test_alter_source/src/EventSubscriber/CsvFeed.php
// @see: https://git.drupalcode.org/project/feeds_stock/-/blob/8.x-1.x/src/Feeds/Target/StockLevel.php
// @see: web/modules/contrib/commerce_stock/modules/field/src/Plugin/Field/FieldType/StockLevel.php
// @see: web/modules/contrib/commerce_stock/modules/field/config/schema/commerce_stock_field.schema.yml => field.value.commerce_stock_level
// @see: web/modules/contrib/commerce_stock/src/EventSubscriber/OrderEventSubscriber.php => maybe we should update stock level like here with "Transaction"
// @see: web/modules/contrib/commerce_stock/modules/field/src/Plugin/Field/FieldWidget/AbsoluteStockLevelWidget.php
// @see: https://www.drupal.org/docs/8/modules/feeds/creating-and-editing-import-feeds#overview

namespace Drupal\soft4net_commerce_feeds\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_stock\StockServiceManagerInterface;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\commerce_stock\StockTransactionsInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

use Drupal\commerce_stock\ContextCreatorTrait;
use Drupal\Core\Entity\EntityInterface;

// use \Drupal\commerce_product\Entity\ProductVariation;
// use \Drupal\commerce_product\Entity\Product;
// use \Drupal\commerce_store\Entity\Store;

/**
 * React on authors being processed.
 */
class FeedsEventSubscriber implements EventSubscriberInterface {
  use LoggerChannelTrait;
  use ContextCreatorTrait;

  //const FEED_ID = 'varianti';
  const COLUMN_PRODUCT_ID = 'product_id';
  const FIELD_STOCK_LEVEL = 'field_stock_level';

  /**
   * The stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new StockAvailabilityChecker object.
   *
   * @param \Drupal\commerce_stock\StockServiceManager $stock_service_manager
   *   The stock service manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    StockServiceManagerInterface $stockServiceManager,
    AccountInterface $currentUser
  ) {
    $this->stockServiceManager = $stockServiceManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::PROCESS_ENTITY_POSTSAVE => ['postSave'],
    ];
  }

  /**
   * Acts on postsaving an entity.
   */
  public function postSave(EntityEvent $event) {
     //$this->getLogger('soft4net')->notice(__METHOD__);

    //$this->getLogger('soft4net')->notice('$feed->getType()->id(): ' . $feed->getType()->id());
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    $entity = $event->getEntity();

    if (empty($entity->id()) || !$entity instanceof ProductVariationInterface) {
      return;
    }

    /** @var \Drupal\feeds\FeedInterface */
    $feed = $event->getFeed();

    //$this->getLogger('soft4net')->notice($feed->getType()->id() .' => '.self::FEED_ID);
    /*
    if ($feed->getType()->id() != self::FEED_ID) {
      // Not interested in this feed. Abort.
      return;
    }*/

    $mappings = $feed->getType()->getMappings();
    $source_stock_level = '';

    foreach($mappings as $map){
      if($map['target'] == self::FIELD_STOCK_LEVEL){
        $source_stock_level = $map['map']['value'];
        break;
      }
    }

    /** @var \Drupal\feeds\Feeds\Item\ItemInterface */
    $item = $event->getItem();
    /**
     * With setting $variation->product_id this we don't need to run separate import with relation [ product <=> product variation ]
     * The product backreference, populated by Product::postSave().
     * Ensure there's a back-reference on each product variation.
     * @todo: check if product exists
     */
    $product_id = $item->get(self::COLUMN_PRODUCT_ID); // We don't need to map this field in Feed types Mapping
    $variation = $entity;
    if ($variation->product_id->isEmpty() && !empty($product_id)) {
      $variation->product_id = $product_id;
      $variation->save();
    }
    
    $entity->setOwnerId($this->currentUser->id());
    $field_stock_level = $item->get($source_stock_level);
    $value = trim($field_stock_level);
    $new_level = is_numeric($value) ? (float) $value : 0.0; // float(40)
    $current_level = $this->stockServiceManager->getStockLevel($entity); // float(20)

    // Calculkate adjustment to set as much as we requested in adjustment :)
    // $values['adjustment'] = $value; 
    $adjustment = $new_level - $current_level;
    $transaction_qty = $adjustment;

    $transaction_note = 'Transaction issued by Importer.';
    $metadata = ['data' => ['message' => $transaction_note]];

    // @see: https://www.drupal.org/project/commerce_stock/issues/3013616
    // $this->stockServiceManager->createTransaction($entity, 1, '', 10, NULL, NULL, StockTransactionsInterface::STOCK_IN, ['data' => []]);
    $transaction_type = ($transaction_qty > 0) ? StockTransactionsInterface::STOCK_IN : StockTransactionsInterface::STOCK_OUT;
    $this->stockServiceManager->createTransaction($entity, 1, '', $transaction_qty, NULL, NULL, $transaction_type, $metadata);
  }



  // @see: web/modules/contrib/commerce_stock/modules/field/src/Plugin/Field/FieldType/StockLevel.php
  /**
   * Internal method to create transactions.
   */
  private function createTransaction(EntityInterface $entity, array $values) {
    // To prevent multiple stock transactions, we need to track the processing.
    static $processed = [];

    // This is essential to prevent triggering of multiple transactions.
    if (isset($processed[$entity->getEntityTypeId() . $entity->id()])) {
      return;
    }
    $processed[$entity->getEntityTypeId() . $entity->id()] = TRUE;

    $stockServiceManager = \Drupal::service('commerce_stock.service_manager');
    $transaction_qty = empty($values['adjustment']) ? 0 : $values['adjustment'];

    // Some basic validation and type coercion.
    $transaction_qty = filter_var((float) ($transaction_qty), FILTER_VALIDATE_FLOAT);

    if ($transaction_qty) {
      $transaction_type = ($transaction_qty > 0) ? StockTransactionsInterface::STOCK_IN : StockTransactionsInterface::STOCK_OUT;
      // @todo Add zone and location to form.
      /** @var \Drupal\commerce_stock\StockLocationInterface $location */
      $location = $stockServiceManager->getTransactionLocation($this->getContext($entity), $entity, $transaction_qty);
      if (empty($location)) {
        // If we have no location, something isn't properly configured.
        throw new \RuntimeException('The StockServiceManager didn\'t return a location. Make sure your store is set up correctly?');
      }
      $zone = empty($values['zone']) ? '' : $values['zone'];
      $unit_cost = NULL;
      if (isset($values['unit_cost']['amount'])) {
        $unit_cost = filter_var((float) ($values['unit_cost']['amount']), FILTER_VALIDATE_FLOAT);
        $unit_cost ?: NULL;
      };
      $currency_code = empty($values['unit_cost']['currency_code']) ? NULL : $values['unit_cost']['currency_code'];
      $transaction_note = empty($values['stock_transaction_note']) ? '' : $values['stock_transaction_note'];
      $metadata = ['data' => ['message' => $transaction_note]];
      if (!empty($values['user_id'])) {
        $metadata['related_uid'] = $values['user_id'];
      }
      else {
        $metadata['related_uid'] = \Drupal::currentUser()->id();
      }
      $stockServiceManager->createTransaction($entity, $location->getId(), $zone, $transaction_qty, (float) $unit_cost, $currency_code, $transaction_type, $metadata);
    }
  }

}