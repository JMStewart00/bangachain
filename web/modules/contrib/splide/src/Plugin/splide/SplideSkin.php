<?php

namespace Drupal\splide\Plugin\splide;

use Drupal\splide\SplideSkinPluginBase;

/**
 * Provides splide skins.
 *
 * @SplideSkin(
 *   id = "splide_skin",
 *   label = @Translation("Splide skin")
 * )
 */
class SplideSkin extends SplideSkinPluginBase {

  /**
   * Sets the splide skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    // If you copy this file, be sure to add base_path() before any asset path
    // (css or js) as otherwise failing to load the assets. Your module can
    // register paths pointing to a theme. Check out splide.api.php for details.
    $skins = [
      'default' => [
        'name' => 'Default',
        'css' => [
          'theme' => [
            'css/theme/splide.theme--default.css' => [],
          ],
        ],
      ],
      'asnavfor' => [
        'name' => 'Thumbnail: asNavFor',
        'css' => [
          'theme' => [
            'css/theme/splide.theme--asnavfor.css' => [],
          ],
        ],
        'description' => $this->t('Affected thumbnail navigation only.'),
      ],
      'classic' => [
        'name' => 'Classic',
        'description' => $this->t('Adds dark background color over white caption, only good for slider (single slide visible), not carousel (multiple slides visible), where small captions are placed over images.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--classic.css' => [],
          ],
        ],
      ],
      'fullscreen' => [
        'name' => 'Full screen',
        'description' => $this->t('Adds full screen display, works best with 1 perPage.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--full.css' => [],
            'css/theme/splide.theme--fullscreen.css' => [],
          ],
        ],
      ],
      'fullwidth' => [
        'name' => 'Full width',
        'description' => $this->t('Adds .slide__constrained wrapper to hold caption overlay within the max-container.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--full.css' => [],
            'css/theme/splide.theme--fullwidth.css' => [],
          ],
        ],
      ],
      'grid' => [
        'name' => 'Grid Foundation',
        'description' => $this->t('Use perPage > 1 to have more grid combination, only if you have considerable amount of grids, otherwise 1.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--grid.css' => [],
          ],
        ],
      ],
      'split' => [
        'name' => 'Split',
        'description' => $this->t('Puts image and caption side by side, requires any split layout option.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--split.css' => [],
          ],
        ],
      ],
      'seagreen' => [
        'name' => 'Seagreen',
        'description' => $this->t('Core library skin with sea green color.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--seagreen.css' => [],
          ],
        ],
      ],
      'skyblue' => [
        'name' => 'Skyblue',
        'description' => $this->t('Core library skin with blue sky color.'),
        'css' => [
          'theme' => [
            'css/theme/splide.theme--skyblue.css' => [],
          ],
        ],
      ],
    ];

    foreach ($skins as $key => $skin) {
      $skins[$key]['group'] = $key == 'asnavfor' ? 'nav' : 'main';
      $skins[$key]['provider'] = 'splide';
    }

    return $skins;
  }

}
