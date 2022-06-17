<?php

namespace Drupal\splide;

use Drupal\splide\Entity\Splide;
use Drupal\blazy\BlazyFormatter;

/**
 * Provides Splide field formatters utilities.
 */
class SplideFormatter extends BlazyFormatter implements SplideFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];
    $settings += SplideDefault::htmlSettings();

    // Splide specific stuffs.
    $settings['_unload'] = FALSE;

    // @todo move it into self::preSettingsData() post Blazy 2.10.
    $optionset = Splide::verifyOptionset($build, $settings['optionset']);

    // Prepare integration with Blazy.
    $blazies = $settings['blazies'];
    $blazies->set('initial', $optionset->getSetting('start'));

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    parent::preBuildElements($build, $items, $entities);

    $settings = &$build['settings'];

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

}
