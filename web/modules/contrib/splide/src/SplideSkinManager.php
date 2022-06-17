<?php

namespace Drupal\splide;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\splide\Entity\Splide;

/**
 * Provides Splide skin manager.
 */
class SplideSkinManager extends DefaultPluginManager implements SplideSkinManagerInterface, MapperInterface {

  use StringTranslationTrait;

  /**
   * The app root.
   *
   * @var \SplString
   */
  protected $root;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Static cache for the skins by group.
   *
   * @var array
   */
  protected $skinsByGroup;

  /**
   * The library info definition.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * The easing library path.
   *
   * @var string|bool
   */
  protected $easingPath;

  /**
   * The splide library path.
   *
   * @var string|bool
   */
  protected $splidePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, $root, ConfigFactoryInterface $config) {
    parent::__construct('Plugin/splide', $namespaces, $module_handler, SplideSkinPluginInterface::class, 'Drupal\splide\Annotation\SplideSkin');

    $this->root = $root;
    $this->config = $config;

    $this->alterInfo('splide_skin_info');
    $this->setCacheBackend($cache_backend, 'splide_skin_plugins');
  }

  /**
   * Returns the supported skins.
   */
  public function getConstantSkins(): array {
    return [
      'browser',
      'lightbox',
      'overlay',
      'main',
      'nav',
      'arrows',
      'dots',
      'widget',
    ];
  }

  /**
   * Returns splide config shortcut.
   */
  public function config($key = '', $settings = 'splide.settings') {
    return $this->config->get($settings)->get($key);
  }

  /**
   * Returns cache backend service.
   */
  public function getCache() {
    return $this->cacheBackend;
  }

  /**
   * Returns app root.
   */
  public function root() {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function load($plugin_id) {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(): array {
    $skins = [];
    foreach ($this->getDefinitions() as $definition) {
      array_push($skins, $this->createInstance($definition['id']));
    }
    return $skins;
  }

  /**
   * Returns splide skins registered via SplideSkin plugin and or defaults.
   */
  public function getSkins(): array {
    if (!isset($this->skinDefinition)) {
      $cid = 'splide_skins_data';

      if ($cache = $this->cacheBackend->get($cid)) {
        $this->skinDefinition = $cache->data;
      }
      else {
        $methods = ['skins', 'arrows', 'dots'];
        $skins = $items = [];
        foreach ($this->loadMultiple() as $skin) {
          foreach ($methods as $method) {
            $items[$method] = $skin->{$method}();
          }
          $skins = NestedArray::mergeDeep($skins, $items);
        }

        $count = isset($items['skins']) ? count($items['skins']) : count($items);
        $tags = Cache::buildTags($cid, ['count:' . $count]);
        $this->cacheBackend->set($cid, $skins, Cache::PERMANENT, $tags);

        $this->skinDefinition = $skins;
      }
    }
    return $this->skinDefinition ?: [];
  }

  /**
   * Returns available splide skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array {
    if (!isset($this->skinsByGroup[$group])) {
      $skins         = $groups = $ungroups = [];
      $nav_skins     = in_array($group, ['arrows', 'dots']);
      $defined_skins = $nav_skins ? $this->getSkins()[$group] : $this->getSkins()['skins'];

      foreach ($defined_skins as $skin => $properties) {
        $item = $option ? strip_tags($properties['name']) : $properties;
        if (!empty($group)) {
          if (isset($properties['group'])) {
            if ($properties['group'] != $group) {
              continue;
            }
            $groups[$skin] = $item;
          }
          elseif (!$nav_skins) {
            $ungroups[$skin] = $item;
          }
        }
        $skins[$skin] = $item;
      }
      $this->skinsByGroup[$group] = $group ? array_merge($ungroups, $groups) : $skins;
    }
    return $this->skinsByGroup[$group] ?: [];
  }

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild(): array {
    if (!isset($this->libraryInfoBuild)) {
      $libraries['splide.css'] = [
        'dependencies' => ['splide/splide'],
        'css' => [
          'theme' => ['/' . $this->getSplidePath() . '/dist/css/splide.min.css' => []],
        ],
      ];

      $fullscreen = ['css/components/splide.fullscreen.css' => []];
      foreach ($this->getModuleComponents() as $key) {
        $libraries[$key] = [
          'dependencies' => ['splide/base'],
          'js' => [
            'js/components/splide.' . $key . '.min.js' => [
              'minified' => TRUE,
              'weight' => -0.03,
            ],
          ],
        ];
        if ($key == 'fullscreen') {
          $libraries[$key]['css']['component'] = $fullscreen;
        }
      }

      $libraries['colorbox']['dependencies'][] = 'blazy/colorbox';
      $libraries['media']['dependencies'][] = 'splide/blazy';
      $libraries['zoom']['dependencies'][] = 'splide/swipedetect';

      foreach ($this->getConstantSkins() as $group) {
        if ($skins = $this->getSkinsByGroup($group)) {
          foreach ($skins as $key => $skin) {
            $provider = $skin['provider'] ?? 'splide';
            $id = $provider . '.' . $group . '.' . $key;

            foreach (['css', 'js', 'dependencies'] as $property) {
              if (isset($skin[$property]) && is_array($skin[$property])) {
                $libraries[$id][$property] = $skin[$property];
              }
            }
          }
        }
      }

      $this->libraryInfoBuild = $libraries;
    }
    return $this->libraryInfoBuild;
  }

  /**
   * Provides splide skins and libraries.
   */
  public function attach(array &$load, array $attach = []): void {
    $this->attachCore($load, $attach);
    $load['drupalSettings']['splide'] = $this->getSafeSettings(Splide::defaultSettings());

    if (!empty($attach['pagination_tab'])) {
      $load['library'][] = 'splide/pagination.tab';

      // @todo move it into [data-splide] to support multiple instances on page.
      $load['drupalSettings']['splide']['paginationTexts'] = $attach['pagination_texts'];
    }
  }

  /**
   * Returns typecast settings.
   */
  public function getSafeSettings(array $settings): array {
    // Attach default JS settings to allow responsive displays have a lookup,
    // excluding wasted/trouble options, e.g.: PHP string vs JS object.
    $excludes = explode(' ', 'breakpoints classes i18n padding easingOverride downTarget downOffset');
    $excludes = array_combine($excludes, $excludes);
    $settings = Splide::typecast($settings, FALSE);

    // The library assumes some object FALSE explicitly by default.
    // @todo recheck others like breakpoints classes i18n padding, etc.
    foreach (Splide::getObjectsAsBool() as $key) {
      $settings[$key] = FALSE;
    }

    $breakpoints = SplideDefault::validBreakpointOptions();
    $breakpoints = array_combine($breakpoints, $breakpoints);
    $extras = [];

    foreach ($settings as $key => $value) {
      if (isset($breakpoints[$key])) {
        $extras[$key] = $value;
      }
    }

    $settings = array_diff_key($settings, $excludes);
    $settings['extras'] = Splide::typecast($extras, FALSE);
    $settings['resets'] = [
      'arrows' => FALSE,
      'autoplay' => FALSE,
      'drag' => FALSE,
      'pagination' => FALSE,
      'perPage' => 1,
      'perMove' => 1,
      'start' => 0,
      'type' => 'fade',
    ];

    return $settings;
  }

  /**
   * Provides core libraries.
   */
  public function attachCore(array &$load, array $attach = []): void {
    if ($this->config('splide_css')) {
      $load['library'][] = 'splide/splide.css';
    }

    if (!empty($attach['lazy'])) {
      $load['library'][] = 'blazy/loading';
    }

    foreach ($this->getComponents() as $key) {
      if (!empty($attach[$key])) {
        $load['library'][] = 'splide/' . $key;
      }
    }

    $load['library'][] = 'splide/load';
    if (!empty($attach['_vanilla'])) {
      $load['library'][] = 'splide/vanilla';
    }

    $load['library'][] = 'splide/nav';

    if (!empty($attach['skin'])) {
      $this->attachSkin($load, $attach);
    }
  }

  /**
   * Provides skins only if required.
   */
  public function attachSkin(array &$load, array $attach = []): void {
    if ($this->config('module_css', 'splide.settings')) {
      $load['library'][] = 'splide/theme';
    }

    if (!empty($attach['pagination_fx'])) {
      $load['library'][] = 'splide/pagination.' . $attach['pagination_fx'];
    }

    if (!empty($attach['down'])) {
      $load['library'][] = 'splide/arrow.down';
    }

    foreach ($this->getConstantSkins() as $group) {
      $skin = $group == 'main' ? $attach['skin'] : ($attach['skin_' . $group] ?? '');
      if (!empty($skin)) {
        $skins = $this->getSkinsByGroup($group);
        $provider = $skins[$skin]['provider'] ?? 'splide';
        $load['library'][] = 'splide/' . $provider . '.' . $group . '.' . $skin;
      }
    }
  }

  /**
   * Returns splide library path if available, else FALSE.
   */
  public function getSplidePath(
    $base = 'splide',
    $packagist = 'splidejs--splide'
  ): ?string {
    if (!isset($this->splidePath[$base])) {
      $this->splidePath[$base] = \blazy_libraries_get_path($packagist) ?: \blazy_libraries_get_path($base);
    }
    return $this->splidePath[$base];
  }

  /**
   * Implements hook_library_info_alter().
   */
  public function libraryInfoAlter(array &$libraries, $extension): void {
    if ($path = $this->getSplidePath()) {
      $js = [
        '/' . $path . '/dist/js/splide.min.js' => [
          'weight' => -3,
          'minified' => TRUE,
        ],
      ];
      $base = ['/' . $path . '/dist/css/splide-core.min.css' => []];
      $theme = ['/' . $path . '/dist/css/splide.min.css' => []];

      $libraries['splide']['js'] = $js;
      $libraries['splide']['css']['base'] = $base;
      $libraries['splide.css']['css']['theme'] = $theme;
    }

    $plugins = [
      'autoscroll' => 'auto-scroll',
      'intersection' => 'intersection',
    ];

    foreach ($plugins as $key => $value) {
      $base = 'splide-extension-' . $value;
      if ($path = $this->getSplidePath($base, 'splidejs--' . $base)) {
        $js = [
          '/' . $path . '/dist/js/' . $base . '.min.js' => [
            'weight' => -2.9,
            'minified' => TRUE,
          ],
        ];
        $libraries[$key]['js'] = $js;
      }
    }
  }

  /**
   * Splide module-managed/ builtin library components including library ones.
   */
  private function getComponents(): array {
    return array_merge($this->getModuleComponents(), [
      'autoscroll',
      'intersection',
    ]);
  }

  /**
   * Splide module-managed/ builtin library components.
   */
  private function getModuleComponents(): array {
    return [
      'blazy',
      'colorbox',
      'fullscreen',
      'media',
      'swipedetect',
      'zoom',
    ];
  }

}
