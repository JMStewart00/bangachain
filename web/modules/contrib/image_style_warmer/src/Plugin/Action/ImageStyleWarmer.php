<?php

namespace Drupal\image_style_warmer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image_style_warmer\ImageStylesWarmerInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An action to warmup image styles.
 *
 * @Action(
 *   id = "image_style_warmer",
 *   label = @Translation("Warmup Image Styles"),
 *   type = "file",
 *   confirm = TRUE,
 * )
 */
class ImageStyleWarmer extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Image Style Warmer.
   *
   * @var \Drupal\image_style_warmer\ImageStylesWarmerInterface
   */
  protected $imageStyleWarmer;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\image_style_warmer\ImageStylesWarmerInterface $imageStyleWarmer
   *   Media Thumbnail Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImageStylesWarmerInterface $imageStyleWarmer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->imageStyleWarmer = $imageStyleWarmer;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image_style_warmer.warmer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->imageStyleWarmer->warmUp($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof FileInterface && $object->getEntityType() === 'file') {
      /** @var \Drupal\Core\Access\AccessResult $access */
      $access = $object->access('view', $account, TRUE);
      return $return_as_object ? $access : $access->isAllowed();
    }
    return TRUE;
  }

}
