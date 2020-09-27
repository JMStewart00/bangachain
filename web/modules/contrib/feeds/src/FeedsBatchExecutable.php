<?php

namespace Drupal\feeds;

/**
 * Import feeds using the batch API.
 */
class FeedsBatchExecutable extends FeedsExecutable {

  /**
   * {@inheritdoc}
   */
  protected function createBatch(FeedInterface $feed, $stage) {
    return new FeedsBatchBatch($this, $feed, $stage);
  }

}
