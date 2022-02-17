<?php

namespace Drupal\splide_x\Plugin\splide;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\splide\SplideSkinPluginBase;
use Drupal\splide\SplideDefault;

/**
 * Provides splide extras skins.
 *
 * @SplideSkin(
 *   id = "splide_x_skin",
 *   label = @Translation("Splide X skin")
 * )
 */
class SplideXSkin extends SplideSkinPluginBase {

  use StringTranslationTrait;

  /**
   * Sets the splide skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    // If you copy this file, be sure to add base_path() before any asset path
    // (css or js) as otherwise failing to load the assets. Your module can
    // register paths pointing to a theme. Check out splide.api.php for details.
    $splide = SplideDefault::getPath('module', 'splide', TRUE);
    $path = SplideDefault::getPath('module', 'splide_x', TRUE);
    $skins = [
      'd3-back' => [
        'name' => 'X 3d back',
        'group' => 'main',
        'provider' => 'splide_x',
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--d3-back.css' => [],
          ],
        ],
        'description' => $this->t('Adds 3d view with focal point at back, works best with 3 perPage, and caption below.'),
      ],
      'boxed' => [
        'name' => 'X Boxed',
        'group' => 'main',
        'provider' => 'splide_x',
        'description' => $this->t('Adds margins to the sides of splide__list revealing arrows.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--boxed.css' => [],
          ],
        ],
      ],
      'boxed-carousel' => [
        'name' => 'X Box carousel',
        'group' => 'main',
        'provider' => 'splide_x',
        'description' => $this->t('Carousel that has margins, alternative to centerMode.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--boxed.css' => [],
            $path . '/css/theme/splide.theme--boxed--carousel.css' => [],
          ],
        ],
      ],
      'boxed-split' => [
        'name' => 'X Box split',
        'group' => 'main',
        'provider' => 'splide_x',
        'description' => $this->t('Adds margins and split caption and image.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--boxed.css' => [],
            $splide . '/css/theme/splide.theme--split.css' => [],
          ],
        ],
      ],
      'rounded' => [
        'name' => 'X Rounded',
        'group' => 'main',
        'provider' => 'splide_x',
        'description' => $this->t('Rounds the .slide__image, great for 3-5 visible-slides carousel.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--rounded.css' => [],
          ],
        ],
      ],
      'vtabs' => [
        'name' => 'X VTabs',
        'group' => 'nav',
        'provider' => 'splide_x',
        'description' => $this->t('Adds a vertical tabs like thumbnail navigation.'),
        'css' => [
          'theme' => [
            $path . '/css/theme/splide.theme--vtabs.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
