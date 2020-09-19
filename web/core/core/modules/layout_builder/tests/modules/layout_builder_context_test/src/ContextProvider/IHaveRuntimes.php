<?php

namespace Drupal\layout_builder_context_test\ContextProvider;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Defines a class for a fake context provider.
 */
class IHaveRuntimes implements ContextProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    return [
      'runtimes' => new Context(new ContextDefinition('string', 'Do you have runtimes?'),
        'for sure you can'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [
      'runtimes' => new Context(new ContextDefinition('string', 'Do you have runtimes?')),
    ];
  }

}
