<?php

namespace Drupal\Tests\splide\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\splide\Form\SplideAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Splide admin form.
 *
 * @coversDefaultClass \Drupal\splide\Form\SplideAdmin
 * @group splide
 */
class SplideAdminUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blazyAdminExtended = $this->getMockBuilder('\Drupal\blazy\Dejavu\BlazyAdminExtended')
      ->disableOriginalConstructor()
      ->getMock();
    $this->splideManager = $this->createMock('\Drupal\splide\SplideManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::blazyAdmin
   * @covers ::manager
   */
  public function testBlazyAdminCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['blazy.admin.extended', $exception, $this->blazyAdminExtended],
      ['splide.manager', $exception, $this->splideManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $splideAdmin = SplideAdmin::create($container);
    $this->assertInstanceOf(SplideAdmin::class, $splideAdmin);

    $this->assertInstanceOf('\Drupal\blazy\Dejavu\BlazyAdminExtended', $splideAdmin->blazyAdmin());
    $this->assertInstanceOf('\Drupal\splide\SplideManagerInterface', $splideAdmin->manager());
  }

}
