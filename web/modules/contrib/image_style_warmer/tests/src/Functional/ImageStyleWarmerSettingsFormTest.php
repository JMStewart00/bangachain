<?php

namespace Drupal\Tests\image_style_warmer\Functional;

/**
 * Functional test to check settings form of Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerSettingsFormTest extends ImageStyleWarmerTestBase {

  /**
   * Test Image Style Warmer settings page.
   */
  public function testSettingsPage() {

    // Anonymous users don't have access to image_style_warmer settings pages.
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $this->assertSession()->statusCodeEquals(403);

    // Can access pages if logged in.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Initial image styles'));
    $this->assertSession()->pageTextContains(t('Select image styles which will be created initial for an image.'));
    $this->assertSession()->pageTextContains(t('Queue image styles'));
    $this->assertSession()->pageTextContains(t('Select image styles which will be created via queue worker.'));
    $this->assertSession()->buttonExists(t('Save configuration'));

    // Can save settings with a selected initial and queue image style.
    $settings = [
      'initial_image_styles[test_initial]' => 'test_initial',
      'queue_image_styles[test_queue]' => 'test_queue',
    ];
    $this->drupalPostForm('admin/config/development/performance/image-style-warmer', $settings, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));
    $this->assertSession()->checkboxChecked('initial_image_styles[test_initial]');
    $this->assertSession()->checkboxChecked('queue_image_styles[test_queue]');
  }

}
