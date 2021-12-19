<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'Splide Paragraphs Vanilla' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_paragraphs_vanilla",
 *   label = @Translation("Splide Paragraphs Vanilla"),
 *   description = @Translation("Display the vanilla paragraph as a Splide Slider."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideParagraphsVanillaFormatter extends SplideEntityFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    // Entity revision loading currently has no static/persistent cache and no
    // multiload. As entity reference checks _loaded, while we don't want to
    // indicate a loaded entity, when there is none, as it could cause errors,
    // we actually load the entity and set the flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {

        if ($item->entity) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple() && $storage->getSetting('target_type') === 'paragraph';
  }

}
