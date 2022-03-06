<?php

namespace Drupal\Tests\rules\Unit;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Implements just the methods we need for the Rules unit tests.
 */
abstract class TestSessionBase implements SessionInterface {

  /**
   * Simulated session storage.
   *
   * @var array
   */
  protected $logs = [];

  /**
   * {@inheritdoc}
   */
  public function all(): array {
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
  }

  /**
   * {@inheritdoc}
   */
  public function getBag($name): SessionBagInterface {
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataBag(): MetadataBag {
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
  }

  /**
   * {@inheritdoc}
   */
  public function has($name): bool {
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($lifetime = NULL): bool {
  }

  /**
   * {@inheritdoc}
   */
  public function isStarted(): bool {
  }

  /**
   * {@inheritdoc}
   */
  public function migrate($destroy = FALSE, $lifetime = NULL): bool {
  }

  /**
   * {@inheritdoc}
   */
  public function registerBag(SessionBagInterface $bag) {
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array $attributes) {
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->logs[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
  }

  /**
   * {@inheritdoc}
   */
  public function start(): bool {
  }

}
