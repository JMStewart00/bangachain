<?php

namespace Drupal\Tests\splide\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\splide_ui\Form\SplideSettingsForm;

/**
 * Tests the Splide UI settings form.
 *
 * @coversDefaultClass \Drupal\splide_ui\Form\SplideSettingsForm
 *
 * @group splide
 */
class SplideSettingsFormTest extends KernelTestBase {

  /**
   * The splide settings form object under test.
   *
   * @var \Drupal\splide_ui\Form\SplideSettingsForm
   */
  protected $splideSettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'file',
    'image',
    'media',
    'blazy',
    'splide',
    'splide_ui',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->splideManager = $this->container->get('splide.manager');

    $this->splideSettingsForm = SplideSettingsForm::create($this->container);
  }

  /**
   * Tests for \Drupal\splide_ui\Form\SplideSettingsForm.
   *
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testSplideSettingsForm() {
    // Emulate a form state of a submitted form.
    $form_state = (new FormState())->setValues([
      'module_css' => TRUE,
    ]);

    $this->assertInstanceOf(FormInterface::class, $this->splideSettingsForm);
    $this->assertTrue($this->splideManager->getConfigFactory()->get('splide.settings')->get('module_css'));

    $id = $this->splideSettingsForm->getFormId();
    $this->assertEquals('splide_settings_form', $id);

    $method = new \ReflectionMethod(SplideSettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->splideSettingsForm);
    $this->assertEquals(['splide.settings'], $name);

    $form = $this->splideSettingsForm->buildForm([], $form_state);
    $this->splideSettingsForm->submitForm($form, $form_state);
  }

}
