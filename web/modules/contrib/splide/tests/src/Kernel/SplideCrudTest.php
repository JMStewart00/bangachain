<?php

namespace Drupal\Tests\splide\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\splide\SplideDefault;
use Drupal\splide\Entity\Splide;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\splide\Traits\SplideUnitTestTrait;

/**
 * Tests creation, loading, updating, deleting of Splide optionsets.
 *
 * @coversDefaultClass \Drupal\splide\Entity\Splide
 *
 * @group splide
 */
class SplideCrudTest extends BlazyKernelTestBase {

  use SplideUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'image',
    'blazy',
    'splide',
    'splide_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('splide');

    $this->blazyAdmin      = $this->container->get('blazy.admin.extended');
    $this->splideManager   = $this->container->get('splide.manager');
    $this->splideFormatter = $this->container->get('splide.formatter');
    $this->splideAdmin     = $this->container->get('splide.admin');
  }

  /**
   * Tests CRUD operations for Splide optionsets.
   */
  public function testSplideCrud() {
    // Add a Splide optionset with minimum data only.
    $empty = Splide::create([
      'name'  => 'test_empty',
      'label' => 'Empty splide',
    ]);

    $empty->save();
    $this->verifySplideOptionset($empty);

    // Add main Splide optionset with possible properties.
    $main = Splide::create([
      'name'  => 'test_main',
      'label' => 'Test main',
    ]);

    $main->save();

    $settings = [
      'arrows'     => FALSE,
      'pagination' => TRUE,
      'lazyLoad'   => 'sequential',
    ] + $main->getSettings();

    $main->set('group', 'main');
    $main->set('optimized', TRUE);
    $main->setSettings($settings);

    $main->save();

    $breakpoint = $main->getBreakpoint();
    $this->assertEmpty($breakpoint);
    $this->assertEquals('main', $main->getGroup());

    $optimized = $main->optimized();
    $this->assertNotEmpty($optimized);

    $this->verifySplideOptionset($main);

    // @todo Use dataProvider.
    try {
      $responsive_options = $main->getResponsiveOptions();
    }
    catch (\PHPUnit_Framework_Exception $e) {
    }

    $this->assertTrue(TRUE);

    $responsive_settings = $settings;
    $main->set('breakpoint', 2);

    $breakpoints = [481, 769];
    foreach ($breakpoints as $key => $breakpoint) {
      $main->setResponsiveSettings($responsive_settings, $key, 'settings');
      $main->setResponsiveSettings($breakpoint, $key, 'breakpoint');
    }

    $main->save();

    $responsive_options = $main->getResponsiveOptions();

    foreach ($responsive_options as $key => $responsive) {
      $this->assertEquals('sequential', $responsive['settings']['lazyLoad']);
      $this->assertEquals($breakpoints[$key], $responsive['breakpoint']);
    }

    $options = $main->getSettings();
    $cleaned = $main->toJson($options);
    $this->assertArrayHasKey('breakpoints', $cleaned);

    foreach ($responsive_options as $key => $responsive) {
      $main->setResponsiveSettings(TRUE, $key, 'unsplide');
    }

    $main->save();

    $options = $main->getSettings();
    $cleaned = $main->toJson($options);

    foreach ($cleaned['breakpoints'] as $key => $responsive) {
      $this->assertArrayHasKey('destroy', $responsive);
    }

    // Alter some splide optionset properties and save again.
    $main->set('label', 'Altered splide');
    $main->setSetting('drag', TRUE);
    $main->save();
    $this->verifySplideOptionset($main);

    // Enable autoplay and save again.
    $main->setSetting('autoplay', TRUE);
    $main->save();
    $this->verifySplideOptionset($main);

    // Add nav Splide optionset with possible properties.
    $nav = Splide::create([
      'name' => 'test_nav',
      'label' => 'Test nav',
    ]);

    $skin = $nav->getSkin();
    $this->assertEmpty($skin);

    // @todo recheck.
    // $nav->setSetting('easingOverride', 'easeInQuad');
    // $nav->save();
    // $settings = $nav->getSettings();
    // $nav->removeWastedDependentOptions($settings);
    // $this->assertEquals('cubic-bezier(0.550, 0.085, 0.680, 0.530)',
    // $settings['easing']);
    $this->assertNotEmpty($nav->getSetting('drag'));
    $nav->setSetting('drag', TRUE);
    $nav->save();
    $this->assertNotEmpty($nav->getSetting('drag'));

    // @todo Use dataProvider.
    try {
      $result = $nav->getOptions('settings', 'drag');
    }
    catch (\PHPUnit_Framework_Exception $e) {
    }

    $this->assertTrue(!empty($result));

    try {
      $result = $nav->getOptions(['settings', 'drag']);
    }
    catch (\PHPUnit_Framework_Exception $e) {
    }

    $this->assertTrue(!empty($result));

    $settings = $nav->getOptions('settings');
    $this->assertArrayHasKey('drag', $settings);

    $options = $nav->getOptions();
    $this->assertArrayHasKey('settings', $options);

    $merged = array_merge(Splide::defaultSettings() + SplideDefault::jsSettings(), $settings);
    $nav->setSettings($merged);
    $nav->save();
    $this->assertTrue(!empty($nav->getSetting('drag')));

    $nav->toJson($settings);
    $this->assertArrayNotHasKey('lazyLoad', $settings);

    // Delete the splide optionset.
    $nav->delete();

    $splides = Splide::loadMultiple();
    $this->assertFalse(isset($splides[$nav->id()]), 'Splide::loadMultiple: Disabled splide optionset no longer exists.');
  }

  /**
   * Verifies that a splide optionset is properly stored.
   *
   * @param \Drupal\splide\Entity\Splide $splide
   *   The Splide instance.
   */
  public function verifySplideOptionset(Splide $splide) {
    $t_args = ['%splide' => $splide->label()];
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded splide has all properties.
    $splide = Splide::load($splide->id());
    $this->assertEquals($splide->id(), $splide->id(), new FormattableMarkup('Splide::load: Proper splide id for splide optionset %splide.', $t_args));
    $this->assertEquals($splide->label(), $splide->label(), new FormattableMarkup('Splide::load: Proper title for splide optionset %splide.', $t_args));

    // Check that the splide was created in site default language.
    $this->assertEquals($splide->language()->getId(), $default_langcode, new FormattableMarkup('Splide::load: Proper language code for splide optionset %splide.', $t_args));
  }

}
