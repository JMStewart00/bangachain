<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Image Style Warmer test base class.
 *
 * @group image_style_warmer
 */
abstract class ImageStyleWarmerTestBase extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'image', 'image_style_warmer'];

  /**
   * Test initial image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testInitialStyle;

  /**
   * Test queue image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testQueueStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);

    // Create test image style for initial tests.
    $this->testInitialStyle = $this->container->get('entity_type.manager')->getStorage('image_style')->create([
      'name' => 'test_initial',
      'label' => 'Test initial image style',
      'effects' => [],
    ]);
    $this->testInitialStyle->save();

    // Create test image style for queue tests.
    $this->testQueueStyle = $this->container->get('entity_type.manager')->getStorage('image_style')->create([
      'name' => 'test_queue',
      'label' => 'Test queue image style',
      'effects' => [],
    ]);
    $this->testQueueStyle->save();
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *   Return file entity object.
   */
  public function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $this->testFile = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $this->testFile->filesize = filesize($this->testFile->uri);

    return File::create((array) $this->testFile);
  }

}
