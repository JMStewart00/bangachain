<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Functional tests to check general function of Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerGeneralTest extends ImageStyleWarmerTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create queue of image style warmer pregenerator.
    $this->testQueue = new DatabaseQueue('image_style_warmer_pregenerator', Database::getConnection());
  }

  /**
   * Test Image Style Warmer initial warming for temporary file.
   */
  public function testImageStyleWarmerUploadTemporaryImageFile() {
    $this->prepareImageStyleWarmerTests();

    $this->assertFalse(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
  }

  /**
   * Test Image Style Warmer initial warming for permanent file.
   */
  public function testImageStyleWarmerUploadPermanentImageFile() {
    $this->prepareImageStyleWarmerTests(TRUE);

    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
  }

  /**
   * Test Image Style Warmer queue warming for temporary file.
   */
  public function testImageStyleWarmerQueueTemporaryImageFile() {
    $this->prepareImageStyleWarmerTests();

    $this->assertSame(0, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should be empty.');
    $this->assertFalse(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
  }

  /**
   * Test Image Style Warmer queue warming for permanent file.
   */
  public function testImageStyleWarmerQueuePermanentImageFile() {
    $this->prepareImageStyleWarmerTests(TRUE);

    $this->assertSame(1, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should not be empty.');
    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');

    $this->cronRun();
    $this->assertSame(0, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should be empty.');
    $this->assertTrue(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does exist.');
  }

  /**
   * Prepare Image Style Warmer settings and file for tests.
   *
   * @param bool $permanent
   *   Create permanent file for tests. (default: FALSE)
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function prepareImageStyleWarmerTests($permanent = FALSE) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $settings = [
      'initial_image_styles[test_initial]' => 'test_initial',
      'queue_image_styles[test_queue]' => 'test_queue',
    ];
    $this->drupalPostForm('admin/config/development/performance/image-style-warmer', $settings, t('Save configuration'));

    // Create an image file without usages.
    $this->testFile = $this->getTestFile('image');
    $this->testFile->setTemporary();
    if ($permanent) {
      $this->testFile->setPermanent();
    }
    $this->testFile->save();
  }

}
