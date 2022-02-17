<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides file_BLAH BC for D8 - D10+ till D11 rules.
 *
 * @todo remove deprecated functions post D11, not D10, and when D8 is dropped.
 */
class BlazyFile {

  /**
   * The image style ID.
   *
   * @var array
   */
  private static $styleId;

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is valid.
   */
  public static function isValidUri($uri): bool {
    if (!empty($uri) && $manager = Blazy::streamWrapperManager()) {
      return $manager->isValidUri($uri);
    }
    return FALSE;
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @param string $uri
   *   The file uri.
   *
   * @return string
   *   Returns an absolute web-accessible URL string.
   */
  public static function createUrl($uri): string {
    if ($gen = Blazy::fileUrlGenerator()) {
      return $gen->generateAbsoluteString($uri);
    }

    $function = 'file_create_url';
    return is_callable($function) ? $function($uri) : '';
  }

  /**
   * Transforms an absolute URL of a local file to a relative URL.
   *
   * @param string $uri
   *   The file uri.
   * @param object $style
   *   The optional image style instance.
   *
   * @return string
   *   Returns an absolute URL of a local file to a relative URL.
   */
  public static function transformRelative($uri, $style = NULL): string {
    $url = $style ? $style->buildUrl($uri) : self::createUrl($uri);

    if ($gen = Blazy::fileUrlGenerator()) {
      return $gen->transformRelative($url);
    }

    $function = 'file_url_transform_relative';
    return is_callable($function) ? $function($url) : '';
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   */
  public static function buildUri($url): ?string {
    if (!UrlHelper::isExternal($url) && $normal_path = UrlHelper::parse($url)['path']) {
      // If the request has a base path, remove it from the beginning of the
      // normal path as it should not be included in the URI.
      $base_path = \Drupal::request()->getBasePath();
      if ($base_path && mb_strpos($normal_path, $base_path) === 0) {
        $normal_path = str_replace($base_path, '', $normal_path);
      }

      $public_path = Settings::get('file_public_path', 'sites/default/files');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && mb_strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return Blazy::streamWrapperManager()->normalizeUri($rel_path);
      }
    }
    return NULL;
  }

  /**
   * Provides image url based on the given settings.
   */
  public static function imageUrl(array &$settings): string {
    // Provides image_url, not URI, expected by lazyload.
    $uri = $settings['uri'] ?? $settings['_uri'];
    $valid = self::isValidUri($uri);
    $styled = $valid && empty($settings['unstyled']);
    $url = $settings['image_url'] ?? '';

    // Image style modifier can be multi-style images such as GridStack.
    if ($valid && !empty($settings['image_style']) && ($style = ImageStyle::load($settings['image_style']))) {
      $url = self::transformRelative($uri, ($styled ? $style : NULL));
      $settings['cache_tags'] = $style->getCacheTags();

      // Only re-calculate dimensions if not cropped, nor already set.
      if (empty($settings['_dimensions']) && empty($settings['responsive_image_style'])) {
        $settings = array_merge($settings, self::transformDimensions($style, $settings));
      }
    }
    else {
      $rel_url = $valid ? self::transformRelative($uri) : $uri;
      $url = empty($url) ? $rel_url : $url;
    }

    // Just in case, an attempted kidding gets in the way, relevant for UGC.
    $data_uri = mb_substr($url, 0, 10) === 'data:image';
    if (!empty($settings['_check_protocol']) && !$data_uri) {
      $url = UrlHelper::stripDangerousProtocols($url);
    }

    $settings['image_url'] = $url;
    return $url;
  }

  /**
   * Provides original unstyled image dimensions based on the given image item.
   */
  public static function imageDimensions(array &$settings, $item = NULL, $initial = FALSE): void {
    $width = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';
    $uri = $initial ? '_uri' : 'uri';

    if (empty($settings[$width]) && $item) {
      $settings[$width] = $item->width ?? NULL;
      $settings[$height] = $item->height ?? NULL;
    }

    // Only applies when Image style is empty, no file API, no $item,
    // with unmanaged VEF/ WYSIWG/ filter image without image_style.
    // Prevents 404 warning when video thumbnail missing for a reason.
    if (empty($settings['image_style']) && empty($settings[$width]) && !empty($settings[$uri])) {
      $abs = empty($settings['uri_root']) ? $settings[$uri] : $settings['uri_root'];
      if ($data = @getimagesize($abs)) {
        [$settings[$width], $settings[$height]] = $data;
      }
    }

    // Sometimes they are string, cast them integer to reduce JS logic.
    $settings[$width] = empty($settings[$width]) ? NULL : (int) $settings[$width];
    $settings[$height] = empty($settings[$height]) ? NULL : (int) $settings[$height];
  }

  /**
   * A wrapper for ImageStyle::transformDimensions().
   *
   * @param object $style
   *   The given image style.
   * @param array $data
   *   The data settings: _width, _height, _uri, width, height, and uri.
   * @param bool $initial
   *   Whether particularly transforms once for all, or individually.
   */
  public static function transformDimensions($style, array $data, $initial = FALSE): array {
    $uri = $initial ? '_uri' : 'uri';
    $key = hash('md2', ($style->id() . $data[$uri]));

    if (!isset(static::$styleId[$key])) {
      $width  = $initial ? '_width' : 'width';
      $height = $initial ? '_height' : 'height';

      $width  = $data[$width] ?? NULL;
      $height = $data[$height] ?? NULL;
      $dim    = ['width' => $width, 'height' => $height];

      // Funnily $uri is ignored at all core image effects.
      $style->transformDimensions($dim, $data[$uri]);

      // Sometimes they are string, cast them integer to reduce JS logic.
      if ($dim['width'] != NULL) {
        $dim['width'] = (int) $dim['width'];
      }
      if ($dim['height'] != NULL) {
        $dim['height'] = (int) $dim['height'];
      }

      static::$styleId[$key] = [
        'width' => $dim['width'],
        'height' => $dim['height'],
      ];
    }
    return static::$styleId[$key];
  }

  /**
   * Prepares CSS background image.
   */
  public static function backgroundImage(array $settings, $style = NULL) {
    return [
      'src' => $style ? self::transformRelative($settings['uri'], $style) : $settings['image_url'],
      'ratio' => round((($settings['height'] / $settings['width']) * 100), 2),
    ];
  }

  /**
   * Build thumbnails, also to provide placeholder for blur effect.
   */
  public static function placeholder(array &$settings, $style = NULL, $path = '') {
    if (empty($path) && ($style = \blazy()->entityLoad('thumbnail', 'image_style')) && self::isValidUri($settings['uri'])) {
      $path = $style->buildUri($settings['uri']);
    }

    if ($path && self::isValidUri($path)) {
      // Ensures the thumbnail exists before creating a dataURI.
      if (!is_file($path) && $style) {
        $style->createDerivative($settings['uri'], $path);
      }

      // Overrides placeholder with data URI based on configured thumbnail.
      if (is_file($path)) {
        $settings['placeholder_fx'] = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
        // Prevents double animations.
        $settings['use_loading'] = FALSE;
      }
    }
  }

  /**
   * Build thumbnails, also to provide placeholder for blur effect.
   */
  public static function thumbnailAndPlaceholder(array &$attributes, array &$settings) {
    $settings['placeholder_ui'] = $settings['placeholder'];
    $path = $style = '';
    // With CSS background, IMG may be empty, add thumbnail to the container.
    if (!$settings['is_external'] && $settings['thumbnail_style']) {
      $style = \blazy()->entityLoad($settings['thumbnail_style'], 'image_style');
      if ($style) {
        $path = $style->buildUri($settings['uri']);
        $attributes['data-thumb'] = $settings['thumbnail_url'] = self::transformRelative($settings['uri'], $style);

        if (!is_file($path) && self::isValidUri($path)) {
          $style->createDerivative($settings['uri'], $path);
        }
      }
    }

    // Supports unique thumbnail different from main image, such as logo for
    // thumbnail and main image for company profile.
    if (!empty($settings['thumbnail_uri'])) {
      $path = $settings['thumbnail_uri'];
      $attributes['data-thumb'] = $settings['thumbnail_url'] = self::transformRelative($path);
    }

    // Provides image effect if so configured unless being sandboxed.
    if (!$settings['is_sandboxed'] && $settings['fx']) {
      $attributes['class'][] = 'media--fx';

      // Ensures at least a hook_alter is always respected. This still allows
      // Blur and hook_alter for Views rewrite issues, unless global UI is set
      // which was already warned about anyway.
      if (empty($settings['placeholder_fx']) && !$settings['unstyled']) {
        self::placeholder($settings, $style, $path);
      }

      // Being a separated .b-blur with .b-lazy, this should work for any lazy.
      $attributes['data-animation'] = $settings['fx'];
    }

    // Mimicks private _responsive_image_image_style_url, #3119527.
    if (empty($settings['image_style']) && $settings['resimage']) {
      $fallback = $settings['resimage']->getFallbackImageStyle();
      if ($fallback == '_empty image_') {
        $placeholder = BlazyUtil::generatePlaceholder($settings['width'], $settings['height']);
        $settings['image_url'] = $settings['placeholder'] ?: $placeholder;
      }
      else {
        $settings['image_style'] = $fallback;
      }
    }
  }

  /**
   * Preload late-discovered resources for better performance.
   *
   * @see https://web.dev/preload-critical-assets/
   * @see https://caniuse.com/?search=preload
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
   * @see https://developer.chrome.com/blog/new-in-chrome-73/#more
   */
  public static function preload(array &$load, array $settings = []) {
    if (empty($settings['_uri'])) {
      return;
    }

    $mime = mime_content_type($settings['_uri']);
    [$type] = array_map('trim', explode('/', $mime, 2));

    $links = [];
    $sources = $settings['sources'] ?? [];
    if ($sources && $url = $sources['fallback']) {
      foreach ($sources['items'] as $key => $item) {
        if (!empty($item['srcset'])) {
          $mime = $item['type']->value() ?? $mime;
          [$type] = array_map('trim', explode('/', $mime, 2));
          $key = hash('md2', $url);
          $links[] = [
            [
              '#tag' => 'link',
              '#attributes' => [
                'rel' => 'preload',
                'as' => $type,
                'href' => $url,
                'type' => $mime,
                'imagesrcset' => $item['srcset']->value(),
                'imagesizes' => $item['sizes']->value(),
              ],
            ],
            'blazy_responsive_' . $type . $key,
          ];
        }
      }
    }
    else {
      $url = self::imageUrl($settings);
      $key = hash('md2', $url);
      $links[] = [
        [
          '#tag' => 'link',
          '#attributes' => [
            'rel' => 'preload',
            'as' => $type,
            'href' => $url,
            'type' => $mime,
          ],
        ],
        'blazy_' . $type . $key,
      ];
    }

    if ($links) {
      foreach ($links as $key => $value) {
        $load['html_head'][$key] = $value;
      }
    }
  }

}
