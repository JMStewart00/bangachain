<?php

declare(strict_types = 1);

namespace Drupal\react_search\Plugin\search_api\processor;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Adds the Center and Services title for sidebar page titles.
 *
 * @SearchApiProcessor(
 *   id = "stock_level",
 *   label = @Translation("Stock Level"),
 *   description = @Translation("Adds Product Stock Level"),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class StockLevel extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) : array {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'parent name' => $this->t('Center & Service Parent'),
        'description' => $this->t('Adds Product Stock Level'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['stock_level'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) : void {
    $entity = $item->getOriginalObject()->getValue();

    $stock_level = 0;
    if ($entity->getEntityType()->id() === 'commerce_product') {
      $productVariationIds = $entity->getVariationIds();

      foreach($productVariationIds as $variation) {
        /*Load Product Variations*/
        $entity_manager = \Drupal::entityTypeManager();
        $product_variation = $entity_manager->getStorage('commerce_product_variation')->load((int)$variation);
        if ($product_variation->status->value) {
          $context = commerce_stock_enforcement_get_context($product_variation);
          // Get the available stock level.
          $variation_stock = commerce_stock_enforcement_get_stock_level($product_variation, $context);
          $stock_level += $variation_stock;
        }
      }
    }

    // Set the field in the index if center and service name exists.
    if ($stock_level > -1) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'stock_level');
      foreach ($fields as $field) {
        if (!$field->getDatasourceId()) {
          $field->addValue($stock_level);
        }
      }
    }
  }

  /**
   * Get the available stock level for the PurchasableEntity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\commerce\Context $context
   *   The context object.
   *
   * @return int
   *   The stock level.
   */
  function commerce_stock_enforcement_get_stock_level(
    PurchasableEntityInterface $entity,
    Context $context
  ) {
    /** @var \Drupal\commerce_stock\StockServiceManagerInterface $stockManager */
    $stockManager = \Drupal::service('commerce_stock.service_manager');

    /** @var \Drupal\commerce_stock\StockServiceInterface $stock_service */
    $stock_service = $stockManager->getService($entity);
    /** @var \Drupal\commerce_stock\StockCheckInterface $stock_checker */
    $stock_checker = $stock_service->getStockChecker();

    if ($stock_checker->getIsAlwaysInStock($entity)) {
      return PHP_INT_MAX;
    }

    $stock_config = $stock_service->getConfiguration();
    $stock_level = $stock_checker->getTotalStockLevel(
      $entity,
      $stock_config->getAvailabilityLocations($context, $entity)
    );

    return $stock_level;
  }

  /**
   * Get the context for the provided Purchasable Entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @return \Drupal\commerce\Context
   *   The context.
   *
   * @see \Drupal\commerce_stock\ContextCreatorTrait::getContextDetails()
   * @see \Drupal\commerce_cart\Form\AddToCartForm::selectStore()
   */
  function commerce_stock_enforcement_get_context(
    PurchasableEntityInterface $entity
  ) {

    // @todo - think about using selectStore() in commerce_cart.module.
    $store_to_use = \Drupal::service('commerce_store.current_store')->getStore();
    $current_user = \Drupal::currentUser();
    // Make sure the current store is in the entity stores.
    $stores = $entity->getStores();
    $found = FALSE;
    // If we have a current store.
    if ($store_to_use) {
      // Make sure it is associated with the curent product.
      foreach ($stores as $store) {
        if ($store->id() == $store_to_use->id()) {
          $found = TRUE;
          break;
        }
      }
    }
    // If not found and we have stores associated with the product.
    if (!$found) {
      if (!empty($stores)) {
        // Get the first store the product is assigned to.
        $store_to_use = array_shift($stores);
      }
    }
    return new Context($current_user, $store_to_use);
  }
}

