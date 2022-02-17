<?php

namespace Drupal\Tests\splide\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\splide\Traits\SplideUnitTestTrait;
use Drupal\splide\SplideDefault;
use Drupal\splide\Entity\Splide;
use Drupal\splide_ui\Form\SplideForm;

/**
 * Tests the Splide manager methods.
 *
 * @coversDefaultClass \Drupal\splide\SplideManager
 *
 * @group splide
 */
class SplideManagerTest extends BlazyKernelTestBase {

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

    $bundle = $this->bundle;

    $this->messenger = $this->container->get('messenger');
    $this->splideAdmin = $this->container->get('splide.admin');
    $this->blazyAdminFormatter = $this->splideAdmin;
    $this->splideFormatter = $this->container->get('splide.formatter');
    $this->splideManager = $this->container->get('splide.manager');

    $this->splideForm = SplideForm::create($this->container);

    $this->testPluginId  = 'splide_image';
    $this->testFieldName = 'field_splide_image';
    $this->maxItems      = 7;
    $this->maxParagraphs = 2;

    $settings['fields']['field_text_multiple'] = 'text';
    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    $this->display = $this->setUpFormatterDisplay($bundle);
    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   */
  public function testSplideManagerMethods() {
    $manager = $this->splideManager;
    $settings = [
      'media_switch'     => 'media',
      'lazy'             => '',
      'skin'             => 'classic',
      'down'             => TRUE,
      'thumbnail_effect' => 'hover',
      'module_css'       => TRUE,
    ] + $this->getFormatterSettings() + SplideDefault::extendedSettings();

    $attachments = $manager->attach($settings);
    $this->assertArrayHasKey('splide', $attachments['drupalSettings']);
  }

  /**
   * Tests for Splide build.
   *
   * @param bool $items
   *   Whether to provide items, or not.
   * @param array $settings
   *   The settings being tested.
   * @param array $options
   *   The options being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers ::splide
   * @covers ::preRenderSplide
   * @covers ::buildGrid
   * @covers ::build
   * @covers ::preRenderSplideWrapper
   * @dataProvider providerTestSplideBuild
   */
  public function testBuild($items, array $settings, array $options, $expected) {
    $manager = $this->splideManager;
    $defaults = $this->getFormatterSettings() + SplideDefault::htmlSettings();
    $settings = array_merge($defaults, $settings);

    $settings['optionset'] = 'test';

    $build = $this->display->build($this->entity);

    $items = !$items ? [] : $build[$this->testFieldName]['#build']['items'];
    $build = [
      'items'     => $items,
      'settings'  => $settings,
      'options'   => $options,
      'optionset' => Splide::load($settings['optionset']),
    ];

    $splide = $manager->splide($build);
    $this->assertEquals($expected, !empty($splide));

    $splide['#build']['settings'] = $settings;
    $splide['#build']['items'] = $items;
    $splide['#build']['options'] = [];

    $element = $manager->preRenderSplide($splide);
    $this->assertEquals($expected, !empty($element));

    if (!empty($settings['optionset_nav'])) {
      $build['nav'] = [
        'items'    => $items,
        'settings' => $settings,
        'options'  => $options,
      ];
    }

    $splides = $manager->build($build);
    $this->assertEquals($expected, !empty($splides));

    $splides['#build']['items'] = $items;
    $splides['#build']['settings'] = $settings;

    if (!empty($settings['optionset_nav'])) {
      $splides['#build']['nav']['items'] = $build['nav']['items'];
      $splides['#build']['nav']['settings'] = $build['nav']['settings'];
    }

    $elements = $manager->preRenderSplideWrapper($splides);
    $this->assertEquals($expected, !empty($elements));
  }

  /**
   * Provide test cases for ::testBuild().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestSplideBuild() {
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'override' => TRUE,
        'overridables' => ['arrows' => FALSE, 'pagination' => TRUE],
        'skin_dots' => 'dots',
        'cache' => -1,
        'cache_tags' => ['url.site'],
      ],
      ['pagination' => TRUE],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'unsplide' => TRUE,
      ],
      [],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'skin' => 'test',
        'nav' => TRUE,
        'optionset_nav' => 'test_nav',
        'navpos' => 'top',
        'thumbnail_style' => 'thumbnail',
        'thumbnail_effect' => 'hover',

      ],
      [],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\splide_ui\Form\SplideForm.
   *
   * @covers \Drupal\splide_ui\Form\SplideForm::getFormElements
   * @covers \Drupal\splide_ui\Form\SplideForm::getResponsiveFormElements
   * @covers \Drupal\splide_ui\Form\SplideForm::getLazyloadOptions
   * @covers \Drupal\splide_ui\Form\SplideForm::typecastOptionset
   * @covers \Drupal\splide_ui\Form\SplideForm::getJsEasingOptions
   * @covers \Drupal\splide_ui\Form\SplideForm::getCssEasingOptions
   * @covers \Drupal\splide_ui\Form\SplideForm::getOptionsRequiredByTemplate
   * @covers \Drupal\splide_ui\Form\SplideForm::getBezier
   */
  public function testSplideForm() {
    $elements = $this->splideForm->getFormElements();
    $this->assertArrayHasKey('drag', $elements);

    $elements = $this->splideForm->getResponsiveFormElements(2);
    $this->assertArrayHasKey('breakpoint', $elements[0]);

    $options = $this->splideForm->getLazyloadOptions();
    $this->assertArrayHasKey('nearby', $options);

    $settings = [];
    $this->splideForm->typecastOptionset($settings);
    $this->assertEmpty($settings);

    $settings['drag'] = 1;
    $settings['edgeFriction'] = 0.27;
    $this->splideForm->typecastOptionset($settings);
    $this->assertEquals(TRUE, $settings['drag']);

    $options = $this->splideForm->getJsEasingOptions();
    $this->assertArrayHasKey('easeInQuad', $options);

    $options = $this->splideForm->getCssEasingOptions();
    $this->assertArrayHasKey('easeInQuad', $options);

    $options = $this->splideForm->getOptionsRequiredByTemplate();
    $this->assertArrayHasKey('lazyLoad', $options);

    $bezier = $this->splideForm->getBezier('easeInQuad');
    $this->assertEquals('cubic-bezier(0.550, 0.085, 0.680, 0.530)', $bezier);
  }

}
