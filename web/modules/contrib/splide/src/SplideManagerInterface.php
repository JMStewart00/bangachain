<?php

namespace Drupal\splide;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\blazy\BlazyManagerInterface;

/**
 * Defines re-usable services and functions for splide plugins.
 */
interface SplideManagerInterface extends BlazyManagerInterface, TrustedCallbackInterface {

  /**
   * Returns a cacheable renderable array of a single splide instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of splide contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a splide instance, or empty array.
   */
  public function splide(array $build = []);

  /**
   * Returns a renderable array of both main and thumbnail splide instances.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of splide contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *   - thumb: An associative array of splide thumbnail following the same
   *     structure as the main display: $build['nav']['items'], etc.
   *
   * @return array
   *   The renderable array of both main and thumbnail splide instances.
   */
  public function build(array $build = []);

}
