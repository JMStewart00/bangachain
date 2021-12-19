<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\splide\SplideDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Splide Text' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_text",
 *   label = @Translation("Splide Text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SplideTextFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use SplideFormatterViewTrait;
  use SplideFormatterTrait {
    buildSettings as traitBuildSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
    return self::injectServices($instance, $container, 'text');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::baseSettings() + SplideDefault::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return $this->commonViewElements($items, $langcode);
  }

  /**
   * Build the splide carousel elements.
   */
  public function buildElements(array &$build, $items) {
    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $key => $item) {
      if (empty($item->value)) {
        continue;
      }

      $element = [
        '#type'     => 'processed_text',
        '#text'     => $item->value,
        '#format'   => $item->format,
        '#langcode' => $item->getLangcode(),
      ];
      $build['items'][$key] = $element;
      unset($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element    = [];
    $definition = $this->getScopedFormElements();

    $this->admin()->buildSettingsForm($element, $definition);
    return $element;
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    return ['vanilla' => TRUE, 'navless' => TRUE] + $this->traitBuildSettings();
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'grid_form'        => TRUE,
      'no_image_style'   => TRUE,
      'no_layouts'       => TRUE,
      'responsive_image' => FALSE,
      'style'            => TRUE,
    ] + $this->getCommonScopedFormElements();
  }

}
