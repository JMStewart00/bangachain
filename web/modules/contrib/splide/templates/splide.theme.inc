<?php

/**
 * @file
 * Hooks and preprocess functions for the Splide module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\blazy\Blazy;
use Drupal\splide\SplideDefault;
use Drupal\splide\Entity\Splide;

/**
 * Prepares variables for splide.html.twig templates.
 */
function template_preprocess_splide(&$variables) {
  $element = $variables['element'];
  foreach (['attributes', 'options', 'optionset', 'settings'] as $key) {
    $variables[$key] = $element["#$key"] ?? [];
  }

  $variables['settings'] += SplideDefault::htmlSettings();
  $sets = &$variables['settings'];
  $blazies = $settings['blazies'] ?? NULL;

  Splide::verifyOptionset($variables, $sets['optionset']);

  $optionset = $variables['optionset'];
  $variables['options'] += $optionset->getSettings();
  $key = 'content_attributes';
  $variables[$key] = $element["#$key"] ?? new Attribute();

  // Shortcuts.
  $js        = &$variables['options'];
  $display   = $variables['display'] = $sets['display'];
  $direction = $js['direction'] ?? 'ltr';
  $id        = empty($sets['id']) ? Blazy::getHtmlId('splide') : $sets['id'];
  $id        = 'splide-' . substr(md5($id), 0, 11);
  $attrs     = &$variables['attributes'];
  $classes   = empty($attrs['class']) ? [] : $attrs['class'];
  $minimal   = $sets['vanilla'] || $display == 'nav';

  // @see SplideManager::buildGrid(), and this should make sense.
  $sets['count']    = $sets['count'] ?? count($element['#items']);
  $sets['vertical'] = $direction == 'ttb';
  $attrs['id']      = $display == 'nav' ? $id . '-nav' : $id;
  $attrs['class']   = array_merge(['splide', 'splide--default'], $classes);

  foreach (SplideDefault::jsSettings() as $key => $default) {
    $sets[$key] = $js[$key] ?? $default;
  }

  // Blazy can still lazyload an unsplide.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  if ($display != 'nav' && $blazies) {
    if ($blazies->is('blazy') && !$blazies->is('unlazy')) {
      $js['lazyLoad'] = 'blazy';

      if (!empty($sets['background'])) {
        $js['cover'] = FALSE;
      }
    }
  }

  // Make splide language-direction-aware.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->getDirection() == 'rtl') {
    $attrs['dir'] = 'rtl';
    // Ensures to not affect vertical direction which is mixed with rtl & ltr.
    if (!$sets['vertical']) {
      $js['direction'] = 'rtl';
    }
  }

  // Prevents broken splide when only one item given, or an enforced unsplide.
  if (!$sets['unsplide']) {
    if ($display == 'main' && (!empty($js['down']) && !empty($js['downTarget']))) {
      $variables['arrow_down_attributes'] = new Attribute();
    }

    // Add the configuration as JSON object into the splide container.
    if ($json = $optionset->toJson($js)) {
      $js = array_filter($json, '\Drupal\splide\Entity\Splide::filterEmpty');
      $attrs['data-splide'] = Json::encode($js);
    }
  }

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $item_sets = array_merge($sets, (array) ($item['settings'] ?? []));
    $item_attrs = (array) ($item['attributes'] ?? []);
    $content_attrs = (array) ($item['content_attributes'] ?? []);
    $item_sets['current_item'] = $display;

    unset($item['settings'], $item['attributes'], $item['content_attributes'], $item['item']);

    $theme = $minimal ? 'minimal' : 'slide';
    $slide = [
      '#theme' => 'splide_' . $theme,
      '#item' => $item,
      '#delta' => $delta,
      '#settings' => $item_sets,
      '#attributes' => $item_attrs,
      '#content_attributes' => $content_attrs,
    ];

    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for splide-wrapper.html.twig templates.
 */
function template_preprocess_splide_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = $variables['element']["#$key"] ?? [];
  }
}

/**
 * Prepares variables for splide__slide.html.twig templates.
 */
function template_preprocess_splide_slide(&$variables) {
  _splide_preprocess_splide_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = $variables['item']['caption'][$key] ?? [];
  }

  $item = &$variables['item'];
  $sets = &$variables['settings'];
  $variables['caption_attributes'] = new Attribute();

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unsplide grid.
  // wrapper: Remove divities for a single item or grid to have clean markups.
  $item['slide']     = $item['slide'] ?? [];
  $item['caption']   = array_filter($item['caption']);
  $caption           = &$item['caption'];
  $sets['split']     = !empty($item) && (!empty($sets['caption']) || !empty($sets['title']));
  $sets['data']      = !empty($caption['alt']) || !empty($caption['title']) || !empty($caption['data']);
  $sets['fullwidth'] = !empty($sets['skin']) && strpos($sets['skin'], 'full') !== FALSE;
  $sets['grid']      = !empty($sets['grid']);
  $sets['detroy']    = $sets['current_item'] == 'main' && !empty($sets['grid']) && !empty($sets['unsplide']);
  $sets['use_ca']    = empty($sets['grid']) || !empty($variables['content_attributes']);
}

/**
 * Prepares common variables for splide item templates.
 */
function _splide_preprocess_splide_item(&$variables) {
  $element = $variables['element'];
  foreach (['attributes', 'content_attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? NULL : [];
    $variables[$key] = $element["#$key"] ?? $default;
  }

  $sets = &$variables['settings'];
  $sets['wrapper'] = $sets['current_item'] != 'grid';

  // Because Twig prepends new classes, not appends as expected by BEM.
  $attributes = &$variables['attributes'];
  $classes = empty($attributes['class']) ? [] : (array) $attributes['class'];
  $attributes['class'] = array_merge(['splide__slide', 'slide'], $classes);
}

/**
 * Prepares variables for splide-minimal.html.twig templates.
 */
function template_preprocess_splide_minimal(&$variables) {
  _splide_preprocess_splide_item($variables);
}
