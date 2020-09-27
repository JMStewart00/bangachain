<?php

namespace Drupal\commerce_feeds\Feeds\Processor;

use Drupal\feeds\Feeds\Processor\EntityProcessorBase;

/**
 * Defines a product processor.
 *
 * Creates products from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:commerce_product",
 *   title = @Translation("Product"),
 *   description = @Translation("Creates products from feed items."),
 *   entity_type = "commerce_product",
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class ProductProcessor extends EntityProcessorBase {
  // @todo set default store.
}
