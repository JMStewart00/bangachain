<?php

declare(strict_types = 1);

namespace Drupal\bangachain_layout\Element;

use Drupal\bangachain_layout\BangAChainLayout;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Provides a background color radios form element.
 *
 * This form element inherits configuration from the Radios elements but also
 * adds additional configuration:
 * - #plugin_id: This option is a string and used for
 *   hook_bangachain_layout_background_colors__HOOK().
 *
 * @FormElement("bangachain_layout_background_color_radios")
 */
final class BackgroundColorRadios extends Radios {

  /**
   * The theme manager.
   *
   * @var Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();

    $info['#process'] = [
      [$this, 'processBackgroundColorRadios'],
      [__CLASS__, 'processRadios'],
    ];

    $info['#plugin_id'] = NULL;

    return $info;
  }

  /**
   * Process the form element.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The processed form element.
   */
  public function processBackgroundColorRadios(array $element): array {
    $element['#options'] = $this->buildBackgroundColorOptions(
      $this->getBackgroundColors($element['#plugin_id'])
    );

    $element['#title'] = $this->t('Background Color');

    $element['#default_value'] = $element['#default_value'] ?: BangAChainLayout::NO_BACKGROUND_COLOR;

    return $element;
  }

  /**
   * Get the theme manager.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   *   The theme manager.
   */
  public function getThemeManager(): ThemeManagerInterface {
    if (!$this->themeManager) {
      $this->themeManager = \Drupal::service('theme.manager');
    }

    return $this->themeManager;
  }

  /**
   * Set the theme manager.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   *
   * @return self
   *   Returns itself for a fluid interface.
   */
  public function setThemeManager(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
    return $this;
  }

  /**
   * Build the background color options.
   *
   * @param array $backgroundColors
   *   The background colors.
   *
   * @return array
   *   An options list of background colors.
   */
  protected function buildBackgroundColorOptions(array $backgroundColors): array {
    $options = [BangAChainLayout::NO_BACKGROUND_COLOR => $this->t('None')];
    foreach ($backgroundColors as $id => $backgroundColor) {
      $classes = implode(' ', [
        'bangachain-layout__background-color-option-card',
        'bangachain-layout__background-color--' . $id,
      ]);
      $options[$id] = $backgroundColor . ' <div class="' . $classes . '"></div>';
    }

    return $options;
  }

  /**
   * Get the layout background colors from the active theme.
   *
   * @param string|null $plugin_id
   *   The plugin id.
   *
   * @return array
   *   A list of background colors.
   */
  protected function getBackgroundColors(?string $plugin_id = NULL): array {
    $theme = $this->getActiveThemeExtension();

    $backgroundColors = [];

    $plugin_id = preg_replace('/[^\w]/', '_', $plugin_id);
    $hooks = [];
    $hooks[] = $theme->getName() . '_bangachain_layout_background_colors';

    if ($plugin_id) {
      $hooks[] = $theme->getName() . '_bangachain_layout_background_colors__' . $plugin_id;
    }

    // Load the theme file.
    $theme->load();

    // Allow themes to provide background colors.
    foreach ($hooks as $hook) {
      if (function_exists($hook)) {
        $result = call_user_func_array($hook, []);
        if (!is_array($result)) {
          $result = [];
        }

        $backgroundColors = array_merge($result, $backgroundColors);
      }
    }

    return $backgroundColors;
  }

  /**
   * Get the active theme extension.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The theme extension.
   */
  protected function getActiveThemeExtension(): Extension {
    return $this->getThemeManager()->getActiveTheme()->getExtension();
  }

}
