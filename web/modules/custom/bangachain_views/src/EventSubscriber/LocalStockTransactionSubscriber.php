<?php
namespace Drupal\bangachain_views\EventSubscriber;

use Drupal\commerce_stock_local\Event\LocalStockTransactionEvent;
use Drupal\commerce_stock_local\Event\LocalStockTransactionEvents;
use Drupal\commerce_stock_local\LocalStockService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates stock location level when a stock transaction is inserted.
 */
class LocalStockTransactionSubscriber implements EventSubscriberInterface {
  /**
   * The local stock service.
   *
   * @var \Drupal\commerce_stock_local\LocalStockService
   */
  protected $localStockService;
  /**
   * Constructs a LocalStockTransactionSubscriber.
   *
   * @param \Drupal\commerce_stock_local\LocalStockService $local_stock_service
   *   The local stock service.
   */
  public function __construct(LocalStockService $local_stock_service) {
    $this->localStockService = $local_stock_service;
  }
  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      LocalStockTransactionEvents::LOCAL_STOCK_TRANSACTION_INSERT => 'onTransactionInsert',
    ];
  }
  /**
   * Update the stock location level for the purchasable entity.
   *
   * @param \Drupal\commerce_stock_local\Event\LocalStockTransactionEvent $event
   *   The event.
   */
  public function onTransactionInsert(LocalStockTransactionEvent $event) {
    /** @var \Drupal\commerce_stock_local\LocalStockUpdater $updater */
    $updater = $this->localStockService->getStockUpdater();
    $updater->updateLocationStockLevel($event->getTransactionLocation()->getId(), $event->getEntity());
  }
}
