<?php

namespace Drupal\Tests\commerce_feeds\Kernel\Target;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_feeds\Kernel\CommerceFeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_feeds\Feeds\Target\Price
 * @group commerce_feeds
 */
class PriceTest extends CommerceFeedsKernelTestBase {

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
      'sku' => 'sku',
      'title' => 'title',
      'price' => 'price',
    ], [
      'processor' => 'entity:commerce_product_variation',
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'default',
        ],
      ],
      'mappings' => [
        [
          'target' => 'sku',
          'map' => ['value' => 'sku'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);
  }

  /**
   * Tests importing product variations.
   */
  public function testImportSellPrice() {
    $this->feedType->addMapping([
      'target' => 'price',
      'map' => ['number' => 'price'],
      'settings' => [
        'currency_code' => 'USD',
      ],
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/product_variations.csv',
    ]);
    $feed->import();

    // Assert that three product variations were created.
    $variations = ProductVariation::loadMultiple();
    $this->assertCount(3, $variations);

    // Check expected prices.
    $expected = [
      1 => 100,
      2 => 24.52,
      3 => 0,
    ];
    foreach ($expected as $id => $value) {
      $price = $variations[$id]->getPrice();
      $this->assertEquals($value, $price->getNumber());
      $this->assertEquals('USD', $price->getCurrencyCode());
    }
  }

}
