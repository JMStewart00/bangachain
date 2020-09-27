<?php

namespace Drupal\Tests\commerce_feeds\Kernel\Processor;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\Tests\commerce_feeds\Kernel\CommerceFeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_feeds\Feeds\Processor\ProductProcessor
 * @group commerce_feeds
 */
class ProductProcessorTest extends CommerceFeedsKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'sku' => 'sku',
      'store' => 'store',
    ], [
      'processor' => 'entity:commerce_product',
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'default',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'variations',
          'map' => ['target_id' => 'sku'],
          'settings' => [
            'reference_by' => 'sku',
          ],
        ],
      ]),
    ]);
  }

  /**
   * Tests importing products.
   */
  public function testImportProductsWithStore() {
    // Create two stores.
    $store_a = $this->createStore('Store A', 'a@example.com');
    $store_b = $this->createStore('Store B', 'b@example.com');

    // Create a few product variations.
    $skus = [
      'A001',
      'B001',
      'B002',
    ];
    foreach ($skus as $sku) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => $sku,
      ]);
      $variation->save();
    }

    // Add mapping to store.
    $this->feedType->addMapping([
      'target' => 'stores',
      'map' => ['target_id' => 'store'],
      'settings' => [
        'reference_by' => 'name',
      ],
    ]);
    $this->feedType->save();

    // Respond to after parse event.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PARSE, [$this, 'afterParse'], FeedsEvents::AFTER);

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/products.csv',
    ]);
    $feed->import();

    // Assert that two products were created.
    $products = Product::loadMultiple();
    $this->assertCount(2, $products);

    // Assert expected values.
    $expected_per_product = [
      1 => [
        'variation_ids' => [1],
        'store_ids' => [$store_a->id()],
      ],
      2 => [
        'variation_ids' => [2, 3],
        'store_ids' => [$store_b->id()],
      ],
    ];
    foreach ($expected_per_product as $i => $expected) {
      $this->assertEquals($expected['variation_ids'], $products[$i]->getVariationIds());
      $this->assertEquals($expected['store_ids'], $products[$i]->getStoreIds());
    }
  }

  /**
   * Acts on parser result.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {
    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Make sku multivalued by exploding on '|'.
      $item->set('sku', explode('|', $item->get('sku')));
    }
  }

}
