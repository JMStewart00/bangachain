<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\blazy\Dejavu\BlazyEntityBase;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for splide entity reference formatters without field details.
 *
 * @see \Drupal\splide_paragraphs\Plugin\Field\FieldFormatter
 * @see \Drupal\splide_entityreference\Plugin\Field\FieldFormatter
 */
abstract class SplideEntityFormatterBase extends BlazyEntityBase implements ContainerFactoryPluginInterface {

  use SplideFormatterViewTrait;
  use SplideVanillaWithNavTrait;
  use SplideFormatterTrait {
    buildSettings as traitBuildSettings;
  }

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

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
   * Builds the settings.
   *
   * @todo inherit and extend parent post Blazy 2.x release.
   */
  public function buildSettings() {
    return ['blazy' => TRUE, 'vanilla' => TRUE] + $this->traitBuildSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $views_ui    = $this->getFieldSetting('handler') == 'default';
    $bundles     = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];
    $text_fields = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts       = $admin->getFieldOptions($bundles, $text_fields, $target_type);
    $texts2      = $admin->getValidFieldOptions($bundles, $target_type);

    return [
      'fieldable_form'         => TRUE,
      'image_style_form'       => TRUE,
      'images'                 => $admin->getFieldOptions($bundles, ['image'], $target_type),
      'no_layouts'             => TRUE,
      'no_image_style'         => TRUE,
      'responsive_image'       => FALSE,
      'thumb_captions'         => $texts,
      'thumb_positions'        => TRUE,
      'thumbnail_style'        => TRUE,
      'nav'                    => TRUE,
      'nav_state'              => TRUE,
      'vanilla'                => TRUE,
      'pagination_texts' => $texts2,
    ] + $this->getCommonScopedFormElements() + parent::getScopedFormElements();
  }

}
