<?php

namespace Drupal\splide;

use Drupal\Component\Utility\UrlHelper;
use Drupal\splide\Entity\Splide;
use Drupal\blazy\BlazyFormatter;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Provides Splide field formatters utilities.
 */
class SplideFormatter extends BlazyFormatter implements SplideFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Prepare integration with Blazy.
    $settings['item_id']   = 'slide';
    $settings['namespace'] = 'splide';

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    parent::preBuildElements($build, $items, $entities);

    $settings = &$build['settings'];

    // Splide specific stuffs.
    $build['optionset'] = Splide::loadWithFallback($settings['optionset']);

    // Only display thumbnail nav if having at least 2 slides. This might be
    // an issue such as for ElevateZoom Plus module, but it should work it out.
    if (!isset($settings['nav'])) {
      $settings['nav'] = !empty($settings['optionset_nav']) && isset($items[1]);
    }

    // Do not bother for SplideTextFormatter, or when vanilla is on.
    if (empty($settings['navless'])) {
      $build['optionset']->whichLazy($settings);
    }

    // Only trim overridables options if disabled.
    if (empty($settings['override']) && isset($settings['overridables'])) {
      $settings['overridables'] = array_filter($settings['overridables']);
    }

    $this->getModuleHandler()->alter('splide_settings', $build, $items);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove post Blazy 2.5+.
   */
  public function getThumbnail(array $settings = [], $item = NULL) {
    if (!empty($settings['uri'])) {
      $external = UrlHelper::isExternal($settings['uri']);
      return [
        '#theme'      => $external ? 'image' : 'image_style',
        '#style_name' => empty($settings['thumbnail_style']) ? 'thumbnail' : $settings['thumbnail_style'],
        '#uri'        => $settings['uri'],
        '#item'       => $item,
        '#alt'        => $item && $item instanceof ImageItem ? $item->getValue()['alt'] : '',
      ];
    }
    return [];
  }

}
