<?php

namespace Drupal\commerce_loyalty_points\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\commerce\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the "Loyalty Points" formatter.
 *
 * Should only be used on product variations since it needs the price but uses
 * the Default number_decimal formatter otherwise. The price must be found on a
 * field named "price".
 *
 * @FieldFormatter(
 *   id = "loyalty_points",
 *   label = @Translation("Calculated Loyalty Points"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class LoyaltyPointsFormatter extends DecimalFormatter implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The current store for context.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The logged-in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Commerce price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * A module handler for altering values.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AdjustmentTypeManager $adjustment_type_manager, CurrentStoreInterface $current_store, AccountInterface $current_user, PriceCalculatorInterface $price_calculator, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->priceCalculator = $price_calculator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.commerce_adjustment_type'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('commerce_order.price_calculator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($items->isEmpty()) {
      return [];
    }

    $entity = $items->getEntity();
    $type = $entity->getEntityTypeId();
    if ($type !== 'commerce_product_variation') {
      return parent::viewElements($items, $langcode);
    }

    $items = clone $items;

    $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, [
      'field_name' => 'price',
    ]);
    /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
    $purchasable_entity = $items->getEntity();
    $adjustment_types = array_keys($this->adjustmentTypeManager->getDefinitions());
    $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types);
    $calculated_price = $result->getCalculatedPrice();
    foreach ($items as $item) {
      // Allow altering the number of points like AddUserLoyaltyPoints.
      $loyalty_points = $calculated_price->multiply($item->value);
      $operation = 'add';
      $this->moduleHandler->alter('loyalty_points', $operation, $loyalty_points);
      $item->setValue($loyalty_points->getNumber());
    }
    return parent::viewElements($items, $langcode);
  }

}
