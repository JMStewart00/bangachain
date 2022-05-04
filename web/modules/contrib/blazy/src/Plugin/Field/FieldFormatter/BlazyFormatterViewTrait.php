<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * A Trait common for all blazy, including its sub-modules, formatters.
 *
 * Since 2.9 this can replace and remove sub-module FormatterViewTrait anytime
 * for Media or Entity related formatters. For basic texts, use
 * self::baseViewElements() instead to by-pass
 * theme_[blazy|slick|splide|gridstack|mason|etc.]() routines.
 */
trait BlazyFormatterViewTrait {

  // Import once for very minimal difference.
  use BlazyFormatterViewBaseTrait;

  /**
   * Returns similar view elements across sub-modules.
   */
  protected function commonViewElements(
    FieldItemListInterface $items,
    $langcode,
    array $entities = [],
    array $settings = []
  ) {
    // Early opt-out if the field is empty.
    if ($items->isEmpty()) {
      return [];
    }

    // Collects specific settings to this formatter.
    $defaults = $this->buildSettings();
    $settings = $settings ? array_merge($defaults, $settings) : $defaults;

    $this->preSettings($settings, $langcode);

    // Build the settings.
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $entities = empty($entities) ? [] : array_values($entities);
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $elements = $entities ?: $items;
    $this->buildElements($build, $elements, $langcode);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    // Pass to manager for easy updates to all Blazy formatters.
    $output = $this->manager->build($build);

    // Return without field markup, if not so configured, else field.html.twig.
    return empty($settings['use_theme_field']) ? $output : [$output];
  }

}
