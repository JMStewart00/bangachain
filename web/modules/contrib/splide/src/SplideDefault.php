<?php

namespace Drupal\splide;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class SplideDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'optionset'       => 'default',
      'override'        => FALSE,
      'overridables'    => [],
      'skin'            => '',
      'skin_arrows'     => '',
      'skin_dots'       => '',
      'use_theme_field' => FALSE,
      'pagination_pos'  => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function gridSettings() {
    return [
      'preserve_keys' => FALSE,
      'visible_items' => 0,
    ] + parent::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    return [
      'optionset_nav'    => '',
      'skin_nav'         => '',
      'nav_caption'      => '',
      'thumbnail_effect' => '',
      'navpos'           => '',
    ] + self::baseSettings() + parent::imageSettings() + self::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return [
      'thumbnail' => '',
      'pagination_text' => '',
    ] + self::imageSettings() + parent::extendedSettings();
  }

  /**
   * Returns filter settings.
   */
  public static function filterSettings() {
    $settings = self::imageSettings();
    $unused = self::gridSettings() + [
      'breakpoints' => [],
      'sizes'       => '',
      'grid_header' => '',
    ];
    foreach ($unused as $key => $value) {
      if (isset($settings[$key])) {
        unset($settings[$key]);
      }
    }
    return $settings;
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public static function overridableOptions($option = TRUE) {
    return [
      'arrows'     => $option ? new TranslatableMarkup('Arrows') : '',
      'pagination' => $option ? new TranslatableMarkup('Pagination') : '',
      'autoplay'   => $option ? new TranslatableMarkup('Autoplay') : '',
      'autoWidth'  => $option ? new TranslatableMarkup('Auto width') : '',
      'autoHeight' => $option ? new TranslatableMarkup('Auto height') : '',
      'drag'       => $option ? new TranslatableMarkup('Drag') : '',
      'wheel'      => $option ? new TranslatableMarkup('Mouse wheel') : '',
      'randomize'  => $option ? new TranslatableMarkup('Randomize') : '',
    ];
  }

  /**
   * Returns valid options for breakpoints.
   */
  public static function validBreakpointOptions() {
    return [
      'rewind',
      'speed',
      'width',
      'height',
      'fixedWidth',
      'fixedHeight',
      'heightRatio',
      'perPage',
      'perMove',
      'focus',
      'gap',
      'padding',
      'pagination',
      'drag',
      'easing',
      'destroy',
    ];
  }

  /**
   * Returns Splide specific settings.
   */
  public static function splides() {
    return [
      'autoscroll'     => FALSE,
      'display'        => 'main',
      // 'nav'            => FALSE,
      // 'navpos'         => FALSE,
      'pagination_fx'  => '',
      'pagination_tab' => FALSE,
      'thumbnail_uri'  => '',
      '_unload'        => FALSE,
      'unsplide'       => FALSE,
      'vanilla'        => FALSE,
      'vertical'       => FALSE,
      'vertical_nav'   => FALSE,
    ];
  }

  /**
   * Returns HTML or layout related settings to shut up notices.
   *
   * @return array
   *   The default settings.
   */
  public static function htmlSettings() {
    $items = [];
    foreach (self::splides() as $key => $value) {
      if (is_bool($value)) {
        $items['is'][$key] = $value;
      }
      else {
        $items[$key] = $value;
      }
    }

    return [
      'splides'   => Blazy::settings($items),
      'item_id'   => 'slide',
      'namespace' => 'splide',
      // @todo remove `+ self::splides()`.
    ] + self::splides()
      + self::imageSettings()
      + parent::htmlSettings();
  }

  /**
   * Defines JS options required by theme_splide(), used with optimized option.
   */
  public static function jsSettings() {
    return [
      'autoplay'   => FALSE,
      'direction'  => 'ltr',
      'downTarget' => '',
      'downOffset' => '',
      'lazyLoad'   => '',
      'pagination' => TRUE,
      'perPage'    => 1,
    ];
  }

  /**
   * Returns splide theme properties.
   */
  public static function themeProperties() {
    return [
      'attached',
      'attributes',
      'items',
      'options',
      'optionset',
      'settings',
    ];
  }

  /**
   * Returns the commonly used path, or just the base path.
   *
   * @todo deprected and removed for Blazy::getPath post Blazy:2.6+.
   */
  public static function getPath($type, $name, $absolute = FALSE): string {
    return Blazy::getPath($type, $name, $absolute);
  }

}
