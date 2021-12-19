<?php

namespace Drupal\splide\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SplideSkin item annotation object.
 *
 * @Annotation
 */
class SplideSkin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
