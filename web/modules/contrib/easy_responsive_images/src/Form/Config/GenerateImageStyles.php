<?php

namespace Drupal\easy_responsive_images\Form\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to generate image styles.
 */
class GenerateImageStyles extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a GenerateImageStyles object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'easy_responsive_images_generator';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['easy_responsive_images.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#type' => 'details',
      '#title' => $this->t('Width'),
      '#description' => $this->t("Image styles will be generated between the minimum and maximum width with for images with a specific width but flexible height, maintaining the original aspect ratio of the image. To optimize the number of generated styles a fixed difference between the image style width can be configured. If there is a need to generate cropped images with a specific aspect ratio, a list of aspect ratio's can be defined."),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    $form['width']['threshold_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Preferred amount of pixels between the width of each image style'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('threshold_width'),
      '#min' => 10,
      '#max' => 500,
      '#step' => 10,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['width']['minimum_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum image style width'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('minimum_width'),
      '#min' => 50,
      '#max' => 1000,
      '#step' => 50,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['width']['maximum_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum image style width'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('maximum_width'),
      '#min' => 50,
      '#max' => 3000,
      '#step' => 50,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['width']['aspect_ratios'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Supported aspect ratios'),
      '#description' => $this->t("Define a list of aspect ratio's in the format w:h (eg. 16:9 or 4:3). Place each aspect ration on a separate line."),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('aspect_ratios'),
      '#attributes' => ['placeholder' => $this->t('eg. 16:9')],
    ];

    $form['height'] = [
      '#type' => 'details',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Define a minimum and maximum height if there is a need for images that have a specific height but a flexible width. This will not influence the image styles generated based on aspect ratio.'),
      '#tree' => FALSE,
    ];

    $form['height']['threshold_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Preferred amount of pixels between the height of each image style'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('threshold_height'),
      '#min' => 10,
      '#max' => 500,
      '#step' => 10,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['height']['minimum_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum image style height'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('minimum_height'),
      '#min' => 50,
      '#max' => 1000,
      '#step' => 50,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['height']['maximum_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum image style height'),
      '#default_value' => $this->config('easy_responsive_images.settings')->get('maximum_height'),
      '#min' => 100,
      '#max' => 3000,
      '#step' => 50,
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('easy_responsive_images.settings');
    $config->set('threshold_width', $form_state->getValue('threshold_width'))
      ->set('minimum_width', $form_state->getValue('minimum_width'))
      ->set('maximum_width', $form_state->getValue('maximum_width'))
      ->set('aspect_ratios', $form_state->getValue('aspect_ratios'))
      ->set('threshold_height', $form_state->getValue('threshold_height'))
      ->set('minimum_height', $form_state->getValue('minimum_height'))
      ->set('maximum_height', $form_state->getValue('maximum_height'))
      ->save();

    $image_style_storage = $this->entityTypeManager->getStorage('image_style');
    $generated_styles = [];

    // Generate the image styles for images with a specific width but flexible
    // height, maintaining the original aspect ratio of the image.
    $width_threshold = $form_state->getValue('threshold_width') ?? 100;
    $width_min = $form_state->getValue('minimum_width');
    $width_max = $form_state->getValue('maximum_width');
    if ($width_min && $width_max) {
      for ($width = $width_min; $width <= $width_max; $width += $width_threshold) {
        $name = 'responsive_' . $width . 'w';
        $generated_styles[] = $name;
        if (!$image_style_storage->load($name)) {
          /** @var \Drupal\image\ImageStyleInterface $style */
          $style = $image_style_storage->create([
            'name' => $name,
            'label' => $name,
          ]);
          $style->addImageEffect([
            'id' => 'image_scale',
            'data' => [
              'width' => $width,
              'height' => NULL,
              'upscale' => TRUE,
            ],
          ]);
          $style->save();
        }
      }
    }

    // Generate the image styles for images with a specific aspect ratio.
    $aspect_ratios = array_filter(preg_split('/\s+/', $form_state->getValue('aspect_ratios')));
    foreach ($aspect_ratios as $aspect_ratio) {
      [$w, $h] = explode(':', $aspect_ratio);
      for ($width = $width_min; $width <= $width_max; $width += $width_threshold) {
        $height = ($width / $w) * $h;
        $name = 'responsive_' . $w . '_' . $h . '_' . $width . 'w';
        $generated_styles[] = $name;
        if (!$image_style_storage->load($name)) {
          /** @var \Drupal\image\ImageStyleInterface $style */
          $style = $image_style_storage->create([
            'name' => $name,
            'label' => $name,
          ]);
          if ($this->moduleHandler->moduleExists('focal_point')) {
            $style->addImageEffect([
              'id' => 'focal_point_scale_and_crop',
              'data' => [
                'width' => $width,
                'height' => $height,
                'crop_type' => 'focal_point',
              ],
            ]);
          }
          else {
            $style->addImageEffect([
              'id' => 'image_scale_and_crop',
              'data' => [
                'width' => $width,
                'height' => $height,
              ],
            ]);
          }
          $style->save();
        }
      }
    }

    // Generate the image styles for images with a specific height but flexible
    // width, maintaining the original aspect ratio of the image.
    $height_threshold = $form_state->getValue('threshold_height') ?? 100;
    $height_min = $form_state->getValue('minimum_height');
    $height_max = $form_state->getValue('maximum_height');
    if ($height_min && $height_max) {
      for ($height = $height_min; $height <= $height_max; $height += $height_threshold) {
        $name = 'responsive_' . $height . 'h';
        $generated_styles[] = $name;
        if (!$image_style_storage->load($name)) {
          /** @var \Drupal\image\ImageStyleInterface $style */
          $style = $image_style_storage->create([
            'name' => $name,
            'label' => $name,
          ]);
          $style->addImageEffect([
            'id' => 'image_scale',
            'data' => [
              'width' => NULL,
              'height' => $height,
              'upscale' => TRUE,
            ],
          ]);
          $style->save();
        }
      }
    }

    // Delete obsolete styles.
    $obsolete_ids = $image_style_storage->getQuery()
      ->condition('name', 'responsive_', 'STARTS_WITH')
      ->condition('name', $generated_styles, 'NOT IN')
      ->execute();
    $obsolete_styles = $image_style_storage->loadMultiple($obsolete_ids);
    $image_style_storage->delete($obsolete_styles);

    parent::submitForm($form, $form_state);
  }

}
