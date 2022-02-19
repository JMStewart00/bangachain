<?php

namespace Drupal\soft4net_commerce_feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a commerce_stock_level field mapper.
 *
 * @FeedsTarget(
 *   id = "commerce_feeds_commerce_stock_level",
 *   field_types = {"commerce_stock_level"}
 * )
 */
class StockLevel extends FieldTargetBase implements ConfigurableTargetInterface {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [];
    return $configuration;
  }
}