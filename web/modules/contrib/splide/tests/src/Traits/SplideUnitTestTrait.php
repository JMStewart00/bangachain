<?php

namespace Drupal\Tests\splide\Traits;

/**
 * A Trait common for Splide Unit tests.
 */
trait SplideUnitTestTrait {

  /**
   * Defines scoped definition.
   */
  protected function getSplideFormatterDefinition() {
    return [
      'namespace' => 'splide',
    ] + $this->getFormatterDefinition() + $this->getDefaulEntityFormatterDefinition();
  }

}
