<?php

namespace Drupal\splide;

use Drupal\Component\Utility\NestedArray;
use Drupal\splide\Entity\Splide;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyGrid;
use Drupal\blazy\BlazyManagerBase;

/**
 * Implements BlazyManagerInterface, SplideManagerInterface.
 */
class SplideManager extends BlazyManagerBase implements SplideManagerInterface {

  /**
   * The splide skin manager service.
   *
   * @var \Drupal\splide\SplideSkinManagerInterface
   */
  protected $skinManager;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSplide', 'preRenderSplideWrapper'];
  }

  /**
   * Returns splide skin manager service.
   */
  public function skinManager() {
    return $this->skinManager;
  }

  /**
   * Sets splide skin manager service.
   */
  public function setSkinManager(SplideSkinManagerInterface $skin_manager) {
    $this->skinManager = $skin_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $load = parent::attach($attach);

    $this->skinManager->attach($load, $attach);

    $this->moduleHandler->alter('splide_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function splide(array $build = []) {
    foreach (SplideDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'splide',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSplide']],
    ];
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  protected function prepareAttributes(array $build = []) {
    $settings = $build['settings'];
    $attributes = isset($build['attributes']) ? $build['attributes'] : [];

    if ($settings['display'] == 'main') {
      Blazy::containerAttributes($attributes, $settings);
    }
    return $attributes;
  }

  /**
   * Builds the Splide instance as a structured array ready for ::renderer().
   */
  public function preRenderSplide(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    $optionset = &$build['optionset'];
    $settings = &$build['settings'];
    $settings += SplideDefault::htmlSettings();

    if ($settings['display'] == 'main') {
      // Build the Splide grid if provided.
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = $this->buildGrid($build['items'], $settings);
      }
    }

    $build['attributes'] = $this->prepareAttributes($build);

    $this->moduleHandler->alter('splide_optionset', $optionset, $settings);

    foreach (SplideDefault::themeProperties() as $key) {
      $element["#$key"] = $build[$key];
    }

    unset($build);
    return $element;
  }

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items = [], array &$settings = []) {
    $grids = [];

    // BC for non-required Display style. Blazy 2.5+ requires explicit style.
    if (empty($settings['style'])) {
      $settings['style'] = 'grid';
    }

    // Enforces unsplide with less items.
    if (empty($settings['unsplide']) && !empty($settings['count'])) {
      $settings['unsplide'] = $settings['count'] < $settings['visible_items'];
    }

    // Display all items if unsplide is enforced for plain grid to lightbox.
    // Or when the total is less than visible_items.
    if (!empty($settings['unsplide'])) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = 2;

      $grids[0] = $this->buildGridItem($items, $settings);
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = !empty($settings['preserve_keys']);
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = count($grid_items);

      foreach ($grid_items as $grid_item) {
        $grids[] = $this->buildGridItem($grid_item, $settings);
      }
    }

    return $grids;
  }

  /**
   * Returns items as a grid item display.
   */
  public function buildGridItem(array $items, array $settings = []) {
    $output = [];
    foreach ($items as $delta => $item) {
      $sets = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
      $attrs = empty($item['attributes']) ? [] : $item['attributes'];
      $content_attrs = isset($item['content_attributes']) ? $item['content_attributes'] : [];
      $sets['current_item'] = 'grid';
      $sets['delta'] = $delta;

      unset($item['settings'], $item['attributes'], $item['content_attributes']);
      $theme = empty($settings['vanilla']) ? 'slide' : 'minimal';

      if (empty($settings['unsplide'])) {
        $attrs['class'][] = 'slide__grid';
      }

      $attrs['class'][] = 'grid--' . $delta;

      $content = [
        '#theme' => 'splide_' . $theme,
        '#item' => $item,
        '#delta' => $delta,
        '#settings' => $sets,
      ];

      $slide = [
        'content' => $content,
        'attributes' => $attrs,
        'content_attributes' => $content_attrs,
        'settings' => $sets,
      ];

      $output[$delta] = $slide;
      unset($slide);
    }

    $result = BlazyGrid::build($output, $settings);
    $result['#attributes']['class'][] = empty($settings['unsplide']) ? 'slide__content' : 'splide__grid';

    $build = ['slide' => $result, 'settings' => $settings];

    $this->moduleHandler->alter('splide_grid_item', $build, $settings);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (SplideDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $splide = [
      '#theme'      => 'splide_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSplideWrapper']],
      // Satisfy CTools blocks as per 2017/04/06: 2804165.
      'items'       => [],
    ];

    $this->moduleHandler->alter('splide_build', $splide, $build['settings']);
    return empty($build['items']) ? [] : $splide;
  }

  /**
   * Prepares js-related options.
   */
  protected function prepareOptions(array &$options, array &$settings) {
    // Disable draggable for Layout Builder UI to not conflict with UI sortable.
    if (strpos($settings['route_name'], 'layout_builder.') === 0 || !empty($settings['is_preview'])) {
      $options['drag'] = FALSE;
    }

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Splide without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    if (!empty($settings['skin']) && $skins = $this->skinManager->getSkinsByGroup('main')) {
      if (isset($skins[$settings['skin']]['options'])) {
        $options = array_merge($options, $skins[$settings['skin']]['options']);
      }
    }

    if ($settings['display'] == 'main') {
      // Overrides common options to re-use an optionset.
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $options[$key] = $key == $override ? TRUE : FALSE;
          // Supports FIFO hook_splide_overridable_options_info_alter.
          // Makes no sense, but the cheap way without another option for now.
          foreach (['slide', 'loop', 'fade'] as $k) {
            if (isset($options[$k])) {
              if ($options[$k] == $key) {
                $options['type'] = $k;
              }
              unset($options[$k]);
            }
          }
        }
      }
    }
  }

  /**
   * Prepare settings for the known module features, not necessarily users'.
   */
  protected function prepareSettings(array &$element, array &$build) {
    $settings = array_merge(SplideDefault::htmlSettings(), $build['settings']);

    // Formatters or Views may set this, but not custom works.
    if (empty($build['optionset'])) {
      $build['optionset'] = Splide::loadWithFallback($settings['optionset']);
    }

    $optionset = &$build['optionset'];
    $options = &$build['options'];
    $this->prepareOptions($options, $settings);

    // Additional settings, Splide supports nav for Vanilla, unlike Slick.
    $settings['down']       = $optionset->getSetting('down');
    $settings['count']      = empty($settings['count']) ? count($build['items']) : $settings['count'];
    $settings['nav']        = $settings['nav'] && (!empty($settings['optionset_nav']) && isset($build['items'][1]));
    $settings['navpos']     = ($settings['nav'] && !empty($settings['navpos'])) ? $settings['navpos'] : '';
    $settings['transition'] = isset($options['type']) ? $options['type'] : $optionset->getSetting('type');
    $settings['vertical']   = isset($options['direction']) && $options['direction'] == 'ttb';
    $settings['wheel']      = isset($options['wheel']) ? $options['wheel'] : $optionset->getSetting('wheel');

    // Removes pagination thumbnail effect if has no thumbnails.
    $fx = $optionset->getSetting('pagination') && (!empty($settings['thumbnail_style']) || !empty($settings['thumbnail']));
    $settings['pagination_fx'] = $fx ? $settings['thumbnail_effect'] : '';

    if ($settings['nav']) {
      $optionset_nav            = $build['optionset_nav'] = Splide::loadWithFallback($settings['optionset_nav']);
      $settings['vertical_nav'] = $optionset_nav->getSetting('direction') == 'ttb';
      $settings['wheel']        = isset($options['wheel']) ? $options['wheel'] : $optionset_nav->getSetting('wheel');
    }
    else {
      // Pass extra attributes such as those from Commerce product variations to
      // theme_splide() since we have no asNavFor wrapper here.
      if (isset($element['#attributes'])) {
        $build['attributes'] = empty($build['attributes']) ? $element['#attributes'] : NestedArray::mergeDeep($build['attributes'], $element['#attributes']);
      }
    }

    // Supports Blazy multi-breakpoint or lightbox images if provided.
    // Cases: Blazy within Views gallery, or references without direct image.
    if (!empty($settings['check_blazy']) && !empty($settings['first_image'])) {
      $this->isBlazy($settings, $settings['first_image']);
    }

    // Formatters might have checked this, but not views, nor custom works.
    // Why the formatters should check it first? It is so known to children.
    if (empty($settings['_lazy'])) {
      $optionset->whichLazy($settings);
    }

    $build['options']     = $options;
    $attachments          = $this->attach($settings);
    $element['#settings'] = $build['settings'] = $settings;
    $element['#attached'] = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
  }

  /**
   * Returns splide navigation with structured array similar to main display.
   */
  protected function buildNavigation(array &$build, array $navs) {
    $settings = $build['settings'];
    foreach (['items', 'options', 'settings'] as $key) {
      $build[$key] = isset($navs[$key]) ? $navs[$key] : [];
    }

    $optionset             = $build['optionset_nav'];
    $settings              = array_merge($settings, $build['settings']);
    $settings['optionset'] = $settings['optionset_nav'];
    $settings['skin']      = $settings['skin_nav'];
    $settings['display']   = 'nav';
    $build['optionset']    = $optionset;
    $build['settings']     = $settings;

    // The splide thumbnail navigation has the same structure as the main one.
    unset($build['optionset_nav']);
    return $this->splide($build);
  }

  /**
   * One splide_theme() to serve multiple displays: main, overlay, thumbnail.
   */
  public function preRenderSplideWrapper($element) {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare settings and assets.
    $this->prepareSettings($element, $build);

    // Checks if we have thumbnail navigation.
    $navs     = isset($build['nav']) ? $build['nav'] : [];
    $settings = $build['settings'];

    // Prevents unused thumb going through the main display.
    unset($build['nav']);

    // Build the main Splide.
    $splide[0] = $this->splide($build);

    // Build the thumbnail Splide.
    if ($settings['nav'] && $navs) {
      $splide[1] = $this->buildNavigation($build, $navs);
    }

    // Reverse splides if thumbnail position is provided to get CSS float work.
    if ($settings['navpos']) {
      $splide = array_reverse($splide);
    }

    // Collect the splide instances.
    $element['#items'] = $splide;
    $element['#cache'] = $this->getCacheMetadata($build);

    unset($build);
    return $element;
  }

  /**
   * Provides a shortcut to attach skins only if required.
   */
  public function attachSkin(array &$load, $attach = []) {
    $this->skinManager->attachSkin($load, $attach);
  }

  /**
   * Provides alterable transition types.
   */
  public function getTransitionTypes() {
    $types = [
      'slide' => 'Slide',
      'loop'  => 'Loop',
      'fade'  => 'Fade',
    ];
    $this->moduleHandler->alter('splide_transition_types', $types);
    return $types;
  }

}
