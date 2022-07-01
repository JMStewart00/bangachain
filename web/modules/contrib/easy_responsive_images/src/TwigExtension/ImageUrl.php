<?php

namespace Drupal\easy_responsive_images\TwigExtension;

use Drupal\image\Entity\ImageStyle;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filter to create a local image URL from an URI or external URL.
 */
class ImageUrl extends AbstractExtension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   An array of twig filters.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('image_url', [$this, 'createImageUrl']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   The extension name.
   */
  public function getName(): string {
    return 'easy_responsive_images.image_url';
  }

  /**
   * Get a local image URL from an URI or external URL.
   *
   * @param string $uri
   *   File URI or external image URL.
   * @param string $style
   *   The image style name.
   *
   * @return array
   *   The local image URL render-array.
   */
  public static function createImageUrl(string $uri, string $style): array {
    $image_style = ImageStyle::load($style);
    if (!$uri || !$image_style || !$image_style->supportsUri($uri)) {
      return [];
    }

    // If we do not have a stream wrapper, it might be an external URL. If the
    // imagecache_external module is installed, try to get a local URI using
    // that module.
    if (\Drupal::moduleHandler()->moduleExists('imagecache_external')) {
      $stream_wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
      if (!$stream_wrapper) {
        $uri = imagecache_external_generate_path($uri);
      }
    }

    if (!$uri) {
      return [];
    }

    $file_url = file_url_transform_relative($image_style->buildUrl($uri));

    // If the imageapi_optimize_webp module is installed and the browser
    // supports webp, return the webp version of the image.
    if (\Drupal::moduleHandler()->moduleExists('imageapi_optimize_webp')) {
      if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== FALSE) {
        $path_parts = pathinfo($uri);
        $original_extension = '.' . $path_parts['extension'];
        $pos = strrpos($file_url, $original_extension);
        if ($pos !== FALSE) {
          $file_url = substr_replace($file_url, $original_extension . '.webp', $pos, strlen($original_extension));
        }
      }
    }

    $build['#markup'] = $file_url;
    $build['#cache']['contexts'] = ['headers:accept'];
    return $build;
  }

}
