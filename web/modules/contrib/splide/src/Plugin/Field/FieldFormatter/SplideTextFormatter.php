<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyTextFormatter;
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
class SplideTextFormatter extends BlazyTextFormatter {

  use SplideFormatterTrait {
    pluginSettings as traitPluginSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'text');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::baseSettings() + SplideDefault::gridSettings();
  }

  /**
   * Build the splide carousel elements.
   */
  public function buildElements(array &$build, $items) {
    foreach ($this->getElements($items) as $element) {
      $build['items'][] = $element;
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
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    $this->traitPluginSettings($blazies, $settings);

    $blazies->set('is.navless', TRUE)
      ->set('is.text', TRUE)
      ->set('is.vanilla', TRUE);

    // @todo remove.
    $settings['navless'] = TRUE;
    $settings['vanilla'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'grid_form'        => TRUE,
      'no_image_style'   => TRUE,
      'no_layouts'       => TRUE,
      'responsive_image' => FALSE,
      'style'            => TRUE,
    ];
  }

}
