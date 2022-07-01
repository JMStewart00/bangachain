<?php

namespace Drupal\easy_responsive_images\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\easy_responsive_images\EasyResponsiveImagesManagerInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'easy responsive image' formatter.
 *
 * @FieldFormatter(
 *   id = "easy_responsive_images",
 *   label = @Translation("Easy Responsive Images"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class EasyResponsiveImagesFormatter extends ImageFormatter {

  /**
   * The easy responsive images manager.
   *
   * @var \Drupal\easy_responsive_images\EasyResponsiveImagesManagerInterface
   */
  protected EasyResponsiveImagesManagerInterface $easyResponsiveImagesManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->easyResponsiveImagesManager = $container->get('easy_responsive_images.manager');
    $instance->fileUrlGenerator = $container->get('file_url_generator');

    return $instance;
  }

  /**
   * Returns the handling options.
   *
   * @return array
   *   The image handling options key|label.
   */
  public function imageHandlingOptions() {
    $options = [
      'scale' => $this->t('Scale'),
    ];
    // Only add aspect ratio when configured.
    if ($this->easyResponsiveImagesManager->getAspectRatios()) {
      $options['aspect_ratio'] = $this->t('Aspect Ratio');
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'image_handling' => 'scale',
      'aspect_ratio' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    // Do not use an image style here. Drimage calculates one for us.
    unset($element['image_style']);

    $element['image_handling'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image handling'),
      '#default_value' => $this->getSetting('image_handling'),
      '#options' => $this->imageHandlingOptions(),
      'scale' => [
        '#description' => $this->t('The image will be scaled in width until it fits. This maintains the original aspect ratio of the image.'),
      ],
      'aspect_ratio' => [
        '#description' => $this->t('The image will be scaled and cropped to an exact aspect ratio you define.'),
      ],
    ];

    $element['aspect_ratio'] = [
      '#title' => $this->t('Aspect Ratio'),
      '#type' => 'select',
      '#description' => $this->t('Select the desired aspect ratio for the image.'),
      '#default_value' => $this->getSetting('aspect_ratio'),
      '#states' => [
        'visible' => [
          ':input[name$="[image_handling]"]' => [
            'value' => 'aspect_ratio',
          ],
        ],
      ],
      '#options' => $this->easyResponsiveImagesManager->getAspectRatios(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $options = $this->imageHandlingOptions();
    $handler = $this->getSetting('image_handling');
    $args = [
      '@image_handling' => $options[$handler],
    ];

    // Add extra options for some handlers.
    if ($handler === 'aspect_ratio') {
      $args['@image_handling'] .= ' (' . $this->getAspectRatio() . ')';
    }

    $summary[] = $this->t('Image handling: @image_handling', $args);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    $files = $this->getEntitiesToView($items, $langcode);

    foreach ($elements as $delta => $element) {
      $elements[$delta]['#item_attributes'] = new Attribute();
      $elements[$delta]['#item_attributes']['class'] = ['easy_responsive_images'];
      $elements[$delta]['#theme'] = 'easy_responsive_images_formatter';

      // Get original image data. (non cropped, non processed) This is useful
      // when implementing lightbox plugins that show the original image.
      $elements[$delta]['#width'] = $element['#item']->getValue()['width'];
      $elements[$delta]['#height'] = $element['#item']->getValue()['height'];

      // Adding additional image attributes.
      $elements[$delta]['#alt'] = $element['#item']->getValue()['alt'];
      $elements[$delta]['#loading_method'] = $this->getSetting('image_loading')['attribute'] ?? 'lazy';

      // Add image_handling and specific data for the type of handling.
      $elements[$delta]['#data']['image_handling'] = $this->getSetting('image_handling');
      switch ($elements[$delta]['#data']['image_handling']) {
        case 'aspect_ratio':
          $elements[$delta]['#data'] = [
            'uri' => $files[$delta]->getFileUri(),
            'url' => $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($files[$delta]->getFileUri())),
            'aspect_ratio' => $this->getSetting('aspect_ratio'),
          ];
          $elements[$delta]['#srcset'] = $this->easyResponsiveImagesManager->getImagesByAspectRatio($elements[$delta]['#data']['uri'], $elements[$delta]['#data']['aspect_ratio']);
          $elements[$delta]['#src'] = $elements[$delta]['#srcset'][0]['url'];
          break;

        case 'scale':
          $elements[$delta]['#data'] = [
            'uri' => $files[$delta]->getFileUri(),
            'url' => $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($files[$delta]->getFileUri())),
          ];
          $elements[$delta]['#srcset'] = $this->easyResponsiveImagesManager->getImagesByScale($elements[$delta]['#data']['uri']);
          break;

        default:
          // Nothing extra needed here.
          break;
      }
    }

    return $elements;
  }

  /**
   * Helper method to format the aspect ratio.
   *
   * Checks if the key 'aspect_ratio' already exists and if it is an array
   * transforms it into a string with a '_' separator. This is needed because we
   * ran into cases where the key was an array when drimage was used previously.
   *
   * @return string
   *   Transforms a given aspect ratio into a string and returns it.
   */
  private function getAspectRatio(): string {
    $aspect_ratio = $this->getSetting('aspect_ratio');
    if (is_array($aspect_ratio)) {
      $aspect_ratio = implode('_', $aspect_ratio);
    }

    return $aspect_ratio;
  }

}
