<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatterBase;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for splide image and file ER formatters.
 */
abstract class SplideFileFormatterBase extends BlazyFileFormatterBase {

  use SplideFormatterTrait;
  use SplideFormatterViewTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'image');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::imageSettings();
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

    return $this->commonViewElements($items, $langcode, $entities);
  }

  /**
   * Build the splide carousel elements.
   */
  public function buildElements(array &$build, $files) {
    $settings   = &$build['settings'];
    $item_id    = $settings['item_id'];
    $tn_caption = empty($settings['nav_caption']) ? NULL : $settings['nav_caption'];

    foreach ($files as $delta => $file) {
      $settings['delta'] = $delta;
      $settings['type'] = 'image';

      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $file->_referringItem;

      $settings['file_tags'] = $file->getCacheTags();
      $settings['uri']       = $file->getFileUri();

      $element = ['item' => $item, 'settings' => $settings];

      // @todo Remove, no longer file entity/VEF/M for pure Media.
      $this->buildElement($element, $file);
      $settings = $element['settings'];

      // Image with responsive image, lazyLoad, and lightbox supports.
      $element[$item_id] = $this->formatter->getBlazy($element);

      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          $element['caption'][$caption] = empty($element['item']->{$caption}) ? [] : ['#markup' => Xss::filterAdmin($element['item']->{$caption})];
        }
      }

      // Build individual splide item.
      $build['items'][$delta] = $element;

      // Build individual splide thumbnail.
      if (!empty($settings['nav'])) {
        $thumb = ['settings' => $settings];

        // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
        $thumb[$item_id] = $this->formatter->getThumbnail($settings, $element['item']);
        $thumb['caption'] = empty($element['item']->{$tn_caption}) ? [] : ['#markup' => Xss::filterAdmin($element['item']->{$tn_caption})];

        $build['nav']['items'][$delta] = $thumb;
        unset($thumb);
      }

      unset($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $captions = ['title' => $this->t('Title'), 'alt' => $this->t('Alt')];
    return [
      'namespace'       => 'splide',
      'nav'             => TRUE,
      'thumb_captions'  => $captions,
      'thumb_positions' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
