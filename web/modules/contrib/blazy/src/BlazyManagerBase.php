<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements BlazyManagerInterface.
 */
abstract class BlazyManagerBase implements BlazyManagerInterface {

  // Fixed for EB AJAX issue: #2893029.
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The app root.
   *
   * @var \SplString
   */
  protected $root;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->entityRepository  = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler     = $module_handler;
    $this->renderer          = $renderer;
    $this->configFactory     = $config_factory;
    $this->cache             = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('cache.default')
    );

    // @todo remove and use DI at 2.x+ post sub-classes updates.
    $instance->setRoot($container->getParameter('app.root'));
    $instance->setLanguageManager($container->get('language_manager'));
    return $instance;
  }

  /**
   * Returns the app root.
   */
  public function root() {
    return $this->root;
  }

  /**
   * Sets app root service.
   *
   * @todo remove and use DI at 3.x+ post sub-classes updates.
   */
  public function setRoot($root) {
    $this->root = (string) $root;
    return $this;
  }

  /**
   * Returns the language manager service.
   */
  public function languageManager() {
    return $this->languageManager;
  }

  /**
   * Sets the language manager service.
   *
   * @todo remove and use DI at 3.x+ post sub-classes updates.
   */
  public function setLanguageManager($language_manager) {
    $this->languageManager = $language_manager;
    return $this;
  }

  /**
   * Returns the entity repository service.
   */
  public function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * Returns the entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Returns the module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Returns the renderer.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Returns the config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * Returns the cache.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Returns any config, or keyed by the $setting_name.
   */
  public function configLoad($setting_name = '', $settings = 'blazy.settings') {
    $config  = $this->configFactory->get($settings);
    $configs = $config->get();
    unset($configs['_core']);
    return empty($setting_name) ? $configs : $config->get($setting_name);
  }

  /**
   * Returns a shortcut for loading a config entity: image_style, slick, etc.
   */
  public function entityLoad($id, $entity_type = 'image_style') {
    return $this->entityTypeManager->getStorage($entity_type)->load($id);
  }

  /**
   * Returns a shortcut for loading multiple configuration entities.
   */
  public function entityLoadMultiple($entity_type = 'image_style', $ids = NULL) {
    return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $this->getCommonSettings($attach);
    $load = [];
    $switch = $attach['media_switch'] ?? '';

    if ($switch && $switch != 'content') {
      $attach[$switch] = $switch;

      if (in_array($switch, $this->getLightboxes())) {
        $load['library'][] = 'blazy/lightbox';

        if (!empty($attach['colorbox'])) {
          BlazyAlter::attachColorbox($load, $attach);
        }
      }
    }

    // Allow variants of grid, columns, flexbox, native grid to co-exist.
    if ($attach['style']) {
      $attach[$attach['style']] = $attach['style'];
    }

    // Always keep Drupal UI config to support dynamic compat features.
    $config = $this->configLoad('blazy');
    $config['loader'] = empty($attach['nojs']['lazy']);
    $config['unblazy'] = $this->configLoad('io.unblazy');

    // One is enough due to various formatters with different features.
    if ($attach['compat']) {
      $config['compat'] = $attach['compat'];
    }

    $load['drupalSettings']['blazy'] = $config;
    $load['drupalSettings']['blazyIo'] = $this->getIoSettings($attach);

    // Only if `No JavaScript` option is disabled, or has compat.
    // Compat is a loader for Blur, BG, Video which Native doesn't support.
    if (empty($attach['nojs']['lazy']) || $attach['compat']) {
      // Modern sites may want to forget oldies, respect.
      if (!$config['unblazy']) {
        $load['library'][] = 'blazy/blazy';
      }

      foreach (BlazyDefault::nojs() as $key) {
        if (empty($attach['nojs'][$key])) {
          $lib = $key == 'lazy' ? 'load' : $key;
          $load['library'][] = 'blazy/' . $lib;
        }
      }
    }

    foreach (BlazyDefault::components() as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = 'blazy/' . $component;
      }
    }

    // Adds AJAX helper to revalidate Blazy/ IO, if using VIS, or alike.
    if ($attach['use_ajax']) {
      $load['library'][] = 'blazy/bio.ajax';
    }

    // Preload.
    if (!empty($attach['preload'])) {
      BlazyFile::preload($load, $attach);
    }

    $this->moduleHandler->alter('blazy_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function getIoSettings(array $attach = []) {
    $io = [];
    $thold = trim($this->configLoad('io.threshold'));
    $thold = str_replace(['[', ']'], '', $thold ?: '0');

    // @todo re-check, looks like the default 0 is broken sometimes.
    if ($thold == '0') {
      $thold = '0, 0.25, 0.5, 0.75, 1';
    }

    $thold = strpos($thold, ',') !== FALSE ? array_map('trim', explode(',', $thold)) : [$thold];
    $formatted = [];
    foreach ($thold as $value) {
      $formatted[] = strpos($value, '.') !== FALSE ? (float) $value : (int) $value;
    }

    // Respects hook_blazy_attach_alter() for more fine-grained control.
    foreach (['disconnect', 'rootMargin', 'threshold'] as $key) {
      $default = $key == 'rootMargin' ? '0px' : FALSE;
      $value = $key == 'threshold' ? $formatted : $this->configLoad('io.' . $key);
      $io[$key] = $attach['io.' . $key] ?? ($value ?: $default);
    }

    return (object) $io;
  }

  /**
   * Returns the common UI settings inherited down to each item.
   *
   * The `fx` sequence: hook_alter > formatters (not implemented yet) > UI.
   * The `_fx` is a special flag such as to temporarily disable till needed.
   */
  public function getCommonSettings(array &$settings = []) {
    $config = array_intersect_key($this->configLoad(), BlazyDefault::uiSettings());
    $config['fx'] = $config['fx'] ?? '';
    $config['fx'] = empty($settings['fx']) ? $config['fx'] : $settings['fx'];
    $settings = array_merge($settings, $config);
    $settings += BlazyDefault::htmlSettings();
    $switch = $settings['media_switch'];
    $style = $settings['responsive_image_style'];
    $settings['fx'] = $settings['animate'] = $settings['_fx'] ?? $settings['fx'];
    $settings['blur'] = $settings['fx'] == 'blur';
    $settings['iframe_domain'] = $this->configLoad('iframe_domain', 'media.settings');
    $settings['is_amp'] = Blazy::isAmp();
    $settings['is_preview'] = Blazy::isPreview();
    $settings['is_sandboxed'] = Blazy::isSandboxed();
    $settings['is_nojs'] = !empty($settings['nojs']['lazy']) || $settings['is_preview'] || $settings['is_amp'] || $settings['loading'] == 'unlazy';
    $settings['lightbox'] = ($switch && in_array($switch, $this->getLightboxes())) ? $switch : FALSE;
    $settings['route_name'] = $this->getRouteName();
    $settings['_resimage'] = $this->moduleHandler->moduleExists('responsive_image');
    $settings['resimage'] = $settings['_resimage'] && $style;
    $settings['resimage'] = $settings['resimage'] ? $this->entityLoad($style, 'responsive_image_style') : FALSE;
    $settings['fluid'] = $settings['ratio'] == 'fluid';
    $settings['compat'] = $settings['fx'] || $settings['fluid'] || $settings['background'] || $settings['bundle'] == 'video' || $settings['compat'];
    $settings['current_language'] = $this->languageManager->getCurrentLanguage()->getId();

    // Allows lightboxes to provide its own optionsets, e.g.: ElevateZoomPlus.
    if ($switch) {
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Formatters, Views style, not Filters.
    if (!empty($settings['style'])) {
      BlazyGrid::toNativeGrid($settings);
    }
  }

  /**
   * Returns the common settings extracted from the given entity.
   */
  public function getEntitySettings(array &$settings, $entity) {
    $internal_path = $absolute_path = NULL;

    // Deals with UndefinedLinkTemplateException such as paragraphs type.
    // @see #2596385, or fetch the host entity.
    if (!$entity->isNew()) {
      try {
        // Check if multilingual is enabled (@see #3214002).
        if ($entity->hasTranslation($settings['current_language'])) {
          // Load the translated url.
          $url = $entity->getTranslation($settings['current_language'])->toUrl();
        }
        else {
          // Otherwise keep the standard url.
          $url = $entity->toUrl();
        }

        $internal_path = $url->getInternalPath();
        $absolute_path = $url->setAbsolute()->toString();
      }
      catch (\Exception $ignore) {
        // Do nothing.
      }
    }

    // @todo Remove checks after another check, in case already set somewhere.
    // The `current_view_mode` (entity|views display) is not `view_mode` option.
    $settings['current_view_mode'] = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $settings['entity_id'] = empty($settings['entity_id']) ? $entity->id() : $settings['entity_id'];
    $settings['entity_type_id'] = empty($settings['entity_type_id']) ? $entity->getEntityTypeId() : $settings['entity_type_id'];
    $settings['bundle'] = empty($settings['bundle']) ? $entity->bundle() : $settings['bundle'];
    $settings['content_url'] = $settings['absolute_path'] = $absolute_path;
    $settings['internal_path'] = $internal_path;
    $settings['cache_metadata']['keys'][] = $settings['entity_id'];
    $settings['cache_metadata']['keys'][] = $entity->getRevisionID();
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes() {
    $lightboxes = [];
    foreach (['colorbox', 'photobox'] as $lightbox) {
      if (function_exists($lightbox . '_theme')) {
        $lightboxes[] = $lightbox;
      }
    }

    if (is_file($this->root . '/libraries/photobox/photobox/jquery.photobox.js')) {
      $lightboxes[] = 'photobox';
    }

    $this->moduleHandler->alter('blazy_lightboxes', $lightboxes);
    return array_unique($lightboxes);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageEffects() {
    $effects[] = 'blur';

    $this->moduleHandler->alter('blazy_image_effects', $effects);
    $effects = array_unique($effects);
    return array_combine($effects, $effects);
  }

  /**
   * {@inheritdoc}
   */
  public function isBlazy(array &$settings, array $item = []) {
    // Retrieves Blazy formatter related settings from within Views style.
    $item_id = $settings['item_id'] ?? 'x';
    $content = $item[$item_id] ?? $item;
    $image   = $item['item'] ?? NULL;

    // 1. Blazy formatter within Views fields by supported modules.
    $settings['_item'] = $image;
    if (isset($item['settings'])) {
      $this->isBlazyFormatter($settings, $item);
    }

    // 2. Blazy Views fields by supported modules.
    // Prevents edge case with unexpected flattened Views results which is
    // normally triggered by checking "Use field template" option.
    if (is_array($content) && ($view = ($content['#view'] ?? NULL))) {
      if ($blazy_field = BlazyViews::viewsField($view)) {
        $settings = array_merge(array_filter($blazy_field->mergedViewsSettings()), array_filter($settings));
      }
    }

    unset($settings['first_image']);
  }

  /**
   * Collects the first found Blazy formatter settings within Views fields.
   */
  protected function isBlazyFormatter(array &$settings, array $item = []) {
    $blazy = $item['settings'];

    // Merge the first found (Responsive) image data.
    if (!empty($blazy['blazy_data'])) {
      $settings['blazy_data'] = array_merge((array) ($settings['blazy_data'] ?? []), $blazy['blazy_data']);
      $settings['_dimensions'] = !empty($settings['blazy_data']['dimensions']);
    }

    $cherries = BlazyDefault::cherrySettings() + ['uri' => ''];
    foreach ($cherries as $key => $value) {
      $fallback = $settings[$key] ?? $value;
      $settings[$key] = isset($blazy[$key]) && empty($fallback) ? $blazy[$key] : $fallback;
    }

    $settings['_uri'] = empty($settings['_uri']) ? $settings['uri'] : $settings['_uri'];
    unset($settings['uri']);
  }

  /**
   * Return the cache metadata common for all blazy-related modules.
   */
  public function getCacheMetadata(array $build = []) {
    $settings          = $build['settings'] ?? $build;
    $namespace         = $settings['namespace'] ?? 'blazy';
    $max_age           = $this->configLoad('cache.page.max_age', 'system.performance');
    $max_age           = empty($settings['cache']) ? $max_age : $settings['cache'];
    $id                = $settings['id'] ?? Blazy::getHtmlId($namespace);
    $suffixes[]        = empty($settings['count']) ? count(array_filter($settings)) : $settings['count'];
    $cache['tags']     = Cache::buildTags($namespace . ':' . $id, $suffixes, '.');
    $cache['contexts'] = ['languages'];
    $cache['max-age']  = $max_age;
    $cache['keys']     = $settings['cache_metadata']['keys'] ?? [$id];

    if (!empty($settings['cache_tags'])) {
      $cache['tags'] = Cache::mergeTags($cache['tags'], $settings['cache_tags']);
    }

    return $cache;
  }

  /**
   * Provides attachments and cache common for all blazy-related modules.
   */
  protected function setAttachments(array &$element, array $settings, array $attachments = []) {
    $cache                = $this->getCacheMetadata($settings);
    $attached             = $this->attach($settings);
    $attachments          = empty($attachments) ? $attached : NestedArray::mergeDeep($attached, $attachments);
    $element['#attached'] = empty($element['#attached']) ? $attachments : NestedArray::mergeDeep($element['#attached'], $attachments);
    $element['#cache']    = empty($element['#cache']) ? $cache : NestedArray::mergeDeep($element['#cache'], $cache);
  }

  /**
   * Returns the thumbnail image using theme_image(), or theme_image_style().
   */
  public function getThumbnail(array $settings = [], $item = NULL) {
    if (!empty($settings['uri'])) {
      $external = UrlHelper::isExternal($settings['uri']);
      return [
        '#theme'      => $external ? 'image' : 'image_style',
        '#style_name' => empty($settings['thumbnail_style']) ? 'thumbnail' : $settings['thumbnail_style'],
        '#uri'        => $settings['uri'],
        '#item'       => $item,
        '#alt'        => $item && $item instanceof ImageItem ? $item->getValue()['alt'] : '',
      ];
    }
    return [];
  }

  /**
   * Provides alterable display styles.
   */
  public function getStyles() {
    $styles = [
      'column' => 'CSS3 Columns',
      'grid' => 'Grid Foundation',
      'flex' => 'Flexbox Masonry',
      'nativegrid' => 'Native Grid',
    ];
    $this->moduleHandler->alter('blazy_style', $styles);
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return Blazy::routeMatch()->getRouteName();
  }

  /**
   * Collects defined skins as registered via hook_MODULE_NAME_skins_info().
   *
   * @todo remove for sub-modules own skins as plugins at blazy:8.x-2.1+.
   * @see https://www.drupal.org/node/2233261
   * @see https://www.drupal.org/node/3105670
   */
  public function buildSkins($namespace, $skin_class, $methods = []) {
    return [];
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.5 and is removed from blazy:3.0.0. Use
   *   BlazyResponsiveImage::dimensions() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function setResponsiveImageDimensions(array &$settings = [], $initial = TRUE) {
    BlazyResponsiveImage::dimensions($settings, $initial);
  }

  /**
   * Deprecated method.
   *
   * @deprecated in blazy:8.x-2.5 and is removed from blazy:3.0.0. Use
   *   BlazyResponsiveImage::getStyles() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function getResponsiveImageStyles($responsive) {
    return BlazyResponsiveImage::getStyles($responsive);
  }

}
