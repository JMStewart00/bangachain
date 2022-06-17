<?php

namespace Drupal\splide\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\blazy\Blazy;
use Drupal\blazy\Plugin\Filter\BlazyFilterBase;
use Drupal\blazy\Plugin\Filter\BlazyFilterUtil as Util;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for a Splide.
 *
 * Best after Blazy, Align images, caption images.
 *
 * @Filter(
 *   id = "splide_filter",
 *   title = @Translation("Splide"),
 *   description = @Translation("Creates slideshow/ carousel with Splide shortcode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "optionset" = "default",
 *     "media_switch" = "",
 *   },
 *   weight = 4
 * )
 */
class SplideFilter extends BlazyFilterBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->admin = $container->get('splide.admin');
    $instance->manager = $container->get('splide.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => array_merge($this->pluginDefinition['settings'], SplideDefault::filterSettings()),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $this->result = $result = new FilterProcessResult($text);
    $this->langcode = $langcode;

    if (empty($text) || stristr($text, '[splide') === FALSE) {
      return $result;
    }

    $attachments = [];
    $settings = $this->buildSettings($text);
    $text = Util::unwrap($text, 'splide', 'slide');
    $dom = Html::load($text);
    $nodes = Util::validNodes($dom, ['splide']);

    if (count($nodes) > 0) {
      foreach ($nodes as $node) {
        if ($output = $this->build($node, $settings)) {
          $this->render($node, $output);
        }
      }

      $attach = Util::attach($settings);
      $attachments = $this->manager->attach($attach);
    }

    // Attach Blazy component libraries.
    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments($attachments);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings($text) {
    $settings = parent::buildSettings($text);

    // Provides alter like formatters to modify at one go, even clumsy here.
    $build = ['settings' => $settings];
    $this->manager->getModuleHandler()->alter('splide_settings', $build, $this->settings);
    return array_merge($settings, $build['settings']);
  }

  /**
   * {@inheritdoc}
   */
  protected function preSettings(array &$settings, $text) {
    $settings['no_item_container'] = TRUE;
    $settings['item_id'] = 'slide';
    $settings['namespace'] = 'splide';
    $settings['visible_items'] = 0;

    parent::preSettings($settings, $text);
  }

  /**
   * Build the splide.
   */
  private function build($object, array &$settings) {
    $attribute = $object->getAttribute('data');
    $blazies = $settings['blazies'];

    $settings['id'] = $settings['gallery_id'] = $id = Blazy::getHtmlId(str_replace('_', '-', $settings['plugin_id']));

    $blazies->set('lightbox.gallery_id', $id)
      ->set('css.id', $id);

    if (!empty($attribute) && mb_strpos($attribute, ":") !== FALSE) {
      return $this->byEntity($object, $settings, $attribute);
    }

    return $this->byDom($object, $settings);
  }

  /**
   * Build the splide using the node ID and field_name.
   */
  private function byEntity($object, array &$settings, $attribute) {
    [$entity_type, $id, $field_name, $field_image] = array_pad(array_map('trim', explode(":", $attribute, 4)), 4, NULL);
    if (empty($field_name)) {
      return [];
    }

    $entity = $this->manager->entityLoad($id, $entity_type);

    $blazies = $settings['blazies'];
    $blazies->set('entity.id', $id)
      ->set('entity.type_id', $entity_type)
      ->set('field.name', $field_name);

    // @todo remove.
    $settings['field_name'] = $field_name;
    $settings['image'] = $field_image;

    if ($entity && $entity->hasField($field_name)) {
      $list = $entity->get($field_name);
      $definition = $list ? $list->getFieldDefinition() : NULL;
      $field_type = $settings['field_type'] = $definition
        ? $definition->get('field_type') : '';

      $settings['bundle'] = $bundle = $entity->bundle();
      $settings['count'] = count($list);

      $blazies->set('entity.bundle', $bundle);
      $blazies->set('field.type', $field_type);

      $build = ['settings' => $settings];

      $this->prepareBuild($build, $object);
      $settings = $build['settings'];

      if ($list) {
        $field_settings = $definition->get('settings');
        $handler = $field_settings['handler'] ?? NULL;
        $texts = ['text', 'text_long', 'text_with_summary'];

        $formatter = NULL;
        // @todo refine for main stage, etc.
        if ($field_type == 'entity_reference' || $field_type == 'entity_reference_revisions') {
          if ($handler == 'default:media') {
            $formatter = 'splide_media';
          }
          else {
            // @todo refine for Paragraphs, etc.
            if ($field_type == 'entity_reference_revisions') {
              $formatter = 'splide_paragraphs_media';
            }
            else {
              $settings['vanilla'] = TRUE;
              $formatter = 'splide_entityreference';
            }
          }
        }
        elseif ($field_type == 'image') {
          $formatter = 'splide_image';
        }
        elseif (in_array($field_type, $texts)) {
          $formatter = 'splide_text';
        }

        if ($formatter) {
          return $list->view([
            'type' => $formatter,
            'settings' => $settings,
          ]);
        }
      }
    }

    return [];
  }

  /**
   * Build the splide using the DOM lookups.
   */
  private function byDom($object, array $settings) {
    $text = Util::getHtml($object);
    if (empty($text)) {
      return [];
    }

    $dom = Html::load($text);
    $nodes = Util::getNodes($dom, '//slide');
    if ($nodes->length == 0) {
      return [];
    }

    $blazies = $settings['blazies'];
    $settings['count'] = $count = $nodes->length;
    $blazies->set('count', $count);

    $build = ['settings' => $settings];
    $this->prepareBuild($build, $object);

    foreach ($nodes as $delta => $node) {
      if (!($node instanceof \DOMElement)) {
        continue;
      }

      $sets    = &$build['settings'];
      $sets   += SplideDefault::itemSettings();
      $blazies = $sets['blazies']->reset($sets);

      $sets['delta'] = $delta;
      $blazies->set('delta', $delta);

      if ($thumb = $node->getAttribute('data-thumb')) {
        $sets['thumbnail_uri'] = $thumb;
        $blazies->set('thumbnail.uri', $thumb);
      }

      $element = ['caption' => [], 'item' => NULL, 'settings' => $sets];

      $this->buildItem($element, $node, $delta);

      if (empty($element['slide'])) {
        $element['slide'] = ['#markup' => $dom->saveHtml($node)];
      }

      $build['items'][$delta] = $element;

      // Build individual splide thumbnail.
      if (!empty($sets['nav'])) {
        $this->buildNav($build, $element, $delta);
      }
    }

    return $this->manager->build($build);
  }

  /**
   * Build the slide item.
   */
  private function buildItem(array &$build, $node, $delta) {
    $text = Util::getHtml($node);
    if (empty($text)) {
      return;
    }

    $sets     = &$build['settings'];
    $blazies  = $sets['blazies'];
    $dom      = Html::load($text);
    $xpath    = new \DOMXPath($dom);
    $children = $xpath->query("//iframe | //img");

    $this->buildItemAttributes($build, $node, $delta);

    if ($children->length > 0) {
      // Can only have the first found for the main slide stage.
      $child = Util::getValidNode($children);

      // @todo use this post Blazy:2.10, and remove the three build below.
      // Build item settings, image, and caption.
      // $this->buildItemContent($build, $child, $delta);
      // Provides individual item settings.
      $this->buildItemSettings($build, $child, $delta);

      // Extracts image item from SRC attribute.
      $this->buildImageItem($build, $child, $delta);

      // Extracts image caption if available.
      $this->buildImageCaption($build, $child);

      $uri = $sets['uri'] ?? '';
      $uri = $blazies->get('image.uri') ?: $uri;
      if ($uri) {
        $build['slide'] = $this->blazyManager->getBlazy($build, $delta);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildImageCaption(array &$build, &$node) {
    $item = parent::buildImageCaption($build, $node);

    if (!empty($build['captions'])) {
      $build['caption'] = $build['captions'];
      unset($build['captions']);
    }
    return $item;
  }

  /**
   * Prepares the splide.
   */
  private function prepareBuild(array &$build, $node) {
    $sets    = &$build['settings'];
    $blazies = $sets['blazies'];
    $options = [];

    if ($check = $node->getAttribute('options')) {
      $check = str_replace("'", '"', $check);
      if ($check) {
        $options = Json::decode($check);
      }
    }

    // Extract settings from attributes.
    if (method_exists($this, 'extractSettings')) {
      $this->extractSettings($node, $sets);
    }
    // @todo remove post Blazy:2.10.
    elseif (method_exists($this, 'prepareSettings')) {
      $this->prepareSettings($node, $sets);
    }

    if (!isset($sets['nav'])) {
      $sets['nav'] = (!empty($sets['optionset_nav']) && $sets['count'] > 1);
    }

    $blazies->set('is.nav', $sets['nav']);

    $sets['_grid'] = !empty($sets['style']) && !empty($sets['grid']);
    $sets['visible_items'] = $sets['_grid'] && empty($sets['visible_items']) ? 6 : $sets['visible_items'];

    $build['options'] = $options;
  }

  /**
   * Build the splide navigation.
   */
  private function buildNav(array &$build, array $element, $delta) {
    $sets    = $element['settings'];
    $item    = $element['item'] ?? NULL;
    $caption = $sets['nav_caption'] ?? NULL;
    $text    = ($caption && $item && !empty($item->{$caption}))
      ? ['#markup' => Xss::filterAdmin($item->{$caption})] : [];

    // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
    $thumb = [
      'settings' => $sets,
      'slide' => $this->manager->getThumbnail($sets, $item),
      'caption' => $text,
    ];

    $build['nav']['items'][$delta] = $thumb;
    unset($thumb);
  }

  /**
   * Returns the expected caption DOMelement.
   */
  protected function getCaptionElement($node) {
    $caption = parent::getCaptionElement($node);

    if (empty($caption) && $node->parentNode) {
      // @todo use post Blazy 2.10.
      // $caption = $this->getCaptionFallback($node);
      $parent = $node->parentNode->parentNode;
      if ($parent && $grandpa = $parent->parentNode) {
        if ($grandpa->parentNode) {
          $divs = $grandpa->parentNode->getElementsByTagName('div');
        }
        else {
          $divs = $grandpa->getElementsByTagName('div');
        }

        if ($divs) {
          foreach ($divs as $div) {
            $class = $div->getAttribute('class');
            if ($class == 'blazy__caption') {
              $caption = $div;
              break;
            }
          }
        }
      }
    }
    return $caption;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return file_get_contents(dirname(__FILE__) . "/FILTER_TIPS.txt");
    }
    else {
      return $this->t('<b>Splide</b>: Create a slideshow/ carousel: <br><ul><li><b>With self-closing using data entity, <code>data=ENTITY_TYPE:ID:FIELD_NAME:FIELD_IMAGE</code></b>:<br><code>[splide data="node:44:field_media" /]</code>. <code>FIELD_IMAGE</code> is optional.</li><li><b>With any HTML</b>: <br><code>[splide settings="{}" options="{}"]...[slide]...[/slide]...[/splide]</li></code></ul>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $definition = [
      'settings' => $this->settings,
      'background' => TRUE,
      'caches' => FALSE,
      'image_style_form' => TRUE,
      'media_switch_form' => TRUE,
      'multimedia' => TRUE,
      'thumb_captions' => 'default',
      'nav_positions' => TRUE,
      'nav' => TRUE,
    ];

    $element = [];
    $this->admin->buildSettingsForm($element, $definition);

    if (isset($element['media_switch'])) {
      unset($element['media_switch']['#options']['content']);
    }

    if (isset($element['closing'])) {
      $element['closing']['#suffix'] = $this->t('Best after Blazy, Align / Caption images filters -- all are not required to function. Not tested against, nor dependent on, Shortcode module. Be sure to place Splide filter before any other Shortcode if installed.');
    }

    return $element;
  }

}
