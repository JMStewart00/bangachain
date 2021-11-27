<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Drush commands provided by Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerDrushTest extends ImageStyleWarmerTestBase {

  use DrushTestTrait;

  /**
   * Tests the Scheduler Drush messages.
   */
  public function testDrushWarmUpMessages() {
    // Run the plain command using the full image-style-warmer:warm-up command
    // name, and check that all of the output messages are shown.
    $this->drush('image-style-warmer:warm-up');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('No files found', $messages, 'No files found message not found', TRUE);
  }

  /**
   * Tests Image Style Warmer warm-up via Drush command.
   */
  public function testDrushWarmUp() {
    $this->prepareImageStyleWarmerTests(TRUE);

    // Run Image Style Warmer's drush cron command and check that the expected
    // messages are found.
    $this->drush('image-style-warmer:warm-up');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('Warming up styles for file 1 (1/1)', $messages, 'Warming up styles for 1 file message not found', TRUE);
    $this->assertStringContainsString('1 files warmed up', $messages, '1 files warmed up message not found', TRUE);
    $this->assertStringContainsString('Batch operations end', $messages, 'Batch operations end message not found', TRUE);
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
