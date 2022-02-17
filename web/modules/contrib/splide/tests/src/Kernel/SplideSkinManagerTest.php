<?php

namespace Drupal\Tests\splide\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\splide\Traits\SplideUnitTestTrait;

/**
 * Tests the Splide skin manager methods.
 *
 * @coversDefaultClass \Drupal\splide\SplideSkinManager
 *
 * @group splide
 */
class SplideSkinManagerTest extends BlazyKernelTestBase {

  use SplideUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'filter',
    'image',
    'node',
    'text',
    'blazy',
    'splide',
    'splide_ui',
    'splide_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'field',
      'image',
      'media',
      'responsive_image',
      'node',
      'views',
      'blazy',
      'splide',
      'splide_ui',
    ]);

    $this->splideSkinManager = $this->container->get('splide.skin_manager');
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::getSkins
   * @covers ::getSkinsByGroup
   * @covers ::libraryInfoBuild
   */
  public function testSplideManagerMethods() {
    $skins = $this->splideSkinManager->getSkins();
    $this->assertArrayHasKey('skins', $skins);
    $this->assertArrayHasKey('arrows', $skins);
    $this->assertArrayHasKey('dots', $skins);

    // Verify we have cached skins.
    $cid = 'splide_skins_data';
    $cached_skins = $this->splideSkinManager->getCache()->get($cid);
    $this->assertEquals($cid, $cached_skins->cid);
    $this->assertEquals($skins, $cached_skins->data);

    // Verify skins has thumbnail constant.
    $defined_skins = $this->splideSkinManager->getConstantSkins();
    $this->assertTrue(in_array('nav', $defined_skins));

    // Verify libraries.
    $libraries = $this->splideSkinManager->libraryInfoBuild();
    $this->assertArrayHasKey('splide.main.default', $libraries);

    // Tests for Drupal\splide_test\Plugin\splide\SplideSkin as a plugin.
    $skins = $this->splideSkinManager->getSkinsByGroup('dots');
    $this->assertArrayHasKey('dots', $skins);

    $skins = $this->splideSkinManager->getSkinsByGroup('arrows');
    $this->assertArrayHasKey('arrows', $skins);
  }

}
