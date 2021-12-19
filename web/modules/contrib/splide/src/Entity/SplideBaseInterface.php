<?php

namespace Drupal\splide\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Splide entity.
 */
interface SplideBaseInterface extends ConfigEntityInterface {

  /**
   * Returns the Splide options by group, or property.
   *
   * @param string $group
   *   The name of setting group: settings, breakpoints.
   * @param string $property
   *   The name of specific property: prev, nexArrow.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the array of splide settings.
   *
   * @param string $ansich
   *   Whether to return the settings as is.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings($ansich = FALSE);

  /**
   * Sets the array of splide settings.
   *
   * @param array $settings
   *   The new array of settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSettings(array $settings = []);

  /**
   * Returns the value of a splide setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Sets the value of a splide setting.
   *
   * @param string $setting_name
   *   The setting name.
   * @param string $value
   *   The setting value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSetting($setting_name, $value);

  /**
   * Returns available splide default options under group 'settings'.
   *
   * @param string $group
   *   The name of group: settings, breakpoints.
   *
   * @return array
   *   The default settings under options.
   */
  public static function defaultSettings($group = 'settings');

}
