<?php

namespace Drupal\react_search\Plugin\Block;

/**
 * Contains \Drupal\react_search\Plugin\Block\SearchBlock.
*/

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Block' block.
 *
 * @Block(
 *  id = "main_search_block",
 *  admin_label = @Translation("React search block"),
 *  deriver = "Drupal\react_search\Plugin\Derivative\SearchBlock"
 * )
 */
class SearchBlock extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $block = $this->getPluginDefinition();
    $html_id = $block['html_id'];
    $javascript_id = $block['javascript_id'];
    return [
      '#markup' => '<div id="' . $html_id . '"></div>',
      '#attached' => [
        'library' => 'react_search/' . $javascript_id,
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

  }

}
