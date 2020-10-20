<?php

namespace Drupal\Tests\commerce_shipping\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\Condition\ShipmentQuantity;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_shipping\Plugin\Commerce\Condition\ShipmentQuantity
 * @group commerce
 */
class ShipmentQuantityTest extends UnitTestCase {

  /**
   * @covers ::evaluate
   *
   * @dataProvider quantityProvider
   */
  public function testEvaluate($operator, $quantity, $given_quantity, $result) {
    $shipment_items = [];
    $shipment_items[] = new ShipmentItem([
      'order_item_id' => '1',
      'title' => 'Product 1',
      'quantity' => $given_quantity,
      'weight' => [
        'number' => '1000.00',
        'unit' => 'g',
      ],
      'declared_value' => new Price('10', 'USD'),
    ]);

    $shipment = $this->prophesize(ShipmentInterface::class);
    $shipment->getEntityTypeId()->willReturn('commerce_shipment');
    $shipment->getItems()->willReturn($shipment_items);
    $shipment = $shipment->reveal();

    $condition = new ShipmentQuantity([
      'operator' => $operator,
      'quantity' => $quantity,
    ], 'shipment_quantity', ['entity_type' => 'commerce_shipment']);

    $this->assertEquals($result, $condition->evaluate($shipment));
  }

  /**
   * Data provider for ::testEvaluate.
   *
   * @return array
   *   A list of testEvaluate function arguments.
   */
  public function quantityProvider() {
    return [
      ['>', 10, 5, FALSE],
      ['>', 10, 10, FALSE],
      ['>', 10, 11, TRUE],

      ['>=', 10, 5, FALSE],
      ['>=', 10, 10, TRUE],
      ['>=', 10, 11, TRUE],

      ['<', 10, 5, TRUE],
      ['<', 10, 10, FALSE],
      ['<', 10, 11, FALSE],

      ['<=', 10, 5, TRUE],
      ['<=', 10, 10, TRUE],
      ['<=', 10, 11, FALSE],

      ['==', 10, 5, FALSE],
      ['==', 10, 10, TRUE],
      ['==', 10, 11, FALSE],
    ];
  }

}
