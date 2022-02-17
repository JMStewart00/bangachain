<?php

namespace Drupal\blazy;

/**
 * Provides settings object.
 *
 * @todo convert settings into BlazySettings instance at blazy:3.+ if you can.
 */
class BlazySettings implements \Countable {

  /**
   * Stores the settings.
   *
   * @var \stdClass[]
   */
  protected $storage = [];

  /**
   * Creates a new BlazySettings instance.
   *
   * @param \stdClass[] $storage
   *   The storage.
   */
  public function __construct(array $storage) {
    $this->storage = $storage;
  }

  /**
   * Counts total items.
   */
  public function count(): int {
    return count($this->storage);
  }

  /**
   * Returns values from a key.
   *
   * @param string $id
   *   The storage key.
   *
   * @return mixed
   *   A mixed value (array, string, bool, null, etc.).
   */
  public function get($id) {
    return $this->storage[$id] ?? NULL;
  }

  /**
   * Sets values for a key.
   */
  public function set($key, $value = NULL): self {
    if (is_array($key) && empty($value)) {
      foreach ($key as $k => $v) {
        $this->storage[$k] = $v;
      }
    }
    else {
      $this->storage[$key] = $value;
    }

    return $this;
  }

  /**
   * Returns the whole array.
   */
  public function storage(): array {
    return $this->storage;
  }

}
