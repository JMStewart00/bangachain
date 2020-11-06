<?php

declare(strict_types = 1);

namespace Drupal\bangachain_layout\Plugin\Layout;

use Drupal\bangachain_layout\BangAChainLayout;
use Drupal\bangachain_layout\Helper\MediaHelperTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a layout base for custom layouts.
 */
final class OneColumnLayout extends LayoutDefault implements PluginFormInterface {

  use MediaHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $build['#attributes']['class'][] = [];

    $hero = $this->configuration['hero'];
    if ($hero) {
      $build['#attributes']['class'][] = 'l-hero-row';
    }

    $class = $this->configuration['class'];
    if ($class) {
      $build['#attributes']['class'] = array_merge(
        explode(' ', $this->configuration['class']),
        $build['#attributes']['class']
      );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'background_color' => BangAChainLayout::NO_BACKGROUND_COLOR,
      'class' => NULL,
      'column_width' => $this->getDefaultColumnWidth(),
      'hero' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $columnWidths = $this->getColumnWidths();
    $pluginId = $this->getPluginId();

    $form['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => TRUE,
    ];

    $form['layout']['column_width'] = [
      '#type' => 'radios',
      '#title' => $this->t('Column Width'),
      '#options' => $columnWidths,
      '#default_value' => $this->configuration['column_width'],
      '#required' => TRUE,
    ];

    $form['background'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
      '#open' => $this->hasBackgroundSettings(),
    ];

    $form['background']['background_color'] = [
      '#type' => 'bangachain_layout_background_color_radios',
      '#plugin_id' => $this->getPluginDefinition()->id(),
      '#default_value' => $this->configuration['background_color'],
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Extra'),
      '#open' => TRUE,
    ];

    $form['extra']['hero'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hero Mode'),
      'checkbox' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable hero mode'),
        '#description' => $this->t('Enabling hero mode will remove the left and right space of the row. This will make the row the width of the browser window.'),
        '#default_value' => $this->configuration['hero'],
      ],
    ];

    $form['extra']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Class'),
      '#description' => $this->t('Enter custom css classes for this row. Separate multiple classes by a space and do not include a period.'),
      '#default_value' => $this->configuration['class'],
      '#attributes' => [
        'placeholder' => 'class-one class-two',
      ],
    ];

    $form['#attached']['library'][] = 'bangachain_layout/layout_builder';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configuration['background_color'] = $values['background']['background_color'];
    $this->configuration['class'] = $values['extra']['class'];
    $this->configuration['column_width'] = $values['layout']['column_width'];
    $this->configuration['hero'] = (bool) $values['extra']['hero']['checkbox'];

  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      // BangAChainLayout::ROW_WIDTH_50 => $this->t('50%'),
      // BangAChainLayout::ROW_WIDTH_75 => $this->t('75%'),
      BangAChainLayout::ROW_WIDTH_100 => $this->t('100%'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return BangAChainLayout::ROW_WIDTH_100;
  }

  /**
   * Determine if this layout has background settings.
   *
   * @return bool
   *   If this layout has background settings.
   */
  protected function hasBackgroundSettings(): bool {
    if (!empty($this->configuration['background_color'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine if this layout has extra settings.
   *
   * @return bool
   *   If this layout has extra settings.
   */
  protected function hasExtraSettings(): bool {
    return $this->configuration['class'] || $this->configuration['hero'];
  }

}
