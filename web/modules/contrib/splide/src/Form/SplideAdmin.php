<?php

namespace Drupal\splide\Form;

use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\Dejavu\BlazyAdminExtended;
use Drupal\splide\SplideManagerInterface;
use Drupal\splide\SplideDefault;

/**
 * Provides resusable admin functions, or form elements.
 */
class SplideAdmin implements SplideAdminInterface {

  use StringTranslationTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Dejavu\BlazyAdminExtended
   */
  protected $blazyAdmin;

  /**
   * The splide manager service.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $manager;

  /**
   * Constructs a SplideAdmin object.
   *
   * @param \Drupal\blazy\Dejavu\BlazyAdminExtended $blazy_admin
   *   The blazy admin service.
   * @param \Drupal\splide\SplideManagerInterface $manager
   *   The splide manager service.
   */
  public function __construct(BlazyAdminExtended $blazy_admin, SplideManagerInterface $manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.admin.extended'),
      $container->get('splide.manager')
    );
  }

  /**
   * Returns the blazy admin formatter.
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * Returns the splide manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the main form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['caches']           = isset($definition['caches']) ? $definition['caches'] : TRUE;
    $definition['namespace']        = 'splide';
    $definition['optionsets']       = isset($definition['optionsets']) ? $definition['optionsets'] : $this->getOptionsetsByGroupOptions('main');
    $definition['skins']            = isset($definition['skins']) ? $definition['skins'] : $this->getSkinsByGroupOptions('main');
    $definition['responsive_image'] = isset($definition['responsive_image']) ? $definition['responsive_image'] : TRUE;

    foreach (['optionsets', 'skins'] as $key) {
      if (isset($definition[$key]['default'])) {
        ksort($definition[$key]);
        $definition[$key] = ['default' => $definition[$key]['default']] + $definition[$key];
      }
    }

    if (empty($definition['no_layouts'])) {
      $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();
    }

    $this->openingForm($form, $definition);

    if (!empty($definition['image_style_form']) && !isset($form['image_style'])) {
      $this->imageStyleForm($form, $definition);
    }

    if (!empty($definition['media_switch_form']) && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (!empty($definition['grid_form']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);
    }

    if (!empty($definition['fieldable_form']) && !isset($form['image'])) {
      $this->fieldableForm($form, $definition);
    }

    if (!empty($definition['style']) && isset($form['style']['#description'])) {
      $form['style']['#description'] .= ' ' . $this->t('CSS3 Columns is best with autoHeight, non-vertical. Will use regular carousel as default style if left empty. Yet, both CSS3 Columns and Grid Foundation are respected as Grid displays when <strong>Grid large</strong> option is provided.');
    }

    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, &$definition = []) {
    $path         = drupal_get_path('module', 'splide');
    $is_splide_ui = $this->manager()->getModuleHandler()->moduleExists('splide_ui');
    $is_help      = $this->manager()->getModuleHandler()->moduleExists('help');
    $route_name   = ['name' => 'splide_ui'];
    $readme       = $is_splide_ui && $is_help ? Url::fromRoute('help.page', $route_name)->toString() : Url::fromUri('base:' . $path . '/docs/README.md')->toString();
    $readme_field = $is_splide_ui && $is_help ? Url::fromRoute('help.page', $route_name)->toString() : Url::fromUri('base:' . $path . '/docs/FORMATTER.md')->toString();
    $arrows       = $this->getSkinsByGroupOptions('arrows');
    $dots         = $this->getSkinsByGroupOptions('dots');

    $this->blazyAdmin->openingForm($form, $definition);

    if (isset($form['optionset'])) {
      $form['optionset']['#title'] = $this->t('Optionset main');

      if ($is_splide_ui) {
        $route_name = 'entity.splide.collection';
        $form['optionset']['#description'] = $this->t('Manage optionsets at <a href=":url" target="_blank">the optionset admin page</a>.', [':url' => Url::fromRoute($route_name)->toString()]);
      }
    }

    if (!empty($definition['nav']) || !empty($definition['thumbnails'])) {
      $form['optionset_nav'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Optionset nav'),
        '#options'     => $this->getOptionsetsByGroupOptions('nav'),
        '#description' => $this->t('If provided, asNavFor aka thumbnail navigation applies. Leave empty to not use thumbnail navigation.'),
        '#weight'      => -108,
        '#enforced'    => TRUE,
      ];

      $form['skin_nav'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin navigation'),
        '#options'     => $this->getSkinsByGroupOptions('nav'),
        '#description' => $this->t('Thumbnail navigation skin. See main <a href="@url" target="_blank">README</a> for details on Skins. Leave empty to not use thumbnail navigation.', ['@url' => $readme]),
        '#weight'      => -106,
        '#enforced'    => TRUE,
      ];
    }

    if (count($arrows) > 0 && empty($definition['no_arrows'])) {
      $form['skin_arrows'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin arrows'),
        '#options'     => $arrows,
        '#enforced'    => TRUE,
        '#description' => $this->t('Check out splide.api.php to add your own skins.'),
        '#weight'      => -105,
      ];
    }

    if (count($dots) > 0 && empty($definition['no_dots'])) {
      $form['skin_dots'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin dots'),
        '#options'     => $dots,
        '#enforced'    => TRUE,
        '#description' => $this->t('Check out splide.api.php to add your own skins.'),
        '#weight'      => -105,
      ];
    }

    if (!empty($definition['nav_positions']) || !empty($definition['thumb_positions'])) {
      $form['navpos'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Navigation position'),
        '#options' => [
          'left'       => $this->t('Left'),
          'right'      => $this->t('Right'),
          'top'        => $this->t('Top'),
          'over-left'  => $this->t('Overlay left'),
          'over-right' => $this->t('Overlay right'),
          'over-top'   => $this->t('Overlay top'),
        ],
        '#description' => $this->t('By default thumbnail is positioned at bottom. Hence to change the position of thumbnail. Only reasonable with 1 visible main stage at a time. Except any TOP, the rest requires <code>Direction: ttb</code> for Optionset nav, and a custom CSS height to selector <strong>.splide--nav</strong> to avoid overflowing tall thumbnails, or adjust <strong>perPage</strong> to fit the height. Further theming is required as usual. Overlay is absolutely positioned over the stage rather than sharing the space. See skin <strong>X VTabs</strong> for vertical thumbnail sample.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_nav]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => -99,
        '#enforced'    => TRUE,
      ];
    }

    if (!empty($definition['thumb_captions'])) {
      if ($definition['thumb_captions'] == 'default') {
        $definition['thumb_captions'] = [
          'alt' => $this->t('Alt'),
          'title' => $this->t('Title'),
        ];
      }
      $form['nav_caption'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail caption'),
        '#options'     => $definition['thumb_captions'],
        '#description' => $this->t('Thumbnail caption maybe just title/ plain text. If Thumbnail image style is not provided, the thumbnail pagers will be just text like regular tabs.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_nav]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => 2,
        '#enforced'    => TRUE,
      ];
    }

    if (isset($form['skin'])) {
      $form['skin']['#title'] = $this->t('Skin main');
      $form['skin']['#description'] = $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. E.g.: Skin Split requires any split layout option. Failing to choose the expected layout makes it useless. See <a href=":url" target="_blank">SKINS section at README</a> for details on Skins. Leave empty to DIY. Skins are permanently cached. Clear cache if new skins do not appear. Check out splide.api.php to add your own skins.', [':url' => $readme]);
    }

    if (isset($form['layout'])) {
      $form['layout']['#description'] = $this->t('Requires a skin. The builtin layouts affects the entire slides uniformly. Split half requires any skin Split. See <a href="@url" target="_blank">README</a> under "Slide layout" for more info. Leave empty to DIY.', ['@url' => $readme_field]);
    }

    $weight = -99;
    foreach (Element::children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    $this->blazyAdmin->mediaSwitchForm($form, $definition);

    if (isset($form['media_switch'])) {
      $form['media_switch']['#description'] = $this->t('Depends on the enabled supported modules, or has known integration with Splide.<ol><li>Link to content: for aggregated small splides.</li><li>Image to iframe: audio/video is hidden below image until toggled, otherwise iframe is always displayed, and draggable fails. Aspect ratio applies.</li><li>Colorbox.</li><li>Photobox. Be sure to select "Thumbnail style" for the overlay thumbnails.</li><li>Intense: image to fullscreen intense image.</li>');

      if (!empty($definition['multimedia']) && isset($definition['fieldable_form'])) {
        $form['media_switch']['#description'] .= ' ' . $this->t('<li>Image rendered by its formatter: image-related settings here will be ignored: breakpoints, image style, CSS background, aspect ratio, lazyload, etc. Only choose if needing a special image formatter such as Image Link Formatter.</li>');
      }

      $form['media_switch']['#description'] .= ' ' . $this->t('</ol> Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.');
    }

    if (isset($form['ratio']['#description'])) {
      $form['ratio']['#description'] .= ' ' . $this->t('Required if using media entity to switch between iframe and overlay image, otherwise DIY.');
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function imageStyleForm(array &$form, $definition = []) {
    $definition['thumbnail_style'] = isset($definition['thumbnail_style']) ? $definition['thumbnail_style'] : TRUE;
    $definition['ratios'] = isset($definition['ratios']) ? $definition['ratios'] : TRUE;

    $definition['thumbnail_effect'] = isset($definition['_thumbnail_effect']) ? $definition['_thumbnail_effect'] : [
      'hover' => $this->t('Hoverable'),
      'grid'  => $this->t('Static grid'),
    ];

    if (!isset($form['image_style'])) {
      $this->blazyAdmin->imageStyleForm($form, $definition);

      $form['image_style']['#description'] = $this->t('The main image style. This will be treated as the fallback image, which is normally smaller, if Breakpoints are provided, and if <strong>Use CSS background</strong> is disabled. Otherwise this is the only image displayed. Ignored by Responsive image option.');
    }

    if (isset($form['thumbnail_style'])) {
      $form['thumbnail_style']['#description'] = $this->t('Usages: <ol><li>If <em>Optionset thumbnail</em> provided, it is for asNavFor thumbnail navigation.</li><li>For <em>Thumbnail effect</em>.</li><li>Photobox thumbnail.</li><li>Splidebox/ PhotoSwipe zoom in/out thumbnail animation, best with the same aspect ratio.</li><li>Custom work via the provided data-thumb attributes: arrows with thumbnails, Photoswipe thumbnail, etc.</li></ol>Leave empty to not use thumbnails. <br>If Vanilla enabled and Optionset nav is provided, this will be used as thumbnail style for the Main stage.');
      $form['thumbnail_style']['#enforced'] = TRUE;
    }

    if (isset($form['thumbnail_effect'])) {
      $form['thumbnail_effect']['#description'] = $this->t('Dependent on a Skin, Dots and Thumbnail style options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.');
    }

    if (isset($form['background'])) {
      $form['background']['#description'] .= ' ' . $this->t('Works best with a single visible slide, skins full width/screen.');
    }
  }

  /**
   * Returns re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, $definition = []) {
    $this->blazyAdmin->fieldableForm($form, $definition);

    if (isset($form['image'])) {
      $form['image']['#enforced'] = TRUE;
      $description = isset($form['image']['#description']) ? ' ' . $form['image']['#description'] : '';
      $form['image']['#description'] = $this->t('If Vanilla enabled and Optionset nav is provided, this will be used for thumbnail instead. The actual Main stage will be the rendered entity, not this image.') . $description;
    }

    if (isset($form['thumbnail'])) {
      $form['thumbnail']['#enforced'] = TRUE;
      $form['thumbnail']['#description'] = $this->t("Only needed if <em>Optionset nav</em> is provided. Maybe the same field as the main image, only different instance and image style. Leave empty to not use thumbnail navigation.");
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#title'] = $this->t('Overlay media/splides');
      $form['overlay']['#description'] = $this->t('For audio/video, be sure the display is not image. For nested splides, use the Splide slider formatter for this field. Zebra layout is reasonable for overlay and captions.');
    }
  }

  /**
   * Returns re-usable grid elements across Splide field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    if (!isset($form['grid'])) {
      $this->blazyAdmin->gridForm($form, $definition);
    }

    $header = $this->t('Group individual item as block grid?<small>An older alternative to core <strong>Rows</strong> option. Only works if the total items &gt; <strong>Visible slides</strong>. <br />block grid != perPage option, yet both can work in tandem.<br />block grid = Rows option, yet the first is module feature, the later core.</small>');

    $form['grid_header']['#markup'] = '<h3 class="form__title form__title--grid">' . $header . '</h3>';

    $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em - 90em. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents,</li><li>Optionset with perPage and perMove = 1.</li></ol>This is module feature offers more flexibility. Leave empty to DIY, or to not build grids.');
  }

  /**
   * Returns the closing ending form elements.
   */
  public function closingForm(array &$form, $definition = []) {
    if (empty($definition['_views']) && !empty($definition['field_name'])) {
      $form['use_theme_field'] = [
        '#title'       => $this->t('Use field template'),
        '#type'        => 'checkbox',
        '#description' => $this->t('Wrap Splide field output into regular field markup (field.html.twig). Vanilla output otherwise.'),
        '#weight'      => -106,
        '#enforced'    => TRUE,
      ];
    }

    $form['override'] = [
      '#title'       => $this->t('Override main optionset'),
      '#type'        => 'checkbox',
      '#description' => $this->t('If checked, the following options will override the main optionset. Useful to re-use one optionset for several different displays.'),
      '#weight'      => 112,
      '#enforced'    => TRUE,
    ];

    $form['overridables'] = [
      '#type'        => 'checkboxes',
      '#title'       => $this->t('Overridable options'),
      '#description' => $this->t("Override the main optionset to re-use one. Anything dictated here will override the current main optionset. Unchecked means FALSE"),
      '#options'     => $this->getOverridableOptions(),
      '#weight'      => 113,
      '#enforced'    => TRUE,
      '#states' => [
        'visible' => [
          ':input[name$="[override]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if (!empty($definition['nav_state'])) {
      $extras = [];
      if (isset($definition['plugin_id']) && $definition['plugin_id'] == 'splide_entityreference') {
        // Thumbnail only makes sense for Media entity, or with navigation.
        $extras = ['thumbnail_style'];
      }
      $options = ['image', 'skin_nav', 'thumbnail_effect'];
      foreach (array_merge($options, $extras) as $key) {
        if (isset($form[$key])) {
          $form[$key]['#states']['visible']['select[name*="[optionset_nav]"]'] = ['!value' => ''];
        }
      }
    }

    $this->blazyAdmin->closingForm($form, $definition);
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions() {
    $options = SplideDefault::overridableOptions(TRUE);

    $this->manager->getModuleHandler()->alter('splide_overridable_options_info', $options);
    return $options;
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions() {
    return [
      'bottom'      => $this->t('Caption bottom'),
      'top'         => $this->t('Caption top'),
      'right'       => $this->t('Caption right'),
      'left'        => $this->t('Caption left'),
      'center'      => $this->t('Caption center'),
      'center-top'  => $this->t('Caption center top'),
      'below'       => $this->t('Caption below the slide'),
      'stage-right' => $this->t('Caption left, stage right'),
      'stage-left'  => $this->t('Caption right, stage left'),
      'split-right' => $this->t('Caption left, stage right, split half'),
      'split-left'  => $this->t('Caption right, stage left, split half'),
      'stage-zebra' => $this->t('Stage zebra'),
      'split-zebra' => $this->t('Split half zebra'),
    ];
  }

  /**
   * Returns available splide optionsets by group.
   */
  public function getOptionsetsByGroupOptions($group = '') {
    $optionsets = $groups = $ungroups = [];
    $splides = $this->manager->entityLoadMultiple('splide');
    foreach ($splides as $splide) {
      $name = Html::escape($splide->label());
      $id = $splide->id();
      $current_group = $splide->getGroup();
      if (!empty($group)) {
        if ($current_group) {
          if ($current_group != $group) {
            continue;
          }
          $groups[$id] = $name;
        }
        else {
          $ungroups[$id] = $name;
        }
      }
      $optionsets[$id] = $name;
    }

    return $group ? array_merge($ungroups, $groups) : $optionsets;
  }

  /**
   * Returns available splide skins for select options.
   */
  public function getSkinsByGroupOptions($group = '') {
    return $this->manager->skinManager()->getSkinsByGroup($group, TRUE);
  }

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary($definition = []) {
    return $this->blazyAdmin->getSettingsSummary($definition);
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions($target_bundles = [], $allowed_field_types = [], $entity_type_id = 'media', $target_type = '') {
    return $this->blazyAdmin->getFieldOptions($target_bundles, $allowed_field_types, $entity_type_id, $target_type);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $this->blazyAdmin->finalizeForm($form, $definition);
  }

}