<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Splide File' formatter to get ME within images.
 *
 * This is not 'Splide Media', instead a simple mix of image and optional video.
 *
 * @todo TBD; deprecate for core Media and remove post/ prior to 3.x release.
 * @todo Fix deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
 *   \Drupal\splide\Plugin\Field\FieldFormatter\SplideMediaFormatter instead.
 */
class SplideFileFormatter extends SplideFileFormatterBase {

  use SplideFormatterTrait {
    pluginSettings as traitPluginSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity) {
    $this->blazyOembed->build($build, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::getSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    $this->traitPluginSettings($blazies, $settings);
    $blazies->set('is.blazy', TRUE);

    // @todo remove.
    $settings['blazy'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
