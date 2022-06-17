<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyField;

/**
 * A Trait common for splide vanilla formatters.
 */
trait SplideVanillaWithNavTrait {

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity, $langcode) {
    parent::buildElement($build, $entity, $langcode);

    // Supports Vanilla with thumbnail navigation.
    $settings = $build['settings'];
    $blazies = $settings['blazies'];

    if (!empty($settings['vanilla']) && !empty($settings['nav'])) {
      $delta = $blazies->get('delta');
      $element = ['settings' => $settings, 'item' => NULL];
      // Build media item including custom highres video thumbnail.
      // @todo re-check/ refine for Paragraphs, etc.
      $this->blazyOembed->build($element, $entity);

      $this->buildElementThumbnail($build, $element, $entity, $delta);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildElementThumbnail(array &$build, $element, $entity, $delta) {
    // @todo move it to Splide as too specific for Splide which has thumbnail.
    // The settings in $element has updated metadata extracted from media.
    $settings = $element['settings'];
    if (empty($settings['nav'])) {
      return;
    }

    $blazies    = $settings['blazies'];
    $item_id    = $blazies->get('item.id');
    $view_mode  = $settings['view_mode'] ?? '';
    $caption_id = 'caption';
    $item       = $element['item'] ?? NULL;

    $element[$caption_id] = [];

    // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
    $element[$item_id] = empty($settings['thumbnail_style']) ? [] : $this->formatter->getThumbnail($settings, $item);

    if ($name = $settings['nav_caption'] ?? '') {
      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      if ($item) {
        // Provides basic captions based on image attributes (Alt, Title).
        foreach (['title', 'alt'] as $attribute) {
          if ($name == $attribute && $caption = trim($item->{$attribute} ?? '')) {
            $markup = Xss::filter($caption, BlazyDefault::TAGS);
            $element[$caption_id] = ['#markup' => $markup];
          }
        }
      }

      // Otherwise a caption field.
      if (empty($element[$caption_id])) {
        $element[$caption_id] = BlazyField::view($entity, $name, $view_mode);
      }
    }

    $build['nav']['items'][$delta] = $element;
  }

  /**
   * If text pagination is configured, pass strings to the JavaScript.
   */
  protected function checkTextPagination(array $entities) {
    if ($pagination_text = $this->getSetting('pagination_text')) {
      $pagination_texts = [];
      foreach ($entities as $entity) {
        if (!isset($entity->{$pagination_text})) {
          continue;
        }
        if ($field = $entity->get($pagination_text)) {
          $value = $field->getString();
          $value = $value ? Xss::filter($value, BlazyDefault::TAGS) : NULL;
          $pagination_texts[] = $value ?: $this->t('Missing navigation label!');
        }
      }

      if ($pagination_texts) {
        $this->setSetting('pagination_texts', $pagination_texts);
      }
    }
  }

}
