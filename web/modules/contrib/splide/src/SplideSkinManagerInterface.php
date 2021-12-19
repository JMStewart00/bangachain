<?php

namespace Drupal\splide;

/**
 * Provides an interface defining Splide skins, and asset managements.
 */
interface SplideSkinManagerInterface {

  /**
   * Returns an instance of a plugin by given plugin id.
   *
   * @param string $id
   *   The plugin id.
   *
   * @return object
   *   Return instance of SplideSkin.
   */
  public function load($id);

}
