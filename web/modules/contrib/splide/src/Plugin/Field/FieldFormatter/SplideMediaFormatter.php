<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'splide media' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_media",
 *   label = @Translation("Splide Media"),
 *   description = @Translation("Display the referenced entities as a Splide slider."),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideMediaFormatter extends SplideEntityReferenceFormatterBase {

  use SplideFormatterViewTrait;

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    // If text pagination is enabled and configured, we need to build an array
    // of strings to pass to the JavaScript.
    $this->checkTextPagination($entities);

    return $this->commonViewElements($items, $langcode, $entities);
  }

  /**
   * Builds the settings.
   *
   * @todo inherit and extends parent post blazy:2.x.
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + $this->traitBuildSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    return [
      'grid_form' => $multiple,
      'style'     => $multiple,
    ] + $this->getCommonScopedFormElements() + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'media';
  }

}
