<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
// @todo enabled post Blazy:2.10:
// use Drupal\blazy\Field\BlazyEntityVanillaBase;
use Drupal\blazy\Dejavu\BlazyEntityBase as BlazyEntityVanillaBase;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for splide entity reference formatters without field details.
 *
 * @see \Drupal\splide\Plugin\Field\FieldFormatter\SplideEntityReferenceFormatterBase
 * @see \Drupal\splide\Plugin\Field\FieldFormatter\SplideParagraphsFormatter
 */
abstract class SplideEntityFormatterBase extends BlazyEntityVanillaBase {

  use SplideVanillaWithNavTrait;
  use SplideFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::extendedSettings() + parent::defaultSettings();
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
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $bundles     = $this->getAvailableBundles();
    $text_fields = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts       = $this->getFieldOptions($text_fields);
    $texts2      = $admin->getValidFieldOptions($bundles, $target_type);

    return [
      'fieldable_form'   => TRUE,
      'image_style_form' => TRUE,
      'images'           => $this->getFieldOptions(['image']),
      'thumb_captions'   => $texts,
      'thumb_positions'  => TRUE,
      'thumbnail_style'  => TRUE,
      'nav'              => TRUE,
      'nav_state'        => TRUE,
      'pagination_texts' => $texts2,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple();
  }

}
