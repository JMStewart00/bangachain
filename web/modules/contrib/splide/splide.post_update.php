<?php

/**
 * @file
 * Post update hooks for Splide.
 */

/**
 * Removed deprecated v2 options, and update drag to string.
 */
function splide_post_update_remove_v2_deprecated_options() {
  $config = \Drupal::configFactory();
  foreach ($config->listAll('splide.optionset.') as $optionset_name) {
    $optionset = $config->getEditable($optionset_name);

    // Removed deprecated options.
    foreach (['dragAngleThreshold', 'accessibility', 'throttle'] as $key) {
      $optionset->clear('options.settings.' . $key);
    }

    // Added the new default options.
    if ($optionset_name == 'default') {
      $optionset->set('options.settings.dragMinThreshold', 15);
      $optionset->set('options.settings.mediaQuery', 'min');
    }

    // Cast to string to support `free` aside from boolean.
    // If optimized, similar to default, skip.
    $settings = $optionset->get('options.settings');
    if (isset($settings['drag'])) {
      $drag = $settings['drag'];
      $optionset->set('options.settings.drag', $drag ? 'true' : 'false');
    }

    // Also at breakpoints if any.
    if ($breakpoint = $optionset->get('breakpoint')) {
      foreach (range(0, ((int) $breakpoint - 1)) as $key) {
        $setting = 'options.breakpoints.' . $key . '.settings';
        $settings = $optionset->get($setting);

        if (isset($settings['drag'])) {
          $drag = $optionset->get($setting . '.drag');
          $optionset->set($setting . '.drag', $drag ? 'true' : 'false');
        }
      }
    }

    $optionset->save(TRUE);
  }
}

/**
 * Change height ratio to string.
 */
function splide_post_update_change_height_ratio_type() {
  $config = \Drupal::configFactory()->getEditable('splide.optionset.default');
  $config->set('options.settings.heightRatio', '0');
  $config->save(TRUE);
}

/**
 * Added a service and pagination tab options #3256953.
 */
function splide_post_add_pagination_tab_options() {
  // Empty block to clear cache.
}
