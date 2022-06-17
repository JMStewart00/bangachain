<?php

namespace Drupal\bangachain_reports;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MemoryReclaimer {
  use StringTranslationTrait;

  /**
   * The ratio of the memory limit at which an operation will be interrupted.
   *
   * @var float
   */
  protected $memoryThreshold = 0.5;

  /**
   * The PHP memory_limit expressed in bytes.
   *
   * @var int
   */
  protected $memoryLimit;

  /**
   * The entity memory cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCache
   */
  protected $memoryCache;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  public function __construct(MemoryCache $memoryCache, LoggerChannelFactoryInterface $loggerFactory) {
    $this->memoryCache = $memoryCache;
    $this->logger = $loggerFactory->get('bangachain_reports');
    // Record the memory limit in bytes.
    $limit = trim(ini_get('memory_limit'));
    if ($limit == '-1') {
      $this->memoryLimit = PHP_INT_MAX;
    }
    else {
      $this->memoryLimit = Bytes::toNumber($limit);
    }
  }

  /**
   * Tests whether we've exceeded the desired memory threshold.
   *
   * If so, output a message.
   *
   * @param bool $force
   *   Ignore threshold and attempt to reclaim memory.
   *
   * @return bool
   *   TRUE if the threshold is exceeded, otherwise FALSE.
   */
  public function checkMemoryExceeded($force = FALSE) {
    $usage = $this->getMemoryUsage();
    $pct_memory = $usage / $this->memoryLimit;
    if (!$threshold = $this->memoryThreshold) {
      return FALSE;
    }
    if ($force || $pct_memory > $threshold) {
      $this->logger->warning(
        $this->t(
          'Memory usage is @usage (@pct% of limit @limit), reclaiming memory.',
          [
            '@pct' => round($pct_memory * 100),
            '@usage' => $this->formatSize($usage),
            '@limit' => $this->formatSize($this->memoryLimit),
          ]
        ),
      );
      $usage = $this->attemptMemoryReclaim();
      $pct_memory = $usage / $this->memoryLimit;
      // Use a lower threshold - we don't want to be in a situation where we
      // keep coming back here and trimming a tiny amount.
      if ($pct_memory > (0.90 * $threshold)) {
        $this->logger->warning(
          $this->t(
            'Memory usage is now @usage (@pct% of limit @limit), not enough reclaimed, starting new batch',
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          ),
        );
        return TRUE;
      }
      else {
        $this->logger->warning(
          $this->t(
            'Memory usage is now @usage (@pct% of limit @limit), reclaimed enough, continuing',
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          )
        );
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the memory usage so far.
   *
   * @return int
   *   The memory usage.
   */
  protected function getMemoryUsage() {
    return memory_get_usage();
  }

  /**
   * Tries to reclaim memory.
   *
   * @return int
   *   The memory usage after reclaim.
   */
  protected function attemptMemoryReclaim() {
    // First, try resetting Drupal's static storage - this frequently releases
    // plenty of memory to continue.
    drupal_static_reset();

    // Entity storage can blow up with caches, so clear it out.
    $this->memoryCache->deleteAll();

    // Run garbage collector to further reduce memory.
    gc_collect_cycles();

    return memory_get_usage();
  }

  /**
   * Generates a string representation for the given byte count.
   *
   * @param int $size
   *   A size in bytes.
   *
   * @return string
   *   A translated string representation of the size.
   */
  protected function formatSize($size) {
    return format_size($size);
  }

}
