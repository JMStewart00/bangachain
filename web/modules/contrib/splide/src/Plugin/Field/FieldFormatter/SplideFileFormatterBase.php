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
    $settings   = $build['settings'];
    $blazies    = $settings['blazies'];
    $item_id    = $blazies->get('item.id');
    $caption_id = 'caption';
    $tn_caption = empty($settings['nav_caption']) ? NULL : $settings['nav_caption'];
    $elements   = $this->getElements($build, $files, $caption_id);

    foreach ($elements as $delta => $element) {
      $sets = $element['settings'];
      $captions = $element[$caption_id] ?? [];

      // Do not pass captions to theme_blazy().
      unset($element[$caption_id]);

      // Image with responsive image, lazyLoad, and lightbox supports.
      $element[$item_id] = $this->formatter->getBlazy($element, $delta);

      // Build captions if so configured.
      $element[$caption_id] = $captions;

      // Build individual splide item.
      $build['items'][] = $element;

      // Build individual splide thumbnail.
      if (!empty($sets['nav'])) {
        $item = $element['item'];
        $nav = ['settings' => $sets];

        // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
        $nav[$item_id] = empty($sets['thumbnail_style']) ? [] : $this->formatter->getThumbnail($sets, $item);

        $markup = empty($item->{$tn_caption}) ? [] : [
          '#markup' => Xss::filterAdmin($item->{$tn_caption}),
        ];
        $nav[$caption_id] = $tn_caption ? $markup : [];

        $build['nav']['items'][] = $nav;
        unset($nav);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $captions = ['title' => $this->t('Title'), 'alt' => $this->t('Alt')];
    return [
      'namespace'       => 'splide',
      'nav'             => TRUE,
      'thumb_captions'  => $captions,
      'thumb_positions' => TRUE,
    ] + parent::getPluginScopes();
  }

}
