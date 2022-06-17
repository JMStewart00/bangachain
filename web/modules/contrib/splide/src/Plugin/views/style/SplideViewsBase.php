<?php

namespace Drupal\splide\Plugin\views\style;

use Drupal\blazy\Dejavu\BlazyStylePluginBase;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class common for Splide style plugins.
 */
abstract class SplideViewsBase extends BlazyStylePluginBase {

  /**
   * The splide service manager.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->manager = $container->get('splide.manager');
    $instance->blazyManager = $instance->blazyManager ?? $container->get('blazy.manager');

    return $instance;
  }

  /**
   * Returns the splide manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the splide admin.
   */
  public function admin() {
    return \Drupal::service('splide.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (SplideDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Returns the defined scopes for the current form.
   */
  protected function getDefinedFormScopes(array $extra_fields = []) {
    // Pass the common field options relevant to this style.
    $fields = [
      'captions',
      'classes',
      'images',
      'layouts',
      'links',
      'overlays',
      'thumbnails',
      'thumb_captions',
      'titles',
    ];

    // Fetches the returned field definitions to be used to define form scopes.
    $fields = array_merge($fields, $extra_fields);
    $definition = $this->getDefinedFieldOptions($fields);

    // @todo remove _form for forms when Blazy 2.x has it.
    $options = [
      'fieldable_form',
      'grid_form',
      'nav',
      'style',
      'thumb_positions',
      'vanilla',
    ];

    foreach ($options as $key) {
      $definition[$key] = TRUE;
    }

    $definition['forms'] = ['fieldable' => TRUE, 'grid' => TRUE];
    $definition['opening_class'] = 'form--slick form--views';
    $definition['_views'] = TRUE;

    return $definition;
  }

  /**
   * Build the Splide settings form.
   */
  protected function buildSettingsForm(&$form, &$definition) {
    $count = count($definition['captions']);
    $definition['captions_count'] = $count;

    $this->admin()->buildSettingsForm($form, $definition);

    // @todo remove custom classes for Blazy 2.x and 1.x updates.
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;
    $title = '<p class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise splide markups apply which require some fields added below.</small>');
    $title .= '</p>';

    $form['opening']['#markup'] = '<div class="form--slick form--splide form--views form--half form--vanilla has-tooltip' . $wide . '">' . $title;

    if (isset($form['image'])) {
      $form['image']['#description'] .= ' ' . $this->t('Use Blazy formatter to have it lazyloaded. Other supported Formatters: Colorbox, Intense, Responsive image, Video Embed Field, Youtube Field.');
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#description'] .= ' ' . $this->t('Be sure to CHECK "<strong>Style settings > Use field template</strong>" _only if using Splide formatter for nested sliders, otherwise keep it UNCHECKED!');
    }

    // Bring in dots thumbnail effect normally used by Splide Image formatter.
    $form['thumbnail_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Dots thumbnail effect'),
      '#options' => [
        'hover' => $this->t('Hoverable'),
        'grid' => $this->t('Static grid'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Dependent on a Skin, Dots and Thumbnail image options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.'),
      '#weight' => -100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettings() {
    // @todo move it into self::prepareSettings() post blazy:2.x.
    $this->options['item_id'] = 'slide';
    $this->options['namespace'] = 'splide';
    $settings = parent::buildSettings();

    // Prepare needed settings to work with.
    $settings['caption'] = array_filter($settings['caption']);
    $settings['nav'] = $settings['optionset_nav'] && isset($this->view->result[1]);
    $settings['overridables'] = empty($settings['override']) ? array_filter($settings['overridables']) : $settings['overridables'];

    return $settings;
  }

  /**
   * Returns splide contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $blazies = $settings['blazies'];
    $item_id = $blazies->get('item.id', 'slide');

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $slide = [];
      $thumb = $slide[$item_id] = [];
      $sets  = $settings;

      $this->reset($sets);
      $blazies = $sets['blazies'];
      $blazies->set('is.reset', TRUE);

      // Provides a potential unique thumbnail different from the main image.
      if (!empty($sets['thumbnail'])) {
        $tn = $this->getFieldRenderable($row, 0, $sets['thumbnail']);
        if (isset($tn['rendered']['#image_style'])) {
          if ($item = $tn['rendered']['#item'] ?? NULL) {
            $uri = (($entity = $item->entity) && empty($item->uri)) ? $entity->getFileUri() : $item->uri;
            $sets['thumbnail_style'] = $tn_style = $tn['rendered']['#image_style'];
            $tn_uri = empty($tn_style)
              ? $uri
              : $this->manager
                ->entityLoad($tn_style, 'image_style')
                ->buildUri($uri);

            if ($tn_uri) {
              $sets['thumbnail_uri'] = $tn_uri;
              $blazies->set('thumbnail.uri', $tn_uri);
            }
          }
        }
      }

      $slide['settings'] = $sets;

      // Use Vanilla splide if so configured, ignoring Splide markups.
      if (!empty($sets['vanilla'])) {
        $slide[$item_id] = $view->rowPlugin->render($row);
      }
      else {
        // Otherwise, extra works. With a working Views cache, no big deal.
        $this->buildElement($slide, $row, $index);
      }

      // Build thumbnail navs if so configured.
      // @todo move it back to non-vanilla if any issue.
      if (!empty($sets['nav'])) {
        $thumb[$item_id] = empty($sets['thumbnail']) ? []
          : $this->getFieldRendered($index, $sets['thumbnail']);

        $thumb['caption'] = empty($sets['nav_caption']) ? []
          : $this->getFieldRendered($index, $sets['nav_caption']);

        $build['nav']['items'][$index] = $thumb;
      }

      if (!empty($sets['class'])) {
        $classes = $this->getFieldString($row, $sets['class'], $index);
        $slide['settings']['class'] = empty($classes[$index]) ? [] : $classes[$index];
      }

      if (empty($slide[$item_id]) && !empty($sets['image'])) {
        $slide[$item_id] = $this->getFieldRendered($index, $sets['image']);
      }

      $build['items'][$index] = $slide;
      unset($slide, $thumb);
    }

    unset($view->row_index);
    return $build;
  }

  /**
   * Check Blazy formatter to build lightbox galleries.
   */
  protected function checkBlazy(array &$settings, array $build, array $rows = []) {
    // Extracts Blazy formatter settings if available.
    // @todo re-check and remove, first.data already takes care of this.
    // if (empty($settings['vanilla']) && isset($build['items'][0])) {
    // $this->blazyManager()->isBlazy($settings, $build['items'][0]);
    // }
    // @todo remove check Blazy 2.10.
    $blazies = $settings['blazies'] ?? NULL;
    if ($data = $this->getFirstImage($rows[0] ?? NULL)) {
      if ($blazies) {
        $blazies->set('first.data', $data);
      }
      // @todo remove post Blazy 2.10.
      else {
        $settings['first_image'] = $data;
      }
    }
  }

}
