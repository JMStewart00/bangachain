<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'Splide Entity Reference' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_entityreference",
 *   label = @Translation("Splide Entity Reference"),
 *   description = @Translation("Display the entity reference (revisions) as a Splide Slider."),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideEntityReferenceFormatter extends SplideEntityFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple();
  }

}
