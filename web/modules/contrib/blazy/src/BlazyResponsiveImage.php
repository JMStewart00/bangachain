<?php

namespace Drupal\blazy;

use Drupal\Core\Cache\Cache;

/**
 * Provides responsive image utilities.
 */
class BlazyResponsiveImage {

  /**
   * Sets dimensions once to reduce method calls for Responsive image.
   */
  public static function dimensions(array &$settings = [], $initial = TRUE) {
    $srcset = [];

    foreach (self::getStyles($settings['resimage'])['styles'] as $style) {
      $styled = array_merge($settings, BlazyFile::transformDimensions($style, $settings, $initial));

      // In order to avoid layout reflow, we get dimensions beforehand.
      $srcset[$styled['width']] = round((($styled['height'] / $styled['width']) * 100), 2);
    }

    // Sort the srcset from small to large image width or multiplier.
    ksort($srcset);

    // Informs individual images that dimensions are already set once.
    // Dynamic aspect ratio is useless without JS.
    $settings['blazy_data']['dimensions'] = $srcset;
    $settings['padding_bottom'] = end($srcset);
    $settings['_dimensions'] = TRUE;
  }

  /**
   * Provides Responsive image sources relevant for link preload.
   */
  public static function sources(array &$settings = []) {
    if (!($manager = Blazy::breakpointManager())) {
      return [];
    }

    $fallback = NULL;
    $sources = $variables = [];
    $style = $settings['resimage'];

    foreach (['uri', 'width', 'height'] as $key) {
      $variables[$key] = $settings['_' . $key] ?? NULL;
    }

    if (!empty($variables['uri'])) {
      $breakpoints = array_reverse($manager->getBreakpointsByGroup($style->getBreakpointGroup()));
      $function = '_responsive_image_build_source_attributes';
      if (is_callable($function)) {
        $fallback = \_responsive_image_image_style_url($style->getFallbackImageStyle(), $variables['uri']);
        foreach ($style->getKeyedImageStyleMappings() as $breakpoint_id => $multipliers) {
          if (isset($breakpoints[$breakpoint_id])) {
            $sources[] = $function($variables, $breakpoints[$breakpoint_id], $multipliers);
          }
        }
      }
    }

    $settings['sources'] = empty($sources) ? [] : [
      'items' => $sources,
      'fallback' => $fallback,
    ];
  }

  /**
   * Returns the Responsive image styles and caches tags.
   *
   * @param object $responsive
   *   The responsive image style entity.
   *
   * @return array|mixed
   *   The responsive image styles and cache tags.
   */
  public static function getStyles($responsive) {
    $cache_tags = $responsive->getCacheTags();
    $image_styles = \blazy()->entityLoadMultiple('image_style', $responsive->getImageStyleIds());

    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }
    return ['caches' => $cache_tags, 'styles' => $image_styles];
  }

}
