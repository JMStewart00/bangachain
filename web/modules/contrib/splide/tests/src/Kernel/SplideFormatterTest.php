<?php

namespace Drupal\Tests\splide\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\splide\Traits\SplideUnitTestTrait;
use Drupal\Splide\SplideDefault;

/**
 * Tests the Splide field rendering using the image field type.
 *
 * @coversDefaultClass \Drupal\splide\Plugin\Field\FieldFormatter\SplideImageFormatter
 * @group splide
 */
class SplideFormatterTest extends BlazyKernelTestBase {

  use SplideUnitTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'help',
    'field',
    'file',
    'image',
    'filter',
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
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('splide');

    $this->testFieldName   = 'field_image_multiple';
    $this->testEmptyName   = 'field_image_multiple_empty';
    $this->testPluginId    = 'splide_image';
    $this->maxItems        = 7;
    $this->maxParagraphs   = 2;
    $this->splideAdmin     = $this->container->get('splide.admin');
    $this->splideManager   = $this->container->get('splide.manager');
    $this->splideFormatter = $this->container->get('splide.formatter');

    $data['fields'] = [
      'field_video'                => 'text',
      'field_image'                => 'image',
      'field_image_multiple_empty' => 'image',
    ];

    // Create contents.
    $bundle = $this->bundle;
    $this->setUpContentTypeTest($bundle, $data);

    $settings = [
      'optionset' => 'test',
      'optionset_nav' => 'test_nav',
    ] + $this->getFormatterSettings() + SplideDefault::extendedSettings();

    $data['settings'] = $settings;
    $this->display = $this->setUpFormatterDisplay($bundle, $data);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
    $this->skins = $this->splideManager->skinManager()->getSkins();

    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();
  }

  /**
   * Tests the Splide formatters.
   */
  public function testSplideFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $render = $this->splideManager->getRenderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->splideManager->getRenderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->testItems);
    $this->assertInstanceOf('\Drupal\splide\Form\SplideAdminInterface', $this->formatterInstance->admin());
    $this->assertInstanceOf('\Drupal\splide\SplideFormatterInterface', $this->formatterInstance->formatter());
    $this->assertInstanceOf('\Drupal\splide\SplideManagerInterface', $this->formatterInstance->manager());

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);
    $this->assertEquals($this->testPluginId, $build[$this->testFieldName]['#formatter']);

    $scopes = $this->formatterInstance->getScopedFormElements();
    $this->assertEquals('splide', $scopes['namespace']);
    $this->assertArrayHasKey('optionset', $scopes['settings']);

    $summary = $this->formatterInstance->settingsSummary();
    $this->assertNotEmpty($summary);
  }

  /**
   * Tests for \Drupal\splide\SplideFormatter::testGetThumbnail().
   *
   * @param string $uri
   *   The uri being tested.
   * @param bool $expected
   *   The expected output.
   *
   * @covers \Drupal\splide\SplideFormatter::getThumbnail
   * @dataProvider providerTestGetThumbnail
   */
  public function testGetThumbnail($uri, $expected) {
    $settings = $this->getFormatterSettings() + SplideDefault::extendedSettings();
    $settings['uri'] = empty($uri) ? '' : $this->uri;

    $thumbnail = $this->splideFormatter->getThumbnail($settings, $this->testItem);
    $this->assertEquals($expected, !empty($thumbnail));
  }

  /**
   * Provide test cases for ::testGetThumbnail().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestGetThumbnail() {
    $data[] = [
      '',
      FALSE,
    ];
    $data[] = [
      'public://example.jpg',
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\splide\SplideFormatter.
   *
   * @param array $settings
   *   The settings being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers \Drupal\splide\SplideFormatter::buildSettings
   * @dataProvider providerTestBuildSettings
   */
  public function testBuildSettings(array $settings, $expected) {
    $format['settings'] = array_merge($this->getFormatterSettings(), $settings) + SplideDefault::extendedSettings();

    $this->splideFormatter->buildSettings($format, $this->testItems);
    $this->assertArrayHasKey('bundle', $format['settings']);
  }

  /**
   * Provide test cases for ::testBuildSettings().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestBuildSettings() {
    $data[] = [
      [
        'vanilla' => TRUE,
      ],
      FALSE,
    ];
    $data[] = [
      [
        'vanilla' => FALSE,
        'blazy' => FALSE,
        'ratio' => 'fluid',
      ],
      TRUE,
    ];
    $data[] = [
      [
        'vanilla' => FALSE,
        'blazy' => TRUE,
      ],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\splide\Form\SplideAdmin.
   *
   * @covers \Drupal\splide\Form\SplideAdmin::buildSettingsForm
   * @covers \Drupal\splide\Form\SplideAdmin::openingForm
   * @covers \Drupal\splide\Form\SplideAdmin::imageStyleForm
   * @covers \Drupal\splide\Form\SplideAdmin::fieldableForm
   * @covers \Drupal\splide\Form\SplideAdmin::mediaSwitchForm
   * @covers \Drupal\splide\Form\SplideAdmin::gridForm
   * @covers \Drupal\splide\Form\SplideAdmin::closingForm
   * @covers \Drupal\splide\Form\SplideAdmin::finalizeForm
   * @covers \Drupal\splide\Form\SplideAdmin::getOverridableOptions
   * @covers \Drupal\splide\Form\SplideAdmin::getLayoutOptions
   * @covers \Drupal\splide\Form\SplideAdmin::getOptionsetsByGroupOptions
   * @covers \Drupal\splide\Form\SplideAdmin::getSkinsByGroupOptions
   * @covers \Drupal\splide\Form\SplideAdmin::getSettingsSummary
   * @covers \Drupal\splide\Form\SplideAdmin::getFieldOptions
   */
  public function testAdminOptions() {
    $definition = $this->getSplideFormatterDefinition();
    $form['test'] = ['#type' => 'hidden'];

    $this->splideAdmin->buildSettingsForm($form, $definition);
    $this->assertArrayHasKey('optionset', $form);

    $options = $this->splideAdmin->getOverridableOptions();
    $this->assertArrayHasKey('arrows', $options);

    $options = $this->splideAdmin->getLayoutOptions();
    $this->assertArrayHasKey('bottom', $options);

    $options = $this->splideAdmin->getOptionsetsByGroupOptions();
    $this->assertArrayHasKey('default', $options);

    $options = $this->splideAdmin->getOptionsetsByGroupOptions('main');
    $this->assertArrayHasKey('test', $options);

    $options = $this->splideAdmin->getSkinsByGroupOptions('main');
    $this->assertArrayHasKey('classic', $options);

    $summary = $this->splideAdmin->getSettingsSummary($definition);
    $this->assertNotEmpty($summary);

    $options = $this->splideAdmin->getFieldOptions([], [], 'node');
    $this->assertArrayHasKey($this->testFieldName, $options);
  }

}
