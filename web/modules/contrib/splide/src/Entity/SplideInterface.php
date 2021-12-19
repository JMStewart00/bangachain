<?php

namespace Drupal\splide\Entity;

/**
 * Provides an interface defining a Splide entity.
 */
interface SplideInterface extends SplideBaseInterface {

  /**
   * Returns the number of breakpoints.
   *
   * @return int
   *   The number of the provided breakpoints.
   */
  public function getBreakpoint();

  /**
   * Returns the Splide skin.
   *
   * @return string
   *   The name of the Splide skin.
   */
  public function getSkin();

  /**
   * Returns the group this optioset instance belongs to for easy selections.
   *
   * @return string
   *   The name of the optionset group.
   */
  public function getGroup();

  /**
   * Returns whether to optimize the stored options, or not.
   *
   * @return bool
   *   If true, the stored options will be cleaned out from defaults.
   */
  public function optimized();

}
