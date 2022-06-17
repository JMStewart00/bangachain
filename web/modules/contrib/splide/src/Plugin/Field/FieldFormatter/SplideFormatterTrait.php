<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Trait common for splide formatters.
 */
trait SplideFormatterTrait {

  use BlazyFormatterTrait {
    injectServices as blazyInjectServices;
    getCommonFieldDefinition as blazyCommonFieldDefinition;
  }

  /**
   * Returns the splide admin service shortcut.
   */
  public function admin() {
    return \Drupal::service('splide.admin');
  }

  /**
   * Injects DI services.
   */
  protected static function injectServices($instance, ContainerInterface $container, $type = '') {
    $instance = static::blazyInjectServices($instance, $container, $type);
    $instance->formatter = $instance->blazyManager = $container->get('splide.formatter');
    $instance->manager = $container->get('splide.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->isMultiple();
  }

  /**
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    $blazies->set('item.id', 'slide')
      ->set('is.blazy', TRUE)
      ->set('namespace', 'splide');
  }

  /**
   * Defines the common scope for both front and admin.
   */
  public function getCommonFieldDefinition() {
    return ['namespace' => 'splide'] + $this->blazyCommonFieldDefinition();
  }

}
