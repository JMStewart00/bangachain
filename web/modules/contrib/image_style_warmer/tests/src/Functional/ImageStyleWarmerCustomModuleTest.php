<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Functional tests to check Image Style Warmer usage like a custom module.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerCustomModuleTest extends ImageStyleWarmerTestBase {

  use CronRunTrait;

  /**
   * Test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $testFile;

  /**
   * Test queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $testQueue;

  /**
   * Test service.
   *
   * @var \Drupal\image_style_warmer\ImageStylesWarmerInterface
   */
  protected $testService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create queue of image style warmer pregenerator.
    $this->testQueue = new DatabaseQueue('image_style_warmer_pregenerator', Database::getConnection());
  }

  /**
   * Test Image Style Warmer warming like a custom module.
   */
  public function testImageStyleWarmerDoWarmUpCustomModule() {
    $this->prepareImageStyleWarmerCustomModuleTests();

    $this->assertFalse(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');

    $this->testService->doWarmUp($this->testFile, [$this->testInitialStyle->id()]);
    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does exist.');
  }

  /**
   * Test Image Style Warmer queue warming like a custom module.
   */
  public function testImageStyleWarmerQueueCustomModule() {
    $this->prepareImageStyleWarmerCustomModuleTests();

    // Add image file to Image Style Warmer queue like a custom module.
    $this->testService->addQueue($this->testFile, [$this->testQueueStyle->id()]);

    $this->assertSame(1, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should not be empty.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');

    $this->cronRun();
    $this->assertSame(0, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should be empty.');
    $this->assertTrue(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does exist.');
  }

  /**
   * Prepare Image Style Warmer for custom module tests.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function prepareImageStyleWarmerCustomModuleTests() {

    // Disable image styles in image_style_warmer.settings.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $settings = [];
    $this->drupalPostForm('admin/config/development/performance/image-style-warmer', $settings, t('Save configuration'));

    // Create an Image Styles Warmer service.
    $this->testService = \Drupal::service('image_style_warmer.warmer');

    // Create an image file.
    $this->testFile = $this->getTestFile('image');
    $this->testFile->setPermanent();
    $this->testFile->save();
  }

}
