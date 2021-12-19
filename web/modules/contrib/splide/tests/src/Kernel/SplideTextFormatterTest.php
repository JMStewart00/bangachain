<?php

namespace Drupal\Tests\splide\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\splide\Traits\SplideUnitTestTrait;

/**
 * Tests the Splide field rendering using the text field type.
 *
 * @coversDefaultClass \Drupal\splide\Plugin\Field\FieldFormatter\SplideTextFormatter
 * @group splide
 */
class SplideTextFormatterTest extends BlazyKernelTestBase {

  use SplideUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'filter',
    'node',
    'text',
    'blazy',
    'splide',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('splide');

    $this->testFieldName   = 'field_text_multiple';
    $this->testEmptyName   = 'field_text_multiple_empty';
    $this->testFieldType   = 'text';
    $this->testPluginId    = 'splide_text';
    $this->maxItems        = 7;
    $this->maxParagraphs   = 2;
    $this->splideAdmin     = $this->container->get('splide.admin');
    $this->splideManager   = $this->container->get('splide.manager');
    $this->splideFormatter = $this->container->get('splide.formatter');

    // Create contents.
    $bundle = $this->bundle;

    $data = [
      'field_name' => $this->testEmptyName,
      'field_type' => 'text',
    ];

    $this->setUpContentTypeTest($bundle, $data);
    $this->setUpContentWithItems($bundle);

    $this->display = $this->setUpFormatterDisplay($bundle);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests the Splide formatters.
   */
  public function testSplideFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);

    $render = $this->splideManager->getRenderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->splideManager->getRenderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $scopes = $this->formatterInstance->getScopedFormElements();
    $this->assertEquals($this->testPluginId, $scopes['plugin_id']);

    $form = [];
    $form_state = new FormState();
    $element = $this->formatterInstance->settingsForm($form, $form_state);
    $this->assertArrayHasKey('optionset', $element);
  }

}
