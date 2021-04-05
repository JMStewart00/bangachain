<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

/**
 * Class IframeDefaultFormatter.
 *
 * @FieldFormatter(
 *  id = "iframe_default",
 *  module = "iframe",
 *  label = @Translation("Title, over iframe (default)"),
 *  field_types = {"iframe"}
 * )
 */
class IframeDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'url' => '',
      'title' => '',
      'width' => '',
      'height' => '',
      'class' => '',
      'frameborder' => '0',
      'scrolling' => '',
      'transparency' => '0',
      'tokensupport' => '0',
      'allowfullscreen' => '0',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();
    $entity = $items->getEntity();
    \iframe_debug(3, __METHOD__, $field_settings);
    \iframe_debug(3, __METHOD__, $settings);
    \iframe_debug(3, __METHOD__, $entity);
    // \iframe_debug(3, __METHOD__, $items->getValue());
    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      if (!isset($item->title)) {
        $item->title = '';
      }
      $elements[$delta] = self::iframeIframe($item->title, $item->url, $item);
      // Tokens can be dynamic, so its not cacheable.
      if (isset($settings['tokensupport']) && $settings['tokensupport']) {
        $elements[$delta]['cache'] = ['max-age' => 0];
      }
    }
    return $elements;
  }

  /**
   * Like central function form the iframe code.
   */
  public static function iframeIframe($text, $path, $item) {
    // \iframe_debug(0, __METHOD__, $item->toArray());
    $options = [];
    $options['width'] = !empty($item->width) ? $item->width : '100%';
    $options['height'] = !empty($item->height) ? $item->height : '701';
    // Collect all allow policies.
    $allow = [];
    // Collect styles, but leave it overwritable.
    $style = '';
    $itemName = $item->getFieldDefinition()->getName();
    $itemParentId = $item->getParent()->getParent()->getEntity()->ID();

    if (!empty($item->frameborder) && $item->frameborder > 0) {
      $style .= '/*frameborder*/ border-width:2px;';
    }
    else {
      $style .= '/*frameborder*/ border-width:0;';
    }
    if (!empty($item->scrolling)) {
      if ($item->scrolling == 'yes') {
        $style .= '/*scrolling*/ overflow:scroll;';
      }
      elseif ($item->scrolling == 'no') {
        $style .= '/*scrolling*/ overflow:hidden;';
      }
      else {
        // Default: auto.
        $style .= '/*scrolling*/ overflow:auto;';
      }
    }
    if (!empty($item->transparency) && $item->transparency > 0) {
      $style .= '/*transparency*/ background-color:transparent;';
    }

    $htmlid = 'iframe-' . $itemName . '-' . $itemParentId;
    if (isset($item->htmlid) && !empty($item->htmlid)) {
      $htmlid = $item->htmlid;
    }
    $htmlid = preg_replace('#[^A-Za-z0-9\-\_]+#', '-', $htmlid);
    $options['id'] = $options['name'] = $htmlid;

    // Append active class.
    $options['class'] = !empty($item->class) ? $item->class : '';

    // Remove all HTML and PHP tags from a tooltip.
    // For best performance, we act only
    // if a quick strpos() pre-check gave a suspicion
    // (because strip_tags() is expensive).
    $options['title'] = !empty($item->title) ? $item->title : '';
    if (!empty($options['title']) && strpos($options['title'], '<') !== FALSE) {
      $options['title'] = strip_tags($options['title']);
    }

    // Policy attribute.
    if (!empty($item->allowfullscreen) && $item->allowfullscreen) {
      $allow[] = 'fullscreen';
    }
    $allow[] = 'autoplay';
    $allow[] = 'camera';
    $allow[] = 'microphone';
    $allow[] = 'payment';
    $allow[] = 'accelerometer';
    $allow[] = 'geolocation';
    $allow[] = 'encrypted-media';
    $allow[] = 'gyroscope';
    $options['allow'] = implode(';', $allow);

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      // Token Support for field "url" and "title".
      $tokensupport = $item->getTokenSupport();
      $tokencontext = ['user' => \Drupal::currentUser()];
      if (isset($GLOBALS['node'])) {
        $tokencontext['node'] = $GLOBALS['node'];
      }
      if ($tokensupport > 0) {
        $text = \Drupal::token()->replace($text, $tokencontext);
      }
      if ($tokensupport > 1) {
        $path = \Drupal::token()->replace($path, $tokencontext);
      }
    }

    $options_link = [];
    $options_link['attributes'] = [];
    $options_link['attributes']['title'] = $options['title'];
    try {
      $srcuri = Url::fromUri($path, $options_link);
      $src = $srcuri->toString();
      $options['src'] = $src;
      $drupal_attributes = new Attribute($options);

      $render_array = [
        '#theme' => 'iframe',
        '#src' => $src,
        '#attributes' => $drupal_attributes,
        '#text' => (isset($options['html']) && $options['html'] ? $text : new HtmlEscapedText($text)),
        '#style' => 'iframe#' . $htmlid . ' {' . $style . '}',
      ];
      return $render_array;
    } catch (\Exception $excep) {
      watchdog_exception(__METHOD__, $excep);
      return [];
    }
  }
}
