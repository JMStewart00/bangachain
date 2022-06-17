<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * A Trait common for all blazy formatters.
 *
 * @todo remove post Blazy:2.10, and use BlazyFormatterViewTrait instead.
 */
trait SplideFormatterViewTrait {

  /**
   * Returns similar view elements.
   */
  public function deprecatedViewElements(FieldItemListInterface $items, $langcode, array $entities = [], array $settings = []) {
    // Collects specific settings to this formatter.
    $settings['langcode'] = $langcode;
    $settings = array_merge($this->buildSettings(), $settings);

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
    if (empty($settings['use_theme_field'])) {
      // Return field-vanilla without field markup.
      return $this->manager->build($build);
    }

    // Return as array to render in regular field.html.twig:
    return [$this->manager->build($build)];
  }

}
