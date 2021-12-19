<?php

namespace Drupal\splide;

use Drupal\Core\StringTranslation\TranslatableMarkup;
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
   * Returns HTML or layout related settings to shut up notices.
   *
   * @return array
   *   The default settings.
   */
  public static function htmlSettings() {
    return [
      'display'       => 'main',
      'grid'          => 0,
      'id'            => '',
      'lazy'          => '',
      'namespace'     => 'splide',
      'nav'           => FALSE,
      'navpos'        => FALSE,
      'pagination_fx' => '',
      'thumbnail_uri' => '',
      'route_name'    => '',
      '_unload'       => FALSE,
      'unsplide'      => FALSE,
      'vanilla'       => FALSE,
      'vertical'      => FALSE,
      'vertical_nav'  => FALSE,
      'view_name'     => '',
    ] + self::imageSettings();
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

}
