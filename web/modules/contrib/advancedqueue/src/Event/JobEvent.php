<?php

namespace Drupal\advancedqueue\Event;

use Drupal\advancedqueue\Job;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the job event.
 *
 * @see \Drupal\advancedqueue\Event\AdvancedQueueEvents
 */
class JobEvent extends Event {

  /**
   * The job.
   *
   * @var \Drupal\advancedqueue\Job
   */
  protected $job;

  /**
   * Constructs a new JobEvent object.
   *
   * @param \Drupal\advancedqueue\Job $job
   *   The job.
   */
  public function __construct(Job $job) {
    $this->job = $job;
  }

  /**
   * Gets the job.
   *
   * @return \Drupal\advancedqueue\Job
   *   The job.
   */
  public function getJob() {
    return $this->job;
  }

}
