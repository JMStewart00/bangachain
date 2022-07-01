<?php

namespace Drupal\easy_responsive_images;

/**
 * Provides an interface for the EasyResponsiveImagesManager.
 */
interface EasyResponsiveImagesManagerInterface {

  /**
   * Get all available aspect ratios.
   *
   * @return array
   *   Return options array.
   */
  public function getAspectRatios(): array;

  /**
   * Get all images by a specific aspect ratio as an array.
   *
   * @param string $uri
   *   The uri.
   * @param string $aspect_ratio
   *   The aspect ratio.
   *
   * @return array
   *   Returns style_urls array
   *   consisting of url, width, height & srcset_url.
   */
  public function getImagesByAspectRatio(string $uri, string $aspect_ratio): array;

  /**
   * Get all images, where no aspect ratio should be used.
   *
   * @param string $uri
   *   The uri.
   *
   * @return array
   *   Returns style_urls as an array
   *   consisting of url, width, height, srcset_url.
   */
  public function getImagesByScale(string $uri): array;

}
