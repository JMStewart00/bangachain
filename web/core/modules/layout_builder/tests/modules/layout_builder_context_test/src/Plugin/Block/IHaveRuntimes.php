<?php

namespace Drupal\layout_builder_context_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Defines a class for a context-aware block.
 *
 * @Block(
 *   id = "i_have_runtimes",
 *   admin_label = "Can I have runtimes",
 *   category = "Test",
 *   context_definitions = {
 *     "runtimes" = @ContextDefinition("string", label = "Do you have runtimes")
 *   }
 * )
 */
class IHaveRuntimes extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->getContextValue('runtimes'),
    ];
  }

}
