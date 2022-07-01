<?php

namespace Drupal\easy_responsive_images;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Manages easy responsive images.
 */
class EasyResponsiveImagesManager implements EasyResponsiveImagesManagerInterface {

  /**
   * The field formatter configuration.
   *
   * @var array|null
   */
  protected $configuration;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a new Easy Responsive Images object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * The initial image configuration array.
   *
   * @return array|null
   *   Returns the initial images configuration as an array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function initialImagesConfiguration(): ?array {
    if ($this->configuration === NULL) {
      $this->configuration = [];
      $image_style_storage = $this->entityTypeManager
        ->getStorage('image_style');
      $image_style_ids = $image_style_storage->getQuery()
        ->condition('name', 'responsive_', 'STARTS_WITH')
        ->execute();
      $loaded_styles = $image_style_storage->loadMultiple($image_style_ids);

      foreach ($loaded_styles as $style_name => $style) {
        $style_parts = explode('_', $style_name);
        // If style_parts array has 4 keys/values
        // (e.g. {"responsive", "16", "9", "100w"}) we are dealing
        // with a responsive image style.
        if (count($style_parts) === 4) {
          $aspect_w = $style_parts[1];
          $aspect_h = $style_parts[2];
          $width = str_replace('w', '', $style_parts[3]);
          $height = round($width / $aspect_w * $aspect_h, 0);
          $group = "${aspect_w}_${aspect_h}";

          // Process variables - build custom array.
          $this->configuration[$group][$style_name]['style'] = $style;
          $this->configuration[$group][$style_name]['width'] = $width;
          $this->configuration[$group][$style_name]['height'] = $height;
        }
        // If the style_parts array has two keys/values
        // (e.g. {"responsive", "1500w"}) we are dealing
        // with a scaling image.
        elseif (count($style_parts) === 2 && substr($style_parts[1], -1) === 'w') {
          $width = str_replace('w', '', $style_parts[1]);
          $group = 'scale';

          // Process variables - build custom array.
          $this->configuration[$group][$style_name]['style'] = $style;
          $this->configuration[$group][$style_name]['width'] = $width;
        }
      }
      return $this->configuration;
    }
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getAspectRatios(): array {
    $images_configuration = $this->initialImagesConfiguration();
    unset($images_configuration['scale']);
    $options = [];

    foreach ($images_configuration as $key => $image_config_item) {
      $options[$key] = str_replace('_', ':', $key);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getImagesByAspectRatio(string $uri, string $aspect_ratio): array {
    $images_configuration = $this->initialImagesConfiguration();
    $style_infos = $images_configuration[$aspect_ratio] ?? NULL;
    $style_urls = [];

    foreach ($style_infos as $style_info) {
      $style_url = $this->fileUrlGenerator->transformRelative(($style_info['style']->buildUrl($uri)));
      $style_url = $this->getWebpDerivatives($style_url, $uri);

      $style_urls[] = [
        'url' => $style_url,
        'width' => $style_info['width'],
        'height' => (int) $style_info['height'],
        'srcset_url' => $style_url . ' ' . $style_info['width'] . 'w',
      ];
    }

    usort($style_urls, static fn(array $a, array $b) => [$a['width']] <=> [$b['width']]);

    return $style_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getImagesByScale(string $uri): array {
    $images_configuration = $this->initialImagesConfiguration();
    $style_infos = $images_configuration['scale'] ?? NULL;
    $style_urls = [];

    foreach ($style_infos as $style_info) {
      $style_url = $this->fileUrlGenerator->transformRelative(($style_info['style']->buildUrl($uri)));
      $style_url = $this->getWebpDerivatives($style_url, $uri);

      $style_urls[] = [
        'url' => $style_url,
        'width' => $style_info['width'],
        'srcset_url' => $style_url . ' ' . $style_info['width'] . 'w',
      ];
    }

    usort($style_urls, fn(array $a, array $b) => [$a['width']] <=> [$b['width']]);

    return $style_urls;
  }

  /**
   * Get webp derivatives of an image.
   *
   * It checks if the module image_optimize_webp is installed
   * and the browser supports webp.
   *
   * @param string $url
   *   The url.
   * @param string $uri
   *   The uri.
   *
   * @return string
   *   Returns a WebP Derivative as a string.
   */
  protected function getWebpDerivatives(string $url, string $uri): string {
    // If the imageapi_optimize_webp module is installed and the browser
    // supports webp, return the webp version of the image.
    if ($this->moduleHandler->moduleExists('imageapi_optimize_webp')) {
      if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== FALSE) {
        $path_parts = pathinfo($uri);
        $original_extension = '.' . $path_parts['extension'];
        $pos = strrpos($url, $original_extension);
        if ($pos !== FALSE) {
          $url = substr_replace($url, $original_extension . '.webp', $pos, strlen($original_extension));
        }
      }
    }

    return $url;
  }

}
