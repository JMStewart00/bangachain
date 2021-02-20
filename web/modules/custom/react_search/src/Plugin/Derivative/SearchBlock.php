<?php

namespace Drupal\react_search\Plugin\Derivative;

/**
 * Contains \Drupal\react_search\Plugin\Derivative\SearchBlock.
 */

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides derivatives for the SearchBlock block.
 */
class SearchBlock extends DeriverBase {

  /**
   * Gets the definition of all derivatives of a base plugin.
   *
   * @see getDerivativeDefinition()
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $blocks = [
      'react_search_main' => [
        'name' => t('Main Site Search'),
        'html_id' => t('mainSearch'),
        'javascript_id' => t('main'),
      ],
      'react_search_discs' => [
        'name' => t('Disc Search'),
        'html_id' => t('discSearch'),
        'javascript_id' => t('discs'),
      ],
    ];

    foreach ($blocks as $block_id => $value) {
      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = $value['name'];
      $this->derivatives[$block_id]['html_id'] = $value['html_id'];
      $this->derivatives[$block_id]['javascript_id'] = $value['javascript_id'];
    }

    return $this->derivatives;
  }

}
