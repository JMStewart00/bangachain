<?php

namespace Drupal\splide_test\Plugin\splide;

use Drupal\splide\SplideSkinPluginBase;
use Drupal\splide\SplideDefault;

/**
 * Provides splide skin tests.
 *
 * @SplideSkin(
 *   id = "splide_skin_test",
 *   label = @Translation("Splide skin test")
 * )
 */
class SplideSkinTest extends SplideSkinPluginBase {

  /**
   * Sets the splide skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    // If you copy this file, be sure to add base_path() before any asset path
    // (css or js) as otherwise failing to load the assets. Your module can
    // register paths pointing to a theme. Check out splide.api.php for details.
    $path = SplideDefault::getPath('module', 'splide_test', TRUE);
    $skins = [
      'test' => [
        'name' => 'Test',
        'description' => $this->t('Test splide skins.'),
        'group' => 'main',
        'provider' => 'splide_test',
        'css' => [
          'theme' => [
            $path . '/css/splide.theme--test.css' => [],
          ],
        ],
        'options' => [
          'zoom' => TRUE,
        ],
      ],
    ];

    return $skins;
  }

  /**
   * Sets the splide arrow skins.
   *
   * @inheritdoc
   */
  protected function setArrows() {
    $path = SplideDefault::getPath('module', 'splide_test', TRUE);
    $skins = [
      'arrows' => [
        'name' => 'Arrows',
        'description' => $this->t('Test splide arrows.'),
        'provider' => 'splide_test',
        'group' => 'arrows',
        'css' => [
          'theme' => [
            $path . '/css/splide.theme--arrows.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

  /**
   * Sets the splide dots skins.
   *
   * @inheritdoc
   */
  protected function setDots() {
    $path = SplideDefault::getPath('module', 'splide_test', TRUE);
    $skins = [
      'dots' => [
        'name' => 'Dots',
        'description' => $this->t('Test splide dots.'),
        'provider' => 'splide_test',
        'group' => 'dots',
        'css' => [
          'theme' => [
            $path . '/css/splide.theme--dots.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
