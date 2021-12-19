<?php

namespace Drupal\splide;

/**
 * Provides an interface defining Splide skins.
 */
interface SplideSkinPluginInterface {

  /**
   * Returns the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function label();

  /**
   * Returns the Splide skins.
   *
   * This can be used to register skins for the Splide. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded splides.
   *
   * Splide skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_splide_carousel_rounded", the CSS class is:
   * splide--skin--my-module-splide-rounded
   *
   * A skin can specify CSS and JS files to include when Splide is displayed,
   * except for a thumbnail skin which accepts CSS only.
   *
   * Each skin supports a few keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - group: A string grouping the current skin: main, thumbnail, arrows, dots.
   * - dependencies: SImilar to how core library dependencies constructed.
   * - provider: A module name registering the skins.
   * - options: Extra JavaScript (Slicebox, 3d carousel, etc) options merged
   *     into existing [data-splide] attribute to be consumed by custom JS.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

  /**
   * Returns the plugin arrow skins.
   *
   * Unlike Slick, the classes are moved to the .splide due to trouble option.
   * The provided arrow skins will be available at sub-module UI form.
   * A skin arrow 'slit' will have a class 'is-arrowed--slit' for the .slide.
   *
   * @return array
   *   The plugin arrow skins.
   */
  public function arrows();

  /**
   * Returns the plugin dot skins.
   *
   * Unlike Slick, the classes are moved to the .splide due to trouble option.
   * The provided dot skins will be available at sub-module UI form.
   * A skin dot named 'hop' will have a class 'is-paginated--hop' for .slide.
   *
   * @return array
   *   The plugin dot skins.
   *
   * @todo TBD; rename to paginations, or keep it.
   */
  public function dots();

}
