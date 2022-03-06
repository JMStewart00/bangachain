<?php

namespace Drupal\Tests\rules\Unit;

// Symfony 6, which is used by Drupal 10, requires PHP 8 features that
// are not available in PHP 7. Specifically, the 'mixed' return type hint.
// @todo Remove this hack when Drupal 9 is no longer supported.
// @see https://www.drupal.org/project/rules/issues/3265360
if (version_compare(\Drupal::VERSION, '10') >= 0) {

  /**
   * Implements just the methods we need for the Rules unit tests.
   */
  class TestSession extends TestSessionBase {

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = NULL): mixed {
      if (isset($this->logs[$key])) {
        return $this->logs[$key];
      }
      else {
        return $default;
      }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): mixed {
      if (isset($this->logs[$key])) {
        $return = $this->logs[$key];
        unset($this->logs[$key]);
        return $return;
      }
      else {
        return NULL;
      }
    }

  }

}
// Otherwise use the PHP 7 compatible version of TestSession.
else {

  /**
   * Implements just the methods we need for the Rules unit tests.
   */
  class TestSession extends TestSessionBase {

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = NULL) {
      if (isset($this->logs[$key])) {
        return $this->logs[$key];
      }
      else {
        return $default;
      }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
      if (isset($this->logs[$key])) {
        $return = $this->logs[$key];
        unset($this->logs[$key]);
        return $return;
      }
      else {
        return NULL;
      }
    }

  }

}
